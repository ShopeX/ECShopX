<?php

declare(strict_types=1);

use CommunityBundle\Http\FrontApi\V1\Action\chief\CommunityActivity;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Response\Factory;
use Illuminate\Http\Request;

/**
 * 团长选品列表 Controller 版本分支 — TC-S5/S5b/S6/S8/S9、TC-N1～N3、TC-X1/X2/X3（T6 RED）
 * 计划：.tasks/plans/community-chief-items-standard-store.md
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class CommunityChiefItemsListBranchTest extends TestCase
{
    private const COMPANY_ID = 1;

    private const CHIEF_ID = 100;

    private const STORE_ID = 306;

    /**
     * 最小 Lumen 容器，避免 bootstrap/app.php 触发 Doctrine DB 连接。
     */
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__));
        $app->withFacades();
        $app->instance('path.lang', $app->basePath('resources/lang'));
        $app->register(\Illuminate\Translation\TranslationServiceProvider::class);
        $app->register(\Illuminate\Validation\ValidationServiceProvider::class);

        return $app;
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * TC-S5 / A-S5：standard + 绑店=0 + getDistributorSelf→V>0 → ForStandardStore(V)，filter.distributor_id=0。
     * #given product_model=standard，绑店 distributor_id=0，虚拟店 V=306
     * #when getDisitrbutorItemList
     * #then 调用 getItemsListForStandardStore(V)，不调 getItemsList
     */
    public function testTcS5StandardBoundZeroWithVirtualStoreUsesForStandardStore(): void
    {
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindProductModel('standard');
        $this->bindChiefDistributor(['distributor_id' => 0]);
        $this->bindDistributorSelf(self::STORE_ID);

        $capturedFilter = null;
        $capturedStoreId = null;
        $itemsMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityItemsService');
        $itemsMock->shouldReceive('getItemsListForStandardStore')->once()->andReturnUsing(
            function ($filter, $storeDistributorId) use (&$capturedFilter, &$capturedStoreId): array {
                $capturedFilter = $filter;
                $capturedStoreId = $storeDistributorId;

                return ['total_count' => 0, 'list' => []];
            }
        );
        $itemsMock->shouldReceive('getItemsList')->andReturn(['total_count' => 0, 'list' => []]);

        $request = $this->createChiefRequest();
        $controller = new CommunityActivity();
        $controller->getDisitrbutorItemList($request);

        $this->assertNotNull($capturedFilter, 'TC-S5: getItemsListForStandardStore must be called when virtual store > 0');
        $this->assertSame(0, $capturedFilter['distributor_id'], 'TC-S5: standard store branch should filter headquarters distributor_id=0');
        $this->assertSame(self::STORE_ID, $capturedStoreId, 'TC-S5: store parameter should be virtual store ID');
        $this->assertSame('onsale', $capturedFilter['approve_status']);
        $this->assertSame('approved', $capturedFilter['audit_status']);
        $this->assertTrue($capturedFilter['is_default']);
        $this->assertSame('normal', $capturedFilter['item_type']);
        $this->assertSame(self::COMPANY_ID, $capturedFilter['company_id']);
    }

    /**
     * TC-S5b / A-S5b：standard + 绑店=0 + getDistributorSelf→0 → 回退 getItemsList，never ForStandardStore。
     * #given product_model=standard，绑店 distributor_id=0，无虚拟店
     * #when getDisitrbutorItemList
     * #then 调用 getItemsList，filter distributor_id=0、onsale，不调 ForStandardStore
     */
    public function testTcS5bStandardBoundZeroNoVirtualStoreFallsBackToGetItemsList(): void
    {
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindProductModel('standard');
        $this->bindChiefDistributor(['distributor_id' => 0]);
        $this->bindDistributorSelfOptional(0);

        $capturedFilter = null;
        $itemsMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityItemsService');
        $itemsMock->shouldReceive('getItemsList')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );
        $itemsMock->shouldReceive('getItemsListForStandardStore')->never();

        $request = $this->createChiefRequest();
        $controller = new CommunityActivity();
        $controller->getDisitrbutorItemList($request);

        $this->assertSame(0, $capturedFilter['distributor_id'], 'TC-S5b: fallback should filter items.distributor_id=0');
        $this->assertSame('onsale', $capturedFilter['approve_status']);
        $this->assertSame('approved', $capturedFilter['audit_status']);
        $this->assertTrue($capturedFilter['is_default']);
        $this->assertSame('normal', $capturedFilter['item_type']);
        $this->assertSame(self::COMPANY_ID, $capturedFilter['company_id']);
    }

    /**
     * TC-S6 / A-S5b：standard + 绑店=0 + 无虚拟店 → 仅 getItemsList 被调用。
     * #given product_model=standard，绑店 distributor_id=0，getDistributorSelf→0
     * #when getDisitrbutorItemList
     * #then 仅 getItemsList 被调用，不调 ForStandardStore
     */
    public function testTcS6StandardBoundZeroNoVirtualStoreCallsGetItemsListNotForStandardStore(): void
    {
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindProductModel('standard');
        $this->bindChiefDistributor(['distributor_id' => 0]);
        $this->bindDistributorSelfOptional(0);

        $getItemsListCalled = false;
        $forStandardStoreCalled = false;
        $itemsMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityItemsService');
        $itemsMock->shouldReceive('getItemsList')->once()->andReturnUsing(
            function () use (&$getItemsListCalled): array {
                $getItemsListCalled = true;

                return [
                    'total_count' => 1,
                    'list' => [['item_id' => 1001, 'approve_status' => 'onsale']],
                ];
            }
        );
        $itemsMock->shouldReceive('getItemsListForStandardStore')->andReturnUsing(
            function () use (&$forStandardStoreCalled): array {
                $forStandardStoreCalled = true;

                return ['total_count' => 0, 'list' => []];
            }
        );

        $request = $this->createChiefRequest();
        $controller = new CommunityActivity();
        $controller->getDisitrbutorItemList($request);

        $this->assertTrue($getItemsListCalled, 'TC-S6: getItemsList must be called when no virtual store');
        $this->assertFalse($forStandardStoreCalled, 'TC-S6: getItemsListForStandardStore must not be called');
    }

    /**
     * TC-S9 / A-S5：bound=0 + getDistributorSelf→306 → getDistributorSelf 调用一次，ForStandardStore 第二参=306。
     * #given product_model=standard，绑店 distributor_id=0，虚拟店 306
     * #when getDisitrbutorItemList
     * #then getDistributorSelf(companyId, false) 一次，ForStandardStore store=306
     */
    public function testTcS9StandardBoundZeroResolvesVirtualStoreViaGetDistributorSelf(): void
    {
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindProductModel('standard');
        $this->bindChiefDistributor(['distributor_id' => 0]);

        $distributorMock = \Mockery::mock('overload:DistributionBundle\Services\DistributorService');
        $distributorMock->shouldReceive('getDistributorSelf')
            ->once()
            ->with(self::COMPANY_ID, false)
            ->andReturn(self::STORE_ID);

        $capturedStoreId = null;
        $itemsMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityItemsService');
        $itemsMock->shouldReceive('getItemsListForStandardStore')->once()->andReturnUsing(
            function ($filter, $storeDistributorId) use (&$capturedStoreId): array {
                $capturedStoreId = $storeDistributorId;

                return ['total_count' => 0, 'list' => []];
            }
        );
        $itemsMock->shouldReceive('getItemsList')->andReturn(['total_count' => 0, 'list' => []]);

        $request = $this->createChiefRequest();
        $controller = new CommunityActivity();
        $controller->getDisitrbutorItemList($request);

        $this->assertSame(self::STORE_ID, $capturedStoreId, 'TC-S9: ForStandardStore second parameter should equal virtual store ID');
    }

    /**
     * TC-S8 / A-S8：standard + 绑店>0 → 必须调用 getItemsListForStandardStore，总部 filter distributor_id=0。
     * #given product_model=standard，绑店 distributor_id>0
     * #when getDisitrbutorItemList
     * #then 调用 getItemsListForStandardStore(store=绑店)，items 口径 distributor_id=0
     */
    public function testTcS8StandardBoundStoreCallsGetItemsListForStandardStore(): void
    {
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindProductModel('standard');
        $this->bindChiefDistributor(['distributor_id' => self::STORE_ID]);

        $capturedFilter = null;
        $capturedStoreId = null;
        $itemsMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityItemsService');
        $itemsMock->shouldReceive('getItemsListForStandardStore')->once()->andReturnUsing(
            function ($filter, $storeDistributorId) use (&$capturedFilter, &$capturedStoreId): array {
                $capturedFilter = $filter;
                $capturedStoreId = $storeDistributorId;

                return [
                    'total_count' => 1,
                    'list' => [['item_id' => 1001, 'item_name' => '中文名', 'itemName' => '中文名']],
                ];
            }
        );
        $itemsMock->shouldReceive('getItemsList')->andReturn(['total_count' => 0, 'list' => []]);

        $request = $this->createChiefRequest();
        $controller = new CommunityActivity();
        $result = $controller->getDisitrbutorItemList($request);

        $this->assertNotNull($capturedFilter, 'TC-S8: getItemsListForStandardStore must be called for standard + bound store > 0');
        $this->assertSame(0, $capturedFilter['distributor_id'], 'TC-S8: standard store branch should filter headquarters distributor_id=0');
        $this->assertSame(self::STORE_ID, $capturedStoreId, 'TC-S8: store id should be passed as ForStandardStore parameter');
        $this->assertSame('onsale', $capturedFilter['approve_status']);
        $this->assertArrayHasKey('item_name', $result['list'][0], 'TC-S8: result should come from Service post-processing path');
    }

    /**
     * TC-N1 / A-N1：platform + 店>0 → getItemsList，filter 含 distributor_id=店、onsale、approved。
     * #given product_model=platform，绑店>0
     * #when getDisitrbutorItemList
     * #then 调用 getItemsList，不调 ForStandardStore
     */
    public function testTcN1PlatformBoundStoreUsesGetItemsListWithStoreFilter(): void
    {
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindProductModel('platform');
        $this->bindChiefDistributor(['distributor_id' => self::STORE_ID]);

        $capturedFilter = null;
        $itemsMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityItemsService');
        $itemsMock->shouldReceive('getItemsList')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );
        $itemsMock->shouldReceive('getItemsListForStandardStore')->never();

        $request = $this->createChiefRequest();
        $controller = new CommunityActivity();
        $controller->getDisitrbutorItemList($request);

        $this->assertSame(self::STORE_ID, $capturedFilter['distributor_id']);
        $this->assertSame('onsale', $capturedFilter['approve_status']);
        $this->assertSame('approved', $capturedFilter['audit_status']);
    }

    /**
     * TC-N2 / A-N1：b2c + 店>0 → 同 TC-N1。
     * #given product_model=b2c，绑店>0
     * #when getDisitrbutorItemList
     * #then 调用 getItemsList，filter distributor_id=店
     */
    public function testTcN2B2cBoundStoreUsesGetItemsListWithStoreFilter(): void
    {
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindProductModel('b2c');
        $this->bindChiefDistributor(['distributor_id' => self::STORE_ID]);

        $capturedFilter = null;
        $itemsMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityItemsService');
        $itemsMock->shouldReceive('getItemsList')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );
        $itemsMock->shouldReceive('getItemsListForStandardStore')->never();

        $request = $this->createChiefRequest();
        $controller = new CommunityActivity();
        $controller->getDisitrbutorItemList($request);

        $this->assertSame(self::STORE_ID, $capturedFilter['distributor_id']);
        $this->assertSame('onsale', $capturedFilter['approve_status']);
        $this->assertSame('approved', $capturedFilter['audit_status']);
    }

    /**
     * TC-N3 / A-N2：非 standard + 仅总部货语义 → getItemsList 且 filter.distributor_id=绑店（>0）。
     * #given product_model=platform，绑店>0（现网按店 filter 会空列表）
     * #when getDisitrbutorItemList
     * #then 仍走 getItemsList，filter.distributor_id=绑店
     */
    public function testTcN3NonStandardHeadquartersOnlyUsesGetItemsListWithBoundStoreFilter(): void
    {
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindProductModel('platform');
        $this->bindChiefDistributor(['distributor_id' => self::STORE_ID]);

        $capturedFilter = null;
        $itemsMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityItemsService');
        $itemsMock->shouldReceive('getItemsList')->once()->andReturnUsing(
            function ($filter) use (&$capturedFilter): array {
                $capturedFilter = $filter;

                return ['total_count' => 0, 'list' => []];
            }
        );
        $itemsMock->shouldReceive('getItemsListForStandardStore')->never();

        $request = $this->createChiefRequest();
        $controller = new CommunityActivity();
        $result = $controller->getDisitrbutorItemList($request);

        $this->assertSame(self::STORE_ID, $capturedFilter['distributor_id'], 'TC-N3: non-standard should keep bound store in items filter');
        $this->assertSame(0, $result['total_count'], 'TC-N3: empty list aligns with legacy headquarters-only goods path');
    }

    /**
     * TC-X1 / A-X1：无绑店 → ResourceException「当前团长没有配置店铺」。
     * #given getInfo 返回空
     * #when getDisitrbutorItemList
     * #then 抛 ResourceException，不进 ItemsService
     */
    public function testTcX1NoBoundStoreThrowsResourceException(): void
    {
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindProductModel('standard');
        $this->bindChiefDistributor(null);

        $itemsMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityItemsService');
        $itemsMock->shouldReceive('getItemsList')->never();
        $itemsMock->shouldReceive('getItemsListForStandardStore')->never();

        $request = $this->createChiefRequest();
        $controller = new CommunityActivity();

        try {
            $controller->getDisitrbutorItemList($request);
            $this->fail('Expected ResourceException when chief has no bound store');
        } catch (ResourceException $e) {
            $this->assertSame('当前团长没有配置店铺', $e->getMessage());
        }
    }

    /**
     * TC-X2 / A-X2：standard + query distributor_id 覆盖 >0 → 按覆盖店调用 ForStandardStore。
     * #given product_model=standard，绑店>0，query distributor_id 覆盖为其他店
     * #when getDisitrbutorItemList
     * #then getItemsListForStandardStore(store=覆盖值)，总部 filter distributor_id=0
     */
    public function testTcX2StandardQueryDistributorOverrideUsesForStandardStoreWithOverrideStore(): void
    {
        $overrideStoreId = 999;
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindProductModel('standard');
        $this->bindChiefDistributor(['distributor_id' => self::STORE_ID]);

        $capturedFilter = null;
        $capturedStoreId = null;
        $itemsMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityItemsService');
        $itemsMock->shouldReceive('getItemsListForStandardStore')->once()->andReturnUsing(
            function ($filter, $storeDistributorId) use (&$capturedFilter, &$capturedStoreId): array {
                $capturedFilter = $filter;
                $capturedStoreId = $storeDistributorId;

                return ['total_count' => 0, 'list' => []];
            }
        );
        $itemsMock->shouldReceive('getItemsList')->andReturn(['total_count' => 0, 'list' => []]);

        $request = $this->createChiefRequest(['distributor_id' => $overrideStoreId]);
        $controller = new CommunityActivity();
        $controller->getDisitrbutorItemList($request);

        $this->assertNotNull($capturedFilter, 'TC-X2: getItemsListForStandardStore must be called when query distributor_id override > 0');
        $this->assertSame(0, $capturedFilter['distributor_id'], 'TC-X2: standard override should still use headquarters items filter');
        $this->assertSame($overrideStoreId, $capturedStoreId, 'TC-X2: store parameter should equal query distributor_id override');
    }

    /**
     * TC-X3 / A-X3：bound=0 + query distributor_id=999 → ForStandardStore(999)，不调 getDistributorSelf。
     * #given product_model=standard，绑店 distributor_id=0，query 覆盖 distributor_id=999
     * #when getDisitrbutorItemList
     * #then getItemsListForStandardStore(store=999)，getDistributorSelf never
     */
    public function testTcX3StandardBoundZeroQueryOverrideSkipsGetDistributorSelf(): void
    {
        $overrideStoreId = 999;
        $this->bindResponseFactory();
        $this->bindLog();
        $this->bindProductModel('standard');
        $this->bindChiefDistributor(['distributor_id' => 0]);
        $this->bindDistributorSelfNever();

        $capturedFilter = null;
        $capturedStoreId = null;
        $itemsMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityItemsService');
        $itemsMock->shouldReceive('getItemsListForStandardStore')->once()->andReturnUsing(
            function ($filter, $storeDistributorId) use (&$capturedFilter, &$capturedStoreId): array {
                $capturedFilter = $filter;
                $capturedStoreId = $storeDistributorId;

                return ['total_count' => 0, 'list' => []];
            }
        );
        $itemsMock->shouldReceive('getItemsList')->never();

        $request = $this->createChiefRequest(['distributor_id' => $overrideStoreId]);
        $controller = new CommunityActivity();
        $controller->getDisitrbutorItemList($request);

        $this->assertNotNull($capturedFilter, 'TC-X3: getItemsListForStandardStore must be called when query distributor_id override > 0');
        $this->assertSame(0, $capturedFilter['distributor_id'], 'TC-X3: standard override should still use headquarters items filter');
        $this->assertSame($overrideStoreId, $capturedStoreId, 'TC-X3: store parameter should equal query distributor_id override');
    }

    private function createChiefRequest(array $query = []): Request
    {
        $request = Request::create('/wxapp/community/chief/items', 'GET', $query);
        $request->merge([
            'auth' => [
                'chief_id' => self::CHIEF_ID,
                'company_id' => self::COMPANY_ID,
            ],
        ]);

        return $request;
    }

    private function bindProductModel(string $productModel): void
    {
        $egoMock = \Mockery::mock('overload:CompanysBundle\Ego\CompanysActivationEgo');
        $egoMock->shouldReceive('check')
            ->with(self::COMPANY_ID)
            ->andReturn(['product_model' => $productModel]);
    }

    private function bindChiefDistributor(?array $distributor): void
    {
        $serviceMock = \Mockery::mock('overload:CommunityBundle\Services\CommunityChiefDistributorService');
        $serviceMock->shouldReceive('getInfo')
            ->once()
            ->with(['chief_id' => self::CHIEF_ID])
            ->andReturn($distributor ?? []);
    }

    private function bindDistributorSelf(int $virtualStoreId): void
    {
        $mock = \Mockery::mock('overload:DistributionBundle\Services\DistributorService');
        $mock->shouldReceive('getDistributorSelf')
            ->once()
            ->with(self::COMPANY_ID, false)
            ->andReturn($virtualStoreId);
    }

    /**
     * T6 RED：Controller 尚未调用 getDistributorSelf 时，回退路径断言仍可绿。
     */
    private function bindDistributorSelfOptional(int $virtualStoreId): void
    {
        $mock = \Mockery::mock('overload:DistributionBundle\Services\DistributorService');
        $mock->shouldReceive('getDistributorSelf')
            ->zeroOrMoreTimes()
            ->with(self::COMPANY_ID, false)
            ->andReturn($virtualStoreId);
    }

    private function bindDistributorSelfNever(): void
    {
        $mock = \Mockery::mock('overload:DistributionBundle\Services\DistributorService');
        $mock->shouldReceive('getDistributorSelf')->never();
    }

    private function bindResponseFactory(): void
    {
        $factory = \Mockery::mock(Factory::class);
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
