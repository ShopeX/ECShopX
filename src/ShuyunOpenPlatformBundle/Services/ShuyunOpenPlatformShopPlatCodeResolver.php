<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;

/**
 * 店铺同步 shops[].plat_code：云店用租户 plat / 默认 plat；门店 OFFLINE 待产品提供 distributor 判定（TODO）。
 *
 * @see .tasks/plans/shuyun-open-platform-shop-sync-body-and-logging.md
 */
interface ShuyunOpenPlatformShopPlatCodeResolver
{
    /**
     * @param  array<string, mixed>  $distributorRow
     */
    public function resolve(CompanyShuyunOpenPlatformConfig $config, array $distributorRow): string;
}
