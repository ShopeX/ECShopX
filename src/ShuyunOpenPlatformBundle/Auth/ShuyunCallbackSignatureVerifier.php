<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Auth;

use Illuminate\Http\Request;

/**
 * 数云回调验签：
 *
 * - 正式规则（合作方平台「身份注册」全局密匙）：全部参与签名的 query 参数（**不含 sign**）按 key ASCII 升序，
 *   再按 key+value 依次拼接，整体为 MD5(secret + 拼接串 + secret)；请求头 **SY-Request-Time**（若有）
 *   与 **body**（原始请求体字节，含空串）作为参数参与排序。
 * - **SY-Request-Nonce 路径**：在上述参数基础上额外校验 freshness（SY-Request-Time 窗口）并经 {@see ShuyunCallbackNonceStoreInterface} 一次性消费 nonce，拒绝重放；**须**传入 nonce store。
 * - 兼容旧版：数云常见为 query 带 callBackTime（无 SY-Request-Time 头），此时参与签名的即为 callBackTime+取值；与旧 MD5(secret+callBackTime+value+secret) 一致。
 *
 * @see 数云品牌自研接口对接全流程文档 §3.3
 */
final class ShuyunCallbackSignatureVerifier
{
    /**
     * @deprecated 仅兼容旧文档命名；新回调请使用 {@see verifyHttpCallback}
     */
    public function expectedSign(string $appSecret, string $callBackTimeValue): string
    {
        return md5($appSecret.'callBackTime'.$callBackTimeValue.$appSecret);
    }

    /**
     * @deprecated 仅兼容旧文档命名；新回调请使用 {@see verifyHttpCallback}
     */
    public function verify(string $appSecret, string $callBackTimeValue, string $sign): bool
    {
        $expected = $this->expectedSign($appSecret, $callBackTimeValue);

        return hash_equals(strtolower($expected), strtolower(trim($sign)));
    }

    /**
     * HTTP 回调验签：query 全量参与（去掉 sign）+ 头 SY-Request-Time / SY-Request-Nonce（若有）+ raw body。
     *
     * @param  ShuyunCallbackNonceStoreInterface|null  $nonceStore  非 null 时启用 freshness 与 nonce 重放拒绝
     */
    public function verifyHttpCallback(
        string $secret,
        Request $request,
        string $sign,
        ?ShuyunCallbackNonceStoreInterface $nonceStore = null,
    ): bool {
        $debug = $this->isCallbackSignatureDebugEnabled();

        if ($sign === '') {
            if ($debug) {
                $this->logCallbackSignatureDebug('verify_http_callback', [
                    'result' => false,
                    'reason' => 'empty_sign',
                ]);
            }

            return false;
        }

        $syNonce = $this->firstHeader($request, ['SY-Request-Nonce', 'Sy-Request-Nonce']);
        if ($nonceStore !== null || ($syNonce !== null && $syNonce !== '')) {
            if ($syNonce === null || $syNonce === '') {
                if ($debug) {
                    $this->logCallbackSignatureDebug('verify_http_callback', [
                        'result' => false,
                        'reason' => 'missing_sy_request_nonce',
                    ]);
                }

                return false;
            }

            return $this->verifyHttpCallbackWithBodyAndNonce(
                $secret,
                $request,
                $sign,
                $syNonce,
                $nonceStore,
                $debug,
            );
        }

        $params = $this->buildLegacySignParams($request);

        if ($params === []) {
            if ($debug) {
                $this->logCallbackSignatureDebug('verify_http_callback', [
                    'result' => false,
                    'reason' => 'no_sign_params_after_strip',
                ]);
            }

            return false;
        }

        $ok = $this->verifySortedParamsSign($secret, $params, $sign, $debug, 'legacy');

        return $ok;
    }

    /**
     * 计算 HTTP 回调期望签名（与 {@see verifyHttpCallback} 参数集一致；供联调/测试使用）。
     */
    public function expectedHttpCallbackSign(string $secret, Request $request): string
    {
        $syNonce = $this->firstHeader($request, ['SY-Request-Nonce', 'Sy-Request-Nonce']);
        if ($syNonce !== null && $syNonce !== '') {
            $syTime = $this->firstHeader($request, ['SY-Request-Time', 'Sy-Request-Time']) ?? '';
            $params = $request->query->all();
            unset($params['sign']);
            $params['SY-Request-Nonce'] = $syNonce;
            $params['SY-Request-Time'] = $syTime;
            $params['body'] = (string) $request->getContent();
            /** @var array<string, string> $normalized */
            $normalized = [];
            foreach ($params as $k => $v) {
                $normalized[(string) $k] = (string) $v;
            }

            return $this->md5FromSortedParams($secret, $normalized);
        }

        return $this->md5FromSortedParams($secret, $this->buildLegacySignParams($request));
    }

    /**
     * @return array<string, string>
     */
    private function buildLegacySignParams(Request $request): array
    {
        $params = $request->query->all();
        unset($params['sign']);

        $syTime = $this->firstHeader($request, ['SY-Request-Time', 'Sy-Request-Time']);
        if ($syTime !== null && $syTime !== '') {
            $params['SY-Request-Time'] = $syTime;
        }

        if (!isset($params['SY-Request-Time'])) {
            $legacy = $request->query->get('callBackTime');
            if ($legacy === null || $legacy === '') {
                $legacy = $this->firstHeader($request, ['callBackTime', 'Callbacktime']);
            }
            if ($legacy !== null && $legacy !== '') {
                $params['callBackTime'] = (string) $legacy;
            }
        }

        /** @var array<string, string> $normalized */
        $normalized = [];
        foreach ($params as $k => $v) {
            $normalized[(string) $k] = (string) $v;
        }

        $normalized['body'] = (string) $request->getContent();

        return $normalized;
    }

    private function verifyHttpCallbackWithBodyAndNonce(
        string $secret,
        Request $request,
        string $sign,
        string $syNonce,
        ?ShuyunCallbackNonceStoreInterface $nonceStore,
        bool $debug,
    ): bool {
        if ($nonceStore === null) {
            if ($debug) {
                $this->logCallbackSignatureDebug('verify_http_callback', [
                    'result' => false,
                    'reason' => 'nonce_store_required',
                ]);
            }

            return false;
        }

        $syTime = $this->firstHeader($request, ['SY-Request-Time', 'Sy-Request-Time']);
        if ($syTime === null || $syTime === '') {
            if ($debug) {
                $this->logCallbackSignatureDebug('verify_http_callback', [
                    'result' => false,
                    'reason' => 'missing_sy_request_time',
                ]);
            }

            return false;
        }

        if (!$this->isFreshRequestTime($syTime)) {
            if ($debug) {
                $this->logCallbackSignatureDebug('verify_http_callback', [
                    'result' => false,
                    'reason' => 'stale_request_time',
                ]);
            }

            return false;
        }

        $params = $request->query->all();
        unset($params['sign']);
        $params['SY-Request-Nonce'] = $syNonce;
        $params['SY-Request-Time'] = $syTime;
        $params['body'] = (string) $request->getContent();

        /** @var array<string, string> $normalized */
        $normalized = [];
        foreach ($params as $k => $v) {
            $normalized[(string) $k] = (string) $v;
        }

        if (!$this->verifySortedParamsSign($secret, $normalized, $sign, $debug, 'body_nonce')) {
            return false;
        }

        if (!$nonceStore->consume($syNonce, $this->callbackNonceTtlSeconds())) {
            if ($debug) {
                $this->logCallbackSignatureDebug('verify_http_callback', [
                    'result' => false,
                    'reason' => 'nonce_replay',
                ]);
            }

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, string>  $params
     */
    private function verifySortedParamsSign(
        string $secret,
        array $params,
        string $sign,
        bool $debug,
        string $mode,
    ): bool {
        $expected = $this->md5FromSortedParams($secret, $params);
        $ok = hash_equals(strtolower($expected), strtolower(trim($sign)));

        if ($debug) {
            ksort($params, SORT_STRING);
            $this->logCallbackSignatureDebug('verify_http_callback', [
                'result' => $ok,
                'mode' => $mode,
                'sorted_param_keys' => array_keys($params),
                'has_sy_request_time' => isset($params['SY-Request-Time']),
                'has_sy_request_nonce' => isset($params['SY-Request-Nonce']),
                'has_callback_time' => isset($params['callBackTime']),
                'has_body' => isset($params['body']),
                'secret_configured' => $secret !== '',
                'expected_sign' => strtolower($expected),
                'received_sign' => strtolower(trim($sign)),
            ]);
        }

        return $ok;
    }

    /**
     * @param  array<string, string>  $params
     */
    private function md5FromSortedParams(string $secret, array $params): string
    {
        ksort($params, SORT_STRING);
        $sb = $secret;
        foreach ($params as $k => $v) {
            $sb .= $k.$v;
        }
        $sb .= $secret;

        return md5($sb);
    }

    private function isFreshRequestTime(string $requestTimeMs): bool
    {
        $ts = (int) trim($requestTimeMs);
        if ($ts <= 0) {
            return false;
        }

        $nowMs = (int) floor(microtime(true) * 1000);
        $maxSkewMs = $this->callbackFreshnessMaxSkewSeconds() * 1000;

        return abs($nowMs - $ts) <= $maxSkewMs;
    }

    private function callbackFreshnessMaxSkewSeconds(): int
    {
        try {
            if (\function_exists('app') && app()->bound('config')) {
                return max(1, (int) config('shuyun_open_platform.callback_freshness_max_skew_seconds', 300));
            }
        } catch (\Throwable $e) {
        }

        return 300;
    }

    private function callbackNonceTtlSeconds(): int
    {
        try {
            if (\function_exists('app') && app()->bound('config')) {
                return max(1, (int) config('shuyun_open_platform.callback_nonce_ttl_seconds', 600));
            }
        } catch (\Throwable $e) {
        }

        return 600;
    }

    private function isCallbackSignatureDebugEnabled(): bool
    {
        try {
            if (!\function_exists('app')) {
                return false;
            }
            $app = \app();
            if (!$app->bound('config')) {
                return false;
            }

            return (bool) config('shuyun_open_platform.callback_signature_debug_log');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logCallbackSignatureDebug(string $stage, array $context): void
    {
        try {
            if (!\function_exists('app')) {
                return;
            }
            $app = \app();
            if (!$app->bound('log')) {
                return;
            }
            $app->make('log')->channel('shuyun_open_platform')->info('ShuyunOpenPlatform::callback_signature', array_merge([
                'stage' => $stage,
            ], $context));
        } catch (\Throwable $e) {
        }
    }

    /**
     * @param  list<string>  $names
     */
    private function firstHeader(Request $request, array $names): ?string
    {
        foreach ($names as $name) {
            $v = $request->headers->get($name);
            if ($v !== null && $v !== '') {
                return (string) $v;
            }
        }

        return null;
    }
}
