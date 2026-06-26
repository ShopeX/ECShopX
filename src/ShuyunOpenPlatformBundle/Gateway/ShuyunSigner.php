<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Gateway;

/**
 * Gateway-Sign：参数名 ASCII 升序后拼接为 参数名1参数值1...，再 MD5(appSecret . 拼接 . appSecret)。
 */
final class ShuyunSigner
{
    /**
     * @param  array<string, string>  $params 参与签名的键值（含 Gateway-Request-Time 与 GET 的 query 等）
     */
    public function sign(string $appSecret, array $params): string
    {
        if ($appSecret === '') {
            throw new \InvalidArgumentException('appSecret must not be empty.');
        }
        $sorted = $params;
        ksort($sorted, SORT_STRING);
        $concat = '';
        foreach ($sorted as $name => $value) {
            $concat .= $name.$value;
        }

        return md5($appSecret.$concat.$appSecret);
    }
}
