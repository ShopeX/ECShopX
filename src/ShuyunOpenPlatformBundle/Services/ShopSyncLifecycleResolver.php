<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

final class ShopSyncLifecycleResolver
{
    public const ENABLED = 'enabled';

    /** 禁用（is_valid false/0）：店铺同步 Job 仅推 OFFLINE，不推自定义线上 plat。 */
    public const DISABLED = 'disabled';

    /** 闭店：数云双 plat status=2（与 {@see ShuyunOpenPlatformShopSyncService::buildTargetShops} 对齐）。 */
    public const CLOSED = 'closed';

    /** 废弃：数云双 plat status=0。 */
    public const DELETED = 'deleted';

    /**
     * @param  array<string, mixed>  $distributorRow
     */
    public function resolve(array $distributorRow): string
    {
        $isValid = strtolower(trim((string) ($distributorRow['is_valid'] ?? '')));
        if ($isValid === 'true' || $isValid === '1') {
            return self::ENABLED;
        }
        if ($isValid === 'false' || $isValid === '0') {
            return self::DISABLED;
        }
        if ($isValid === 'closed') {
            return self::CLOSED;
        }
        if ($isValid === 'delete') {
            return self::DELETED;
        }

        return self::ENABLED;
    }
}
