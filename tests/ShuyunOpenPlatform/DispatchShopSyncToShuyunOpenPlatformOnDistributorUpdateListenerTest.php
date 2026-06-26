<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use DistributionBundle\Events\DistributorUpdateEvent;
use Illuminate\Contracts\Bus\Dispatcher;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Listeners\DispatchShopSyncToShuyunOpenPlatformOnDistributorUpdateListener;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

class DispatchShopSyncToShuyunOpenPlatformOnDistributorUpdateListenerTest extends \TestCase
{
    /** @see .tasks/plans/shuyun-open-platform-shop-sync-cloud-store-split.md A-SPLIT-05 */
    public function testDispatchesWhenNameChanged(): void
    {
        #given
        config(['doctrine.managers' => []]);
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(1);
        $row->setAuthValue('tenant-x');
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(1)->willReturn($row);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())->method('dispatch');
        $this->app->instance(Dispatcher::class, $dispatcher);
        $listener = new DispatchShopSyncToShuyunOpenPlatformOnDistributorUpdateListener();
        $event = new DistributorUpdateEvent([
            'company_id' => 1,
            'distributor_id' => 2,
            'name' => 'new',
            '__old_name' => 'old',
        ]);

        #when
        $listener->handle($event);

        #then
        $this->assertTrue(true);
    }

    /** @see .tasks/plans/shuyun-open-platform-shop-sync-cloud-store-split.md A-SPLIT-06 */
    public function testDoesNotDispatchWhenNameUnchanged(): void
    {
        #given
        config(['doctrine.managers' => []]);
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(1);
        $row->setAuthValue('tenant-x');
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(1)->willReturn($row);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $this->app->instance(Dispatcher::class, $dispatcher);
        $listener = new DispatchShopSyncToShuyunOpenPlatformOnDistributorUpdateListener();
        $event = new DistributorUpdateEvent([
            'company_id' => 1,
            'distributor_id' => 2,
            'name' => 'same',
            '__old_name' => 'same',
        ]);

        #when
        $listener->handle($event);

        #then
        $this->assertTrue(true);
    }

    /** @see .tasks/plans/shuyun-open-platform-shop-sync-cloud-store-split.md A-SPLIT-06 */
    public function testDoesNotDispatchWhenNameMissing(): void
    {
        #given
        config(['doctrine.managers' => []]);
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(1);
        $row->setAuthValue('tenant-x');
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(1)->willReturn($row);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $this->app->instance(Dispatcher::class, $dispatcher);
        $listener = new DispatchShopSyncToShuyunOpenPlatformOnDistributorUpdateListener();
        $event = new DistributorUpdateEvent([
            'company_id' => 1,
            'distributor_id' => 2,
            '__old_name' => 'old',
        ]);

        #when
        $listener->handle($event);

        #then
        $this->assertTrue(true);
    }

    public function testDispatchesWhenStatusIntentAndIsValidChanged(): void
    {
        #given
        config(['doctrine.managers' => []]);
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(1);
        $row->setAuthValue('tenant-x');
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(1)->willReturn($row);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())->method('dispatch');
        $this->app->instance(Dispatcher::class, $dispatcher);
        $listener = new DispatchShopSyncToShuyunOpenPlatformOnDistributorUpdateListener();
        $event = new DistributorUpdateEvent([
            'company_id' => 1,
            'distributor_id' => 2,
            'name' => 'same',
            '__old_name' => 'same',
            '__old_is_valid' => 'true',
            'is_valid' => 'false',
            '__client_intent_status' => true,
            'distributor_self' => '0',
        ]);

        #when
        $listener->handle($event);

        #then
        $this->assertTrue(true);
    }

    public function testDoesNotDispatchWhenStatusIntentButStateUnchanged(): void
    {
        #given
        config(['doctrine.managers' => []]);
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(1);
        $row->setAuthValue('tenant-x');
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(1)->willReturn($row);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->never())->method('dispatch');
        $this->app->instance(Dispatcher::class, $dispatcher);
        $listener = new DispatchShopSyncToShuyunOpenPlatformOnDistributorUpdateListener();
        $event = new DistributorUpdateEvent([
            'company_id' => 1,
            'distributor_id' => 2,
            'name' => 'same',
            '__old_name' => 'same',
            '__old_is_valid' => 'false',
            'is_valid' => 'false',
            '__client_intent_status' => true,
        ]);

        #when
        $listener->handle($event);

        #then
        $this->assertTrue(true);
    }

    public function testDispatchesWhenProfileIntentWithoutNameChange(): void
    {
        #given
        config(['doctrine.managers' => []]);
        $row = new CompanyShuyunOpenPlatformConfig();
        $row->setCompanyId(1);
        $row->setAuthValue('tenant-x');
        $repo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $repo->method('findOneByCompanyId')->with(1)->willReturn($row);
        $this->app->instance(CompanyShuyunOpenPlatformConfigRepository::class, $repo);
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())->method('dispatch');
        $this->app->instance(Dispatcher::class, $dispatcher);
        $listener = new DispatchShopSyncToShuyunOpenPlatformOnDistributorUpdateListener();
        $event = new DistributorUpdateEvent([
            'company_id' => 1,
            'distributor_id' => 2,
            'name' => 'same',
            '__old_name' => 'same',
            '__client_intent_profile' => true,
        ]);

        #when
        $listener->handle($event);

        #then
        $this->assertTrue(true);
    }
}
