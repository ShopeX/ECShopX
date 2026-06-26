<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Auth;

use Illuminate\Http\Request;

/**
 * 数云回调验签：
 *
 * - 正式规则（合作方平台「身份注册」全局密匙）：全部参与签名的 query 参数（**不含 sign**）按 key ASCII 升序，
 *   再按 key+value 依次拼接，整体为 MD5(secret + 拼接串 + secret)；请求头 **SY-Request-Time** 作为一对参数参与排序。
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
     * HTTP 回调验签：query 全量参与（去掉 sign）+ 头 SY-Request-Time（若有）。
     */
    public function verifyHttpCallback(string $secret, Request $request, string $sign): bool
    {
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

        if ($params === []) {
            if ($debug) {
                $this->logCallbackSignatureDebug('verify_http_callback', [
                    'result' => false,
                    'reason' => 'no_sign_params_after_strip',
                ]);
            }

            return false;
        }

        ksort($params, SORT_STRING);
        $sb = $secret;
        foreach ($params as $k => $v) {
            $sb .= (string) $k.(string) $v;
        }
        $sb .= $secret;
        $expected = md5($sb);
        $ok = hash_equals(strtolower($expected), strtolower(trim($sign)));

        if ($debug) {
            $this->logCallbackSignatureDebug('verify_http_callback', [
                'result' => $ok,
                'sorted_param_keys' => array_keys($params),
                'has_sy_request_time' => isset($params['SY-Request-Time']),
                'has_callback_time' => isset($params['callBackTime']),
                'secret_configured' => $secret !== '',
                'expected_sign' => strtolower($expected),
                'received_sign' => strtolower(trim($sign)),
            ]);
        }

        return $ok;
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
