<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 实体门店状态迁移解析（计划第三节真源）。
 *
 * 仅用于“状态路径”：输入 old/new is_valid，输出应同步的目标 plat 列表（大写）。
 */
final class ShopSyncStatusTransitionResolver
{
    public const REASON_SKIP_NO_CHANGE = 'skip_no_change';

    public const REASON_SKIP_DISABLED_TO_CLOSED = 'skip_disabled_to_closed';

    public const REASON_SYNC_ONLINE_ONLY = 'sync_online_only';

    public const REASON_SYNC_OFFLINE_AND_ONLINE = 'sync_offline_and_online';

    /**
     * @return array{should_sync: bool, target_plat_codes: list<string>, reason: string}
     */
    public function resolve(string $oldIsValid, string $newIsValid, ?string $onlinePlatCodeUpper): array
    {
        $old = $this->normalizeIsValid($oldIsValid);
        $new = $this->normalizeIsValid($newIsValid);
        if ($old === $new) {
            return [
                'should_sync' => false,
                'target_plat_codes' => [],
                'reason' => self::REASON_SKIP_NO_CHANGE,
            ];
        }

        if ($old === ShopSyncLifecycleResolver::DISABLED && $new === ShopSyncLifecycleResolver::CLOSED) {
            return [
                'should_sync' => false,
                'target_plat_codes' => [],
                'reason' => self::REASON_SKIP_DISABLED_TO_CLOSED,
            ];
        }

        if ($new === ShopSyncLifecycleResolver::DELETED || $new === ShopSyncLifecycleResolver::CLOSED) {
            return [
                'should_sync' => true,
                'target_plat_codes' => $this->offlineAndOnline($onlinePlatCodeUpper),
                'reason' => self::REASON_SYNC_OFFLINE_AND_ONLINE,
            ];
        }

        // 启用/禁用迁移：第三节要求仅更新线上；无线上 plat 则无有效目标。
        if ($new === ShopSyncLifecycleResolver::ENABLED || $new === ShopSyncLifecycleResolver::DISABLED) {
            $online = $this->normalizeOnlinePlat($onlinePlatCodeUpper);
            if ($online === null) {
                return [
                    'should_sync' => false,
                    'target_plat_codes' => [],
                    'reason' => self::REASON_SKIP_NO_CHANGE,
                ];
            }

            return [
                'should_sync' => true,
                'target_plat_codes' => [$online],
                'reason' => self::REASON_SYNC_ONLINE_ONLY,
            ];
        }

        return [
            'should_sync' => false,
            'target_plat_codes' => [],
            'reason' => self::REASON_SKIP_NO_CHANGE,
        ];
    }

    private function normalizeIsValid(string $raw): string
    {
        $v = strtolower(trim($raw));
        if ($v === 'true' || $v === '1') {
            return ShopSyncLifecycleResolver::ENABLED;
        }
        if ($v === 'false' || $v === '0') {
            return ShopSyncLifecycleResolver::DISABLED;
        }
        if ($v === 'closed') {
            return ShopSyncLifecycleResolver::CLOSED;
        }
        if ($v === 'delete') {
            return ShopSyncLifecycleResolver::DELETED;
        }

        return ShopSyncLifecycleResolver::ENABLED;
    }

    /**
     * @return list<string>
     */
    private function offlineAndOnline(?string $onlinePlatCodeUpper): array
    {
        $online = $this->normalizeOnlinePlat($onlinePlatCodeUpper);
        if ($online === null) {
            return ['OFFLINE'];
        }

        return ['OFFLINE', $online];
    }

    private function normalizeOnlinePlat(?string $onlinePlatCodeUpper): ?string
    {
        $online = strtoupper(trim((string) $onlinePlatCodeUpper));
        if ($online === '' || $online === 'OFFLINE') {
            return null;
        }

        return $online;
    }
}
