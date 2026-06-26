<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;

/**
 * 店铺同步 Job 使用的目标 {@see shuyun.base.shop.batch.register} `shops[].plat_code` 列表（大写）。
 * 仅 OFFLINE（含虚拟店与各类 is_valid 生命周期）。
 */
final class ShuyunOpenPlatformShopSyncTargetPlatCodesResolver
{
    /**
     * @param  array<string, mixed>  $shopRow  分销商行（Job 内 `getInfo`），保留参数供调用方兼容。
     *
     * @return list<string>
     */
    public function resolveForShopJob(string $lifecycle, ?CompanyShuyunOpenPlatformConfig $config, int $companyId, int $distributorId, array $shopRow = []): array
    {
        return ['OFFLINE'];
    }
}
