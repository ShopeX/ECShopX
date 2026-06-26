<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Psr\Log\LoggerInterface;
use ShuyunOpenPlatformBundle\Http\Support\ShuyunInboundSignedPrepareMode;
use ShuyunOpenPlatformBundle\Http\Support\ShuyunOpenPlatformCallbackRequestDebug;
use ShuyunOpenPlatformBundle\Http\Support\ShuyunOpenPlatformInboundSignedCallbackPreparer;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitCallbackService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

/**
 * 数云线下权益入站回调：create / single.send / batch.send。**入站验签与租户解析**与等级回调共用 {@see ShuyunOpenPlatformInboundSignedCallbackPreparer}（模式 {@see ShuyunInboundSignedPrepareMode::OfflineBenefit}）；通过后另校验 {@see \ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService::isEligible}。
 */
class ShuyunOfflineBenefitCallbackController extends Controller
{
    /** 入站审计 {@see ShuyunOpenPlatformTrafficAudit} 的 `action_method`（与出站同列，配合 direction 区分） */
    private const INBOUND_AUDIT_ACTION_METHOD = 'offline_benefit';

    public function create(Request $request): JsonResponse
    {
        $prepared = $this->prepareSignedInboundBody($request);
        if ($prepared instanceof JsonResponse) {
            return $this->withOfflineBenefitInboundAudit($request, $prepared, 0);
        }

        try {
            $benefitId = app(ShuyunOfflineBenefitCallbackService::class)->create(
                $prepared['companyId'],
                $prepared['body']
            );
        } catch (\InvalidArgumentException $e) {
            return $this->withOfflineBenefitInboundAudit($request, response()->json([
                'code' => 422,
                'message' => $e->getMessage(),
                'data' => (object) [],
            ], 422), (int) $prepared['companyId']);
        }

        // 与数云「权益创建」开放文档一致：成功 code=10000，线下唯一权益 ID 置于 data.benefitId（与请求体同源回显）。
        return $this->withOfflineBenefitInboundAudit($request, response()->json([
            'code' => 10000,
            'message' => '',
            'data' => [
                'benefitId' => (string) $benefitId,
            ],
        ], 200), (int) $prepared['companyId']);
    }

    public function singleSend(Request $request): JsonResponse
    {
        $prepared = $this->prepareSignedInboundBody($request);
        if ($prepared instanceof JsonResponse) {
            return $this->withOfflineBenefitInboundAudit($request, $prepared, 0);
        }

        try {
            $data = app(ShuyunOfflineBenefitCallbackService::class)->singleSend(
                $prepared['companyId'],
                $prepared['body']
            );
        } catch (\InvalidArgumentException $e) {
            return $this->withOfflineBenefitInboundAudit($request, response()->json([
                'code' => 422,
                'message' => $e->getMessage(),
                'data' => [
                    'batchId' => '',
                    'benefitCode' => '',
                ],
            ], 422), (int) $prepared['companyId']);
        }

        // 单笔同步履约：`benefitCode` 非空视为业务成功（code=10000）；否则 code=50001（含失败原因）；仅在仍为异步队列积压时出现「异步发放」文案（code=10001）。
        $benefitCode = (string) ($data['benefitCode'] ?? '');
        $msg = (string) ($data['message'] ?? '');
        $code = 10000;
        if ($benefitCode === '') {
            $code = str_contains($msg, '异步发放处理中') ? 10001 : 50001;
        }

        return $this->withOfflineBenefitInboundAudit($request, response()->json([
            'code' => $code,
            'message' => $msg,
            'data' => [
                'batchId' => $data['batchId'],
                'benefitCode' => $benefitCode,
            ],
        ], 200), (int) $prepared['companyId']);
    }

    public function batchSend(Request $request): JsonResponse
    {
        $prepared = $this->prepareSignedInboundBody($request);
        if ($prepared instanceof JsonResponse) {
            return $this->withOfflineBenefitInboundAudit($request, $prepared, 0);
        }

        try {
            $data = app(ShuyunOfflineBenefitCallbackService::class)->batchSend(
                $prepared['companyId'],
                $prepared['body']
            );
        } catch (\InvalidArgumentException $e) {
            return $this->withOfflineBenefitInboundAudit($request, response()->json([
                'code' => 422,
                'message' => $e->getMessage(),
                'data' => [
                    'batchId' => '',
                ],
            ], 422), (int) $prepared['companyId']);
        }

        // 数云「权益批量发送」响应：仅回 batchId；message 说明异步处理中或汇总失败原因（批次 done 后重试可见）。
        return $this->withOfflineBenefitInboundAudit($request, response()->json([
            'code' => 10000,
            'message' => $data['message'],
            'data' => [
                'batchId' => $data['batchId'],
            ],
        ], 200), (int) $prepared['companyId']);
    }

    /**
     * @return JsonResponse|array{companyId: int, body: array<string, mixed>}
     */
    private function prepareSignedInboundBody(Request $request): JsonResponse|array
    {
        if ((bool) config('shuyun_open_platform.callback_inbound_debug_log')) {
            try {
                /** @var LoggerInterface $log */
                $log = app('log')->channel('shuyun_open_platform');
                $log->info('ShuyunOpenPlatform::offline_benefit_callback::inbound', ShuyunOpenPlatformCallbackRequestDebug::capture($request));
            } catch (\Throwable $e) {
            }
        }

        return app(ShuyunOpenPlatformInboundSignedCallbackPreparer::class)->prepare(
            $request,
            ShuyunInboundSignedPrepareMode::OfflineBenefit,
        );
    }

    private function withOfflineBenefitInboundAudit(Request $request, JsonResponse $response, int $companyId): JsonResponse
    {
        $this->recordOfflineBenefitInboundAudit($request, $response, $companyId);

        return $response;
    }

    private function recordOfflineBenefitInboundAudit(Request $request, JsonResponse $response, int $companyId): void
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
        [$outcome, $errorMessage] = $this->offlineBenefitInboundAuditOutcomeAndError($httpStatus, $content);
        if ($companyId === 0 && $httpStatus === 403) {
            $outcome = ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_PARSE_ERROR;
        }
        $bodyRaw = $cap['body_raw'] ?? '';
        $headers = isset($cap['headers']) && \is_array($cap['headers']) ? $cap['headers'] : [];

        $writer->writeInbound(
            $companyId,
            self::INBOUND_AUDIT_ACTION_METHOD,
            'in_ob_'.bin2hex(random_bytes(8)),
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
    private function offlineBenefitInboundAuditOutcomeAndError(int $httpStatus, string $content): array
    {
        if ($httpStatus === 403) {
            return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_TRANSPORT_ERROR, $this->offlineBenefit403Detail($content)];
        }
        if ($httpStatus === 422) {
            $msg = $this->offlineBenefitExtractMessageField($content);

            return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_BUSINESS_ERROR, $msg];
        }
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
        $code = $decoded['code'] ?? null;
        $codeInt = \is_int($code) ? $code : (\is_numeric($code) ? (int) $code : null);
        $message = isset($decoded['message']) ? (string) $decoded['message'] : '';
        if ($codeInt === 10000) {
            return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_SUCCESS, null];
        }
        if ($codeInt === 10001 || $codeInt === 50001) {
            return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_BUSINESS_ERROR, $message !== '' ? $message : 'code:'.$codeInt];
        }
        $summary = $message !== '' ? $message : ($codeInt !== null ? 'code:'.$codeInt : null);

        return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_BUSINESS_ERROR, $summary];
    }

    private function offlineBenefit403Detail(string $content): ?string
    {
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $content !== '' ? $content : null;
        }
        if (!\is_array($decoded)) {
            return $content !== '' ? $content : null;
        }
        $msg = isset($decoded['msg']) ? (string) $decoded['msg'] : '';
        if ($msg !== '') {
            return $msg;
        }
        $code = $decoded['code'] ?? null;

        return $code !== null && $code !== '' ? 'code:'.(string) $code : null;
    }

    private function offlineBenefitExtractMessageField(string $content): ?string
    {
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $content !== '' ? $content : null;
        }
        if (!\is_array($decoded)) {
            return $content !== '' ? $content : null;
        }
        $message = isset($decoded['message']) ? (string) $decoded['message'] : '';

        return $message !== '' ? $message : null;
    }
}
