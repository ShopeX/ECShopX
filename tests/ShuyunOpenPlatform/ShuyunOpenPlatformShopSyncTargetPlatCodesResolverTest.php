<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncTargetPlatCodesResolver;

/** @see .tasks/plans/shuyun-offline-only.md TC-SHP-02 */
class ShuyunOpenPlatformShopSyncTargetPlatCodesResolverTest extends \TestCase
{
    public function testVirtualShopEnabledResolvesOfflineOnly(): void
    {
        $resolver = new ShuyunOpenPlatformShopSyncTargetPlatCodesResolver();
        $config = new CompanyShuyunOpenPlatformConfig();
        $config->setPlatCode('MYPLAT');

        $codes = $resolver->resolveForShopJob(
            'ENABLED',
            $config,
            1,
            99,
            ['distributor_id' => 99, 'distributor_self' => 1, 'is_valid' => 'true'],
        );

        $this->assertSame(['OFFLINE'], $codes);
    }

    public function testPhysicalShopClosedResolvesOfflineOnly(): void
    {
        $resolver = new ShuyunOpenPlatformShopSyncTargetPlatCodesResolver();

        $codes = $resolver->resolveForShopJob(
            'CLOSED',
            null,
            1,
            10,
            ['distributor_id' => 10, 'is_valid' => 'closed'],
        );

        $this->assertSame(['OFFLINE'], $codes);
    }
}
