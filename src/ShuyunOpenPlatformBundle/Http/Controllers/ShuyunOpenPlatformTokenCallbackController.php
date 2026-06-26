<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Psr\Log\LoggerInterface;
use ShuyunOpenPlatformBundle\Http\Support\ShuyunOpenPlatformCallbackRequestDebug;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTokenCallbackService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

class ShuyunOpenPlatformTokenCallbackController extends Controller
{
    /** 入站审计 {@see ShuyunOpenPlatformTrafficAudit} 的 `action_method`（与出站同列，配合 direction 区分） */
    private const INBOUND_AUDIT_ACTION_METHOD = 'token';

    /**
     * 数云 token 推送回调：落库逻辑见 {@see ShuyunOpenPlatformTokenCallbackService}（当前不验签，依赖部署侧访问控制）。
     */
    public function token(Request $request): JsonResponse
    {
        $debugLog = (bool) config('shuyun_open_platform.callback_debug_log');
        if ($debugLog) {
            $this->logCallbackDebugPayload($request);
        }

        /** @var array{code:int,msg:string,data:string} $payload */
        $payload = app(ShuyunOpenPlatformTokenCallbackService::class)->handle($request);

        if ($debugLog) {
            /** @var LoggerInterface $log */
            $log = app('log')->channel('shuyun_open_platform');
            $log->info('ShuyunOpenPlatform::token_callback::result', [
                'code' => $payload['code'],
                'msg' => $payload['msg'],
            ]);
        }

        $response = response()->json($payload, 200);
        $this->recordTokenInboundAudit($request, $response);

        return $response;
    }

    /**
     * 联调专用：与 {@see shuyun_open_platform.callback_debug_log} 同时打两条 INFO——入口（headers/query/body，通道 shuyun_open_platform）与处理结果（code/msg）；生产须关闭。
     *
     * @return array<string, mixed>
     */
    private function buildCallbackDebugPayload(Request $request): array
    {
        return ShuyunOpenPlatformCallbackRequestDebug::capture($request);
    }

    private function logCallbackDebugPayload(Request $request): void
    {
        /** @var LoggerInterface $log */
        $log = app('log')->channel('shuyun_open_platform');
        $log->info('ShuyunOpenPlatform::token_callback::inbound', $this->buildCallbackDebugPayload($request));
    }

    private function recordTokenInboundAudit(Request $request, JsonResponse $response): void
    {
        try {
            /** @var ShuyunOpenPlatformTrafficAuditWriter $writer */
            $writer = app(ShuyunOpenPlatformTrafficAuditWriter::class);
        } catch (\Throwable) {
            return;
        }
        $companyId = $writer->resolveCompanyIdFromTokenCallbackBody((string) $request->getContent());
        $cap = ShuyunOpenPlatformTrafficAuditWriter::captureFromRequest($request);
        $httpStatus = $response->getStatusCode();
        $content = (string) $response->getContent();
        [$outcome, $errorMessage] = $this->tokenInboundAuditOutcomeAndError($httpStatus, $content);
        $bodyRaw = $cap['body_raw'] ?? '';
        $headers = isset($cap['headers']) && \is_array($cap['headers']) ? $cap['headers'] : [];

        $writer->writeInbound(
            $companyId,
            self::INBOUND_AUDIT_ACTION_METHOD,
            'in_tk_'.bin2hex(random_bytes(8)),
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
    private function tokenInboundAuditOutcomeAndError(int $httpStatus, string $content): array
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
        $code = $decoded['code'] ?? null;
        $codeInt = \is_int($code) ? $code : (\is_numeric($code) ? (int) $code : 0);
        $msg = isset($decoded['msg']) ? (string) $decoded['msg'] : '';
        if ($codeInt === 200) {
            return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_SUCCESS, null];
        }

        return [ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_BUSINESS_ERROR, $msg !== '' ? $msg : 'code:'.$codeInt];
    }
}
