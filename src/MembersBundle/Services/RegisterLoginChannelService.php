<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * @deprecated 产品已改为「手机号注册/登录」与「邮箱注册/登录」**同时可用**，不再按企业做二选一互斥。
 *             本服务读写 Redis 的通道键仍可保留供运维/历史脚本使用，但 **Members 前台接口不再调用**。
 */

namespace MembersBundle\Services;

class RegisterLoginChannelService
{
    public const CHANNEL_MOBILE = 'mobile';
    public const CHANNEL_EMAIL = 'email';

    private function redisKey(int $companyId): string
    {
        return 'register_login_channel:' . sha1((string) $companyId);
    }

    public function getChannel(int $companyId): string
    {
        $raw = app('redis')->connection('members')->get($this->redisKey($companyId));
        if ($raw === null || $raw === false || $raw === '') {
            return self::CHANNEL_MOBILE;
        }
        $data = json_decode((string) $raw, true);
        $ch = $data['channel'] ?? self::CHANNEL_MOBILE;

        return $ch === self::CHANNEL_EMAIL ? self::CHANNEL_EMAIL : self::CHANNEL_MOBILE;
    }

    public function setChannel(int $companyId, string $channel): void
    {
        $normalized = $channel === self::CHANNEL_EMAIL ? self::CHANNEL_EMAIL : self::CHANNEL_MOBILE;
        app('redis')->connection('members')->set($this->redisKey($companyId), json_encode(['channel' => $normalized]));
    }
}
