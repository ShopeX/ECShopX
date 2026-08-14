<?php

declare(strict_types=1);

use Illuminate\Http\Request;

/**
 * H5 widget/items 门店上架过滤 — TC-H1…TC-X1
 * 计划：.tasks/plans/widget-items-store-onsale-filter.md
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class WidgetItemsStoreOnsaleFilterTest extends TestCase
{
    private const COMPANY_ID = 1;

    private const STORE_ID = 306;

    private const GOODS_ID = 12345;

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
        $app->configure('langue');

        return $app;
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * TC-H1 / A-H1：flag+standard+店>0+items → filter 含 is_can_sale=true，且返回含指定 goods 的 list。
     * #given apply_store_onsale_filter=true，product_model=standard，distributor_id>0，data_type=items
     * #when getWidgetItems
     * #then getItemListData 的 filter 含 is_can_sale=true，list 含目标 goods
     */
    public function testTcH1StandardStoreInjectsIsCanSaleAndReturnsGoods(): void
    {
        $this->bindRegistryMock();
        $this->bindProductModel('standard');
        $capturedFilter = null;
        $this->bindItemsServiceGetItemListData(function ($filter) use (&$capturedFilter): array {
            $capturedFilter = $filter;

            return [
                'total_count' => 1,
                'list' => [['goods_id' => self::GOODS_ID, 'item_name' => 'Test Goods']],
            ];
        });

        $service = new \ThemeBundle\Services\PagesTemplateServices();
        $result = $service->getWidgetItems($this->buildServiceParams([
            'data_type' => 'items',
            'data_value' => (string) self::GOODS_ID,
        ]));

        $this->assertNotNull($capturedFilter, 'TC-H1: getItemListData must be called');
        $this->assertArrayHasKey('is_can_sale', $capturedFilter, 'TC-H1: filter must contain is_can_sale');
        $this->assertTrue($capturedFilter['is_can_sale'], 'TC-H1: is_can_sale must be true');
        $this->assertSame(self::STORE_ID, $capturedFilter['distributor_id']);
        $this->assertCount(1, $result['data']);
        $this->assertSame(self::GOODS_ID, $result['data'][0]['goods_id']);
    }

    /**
     * TC-H2 / A-H2：同 TC-H1 前置，断言 filter 含 is_can_sale=true；mock 空 list 时 Service 透传。
     * #given apply_store_onsale_filter=true，product_model=standard，distributor_id>0
     * #when getWidgetItems，getItemListData 返回空 list
     * #then filter 含 is_can_sale=true，result data 为空
     */
    public function testTcH2StandardStoreInjectsIsCanSaleWithEmptyList(): void
    {
        $this->bindRegistryMock();
        $this->bindProductModel('standard');
        $capturedFilter = null;
        $this->bindItemsServiceGetItemListData(function ($filter) use (&$capturedFilter): array {
            $capturedFilter = $filter;

            return ['total_count' => 0, 'list' => []];
        });

        $service = new \ThemeBundle\Services\PagesTemplateServices();
        $result = $service->getWidgetItems($this->buildServiceParams([
            'data_type' => 'items',
            'data_value' => (string) self::GOODS_ID,
        ]));

        $this->assertArrayHasKey('is_can_sale', $capturedFilter, 'TC-H2: filter must contain is_can_sale');
        $this->assertTrue($capturedFilter['is_can_sale'], 'TC-H2: is_can_sale must be true');
        $this->assertSame([], $result['data'], 'TC-H2: empty list should pass through');
    }

    /**
     * TC-H3 / A-H3：flag+无有效门店 → filter 不含 is_can_sale。
     * #given apply_store_onsale_filter=true，无 distributor_id
     * #when getWidgetItems
     * #then filter 不含 is_can_sale
     */
    public function testTcH3NoStoreDoesNotInjectIsCanSale(): void
    {
        $this->bindRegistryMock();
        $this->bindProductModel('standard');
        $capturedFilter = null;
        $this->bindItemsServiceGetItemListData(function ($filter) use (&$capturedFilter): array {
            $capturedFilter = $filter;

            return ['total_count' => 0, 'list' => []];
        });

        $service = new \ThemeBundle\Services\PagesTemplateServices();
        $params = $this->buildServiceParams([
            'data_type' => 'items',
            'data_value' => (string) self::GOODS_ID,
        ]);
        unset($params['distributor_id']);
        $service->getWidgetItems($params);

        $this->assertNotNull($capturedFilter);
        $this->assertArrayNotHasKey('is_can_sale', $capturedFilter, 'TC-H3: no store must not inject is_can_sale');
    }

    /**
     * TC-PF1 / A-PF1：flag+platform+店>0 → filter 不含 is_can_sale。
     * #given apply_store_onsale_filter=true，product_model=platform，distributor_id>0
     * #when getWidgetItems
     * #then filter 不含 is_can_sale
     */
    public function testTcPf1PlatformStoreDoesNotInjectIsCanSale(): void
    {
        $this->bindRegistryMock();
        $this->bindProductModel('platform');
        $capturedFilter = null;
        $this->bindItemsServiceGetItemListData(function ($filter) use (&$capturedFilter): array {
            $capturedFilter = $filter;

            return ['total_count' => 0, 'list' => []];
        });

        $service = new \ThemeBundle\Services\PagesTemplateServices();
        $service->getWidgetItems($this->buildServiceParams());

        $this->assertNotNull($capturedFilter);
        $this->assertArrayNotHasKey('is_can_sale', $capturedFilter, 'TC-PF1: platform must not inject is_can_sale');
    }

    /**
     * TC-A1 / A-A1：无 flag（管理端语义）+ standard + 店>0 → filter 不含 is_can_sale。
     * #given 无 apply_store_onsale_filter，product_model=standard，distributor_id>0
     * #when getWidgetItems
     * #then filter 不含 is_can_sale
     */
    public function testTcA1NoFlagAdminSemanticDoesNotInjectIsCanSale(): void
    {
        $this->bindRegistryMock();
        $capturedFilter = null;
        $this->bindItemsServiceGetItemListData(function ($filter) use (&$capturedFilter): array {
            $capturedFilter = $filter;

            return ['total_count' => 0, 'list' => []];
        });

        $service = new \ThemeBundle\Services\PagesTemplateServices();
        $params = $this->buildServiceParams();
        unset($params['apply_store_onsale_filter']);
        $service->getWidgetItems($params);

        $this->assertNotNull($capturedFilter);
        $this->assertArrayNotHasKey('is_can_sale', $capturedFilter, 'TC-A1: admin path without flag must not inject is_can_sale');
    }

    /**
     * TC-B1 / A-B1：distributor_id=0 → filter 不含 is_can_sale。
     * #given apply_store_onsale_filter=true，product_model=standard，distributor_id=0
     * #when getWidgetItems
     * #then filter 不含 is_can_sale
     */
    public function testTcB1ZeroDistributorDoesNotInjectIsCanSale(): void
    {
        $this->bindRegistryMock();
        $this->bindProductModel('standard');
        $capturedFilter = null;
        $this->bindItemsServiceGetItemListData(function ($filter) use (&$capturedFilter): array {
            $capturedFilter = $filter;

            return ['total_count' => 0, 'list' => []];
        });

        $service = new \ThemeBundle\Services\PagesTemplateServices();
        $params = $this->buildServiceParams(['distributor_id' => 0]);
        $service->getWidgetItems($params);

        $this->assertNotNull($capturedFilter);
        $this->assertArrayNotHasKey('is_can_sale', $capturedFilter, 'TC-B1: distributor_id=0 must not inject is_can_sale');
    }

    /**
     * TC-D1 / A-D1：flag+standard+data_type=distributor → filter 含 is_can_sale。
     * #given apply_store_onsale_filter=true，product_model=standard，data_type=distributor
     * #when getWidgetItems
     * #then filter 含 is_can_sale=true
     */
    public function testTcD1DistributorDataTypeInjectsIsCanSale(): void
    {
        $this->bindRegistryMock();
        $this->bindProductModel('standard');
        $capturedFilter = null;
        $this->bindItemsServiceGetItemListData(function ($filter) use (&$capturedFilter): array {
            $capturedFilter = $filter;

            return ['total_count' => 0, 'list' => []];
        });

        $service = new \ThemeBundle\Services\PagesTemplateServices();
        $service->getWidgetItems($this->buildServiceParams([
            'data_type' => 'distributor',
            'data_value' => (string) self::STORE_ID,
        ]));

        $this->assertArrayHasKey('is_can_sale', $capturedFilter, 'TC-D1: distributor data_type must inject is_can_sale');
        $this->assertTrue($capturedFilter['is_can_sale']);
        $this->assertSame(self::STORE_ID, (int) $capturedFilter['distributor_id']);
    }

    /**
     * TC-M1 / A-M1：standard + 店>0 + 代表性 data_type → 均注入 is_can_sale。
     *
     * @dataProvider representativeDataTypesProvider
     * @param array<string, mixed> $paramsOverride
     */
    public function testTcM1RepresentativeDataTypesInjectIsCanSale(string $dataType, array $paramsOverride): void
    {
        $this->bindRegistryMock();
        $this->bindProductModel('standard');
        $capturedFilter = null;
        $this->bindItemsServiceGetItemListData(function ($filter) use (&$capturedFilter): array {
            $capturedFilter = $filter;

            return ['total_count' => 0, 'list' => []];
        });

        $service = new \ThemeBundle\Services\PagesTemplateServices();
        $service->getWidgetItems($this->buildServiceParams(array_merge([
            'data_type' => $dataType,
        ], $paramsOverride)));

        $this->assertArrayHasKey('is_can_sale', $capturedFilter, "TC-M1: {$dataType} must inject is_can_sale");
        $this->assertTrue($capturedFilter['is_can_sale'], "TC-M1: {$dataType} is_can_sale must be true");
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function representativeDataTypesProvider(): array
    {
        return [
            'items' => ['items', ['data_value' => (string) self::GOODS_ID]],
            'category' => ['category', ['data_value' => '100']],
            'sales' => ['sales', []],
        ];
    }

    /**
     * TC-E1 / A-E1：flag+standard+仅 e_activity_id → 活动注入门店后 filter 含 is_can_sale。
     * #given e_activity_id>0，活动返回 distributor_id>0，无 params.distributor_id
     * #when getWidgetItems
     * #then filter 含 is_can_sale=true
     */
    public function testTcE1EActivityIdInjectsStoreAndIsCanSale(): void
    {
        $activityId = 99;
        $this->bindActivitiesServiceWithStore($activityId, self::STORE_ID);
        $this->bindRegistryMock();
        $this->bindProductModel('standard');
        $capturedFilter = null;
        $this->bindItemsServiceGetItemListData(function ($filter) use (&$capturedFilter): array {
            $capturedFilter = $filter;

            return ['total_count' => 0, 'list' => []];
        });

        $service = new \ThemeBundle\Services\PagesTemplateServices();
        $params = $this->buildServiceParams([
            'data_type' => 'items',
            'data_value' => (string) self::GOODS_ID,
            'e_activity_id' => $activityId,
        ]);
        unset($params['distributor_id']);
        $service->getWidgetItems($params);

        $this->assertArrayHasKey('is_can_sale', $capturedFilter, 'TC-E1: e_activity store must inject is_can_sale');
        $this->assertTrue($capturedFilter['is_can_sale']);
        $this->assertSame(self::STORE_ID, $capturedFilter['distributor_id']);
    }

    /**
     * TC-P1 / A-P1：pointsmall_items 不走 GoodsBundle ItemsService::getItemListData。
     * #given data_type=pointsmall_items，apply_store_onsale_filter=true
     * #when getWidgetItems
     * #then GoodsBundle getItemListData never，early return 结构正常
     */
    public function testTcP1PointsmallItemsSkipsGoodsItemListData(): void
    {
        $this->bindRegistryMock();
        $goodsItemsMock = \Mockery::mock('overload:GoodsBundle\Services\ItemsService');
        $goodsItemsMock->shouldReceive('getItemListData')->never();

        $pointsmallItemsMock = \Mockery::mock('overload:PointsmallBundle\Services\ItemsService');
        $pointsmallItemsMock->shouldReceive('getItemListData')->once()->andReturn([
            'list' => [['item_id' => 1, 'item_name' => 'Point Item']],
        ]);

        $service = new \ThemeBundle\Services\PagesTemplateServices();
        $result = $service->getWidgetItems($this->buildServiceParams([
            'data_type' => 'pointsmall_items',
            'data_value' => '1',
        ]));

        $this->assertSame([['item_id' => 1, 'item_name' => 'Point Item']], $result['data']);
        $this->assertSame(['pointsmall_item_id' => [1]], $result['filter']);
    }

    /**
     * TC-X1 / A-X1：FrontApi Action 硬编码 apply_store_onsale_filter=true，query 不能关闭。
     * #given Request query 含 apply_store_onsale_filter=false
     * #when FrontApi getWidgetItems
     * #then 传入 Service 的 params apply_store_onsale_filter===true
     */
    public function testTcX1ActionHardcodesApplyStoreOnsaleFilter(): void
    {
        $this->bindResponseFactory();

        $capturedParams = null;
        $serviceMock = \Mockery::mock('overload:ThemeBundle\Services\PagesTemplateServices');
        $serviceMock->shouldReceive('getWidgetItems')->once()->andReturnUsing(
            function (array $params) use (&$capturedParams): array {
                $capturedParams = $params;

                return ['data' => [], 'filter' => [], 'goodsSort' => null];
            }
        );

        $itemsMock = \Mockery::mock('overload:GoodsBundle\Services\ItemsService');
        $itemsMock->shouldReceive('applyMultiSpecTotalStoreForItemList')->zeroOrMoreTimes()->andReturnUsing(
            static fn (array $data): array => $data
        );
        $itemsMock->shouldReceive('getItemsListMemberPrice')->zeroOrMoreTimes()->andReturnUsing(
            static fn (array $data): array => $data
        );
        $itemsMock->shouldReceive('getItemsListActityTag')->zeroOrMoreTimes()->andReturnUsing(
            static fn (array $data): array => $data
        );

        $request = Request::create('/api/h5app/wxapp/pagestemplate/widget/items', 'GET', [
            'apply_store_onsale_filter' => 'false',
            'data_type' => 'items',
            'data_value' => (string) self::GOODS_ID,
            'distributor_id' => self::STORE_ID,
        ]);
        $request->merge([
            'auth' => [
                'company_id' => self::COMPANY_ID,
                'user_id' => 1,
            ],
        ]);

        $controller = new \ThemeBundle\Http\FrontApi\V1\Action\PagesTemplate();
        $controller->getWidgetItems($request);

        $this->assertNotNull($capturedParams, 'TC-X1: getWidgetItems must be called on Service');
        $this->assertTrue($capturedParams['apply_store_onsale_filter'], 'TC-X1: Action must hardcode apply_store_onsale_filter=true');
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function buildServiceParams(array $overrides = []): array
    {
        return array_merge([
            'company_id' => self::COMPANY_ID,
            'apply_store_onsale_filter' => true,
            'distributor_id' => self::STORE_ID,
            'data_type' => 'items',
            'data_value' => (string) self::GOODS_ID,
            'num' => 10,
            'page' => 1,
        ], $overrides);
    }

    private function bindRegistryMock(): void
    {
        $mockRepo = $this->getMockBuilder(\stdClass::class)->getMock();

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')->willReturn($mockRepo);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getManager'])
            ->getMock();
        $mockRegistry->method('getManager')
            ->with('default')
            ->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }

    private function bindResponseFactory(): void
    {
        $factory = \Mockery::mock(\Dingo\Api\Http\Response\Factory::class);
        $factory->shouldReceive('array')->andReturnUsing(static fn (array $payload): array => $payload);
        $this->app->instance(\Dingo\Api\Http\Response\Factory::class, $factory);
    }

    private function bindProductModel(string $productModel): void
    {
        $egoMock = \Mockery::mock('overload:CompanysBundle\Ego\CompanysActivationEgo');
        $egoMock->shouldReceive('check')
            ->with(self::COMPANY_ID)
            ->andReturn(['product_model' => $productModel]);
    }

    /**
     * @param callable(array): array $callback
     */
    private function bindItemsServiceGetItemListData(callable $callback): void
    {
        $itemsMock = \Mockery::mock('overload:GoodsBundle\Services\ItemsService');
        $itemsMock->shouldReceive('getItemListData')->once()->andReturnUsing($callback);
    }

    private function bindActivitiesServiceWithStore(int $activityId, int $storeId): void
    {
        $repoMock = \Mockery::mock();
        $repoMock->shouldReceive('getInfo')
            ->once()
            ->with(['id' => $activityId, 'company_id' => self::COMPANY_ID])
            ->andReturn(['distributor_id' => $storeId]);

        $this->registerActivitiesServiceStub($repoMock);
    }

    private function registerActivitiesServiceStub(?\Mockery\MockInterface $repoMock = null): void
    {
        if (class_exists(\EmployeePurchaseBundle\Services\ActivitiesService::class, false)) {
            return;
        }

        WidgetItemsStoreOnsaleFilterTestActivitiesRepoHolder::$repo = $repoMock;

        eval(<<<'PHP'
namespace EmployeePurchaseBundle\Services {
    class ActivitiesService
    {
        public $entityRepository;

        public function __construct()
        {
            $this->entityRepository = \WidgetItemsStoreOnsaleFilterTestActivitiesRepoHolder::$repo;
        }
    }
}
PHP
        );
    }
}

/**
 * TC-E1：ActivitiesService 构造注入 entityRepository 桩。
 */
final class WidgetItemsStoreOnsaleFilterTestActivitiesRepoHolder
{
    /** @var \Mockery\MockInterface|null */
    public static $repo;
}
