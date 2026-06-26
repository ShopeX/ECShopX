<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Jobs\SyncProductsToShuyunOpenPlatformJob;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformProductSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

class SyncProductsToShuyunOpenPlatformJobTest extends \TestCase
{
    public function testReturnsTrueWhenSyncFalseWhileEligible(): void
    {
        $openRow = new CompanyShuyunOpenPlatformConfig();
        $openRow->setCompanyId(1);
        $openRow->setAuthValue('a');
        $openRow->setPlatCode('P');
        $openRow->setAppId('id');
        $openRow->setAppSecret('s');
        $openRow->setAccessToken('t');
        $openRow->setIsEnabled(1);

        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->with(1)->willReturn($openRow);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);

        $prod = $this->createMock(ShuyunOpenPlatformProductSyncService::class);
        $prod->method('syncProductByDefaultItem')->with(1, 2, 100)->willReturn(false);
        $this->app->instance(ShuyunOpenPlatformProductSyncService::class, $prod);

        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturnCallback(fn ($cfg) => $cfg instanceof CompanyShuyunOpenPlatformConfig);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $shop);

        $this->assertTrue((new SyncProductsToShuyunOpenPlatformJob(1, 2, 100))->handle());
    }

    public function testReturnsTrueWhenNotEligible(): void
    {
        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->willReturn(null);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);

        $prod = $this->createMock(ShuyunOpenPlatformProductSyncService::class);
        $prod->method('syncProductByDefaultItem')->willReturn(false);
        $this->app->instance(ShuyunOpenPlatformProductSyncService::class, $prod);

        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->with(null)->willReturn(false);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $shop);

        $this->assertTrue((new SyncProductsToShuyunOpenPlatformJob(1, 2, 100))->handle());
    }
}
