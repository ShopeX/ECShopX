<?php

declare(strict_types=1);

namespace ThirdPartyBundle\Support;

/**
 * 海关 response 回调验签（与 {@see \ThirdPartyBundle\Services\CustomsCentre\CustomsService} 出站签名一致）。
 */
final class CustomsCallbackSignatureVerifier
{
    public static function verifyCustomsCallbackSignature(array $params): bool
    {
        $timestamp = isset($params['timestamp']) ? trim((string) $params['timestamp']) : '';
        $sign = isset($params['sign']) ? strtoupper(trim((string) $params['sign'])) : '';
        if ($timestamp === '' || $sign === '') {
            return false;
        }

        $signKey = (string) config('customs.sign_key');
        if ($signKey === '') {
            return false;
        }

        $expected = strtoupper(md5($timestamp.$signKey));

        return hash_equals($expected, $sign);
    }
}
