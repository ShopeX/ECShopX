<?php

declare(strict_types=1);

namespace Tests\DistributionBundle;

use Dingo\Api\Http\Response\Factory;
use DistributionBundle\Http\Api\V1\Action\PickupLocation;
use Illuminate\Http\Request;
use Mockery;

/**
 * 店铺端自提点列表 filter 组装 — TC1–TC6（RED/GREEN）
 * 计划：.tasks/plans/pickuplocation-list-shop-rel.md
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PickupLocationListFilterTest extends \TestCase
{
    /**
     * 最小 Lumen 容器，避免 bootstrap/app.php 触发 Doctrine DB 连接。
     */
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();
        $app->instance('path.lang', $app->basePath('resources/lang'));
        $app->register(\Illuminate\Translation\TranslationServiceProvider::class);
        $app->register(\Illuminate\Validation\ValidationServiceProvider::class);

        return $app;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * TC1 / AC1：店铺不传 rel → filter 含 company_id + distributor_id，不含 rel。
     * #given 店铺端登录，当前店=57，无 rel_distributor_id
     * #when getPickupLocationList
     * #then lists filter 含 company_id、distributor_id=57，无 rel_distributor_id
     */
    public function testShopWithoutRelFiltersByDistributorId(): void
    {
        $this->bindAuth(1, 'distributor');
        $this->bindResponseFactory();
        $this->bindLog();

        $capturedFilter = null;
        $serviceMock = Mockery::mock('overload:DistributionBundle\Services\PickupLocationService');
        $serviceMock->shouldReceive('lists')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );

        $request = Request::create('/api/pickuplocation/list', 'GET', [
            'distributor_id' => 57,
        ]);

        $controller = new PickupLocation();
        $controller->getPickupLocationList($request);

        $this->assertIsArray($capturedFilter);
        $this->assertSame(1, $capturedFilter['company_id']);
        $this->assertSame(57, $capturedFilter['distributor_id']);
        $this->assertArrayNotHasKey('rel_distributor_id', $capturedFilter);
    }

    /**
     * TC2 / AC2：店铺传 rel=当前店 → filter 含 rel，不含 distributor_id。
     * #given 店铺端登录，当前店=57，rel_distributor_id=57
     * #when getPickupLocationList
     * #then lists filter 含 company_id、rel_distributor_id=57，无 distributor_id
     */
    public function testShopWithRelFiltersByRelAndDropsDistributorId(): void
    {
        $this->bindAuth(1, 'distributor');
        $this->bindResponseFactory();
        $this->bindLog();

        $capturedFilter = null;
        $serviceMock = Mockery::mock('overload:DistributionBundle\Services\PickupLocationService');
        $serviceMock->shouldReceive('lists')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );

        $request = Request::create('/api/pickuplocation/list', 'GET', [
            'distributor_id' => 57,
            'rel_distributor_id' => 57,
        ]);

        $controller = new PickupLocation();
        $controller->getPickupLocationList($request);

        $this->assertIsArray($capturedFilter);
        $this->assertSame(1, $capturedFilter['company_id']);
        $this->assertSame(57, $capturedFilter['rel_distributor_id']);
        $this->assertArrayNotHasKey('distributor_id', $capturedFilter);
    }

    /**
     * TC3 / AC3：店铺传他店 rel → 强制为当前店，不含 distributor_id。
     * #given 店铺端登录，当前店=57，rel_distributor_id=99
     * #when getPickupLocationList
     * #then lists filter rel_distributor_id=57，无 distributor_id
     */
    public function testShopWithOtherShopRelForcedToCurrentShop(): void
    {
        $this->bindAuth(1, 'distributor');
        $this->bindResponseFactory();
        $this->bindLog();

        $capturedFilter = null;
        $serviceMock = Mockery::mock('overload:DistributionBundle\Services\PickupLocationService');
        $serviceMock->shouldReceive('lists')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );

        $request = Request::create('/api/pickuplocation/list', 'GET', [
            'distributor_id' => 57,
            'rel_distributor_id' => 99,
        ]);

        $controller = new PickupLocation();
        $controller->getPickupLocationList($request);

        $this->assertIsArray($capturedFilter);
        $this->assertSame(1, $capturedFilter['company_id']);
        $this->assertSame(57, $capturedFilter['rel_distributor_id']);
        $this->assertArrayNotHasKey('distributor_id', $capturedFilter);
    }

    /**
     * TC4 / AC4：店铺 rel=0 → 同 TC1（走自建过滤）。
     * #given 店铺端登录，当前店=57，rel_distributor_id=0
     * #when getPickupLocationList
     * #then lists filter 含 company_id、distributor_id=57，无 rel_distributor_id
     */
    public function testShopWithZeroRelTreatedAsAbsent(): void
    {
        $this->bindAuth(1, 'distributor');
        $this->bindResponseFactory();
        $this->bindLog();

        $capturedFilter = null;
        $serviceMock = Mockery::mock('overload:DistributionBundle\Services\PickupLocationService');
        $serviceMock->shouldReceive('lists')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );

        $request = Request::create('/api/pickuplocation/list', 'GET', [
            'distributor_id' => 57,
            'rel_distributor_id' => 0,
        ]);

        $controller = new PickupLocation();
        $controller->getPickupLocationList($request);

        $this->assertIsArray($capturedFilter);
        $this->assertSame(1, $capturedFilter['company_id']);
        $this->assertSame(57, $capturedFilter['distributor_id']);
        $this->assertArrayNotHasKey('rel_distributor_id', $capturedFilter);
    }

    /**
     * TC5 / AC5：平台不传 rel → filter 仅 company_id。
     * #given 平台端登录，无 rel_distributor_id
     * #when getPickupLocationList
     * #then lists filter 仅含 company_id
     */
    public function testPlatformWithoutRelHasOnlyCompanyId(): void
    {
        $this->bindAuth(1, 'admin');
        $this->bindResponseFactory();
        $this->bindLog();

        $capturedFilter = null;
        $serviceMock = Mockery::mock('overload:DistributionBundle\Services\PickupLocationService');
        $serviceMock->shouldReceive('lists')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );

        $request = Request::create('/api/pickuplocation/list', 'GET', [
            'distributor_id' => 57,
        ]);

        $controller = new PickupLocation();
        $controller->getPickupLocationList($request);

        $this->assertIsArray($capturedFilter);
        $this->assertSame(1, $capturedFilter['company_id']);
        $this->assertArrayNotHasKey('distributor_id', $capturedFilter);
        $this->assertArrayNotHasKey('rel_distributor_id', $capturedFilter);
    }

    /**
     * TC6 / AC6：平台传 rel → filter 含 company_id + rel，不含 distributor_id。
     * #given 平台端登录，rel_distributor_id=57
     * #when getPickupLocationList
     * #then lists filter 含 company_id、rel_distributor_id=57，无 distributor_id
     */
    public function testPlatformWithRelKeepsRelDropsDistributorId(): void
    {
        $this->bindAuth(1, 'admin');
        $this->bindResponseFactory();
        $this->bindLog();

        $capturedFilter = null;
        $serviceMock = Mockery::mock('overload:DistributionBundle\Services\PickupLocationService');
        $serviceMock->shouldReceive('lists')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );

        $request = Request::create('/api/pickuplocation/list', 'GET', [
            'rel_distributor_id' => 57,
        ]);

        $controller = new PickupLocation();
        $controller->getPickupLocationList($request);

        $this->assertIsArray($capturedFilter);
        $this->assertSame(1, $capturedFilter['company_id']);
        $this->assertSame(57, $capturedFilter['rel_distributor_id']);
        $this->assertArrayNotHasKey('distributor_id', $capturedFilter);
    }

    private function bindAuth(int $companyId, string $operatorType): void
    {
        $user = Mockery::mock();
        $user->shouldReceive('get')->with('company_id')->andReturn($companyId);
        $user->shouldReceive('get')->with('operator_type')->andReturn($operatorType);
        $authGuard = Mockery::mock();
        $authGuard->shouldReceive('user')->andReturn($user);
        $this->app->instance('auth', $authGuard);
    }

    private function bindResponseFactory(): void
    {
        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('array')->andReturnUsing(static fn (array $payload): array => $payload);
        $this->app->instance(Factory::class, $factory);
    }

    private function bindLog(): void
    {
        $log = $this->getMockBuilder(\stdClass::class)->addMethods(['debug', 'info'])->getMock();
        $log->method('debug')->willReturn(null);
        $log->method('info')->willReturn(null);
        $this->app->instance('log', $log);
    }
}
