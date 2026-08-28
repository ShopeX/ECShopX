<?php

declare(strict_types=1);

namespace ThirdPartyBundle\Support;

/**
 * 580 入站回调验签（与 {@see \ThirdPartyBundle\Services\Kuaizhen580Center\Client\Request::signature} 规则一致）。
 */
final class Kuaizhen580InboundSignatureVerifier
{
    public static function expectedSign(array $params, string $clientSecret): string
    {
        $params = self::kSort(self::removeEmptyValues($params));

        $str = '';
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                if (self::isAssoc($value)) {
                    $value = self::getSignStringByArray($value);
                } else {
                    $value = self::getSignStringByList($value);
                }
                $str .= $key.'='.$value.'&';
            } elseif ($value !== '') {
                $str .= $key.'='.$value.'&';
            }
        }
        $str .= 'key='.$clientSecret;

        return strtoupper(md5($str));
    }

    public static function verify(array $params, string $clientSecret, string $sign): bool
    {
        $provided = strtoupper(trim($sign));
        if ($provided === '') {
            return false;
        }

        $unsigned = $params;
        unset($unsigned['sign']);

        return hash_equals(self::expectedSign($unsigned, $clientSecret), $provided);
    }

    public static function getSignStringByArray(array $map): string
    {
        $stringBuffer = '{';
        ksort($map);

        foreach ($map as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $stringBuffer .= $key.':';

            if (is_array($value)) {
                if (self::isAssoc($value)) {
                    $stringBuffer .= self::getSignStringByArray($value).',';
                } else {
                    $stringBuffer .= self::getSignStringByList($value).',';
                }
            } else {
                $stringBuffer .= $value.',';
            }
        }

        if (strlen($stringBuffer) > 1) {
            $stringBuffer = substr($stringBuffer, 0, -1);
        }
        $stringBuffer .= '}';

        return $stringBuffer;
    }

    public static function getSignStringByList(array $list): string
    {
        if ($list === []) {
            return '';
        }

        $stringBuffer = '[';
        foreach ($list as $value) {
            if (is_array($value)) {
                if (self::isAssoc($value)) {
                    $stringBuffer .= self::getSignStringByArray($value).',';
                } else {
                    $stringBuffer .= self::getSignStringByList($value).',';
                }
            } else {
                $stringBuffer .= $value.',';
            }
        }

        if (strlen($stringBuffer) > 1) {
            $stringBuffer = substr($stringBuffer, 0, -1);
        }
        $stringBuffer .= ']';

        return $stringBuffer;
    }

    private static function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private static function kSort(array $params): array
    {
        $params = self::removeEmptyValues($params);
        ksort($params);
        foreach ($params as &$param) {
            if (is_array($param)) {
                $param = self::kSort($param);
            }
        }
        unset($param);

        return $params;
    }

    private static function removeEmptyValues(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && $value !== []) {
                $value = self::removeEmptyValues($value);
                if ($value !== []) {
                    $result[$key] = $value;
                }
            } elseif ($value !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
