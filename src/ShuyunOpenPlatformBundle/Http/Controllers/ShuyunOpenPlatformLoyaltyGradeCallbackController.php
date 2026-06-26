<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use OpenapiBundle\Exceptions\ErrorException;
use Psr\Log\LoggerInterface;
use ShuyunOpenPlatformBundle\Http\Support\ShuyunInboundSignedPrepareMode;
use ShuyunOpenPlatformBundle\Http\Support\ShuyunOpenPlatformCallbackRequestDebug;
use ShuyunOpenPlatformBundle\Http\Support\ShuyunOpenPlatformInboundSignedCallbackPreparer;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyGradeCallbackService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

/**
 * 数云「会员等级变更（路由模式）」回调：`shuyun.callback.loyalty.member.grade.change`。
 *
 * 成功/失败响应体与数云开放平台网关约定一致：`msg`、`code`（字符串）、`success`（字符串 `"true"` / `"false"`）；成功时 `code` 为 `"10000"`。
 * 业务失败：`42201` 参数/映射类（{@see \InvalidArgumentException}）、`42202` Openapi 会员更新类（{@see ErrorException}）、`42203` 其它未预期异常；入站审计 `error_message` 为简短摘要（过长截断）。
 *
 * 租户准入：库中 is_enabled=1；**入站验签与租户解析**共用 {@see ShuyunOpenPlatformInboundSignedCallbackPreparer}（身份注册密匙，非 DB app_secret；算法见 {@see \ShuyunOpenPlatformBundle\Auth\ShuyunCallbackSignatureVerifier}）。
 * appId：优先 query，其次 body；皆无则按 body 的 platCode（及线下体常见的 limitShops[].platCode）匹配 {@see \ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository::findAllEnabledByNormalizedPlatCode}。
 * body 仅 {@code platCode=OFFLINE} 时：依赖库中 {@code plat_code=OFFLINE} 的启用配置行解析租户。
 */
class ShuyunOpenPlatformLoyaltyGradeCallbackController extends Controller
{
    /** 入站审计 {@see ShuyunOpenPlatformTrafficAudit} 的 `action_method`（与出站同列，配合 direction 区分） */
    private const INBOUND_AUDIT_ACTION_METHOD = 'loyalty_grade';

    public function callback(Request $request): JsonResponse
    {
        if ((bool) config('shuyun_open_platform.callback_inbound_debug_log')) {
            try {
                /** @var LoggerInterface $log */
                $log = app('log')->channel('shuyun_open_platform');
                $log->info('ShuyunOpenPlatform::loyalty_grade_callback::inbound', ShuyunOpenPlatformCallbackRequestDebug::capture($request));
            } catch (\Throwable $e) {
            }
        }

        $prepared = app(ShuyunOpenPlatformInboundSignedCallbackPreparer::class)->prepare(
            $request,
            ShuyunInboundSignedPrepareMode::LoyaltyMemberGradeChange,
        );
        if ($prepared instanceof JsonResponse) {
            $this->recordLoyaltyGradeInboundAudit($request, $prepared, 0);

            return $prepared;
        }

        try {
            app(ShuyunOpenPlatformLoyaltyGradeCallbackService::class)->applyGradeChange(
                $prepared['companyId'],
                $prepared['body'],
            );
        } catch (\InvalidArgumentException $e) {
            $resp = $this->shuyunCallbackJsonResponse('42201', $this->truncateLoyaltyCallbackClientMessage($e->getMessage()));
            $this->recordLoyaltyGradeInboundAudit($request, $resp, (int) $prepared['companyId']);

            return $resp;
        } catch (ErrorException $e) {
            $msg = $e->getMessage() !== '' ? $e->getMessage() : 'OPENAPI_MEMBER_UPDATE_FAILED';
            $resp = $this->shuyunCallbackJsonResponse('42202', $this->truncateLoyaltyCallbackClientMessage($msg));
            $this->recordLoyaltyGradeInboundAudit($request, $resp, (int) $prepared['companyId']);

            return $resp;
        } catch (\Throwable $e) {
            $resp = $this->shuyunCallbackJsonResponse('42203', $this->loyaltyCallbackThrowableBrief($e));
            $this->recordLoyaltyGradeInboundAudit($request, $resp, (int) $prepared['companyId']);

            return $resp;
        }

        $resp = $this->shuyunSuccessResponse();
        $this->recordLoyaltyGradeInboundAudit($request, $resp, (int) $prepared['companyId']);

        return $resp;
    }

    /**
     * 数云文档示例成功体：msg、code（字符串 10000）、success（字符串 true）。
     */
    private function shuyunSuccessResponse(): JsonResponse
    {
        return response()->json([
            'msg' => '',
            'code' => '10000',
            'success' => 'true',
        ], 200);
    }

    /**
     * @param  non-empty-string  $code  业务错误码字符串（非 10000）
     */
    private function shuyunCallbackJsonResponse(string $code, ?string $message): JsonResponse
    {
        return response()->json([
            'msg' => $message ?? '',
            'code' => $code,
            'success' => 'false',
        ], 200);
    }

    private function recordLoyaltyGradeInboundAudit(Request $request, JsonResponse $response, int $companyId): void
    {
        try {
            /** @var ShuyunOpenPlatformTrafficAuditWriter $writer */
            $writer = app(ShuyunOpenPlatformTrafficAuditWriter::class);
        } catch (\Throwable) {
            return;
        }
        $cap = ShuyunOpenPlatformTrafficAuditWriter::captureFromRequest($request);
        $httpStatus = $response->getStatusCode();
        $content = (string) $response->getContent();
        [$outcome, $errorMessage] = $this->loyaltyInboundAuditOutcomeAndError($httpStatus, $content);
        if ($companyId === 0 && $httpStatus === 200) {
            $outcome = ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_PARSE_ERROR;
        }
        $bodyRaw = $cap['body_raw'] ?? '';
        $headers = isset($cap['headers']) && \is_array($cap['headers']) ? $cap['headers'] : [];

        $writer->writeInbound(
            $companyId,
            self::INBOUND_AUDIT_ACTION_METHOD,
            'in_lg_'.bin2hex(random_bytes(8)),
            strtoupper($request->getMethod()),
            $headers,
            $bodyRaw !== '' ? $bodyRaw : null,
            $httpStatus,
            $outcome,
            $content !== '' ? $content : null,
            $errorMessage,
        );
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function loyaltyInboundAuditOutcomeAndError(int $httpStatus, string $content): array
    {
        if ($httpStatus !== 200) {
            return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_TRANSPORT_ERROR, $content !== '' ? $content : null];
        }
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_PARSE_ERROR, 'response_json_invalid'];
        }
        if (!\is_array($decoded)) {
            return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_PARSE_ERROR, 'response_not_object'];
        }
        $code = (string) ($decoded['code'] ?? '');
        $success = (string) ($decoded['success'] ?? '');
        $msg = isset($decoded['msg']) ? (string) $decoded['msg'] : '';
        if ($code === '10000' && $success === 'true') {
            return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_SUCCESS, null];
        }
        if ($code === '40001') {
            $line = $msg !== '' ? $msg : null;

            return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_PARSE_ERROR, $this->truncateLoyaltyInboundAuditError($line)];
        }
        $summary = $msg !== '' ? $code.'|'.$msg : ($code !== '' ? $code : null);

        return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_BUSINESS_ERROR, $this->truncateLoyaltyInboundAuditError($summary)];
    }

    private function truncateLoyaltyCallbackClientMessage(string $message): string
    {
        if ($message === '') {
            return '';
        }
        if (\strlen($message) <= 512) {
            return $message;
        }

        return substr($message, 0, 509).'...';
    }

    private function loyaltyCallbackThrowableBrief(\Throwable $e): string
    {
        $type = (new \ReflectionClass($e))->getShortName();
        $msg = trim($e->getMessage());
        $line = $msg !== '' ? $type.': '.$msg : $type;
        if (\strlen($line) > 240) {
            return substr($line, 0, 237).'...';
        }

        return $line;
    }

    private function truncateLoyaltyInboundAuditError(?string $summary): ?string
    {
        if ($summary === null || $summary === '') {
            return null;
        }
        if (\strlen($summary) <= 400) {
            return $summary;
        }

        return substr($summary, 0, 397).'...';
    }

}
