<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Gateway;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayBusinessException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayHttpException;
use ShuyunOpenPlatformBundle\Exception\ShuyunGatewayJsonException;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTrafficAuditWriter;

/**
 * 开放网关：POST/PUT 无 URL 参数时签名仅含 Gateway-Request-Time；GET 须将 URL query 与 Gateway-Request-Time 一并参与签名（ASCII 升序后 key+value 拼接，见 {@see ShuyunSigner}）。
 * 调用结果写入 {@see config/logging.php} 通道 `shuyun_open_platform`（不落库，便于联调与排障）。
 * 每次调用带 `gateway_call_id`：先写一条「主记录」（请求 URL/方式/头/body/响应），再写一条「辅助记录」（curl、token 来源、摘要、分片元数据等）；超大响应仍拆「响应体分片」。
 */
final class ShuyunGatewayClient
{
    private const LOG_CHANNEL = 'shuyun_open_platform';

    private const LOG_MSG_AUX = '数云网关调用辅助记录';

    private const REQUEST_LOG_MAX_LEN = 2000;

    private const DEFAULT_BODY_LOG_MAX_BYTES = 12288;

    private const DEFAULT_RESPONSE_CHUNK_BYTES = 8192;

    private string $appId;

    private string $appSecret;

    private string $baseUri;

    private ClientInterface $http;

    private ShuyunSigner $signer;

    private int $companyId;

    private ?ShuyunOpenPlatformTrafficAuditWriter $trafficAuditWriter;

    public function __construct(
        string $appId,
        string $appSecret,
        string $baseUri,
        ClientInterface $http,
        ?ShuyunSigner $signer = null,
        int $companyId = 0,
        ?ShuyunOpenPlatformTrafficAuditWriter $trafficAuditWriter = null
    ) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->baseUri = $baseUri;
        $this->http = $http;
        $this->signer = $signer ?? new ShuyunSigner();
        $this->companyId = $companyId;
        $this->trafficAuditWriter = $trafficAuditWriter;
    }

    /**
     * @param  array<string, mixed>  $body  JSON body（不参与默认签名）
     * @param  ?string  $platform  公共请求头 `platform`（非空时写入 Header，值为小写；类目/商品等不得在 body 重复传同名标量）
     */
    public function postJson(string $actionMethod, array $body = [], ?string $accessToken = null, ?string $platform = null): ShuyunGatewayResult
    {
        $effectiveToken = $this->resolveEffectiveAccessToken($accessToken);
        $tokenLog = $this->gatewayAccessTokenLogFields($accessToken, $effectiveToken);
        $headers = $this->buildHeaders($actionMethod, $effectiveToken, $platform, []);
        $requestSummary = $this->summarizeRequestPayload($body);
        $encodedBody = $this->jsonEncodeBody($body);
        $requestUrl = $this->buildAbsoluteRequestUrl();
        $replayLog = array_merge(
            ['gateway_call_id' => $this->newGatewayCallId()],
            $this->buildRequestReplayLogContext('POST', $requestUrl, $headers, $body, null),
        );
        if ($encodedBody === null) {
            $this->recordOutboundTrafficIfEnabled($replayLog, 'POST', $actionMethod, null, null, ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_PARSE_ERROR, null, 'Failed to encode POST JSON body.');
            throw new ShuyunGatewayJsonException('Failed to encode POST JSON body.');
        }

        try {
            $response = $this->http->request('POST', $this->resolvePath(), [
                'headers' => $headers,
                'body' => $encodedBody,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            $this->logGatewayTransportFailureDual('error', '数云网关 HTTP 请求异常', array_merge([
                'action' => $actionMethod,
                'verb' => 'POST',
                'request_url' => $requestUrl,
                'app_id' => $this->appId,
                'platform' => $platform !== null && trim($platform) !== '' ? strtolower(trim($platform)) : null,
                'request_summary' => $requestSummary,
                'error' => $e->getMessage(),
            ], $tokenLog, $replayLog));
            $this->recordOutboundTrafficIfEnabled($replayLog, 'POST', $actionMethod, $encodedBody, null, ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_TRANSPORT_ERROR, null, $e->getMessage());
            throw new ShuyunGatewayHttpException(0, 'HTTP request failed: '.$e->getMessage(), $e);
        }

        return $this->resultFromResponse($response, $actionMethod, 'POST', $requestUrl, $requestSummary, $tokenLog, $platform, $replayLog, $encodedBody);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function getQuery(string $actionMethod, array $query = [], ?string $accessToken = null, ?string $platform = null): ShuyunGatewayResult
    {
        $effectiveToken = $this->resolveEffectiveAccessToken($accessToken);
        $tokenLog = $this->gatewayAccessTokenLogFields($accessToken, $effectiveToken);
        $headers = $this->buildHeaders($actionMethod, $effectiveToken, $platform, $query);
        $requestSummary = $this->summarizeRequestPayload($query);
        $requestUrl = $this->buildAbsoluteRequestUrl($query);
        $replayLog = array_merge(
            ['gateway_call_id' => $this->newGatewayCallId()],
            $this->buildRequestReplayLogContext('GET', $requestUrl, $headers, null, $query),
        );
        $auditBody = $this->jsonEncodeBodyForAudit($query);

        try {
            $response = $this->http->request('GET', $this->resolvePath(), [
                'headers' => $headers,
                'query' => $query,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            $this->logGatewayTransportFailureDual('error', '数云网关 HTTP 请求异常', array_merge([
                'action' => $actionMethod,
                'verb' => 'GET',
                'request_url' => $requestUrl,
                'app_id' => $this->appId,
                'platform' => $platform !== null && trim($platform) !== '' ? strtolower(trim($platform)) : null,
                'request_summary' => $requestSummary,
                'error' => $e->getMessage(),
            ], $tokenLog, $replayLog));
            $this->recordOutboundTrafficIfEnabled($replayLog, 'GET', $actionMethod, $auditBody, null, ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_TRANSPORT_ERROR, null, $e->getMessage());
            throw new ShuyunGatewayHttpException(0, 'HTTP request failed: '.$e->getMessage(), $e);
        }

        return $this->resultFromResponse($response, $actionMethod, 'GET', $requestUrl, $requestSummary, $tokenLog, $platform, $replayLog, $auditBody);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function putJson(string $actionMethod, array $body = [], ?string $accessToken = null, ?string $platform = null): ShuyunGatewayResult
    {
        $effectiveToken = $this->resolveEffectiveAccessToken($accessToken);
        $tokenLog = $this->gatewayAccessTokenLogFields($accessToken, $effectiveToken);
        $headers = $this->buildHeaders($actionMethod, $effectiveToken, $platform, []);
        $requestSummary = $this->summarizeRequestPayload($body);
        $encodedBody = $this->jsonEncodeBody($body);
        $requestUrl = $this->buildAbsoluteRequestUrl();
        $replayLog = array_merge(
            ['gateway_call_id' => $this->newGatewayCallId()],
            $this->buildRequestReplayLogContext('PUT', $requestUrl, $headers, $body, null),
        );
        if ($encodedBody === null) {
            $this->recordOutboundTrafficIfEnabled($replayLog, 'PUT', $actionMethod, null, null, ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_PARSE_ERROR, null, 'Failed to encode PUT JSON body.');
            throw new ShuyunGatewayJsonException('Failed to encode PUT JSON body.');
        }

        try {
            $response = $this->http->request('PUT', $this->resolvePath(), [
                'headers' => $headers,
                'body' => $encodedBody,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            $this->logGatewayTransportFailureDual('error', '数云网关 HTTP 请求异常', array_merge([
                'action' => $actionMethod,
                'verb' => 'PUT',
                'request_url' => $requestUrl,
                'app_id' => $this->appId,
                'platform' => $platform !== null && trim($platform) !== '' ? strtolower(trim($platform)) : null,
                'request_summary' => $requestSummary,
                'error' => $e->getMessage(),
            ], $tokenLog, $replayLog));
            $this->recordOutboundTrafficIfEnabled($replayLog, 'PUT', $actionMethod, $encodedBody, null, ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_TRANSPORT_ERROR, null, $e->getMessage());
            throw new ShuyunGatewayHttpException(0, 'HTTP request failed: '.$e->getMessage(), $e);
        }

        return $this->resultFromResponse($response, $actionMethod, 'PUT', $requestUrl, $requestSummary, $tokenLog, $platform, $replayLog, $encodedBody);
    }

    private function resolvePath(): string
    {
        return '/';
    }

    /**
     * 与 Guzzle `request($path)` 相对本 Client 构造时传入的 baseUri 一致时的绝对 URL（GET 含 query 串，便于日志对照）。
     *
     * @param  array<string, mixed>  $query  GET 的 query 参数；POST/PUT 传空
     */
    private function buildAbsoluteRequestUrl(array $query = []): string
    {
        $url = rtrim($this->baseUri, '/').$this->resolvePath();
        if ($query === []) {
            return $url;
        }

        return $url.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * 调用方传入的 token 为空时，使用 {@see config('shuyun_open_platform.fallback_gateway_access_token')}（非空则写入 Gateway-Access-Token）。
     */
    private function resolveEffectiveAccessToken(?string $accessToken): ?string
    {
        if ($accessToken !== null && $accessToken !== '') {
            return $accessToken;
        }
        try {
            if (!\function_exists('app')) {
                return null;
            }
            $app = \app();
            if (!$app->bound('config')) {
                return null;
            }
            $fb = config('shuyun_open_platform.fallback_gateway_access_token');
            if (!is_string($fb)) {
                return null;
            }
            $t = trim($fb);

            return $t !== '' ? $t : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array{access_token_set: bool, gateway_access_token_source: string}
     */
    private function gatewayAccessTokenLogFields(?string $passedAccessToken, ?string $effectiveAccessToken): array
    {
        $set = $effectiveAccessToken !== null && $effectiveAccessToken !== '';
        if (!$set) {
            return ['access_token_set' => false, 'gateway_access_token_source' => 'none'];
        }
        if ($passedAccessToken !== null && $passedAccessToken !== '') {
            return ['access_token_set' => true, 'gateway_access_token_source' => 'request'];
        }

        return ['access_token_set' => true, 'gateway_access_token_source' => 'config_fallback'];
    }

    /**
     * @param  array<string, mixed>  $queryParamsForSign  GET 时与 URL query 一致并参与签名；不得包含伪造的 Gateway-Request-Time（由本方法写入）
     * @return array<string, string>
     */
    private function buildHeaders(string $actionMethod, ?string $effectiveAccessToken, ?string $platform, array $queryParamsForSign): array
    {
        $requestTime = (string) (int) round(microtime(true) * 1000);
        $signParams = [];
        foreach ($queryParamsForSign as $name => $value) {
            $nameStr = (string) $name;
            if ($nameStr === '' || strcasecmp($nameStr, 'Gateway-Request-Time') === 0) {
                continue;
            }
            $signParams[$nameStr] = $this->scalarToGatewaySignString($value);
        }
        $signParams['Gateway-Request-Time'] = $requestTime;
        $sign = $this->signer->sign($this->appSecret, $signParams);

        $headers = [
            'Gateway-Authid' => $this->appId,
            'Gateway-Sign' => $sign,
            'Gateway-Action-Method' => $actionMethod,
            'Gateway-Request-Time' => $requestTime,
            'Content-Type' => 'application/json; charset=utf-8',
        ];
        if ($effectiveAccessToken !== null && $effectiveAccessToken !== '') {
            $headers['Gateway-Access-Token'] = $effectiveAccessToken;
        }
        $platformTrim = $platform !== null ? trim($platform) : '';
        if ($platformTrim !== '') {
            $headers['platform'] = strtolower($platformTrim);
        }

        return $headers;
    }

    /**
     * 与 GET query 中实际传递的标量取值一致（数云验签按参数名 ASCII 排序后拼接 key+value）。
     */
    private function scalarToGatewaySignString(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        if ($value === true) {
            return '1';
        }
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_float($value)) {
            if (is_finite($value) && floor($value) == $value) {
                return (string) (int) $value;
            }

            return (string) $value;
        }

        return (string) $value;
    }

    /**
     * @param  array{access_token_set: bool, gateway_access_token_source: string}  $accessTokenLog
     * @param  array<string, mixed>  $replayLog  {@see buildRequestReplayLogContext}
     */
    private function resultFromResponse(
        ResponseInterface $response,
        string $actionMethod,
        string $httpVerb,
        string $requestUrl,
        string $requestSummary,
        array $accessTokenLog,
        ?string $platform,
        array $replayLog = [],
        ?string $auditRequestBody = null,
    ): ShuyunGatewayResult {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $logCtx = array_merge([
            'action' => $actionMethod,
            'verb' => $httpVerb,
            'request_url' => $requestUrl,
            'app_id' => $this->appId,
            'platform' => $platform !== null && trim($platform) !== '' ? strtolower(trim($platform)) : null,
            'request_summary' => $requestSummary,
            'http_status' => $status,
        ], $accessTokenLog, $replayLog);

        if ($status < 200 || $status >= 300) {
            $this->logOutcomeWithResponse('warning', '数云网关 HTTP 状态非 2xx', $logCtx, $body);
            $this->recordOutboundTrafficIfEnabled($replayLog, $httpVerb, $actionMethod, $auditRequestBody, $status, ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_TRANSPORT_ERROR, $body, 'HTTP '.$status);
            throw new ShuyunGatewayHttpException($status, 'HTTP '.$status.($body !== '' ? ': '.$body : ''));
        }
        if ($body === '') {
            [$primary, $secondary] = $this->splitGatewayLogContext($logCtx);
            $this->logGateway('warning', '数云网关响应体为空', array_merge($primary, ['response_note' => 'empty_http_body']));
            $this->logGateway('warning', self::LOG_MSG_AUX, $secondary);
            $this->recordOutboundTrafficIfEnabled($replayLog, $httpVerb, $actionMethod, $auditRequestBody, $status, ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_TRANSPORT_ERROR, null, 'Empty response body.');
            throw new ShuyunGatewayHttpException($status, 'Empty response body.');
        }
        try {
            $result = ShuyunGatewayResult::fromJsonString($body);
        } catch (ShuyunGatewayJsonException $e) {
            $logCtx['parse_error'] = $e->getMessage();
            $this->logOutcomeWithResponse('error', '数云网关响应 JSON 解析失败', $logCtx, $body);
            $this->recordOutboundTrafficIfEnabled($replayLog, $httpVerb, $actionMethod, $auditRequestBody, $status, ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_PARSE_ERROR, $body, $e->getMessage());
            throw $e;
        }
        $logCtx['business_code'] = $result->getCode();
        $logCtx['business_msg'] = $result->getMsg();
        if (!$result->isSuccess()) {
            $this->logOutcomeWithResponse('warning', '数云网关业务失败', $logCtx, $body);
            $this->recordOutboundTrafficIfEnabled($replayLog, $httpVerb, $actionMethod, $auditRequestBody, $status, ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_BUSINESS_ERROR, $body, $this->formatBusinessFailureMessage($result));
            throw new ShuyunGatewayBusinessException(
                $result->getCode(),
                $this->formatBusinessFailureMessage($result),
            );
        }

        $this->logOutcomeWithResponse('info', '数云网关调用成功', $logCtx, $body);
        $this->recordOutboundTrafficIfEnabled($replayLog, $httpVerb, $actionMethod, $auditRequestBody, $status, ShuyunOpenPlatformTrafficAuditWriter::OUTCOME_SUCCESS, $body, null);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $replayLog
     */
    private function recordOutboundTrafficIfEnabled(
        array $replayLog,
        string $httpVerb,
        string $actionMethod,
        ?string $auditRequestBody,
        ?int $httpStatus,
        string $outcome,
        ?string $responseBody,
        ?string $errorMessage
    ): void {
        if ($this->trafficAuditWriter === null) {
            return;
        }
        $cid = isset($replayLog['gateway_call_id']) && is_string($replayLog['gateway_call_id'])
            ? $replayLog['gateway_call_id']
            : 'sygw_unknown';
        $headersRaw = $replayLog['request_headers'] ?? [];
        $headers = [];
        if (is_array($headersRaw)) {
            foreach ($headersRaw as $k => $v) {
                $headers[(string) $k] = $v;
            }
        }
        $this->trafficAuditWriter->writeOutbound(
            $this->companyId,
            $cid,
            $httpVerb,
            $actionMethod,
            $headers,
            $auditRequestBody,
            $httpStatus,
            $outcome,
            $responseBody,
            $errorMessage,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function jsonEncodeBodyForAudit(array $payload): ?string
    {
        if ($payload === []) {
            return null;
        }

        return $this->jsonEncodeBody($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function summarizeRequestPayload(array $payload): string
    {
        if ($payload === []) {
            return '{}';
        }
        $json = $this->jsonEncodeBody($payload);
        if ($json === null) {
            return '[json_encode_failed]';
        }

        return $this->truncate($json, self::REQUEST_LOG_MAX_LEN);
    }

    private function truncate(string $s, int $maxLen): string
    {
        if (strlen($s) <= $maxLen) {
            return $s;
        }

        return substr($s, 0, $maxLen).'…(truncated)';
    }

    private function newGatewayCallId(): string
    {
        return 'sygw_'.bin2hex(random_bytes(8));
    }

    /**
     * 主记录（请求 URL/方式/头/body/响应）+ 辅助记录（curl、摘要等）+ 可选响应体分片。
     *
     * @param  array<string, mixed>  $logCtx
     */
    private function logOutcomeWithResponse(string $level, string $message, array $logCtx, string $responseBody): void
    {
        $chunks = $this->prepareResponseBodyLogging($responseBody, $logCtx);
        [$primary, $secondary] = $this->splitGatewayLogContext($logCtx);
        $this->logGateway($level, $message, $primary);
        // $this->logGateway($level, self::LOG_MSG_AUX, $secondary);
        $this->emitResponseBodyChunkLogs($level, $logCtx, $chunks);
    }

    /**
     * 传输层失败（未拿到 HTTP 响应）：主记录含请求要素与 error，辅助记录含 curl 等。
     *
     * @param  array<string, mixed>  $logCtx
     */
    private function logGatewayTransportFailureDual(string $level, string $message, array $logCtx): void
    {
        [$primary, $secondary] = $this->splitGatewayLogContext($logCtx);
        $this->logGateway($level, $message, $primary);
        // $this->logGateway($level, self::LOG_MSG_AUX, $secondary);
    }

    /**
     * 主记录：gateway_call_id、action、verb、request_url、request_headers、request_body（POST/PUT JSON 或 GET query JSON）、http_status、response_body、business_*、parse_error、error。
     * 辅助记录：app_id、platform、token 来源、request_summary、curl、响应截断/分片元数据。
     *
     * @param  array<string, mixed>  $logCtx
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function splitGatewayLogContext(array $logCtx): array
    {
        $bodyLine = $logCtx['request_body_json'] ?? null;
        $queryLine = $logCtx['request_query_params_json'] ?? null;
        $requestBody = $bodyLine;
        if (($requestBody === null || $requestBody === '') && $queryLine !== null && $queryLine !== '') {
            $requestBody = $queryLine;
        }

        $primary = $this->filterGatewayLogContext([
            'gateway_call_id' => $logCtx['gateway_call_id'] ?? null,
            'action' => $logCtx['action'] ?? null,
            'verb' => $logCtx['verb'] ?? null,
            'request_url' => $logCtx['request_url'] ?? null,
            'request_headers' => $logCtx['request_headers'] ?? null,
            'request_body' => $requestBody,
            'http_status' => $logCtx['http_status'] ?? null,
            'response_body' => $logCtx['response_body'] ?? null,
            'business_code' => $logCtx['business_code'] ?? null,
            'business_msg' => $logCtx['business_msg'] ?? null,
            'parse_error' => $logCtx['parse_error'] ?? null,
            'error' => $logCtx['error'] ?? null,
        ]);

        $secondary = $this->filterGatewayLogContext([
            'gateway_call_id' => $logCtx['gateway_call_id'] ?? null,
            'app_id' => $logCtx['app_id'] ?? null,
            'platform' => $logCtx['platform'] ?? null,
            'access_token_set' => $logCtx['access_token_set'] ?? null,
            'gateway_access_token_source' => $logCtx['gateway_access_token_source'] ?? null,
            'request_summary' => $logCtx['request_summary'] ?? null,
            'curl' => $logCtx['curl'] ?? null,
            'response_body_truncated_to_max' => $logCtx['response_body_truncated_to_max'] ?? null,
            'response_body_original_bytes' => $logCtx['response_body_original_bytes'] ?? null,
            'response_body_in_chunks' => $logCtx['response_body_in_chunks'] ?? null,
            'response_body_logged_bytes' => $logCtx['response_body_logged_bytes'] ?? null,
            'response_body_chunk_total' => $logCtx['response_body_chunk_total'] ?? null,
        ]);

        return [$primary, $secondary];
    }

    /**
     * @param  array<string, mixed>  $ctx
     *
     * @return array<string, mixed>
     */
    private function filterGatewayLogContext(array $ctx): array
    {
        $out = [];
        foreach ($ctx as $k => $v) {
            if ($v === null) {
                continue;
            }
            if ($v === []) {
                continue;
            }
            if ($v === '') {
                continue;
            }
            $out[$k] = $v;
        }

        return $out;
    }

    /**
     * 小响应：写入 logCtx['response_body']；大响应：写标记并返回分片列表（由调用方在主线日志之后写入）。
     *
     * @param  array<string, mixed>  $logCtx
     *
     * @return list<string>
     */
    private function prepareResponseBodyLogging(string $body, array &$logCtx): array
    {
        $maxBytes = $this->gatewayResponseLogBodyMaxBytes();
        $toLog = $body;
        if ($maxBytes > 0 && strlen($toLog) > $maxBytes) {
            $toLog = substr($toLog, 0, $maxBytes);
            $logCtx['response_body_truncated_to_max'] = true;
            $logCtx['response_body_original_bytes'] = strlen($body);
        }
        $chunkSize = $this->gatewayResponseLogChunkBytes();
        $len = strlen($toLog);
        if ($len <= $chunkSize) {
            $logCtx['response_body'] = $toLog;

            return [];
        }
        $logCtx['response_body_in_chunks'] = true;
        $logCtx['response_body_logged_bytes'] = $len;
        $chunks = [];
        for ($offset = 0; $offset < $len; $offset += $chunkSize) {
            $chunks[] = substr($toLog, $offset, $chunkSize);
        }
        $logCtx['response_body_chunk_total'] = count($chunks);

        return $chunks;
    }

    /**
     * @param  list<string>  $chunks
     * @param  array<string, mixed>  $logCtx
     */
    private function emitResponseBodyChunkLogs(string $level, array $logCtx, array $chunks): void
    {
        if ($chunks === []) {
            return;
        }
        $base = [
            'gateway_call_id' => $logCtx['gateway_call_id'] ?? null,
            'action' => $logCtx['action'] ?? null,
            'verb' => $logCtx['verb'] ?? null,
            'app_id' => $logCtx['app_id'] ?? null,
            'request_url' => $logCtx['request_url'] ?? null,
        ];
        $total = count($chunks);
        foreach ($chunks as $i => $chunk) {
            $this->logGateway($level, '数云网关响应体分片', array_merge($base, [
                'response_body_chunk_index' => $i + 1,
                'response_body_chunk_total' => $total,
                'response_body_chunk' => $chunk,
            ]));
        }
    }

    private function gatewayResponseLogBodyMaxBytes(): int
    {
        try {
            if (!\function_exists('app')) {
                return 0;
            }
            $app = \app();
            if (!$app->bound('config')) {
                return 0;
            }
            $v = config('shuyun_open_platform.gateway_response_log_body_max_bytes');

            return is_int($v) && $v >= 0 ? $v : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function gatewayResponseLogChunkBytes(): int
    {
        try {
            if (!\function_exists('app')) {
                return self::DEFAULT_RESPONSE_CHUNK_BYTES;
            }
            $app = \app();
            if (!$app->bound('config')) {
                return self::DEFAULT_RESPONSE_CHUNK_BYTES;
            }
            $v = config('shuyun_open_platform.gateway_response_log_chunk_bytes');

            return is_int($v) && $v >= 1024 ? $v : self::DEFAULT_RESPONSE_CHUNK_BYTES;
        } catch (\Throwable $e) {
            return self::DEFAULT_RESPONSE_CHUNK_BYTES;
        }
    }

    /**
     * 联调：完整请求头（含 Gateway-Sign / Gateway-Access-Token 明文）、截断后的 body/query、可复制的一行 curl。
     *
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>|null  $jsonBody POST/PUT JSON
     * @param  array<string, mixed>|null  $query GET 的 query（与 request_url 一致）
     *
     * @return array<string, mixed>
     */
    private function buildRequestReplayLogContext(
        string $httpVerb,
        string $requestUrl,
        array $headers,
        ?array $jsonBody,
        ?array $query
    ): array {
        $out = [
            'request_headers' => $headers,
            'curl' => $this->buildCurlOneLiner($httpVerb, $requestUrl, $headers, $jsonBody),
        ];
        if ($jsonBody !== null) {
            $raw = $this->jsonEncodeBody($jsonBody);
            if ($raw !== null) {
                $out['request_body_json'] = $this->truncate($raw, $this->gatewayRequestLogBodyMaxBytes());
            }
        }
        if ($query !== null && $query !== []) {
            $q = json_encode($query, JSON_UNESCAPED_UNICODE);
            $out['request_query_params_json'] = $q !== false
                ? $this->truncate($q, $this->gatewayRequestLogBodyMaxBytes())
                : '[encode_failed]';
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>|null  $jsonBody
     */
    private function buildCurlOneLiner(string $httpVerb, string $requestUrl, array $headers, ?array $jsonBody): string
    {
        $parts = ['curl', '-sS', '-X', $httpVerb];
        $parts[] = $this->shellArg($requestUrl);
        foreach ($headers as $n => $v) {
            $parts[] = '-H';
            $parts[] = $this->shellArg($n.': '.$v);
        }
        $raw = $jsonBody !== null ? $this->jsonEncodeBody($jsonBody) : null;
        if ($raw !== null && $raw !== '') {
            $parts[] = '--data-binary';
            // 单引号包裹时 JSON 里的 \" 在 shell 中为字面反斜杠，易导致引号不闭合或 body 错误；改用双引号 + 转义便于从日志复制执行
            $parts[] = $this->shellDoubleQuotedForCurlPayload($raw);
        }

        return implode(' ', $parts);
    }

    /**
     * 生成可被 bash/zsh 直接粘贴执行的「双引号包裹」参数字面量（用于 --data-binary 的 JSON）。
     */
    private function shellDoubleQuotedForCurlPayload(string $s): string
    {
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace("\r", '\\r', $s);
        $s = str_replace("\n", '\\n', $s);
        $s = str_replace('$', '\$', $s);
        $s = str_replace('`', '\`', $s);
        $s = str_replace('"', '\\"', $s);
        $s = str_replace('!', '\\!', $s);

        return '"'.$s.'"';
    }

    private function shellArg(string $s): string
    {
        if (\function_exists('escapeshellarg')) {
            return escapeshellarg($s);
        }
        return "'".str_replace("'", "'\\''", $s)."'";
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function jsonEncodeBody(array $body): ?string
    {
        $oldPrecision = ini_get('serialize_precision');
        if ($oldPrecision !== false) {
            ini_set('serialize_precision', '-1');
        }
        try {
            $j = json_encode($body, JSON_UNESCAPED_UNICODE);
            if ($j === false) {
                return null;
            }

            return $j;
        } finally {
            if ($oldPrecision !== false) {
                ini_set('serialize_precision', (string) $oldPrecision);
            }
        }
    }

    private function gatewayRequestLogBodyMaxBytes(): int
    {
        try {
            if (!\function_exists('app')) {
                return self::DEFAULT_BODY_LOG_MAX_BYTES;
            }
            $app = \app();
            if (!$app->bound('config')) {
                return self::DEFAULT_BODY_LOG_MAX_BYTES;
            }
            $v = config('shuyun_open_platform.gateway_request_log_body_max_bytes');

            return is_int($v) && $v >= 512 ? $v : self::DEFAULT_BODY_LOG_MAX_BYTES;
        } catch (\Throwable $e) {
            return self::DEFAULT_BODY_LOG_MAX_BYTES;
        }
    }

    /**
     * 无容器（如纯 PHPUnit）时跳过；日志失败不影响网关调用。
     *
     * @param  array<string, mixed>  $context
     */
    private function logGateway(string $level, string $message, array $context): void
    {
        try {
            if (!\function_exists('app')) {
                return;
            }
            $app = \app();
            if (!$app->bound('log')) {
                return;
            }
            $app->make('log')->channel(self::LOG_CHANNEL)->log($level, $message, $context);
        } catch (\Throwable $e) {
        }
    }

    private function formatBusinessFailureMessage(ShuyunGatewayResult $result): string
    {
        $msg = $result->getMsg();
        if ($msg !== '') {
            return $msg;
        }

        $code = $result->getCode();
        $detail = '数云网关业务失败（业务码：'.$code.'）';
        $data = $result->getData();
        if (is_string($data) && trim($data) !== '') {
            return $detail.' '.trim($data);
        }
        if (is_array($data) && $data !== []) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            if ($json !== false && $json !== '' && strlen($json) <= 512) {
                return $detail.' '.$json;
            }
        }

        return $detail;
    }
}
