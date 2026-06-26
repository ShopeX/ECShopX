<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Http\Support;

/**
 * 数云 **入站** HTTP 回调验签用密钥：合作方「身份注册」密匙，对应 env {@code SHUYUN_OPEN_PLATFORM_CALLBACK_IDENTITY_SECRET}。
 *
 * 与 {@see \ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig::getAppSecret}（出站请求数云网关签名）**不是同一用途**，禁止混用。
 */
final class ShuyunOpenPlatformInboundCallbackSecret
{
    public static function getTrimmedFromConfig(): string
    {
        return trim((string) config('shuyun_open_platform.callback_identity_secret', ''));
    }
}
