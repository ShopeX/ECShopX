<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Bus\Dispatcher;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Listeners\ItemsProductSyncToShuyunOpenPlatformDispatch;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMergedJobDispatchService;

class ItemsProductSyncToShuyunOpenPlatformDispatchTest extends \TestCase
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

        ItemsProductSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 2, 100);
    }

    public function testMergesSameProductKey(): void
    {
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(1);
        $row->setAuthValue('x');

        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->willReturn($row);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);

        $this->app->instance(ShuyunOpenPlatformMergedJobDispatchService::class, new ShuyunOpenPlatformMergedJobDispatchService(
            new Repository(new ArrayStore()),
            300,
        ));

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())->method('dispatch');
        $this->app->instance(Dispatcher::class, $dispatcher);

        ItemsProductSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 2, 100);
        ItemsProductSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows(1, 2, 100);
    }
}
