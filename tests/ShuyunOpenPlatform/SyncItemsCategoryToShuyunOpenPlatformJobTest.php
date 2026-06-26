<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Jobs\SyncItemsCategoryToShuyunOpenPlatformJob;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformCategorySyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/** @see .tasks/plans/shuyun-open-platform-category-goods-sync.md */
class SyncItemsCategoryToShuyunOpenPlatformJobTest extends \TestCase
{
    public function testReturnsTrueWhenSyncReturnsFalseWhileEligible(): void
    {
        $openRow = new CompanyShuyunOpenPlatformConfig();
        $openRow->setCompanyId(1);
        $openRow->setAuthValue('a');
        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->with(1)->willReturn($openRow);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);

        $cat = $this->createMock(ShuyunOpenPlatformCategorySyncService::class);
        $cat->method('syncCategory')->with(1, 20)->willReturn(false);
        $this->app->instance(ShuyunOpenPlatformCategorySyncService::class, $cat);

        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturnCallback(function ($cfg) {
            return $cfg instanceof CompanyShuyunOpenPlatformConfig;
        });
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $shop);

        $this->assertTrue((new SyncItemsCategoryToShuyunOpenPlatformJob(1, 20))->handle());
    }

    public function testReturnsTrueWhenSyncFalseButNotEligible(): void
    {
        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->willReturn(null);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $openRepo);

        $cat = $this->createMock(ShuyunOpenPlatformCategorySyncService::class);
        $cat->method('syncCategory')->willReturn(false);
        $this->app->instance(ShuyunOpenPlatformCategorySyncService::class, $cat);

        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->with(null)->willReturn(false);
        $this->app->instance(ShuyunOpenPlatformShopSyncService::class, $shop);

        $this->assertTrue((new SyncItemsCategoryToShuyunOpenPlatformJob(1, 20))->handle());
    }
}
