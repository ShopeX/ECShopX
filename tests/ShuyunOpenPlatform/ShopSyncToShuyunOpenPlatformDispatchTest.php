<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Bus\Dispatcher;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Listeners\ShopSyncToShuyunOpenPlatformDispatch;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMergedJobDispatchService;

/** @see .tasks/plans/shuyun-open-platform-shop-sync-body-and-logging.md A-PRE-01 */
class ShopSyncToShuyunOpenPlatformDispatchTest extends \TestCase
{
    public function testDoesNotDispatchWhenCompanyOrDistributorIdInvalid(): void
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $this->app->instance(Dispatcher::class, $dispatcher);

        ShopSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(0, 1);
        ShopSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 0);
    }

    public function testDoesNotDispatchWhenAuthValueEmpty(): void
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(1);
        $row->setAuthValue('');

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(1)->willReturn($row);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);

        $this->app->instance(ShuyunOpenPlatformMergedJobDispatchService::class, new ShuyunOpenPlatformMergedJobDispatchService(
            new Repository(new ArrayStore()),
            60,
        ));

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $this->app->instance(Dispatcher::class, $dispatcher);

        ShopSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 2);
    }

    public function testDispatchesWhenAuthValueNonEmpty(): void
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(1);
        $row->setAuthValue('tenant-x');

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(1)->willReturn($row);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);

        $this->app->instance(ShuyunOpenPlatformMergedJobDispatchService::class, new ShuyunOpenPlatformMergedJobDispatchService(
            new Repository(new ArrayStore()),
            60,
        ));

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())->method('dispatch');
        $this->app->instance(Dispatcher::class, $dispatcher);

        ShopSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 2);
    }

    /** @see .tasks/plans/shuyun-open-platform-category-goods-sync.md A-MERGE-01 */
    public function testMergesRapidDuplicateDispatchesForSameShop(): void
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(1);
        $row->setAuthValue('tenant-x');

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(1)->willReturn($row);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);

        $this->app->instance(ShuyunOpenPlatformMergedJobDispatchService::class, new ShuyunOpenPlatformMergedJobDispatchService(
            new Repository(new ArrayStore()),
            300,
        ));

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())->method('dispatch');
        $this->app->instance(Dispatcher::class, $dispatcher);

        ShopSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 2);
        ShopSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 2);
    }
}
