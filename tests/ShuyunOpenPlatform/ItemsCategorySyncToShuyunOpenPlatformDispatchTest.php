<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Bus\Dispatcher;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Listeners\ItemsCategorySyncToShuyunOpenPlatformDispatch;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMergedJobDispatchService;

/** @see .tasks/plans/shuyun-open-platform-category-goods-sync.md */
class ItemsCategorySyncToShuyunOpenPlatformDispatchTest extends \TestCase
{
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

        ItemsCategorySyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 20);
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

        ItemsCategorySyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 20);
    }

    public function testMergesRapidDuplicateDispatchesForSameCategory(): void
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

        ItemsCategorySyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 20);
        ItemsCategorySyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 20);
    }
}
