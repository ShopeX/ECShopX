<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;

final class DefaultShuyunOpenPlatformShopPlatCodeResolver implements ShuyunOpenPlatformShopPlatCodeResolver
{
    public function resolve(CompanyShuyunOpenPlatformConfig $config, array $distributorRow): string
    {
        return 'OFFLINE';
    }
}
