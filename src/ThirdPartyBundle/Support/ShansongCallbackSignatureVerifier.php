<?php

declare(strict_types=1);

namespace ThirdPartyBundle\Support;

use OrdersBundle\Services\CompanyRelShansongService;

/**
 * 闪送入站回调验签（fail-closed：缺 sign 或密钥不匹配则拒绝）。
 */
final class ShansongCallbackSignatureVerifier
{
    public static function verifyCallbackSignature(int $companyId, array $params): bool
    {
        $provided = isset($params['sign']) ? strtoupper(trim((string) $params['sign'])) : '';
        if ($provided === '' || $companyId <= 0) {
            return false;
        }

        $repo = new CompanyRelShansongService();
        $rel = $repo->getInfo(['company_id' => $companyId]);
        $appSecret = isset($rel['app_secret']) ? trim((string) $rel['app_secret']) : '';
        if ($appSecret === '') {
            return false;
        }

        $unsigned = $params;
        unset($unsigned['sign']);

        if (isset($unsigned['clientId'], $unsigned['timestamp'])) {
            $expected = self::expectedWrappedSign($unsigned, $appSecret);
        } else {
            $expected = self::expectedFlatSign($unsigned, $appSecret);
        }

        return hash_equals($expected, $provided);
    }

    private static function expectedWrappedSign(array $params, string $appSecret): string
    {
        $str = $appSecret;
        $str .= 'clientId'.($params['clientId'] ?? '');
        if (!empty($params['data'])) {
            $data = is_array($params['data']) ? json_encode($params['data'], JSON_UNESCAPED_UNICODE) : (string) $params['data'];
            $str .= 'data'.$data;
        }
        if (isset($params['shopId'])) {
            $str .= 'shopId'.$params['shopId'];
        }
        $str .= 'timestamp'.($params['timestamp'] ?? '');

        return strtoupper(md5($str));
    }

    private static function expectedFlatSign(array $params, string $appSecret): string
    {
        ksort($params);
        $str = $appSecret;
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $str .= $key.(string) $value;
        }
        $str .= $appSecret;

        return strtoupper(md5($str));
    }
}
