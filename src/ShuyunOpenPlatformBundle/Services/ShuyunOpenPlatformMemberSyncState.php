<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * OFFLINE-only：小程序与店务共用一次 member.register，以本地两字段互为「已入会」标记。
 */
final class ShuyunOpenPlatformMemberSyncState
{
    /**
     * @param  array<string, mixed>  $memberRow
     */
    public static function isRegisteredWithOpenPlatform(array $memberRow): bool
    {
        return (int) ($memberRow['shuyun_open_online_wxapp_sync_at'] ?? 0) > 0
            || (int) ($memberRow['offline_reg_distributor'] ?? 0) > 0;
    }

    /**
     * 店务已 register、尚未 bind.push / 未写 wxapp 同步时间。
     *
     * @param  array<string, mixed>  $memberRow
     */
    public static function needsWxappBindPushOnly(array $memberRow): bool
    {
        if ((int) ($memberRow['shuyun_open_online_wxapp_sync_at'] ?? 0) > 0) {
            return false;
        }

        return (int) ($memberRow['offline_reg_distributor'] ?? 0) > 0;
    }
}
