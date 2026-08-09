<?php

use CompanysBundle\Entities\Companys;
use EspierBundle\Services\Bus\TestBus;
use GoodsBundle\Entities\ItemsCategory;
use GoodsBundle\Http\FrontApi\V1\Action\Category;
use GoodsBundle\Repositories\ItemsCategoryRepository;
use GoodsBundle\Services\ItemsCategoryService;
use Illuminate\Http\Request;

class PlatformFrontCategoryListTest extends TestCase
{
    /** @var ItemsCategoryService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ItemsCategoryService();
        TestBus::reset();
        config(['services.goods' => ['rpc_type' => 'test', 'base_url' => '', 'sign' => '123']]);
    }

    /**
     * TC-U1: Resolver matrix — resolveFrontCategoryListIsMainCategory + shouldFallbackFrontCategoryList
     *
     * @dataProvider resolverMatrixProvider
     */
    public function testResolveFrontCategoryListIsMainCategoryAndShouldFallbackMatrix(
        string $productModel,
        int $distributorId,
        $requestIsMain,
        bool $expectedResolve,
        bool $expectedShouldFallback
    ): void {
        // #given product_model, distributor_id, request is_main
        // #when resolve + shouldFallback
        $resolved = $this->service->resolveFrontCategoryListIsMainCategory(
            $productModel,
            $distributorId,
            $requestIsMain
        );
        $shouldFallback = $this->service->shouldFallbackFrontCategoryList(
            $productModel,
            $distributorId,
            $resolved
        );

        // #then
        $this->assertSame($expectedResolve, $resolved);
        $this->assertSame($expectedShouldFallback, $shouldFallback);
    }

    public function resolverMatrixProvider(): array
    {
        return [
            'platform distributor 0 request false' => ['platform', 0, false, true, false],
            'platform distributor 0 request true' => ['platform', 0, true, true, false],
            'platform distributor 5 request false' => ['platform', 5, false, false, true],
            'standard distributor 0 request false' => ['standard', 0, false, false, true],
            'b2c distributor 0 request false' => ['b2c', 0, false, false, true],
            'b2c distributor 3 request false' => ['b2c', 3, false, false, true],
        ];
    }

    /**
     * TC-P2: platform + distributor_id=0 ignores request is_main_category=false
     */
    public function testPlatformDistributorZeroResolveIgnoresRequestIsMain(): void
    {
        // #given platform, distributor_id=0
        // #when resolve(..., false)
        $resolved = $this->service->resolveFrontCategoryListIsMainCategory('platform', 0, false);
        $shouldFallback = $this->service->shouldFallbackFrontCategoryList('platform', 0, $resolved);

        // #then
        $this->assertTrue($resolved);
        $this->assertFalse($shouldFallback);
    }

    /**
     * TC-P1: platform, menu_type=3, distributor_id=0
     */
    public function testPlatformDistributorZeroGetCategoryListQueriesMainCategoryOnce(): void
    {
        // #given platform (menu_type=3), distributor_id=0
        $this->bindDualRegistryMock(3);

        // #when getCategoryList
        $this->invokeGetCategoryList(['distributor_id' => 0]);

        // #then postCount=1; lastPost filter: is_main_category=true, is_show_front=1; no distributor_id
        $this->assertSame(1, TestBus::getPostCount(), 'TC-P1: platform distributor 0 must not fallback (postCount=1)');
        $filter = TestBus::getLastPostData()['filter'];
        $this->assertTrue($filter['is_main_category']);
        $this->assertSame(1, $filter['is_show_front']);
        $this->assertArrayNotHasKey('distributor_id', $filter);
    }

    /**
     * TC-P3: platform, distributor_id=0, Bus returns []
     */
    public function testPlatformDistributorZeroEmptyResultNoFallback(): void
    {
        // #given platform, distributor_id=0, TestBus returns []
        $this->bindDualRegistryMock(3);

        // #when getCategoryList
        $response = $this->invokeGetCategoryList(['distributor_id' => 0]);

        // #then response []; postCount=1; history[0] is_main_category is true (not false)
        $this->assertSame([], $response->getOriginalContent());
        $this->assertSame(1, TestBus::getPostCount(), 'TC-P3: empty result must not trigger fallback for platform distributor 0');
        $history = TestBus::getPostHistory();
        $this->assertNotSame(false, $history[0]['filter']['is_main_category']);
        $this->assertTrue($history[0]['filter']['is_main_category']);
    }

    /**
     * TC-P4: platform, distributor_id=5
     */
    public function testPlatformDistributorPositiveGetCategoryListQueriesSalesCategory(): void
    {
        // #given platform, distributor_id=5
        $this->bindDualRegistryMock(3);
        TestBus::setPostReturnQueue([['category_id' => 1, 'category_name' => 'sales']]);

        // #when getCategoryList
        $this->invokeGetCategoryList(['distributor_id' => 5]);

        // #then postCount=1; filter: is_main_category=false, distributor_id=5, is_show_front=1
        $this->assertSame(1, TestBus::getPostCount());
        $filter = TestBus::getLastPostData()['filter'];
        $this->assertFalse($filter['is_main_category']);
        $this->assertSame(5, $filter['distributor_id']);
        $this->assertSame(1, $filter['is_show_front']);
    }

    /**
     * TC-S1: standard, menu_type=4, distributor_id=0, first Bus returns []
     */
    public function testStandardDistributorZeroFallbackToMainCategory(): void
    {
        // #given standard (menu_type=4), distributor_id=0, TestBus returns [] on first call
        $this->bindDualRegistryMock(4);

        // #when getCategoryList
        $this->invokeGetCategoryList(['distributor_id' => 0]);

        // #then postCount=2; history[0]: is_main_category=false; history[1]: is_main_category=true, no distributor_id
        $this->assertSame(2, TestBus::getPostCount());
        $history = TestBus::getPostHistory();
        $this->assertFalse($history[0]['filter']['is_main_category']);
        $this->assertTrue($history[1]['filter']['is_main_category']);
        $this->assertArrayNotHasKey('distributor_id', $history[1]['filter']);
    }

    /**
     * TC-B1: b2c, menu_type=2, distributor_id=0
     */
    public function testB2cDistributorZeroGetCategoryListQueriesSalesCategory(): void
    {
        // #given b2c (menu_type=2), distributor_id=0
        $this->bindDualRegistryMock(2);
        TestBus::setPostReturnQueue([['category_id' => 1, 'category_name' => 'sales']]);

        // #when getCategoryList
        $this->invokeGetCategoryList(['distributor_id' => 0]);

        // #then postCount=1; filter: is_main_category=false (not true)
        $this->assertSame(1, TestBus::getPostCount());
        $filter = TestBus::getLastPostData()['filter'];
        $this->assertFalse($filter['is_main_category']);
        $this->assertNotTrue($filter['is_main_category']);
    }

    /**
     * TC-B2: b2c, distributor_id=3
     */
    public function testB2cDistributorPositiveGetCategoryListQueriesSalesCategory(): void
    {
        // #given b2c, distributor_id=3
        $this->bindDualRegistryMock(2);
        TestBus::setPostReturnQueue([['category_id' => 1, 'category_name' => 'sales']]);

        // #when getCategoryList
        $this->invokeGetCategoryList(['distributor_id' => 3]);

        // #then postCount=1; filter: is_main_category=false, distributor_id=3
        $this->assertSame(1, TestBus::getPostCount());
        $filter = TestBus::getLastPostData()['filter'];
        $this->assertFalse($filter['is_main_category']);
        $this->assertSame(3, $filter['distributor_id']);
    }

    /**
     * TC-B-FLAG: b2c, distributor_id=0 — every node is_main_category false
     */
    public function testB2cDistributorZeroGetCategoryListInjectsIsMainCategoryFalseOnAllNodes(): void
    {
        // #given b2c, distributor_id=0, non-empty tree
        $this->bindDualRegistryMock(2);
        TestBus::setPostReturnQueue([[$this->sampleCategoryTree()]]);

        // #when getCategoryList
        $response = $this->invokeGetCategoryList(['distributor_id' => 0]);

        // #then every node has is_main_category === false
        $this->assertAllNodesHaveIsMainCategoryFlag($response->getOriginalContent(), false);
    }

    /**
     * TC-S1-FLAG: standard, distributor_id=0 fallback — every node is_main_category true
     */
    public function testStandardDistributorZeroFallbackInjectsIsMainCategoryTrueOnAllNodes(): void
    {
        // #given standard, distributor_id=0, first call [], second call tree
        $this->bindDualRegistryMock(4);
        TestBus::setPostReturnQueue([[], [$this->sampleCategoryTree()]]);

        // #when getCategoryList
        $response = $this->invokeGetCategoryList(['distributor_id' => 0]);

        // #then postCount=2; every node is_main_category === true (fallback final source)
        $this->assertSame(2, TestBus::getPostCount());
        $this->assertAllNodesHaveIsMainCategoryFlag($response->getOriginalContent(), true);
    }

    /**
     * TC-P4-FLAG: platform, distributor_id=5 — every node is_main_category false
     */
    public function testPlatformDistributorPositiveGetCategoryListInjectsIsMainCategoryFalseOnAllNodes(): void
    {
        // #given platform, distributor_id=5, non-empty tree
        $this->bindDualRegistryMock(3);
        TestBus::setPostReturnQueue([[$this->sampleCategoryTree()]]);

        // #when getCategoryList
        $response = $this->invokeGetCategoryList(['distributor_id' => 5]);

        // #then every node has is_main_category === false
        $this->assertAllNodesHaveIsMainCategoryFlag($response->getOriginalContent(), false);
    }

    /**
     * TC-P1-FLAG: platform, distributor_id=0 — every node is_main_category true
     */
    public function testPlatformDistributorZeroGetCategoryListInjectsIsMainCategoryTrueOnAllNodes(): void
    {
        // #given platform, distributor_id=0, non-empty tree with children
        $this->bindDualRegistryMock(3);
        TestBus::setPostReturnQueue([[$this->sampleCategoryTree()]]);

        // #when getCategoryList
        $response = $this->invokeGetCategoryList(['distributor_id' => 0]);

        // #then every node (including children) has is_main_category === true
        $this->assertAllNodesHaveIsMainCategoryFlag($response->getOriginalContent(), true);
    }

    /**
     * TC-O1: platform, distributor_id=0, only_top=true
     */
    public function testPlatformDistributorZeroOnlyTopQueriesMainCategoryTopLevel(): void
    {
        // #given platform, distributor_id=0, only_top=true
        $this->bindDualRegistryMock(3);

        // #when getCategoryList
        $this->invokeGetCategoryList(['distributor_id' => 0, 'only_top' => true]);

        // #then lastPost filter: is_main_category=true, parent_id=0, category_level=1, is_show_front=1
        $filter = TestBus::getLastPostData()['filter'];
        $this->assertTrue($filter['is_main_category']);
        $this->assertSame(0, $filter['parent_id']);
        $this->assertSame(1, $filter['category_level']);
        $this->assertSame(1, $filter['is_show_front']);
    }

    /**
     * TC-F-JSON: response node is_main_category is JSON bool (not string 0/1)
     */
    public function testGetCategoryListIsMainCategoryFlagIsJsonBoolean(): void
    {
        // #given platform, distributor_id=0, non-empty tree
        $this->bindDualRegistryMock(3);
        TestBus::setPostReturnQueue([[$this->sampleCategoryTree()]]);

        // #when getCategoryList and json encode/decode
        $response = $this->invokeGetCategoryList(['distributor_id' => 0]);
        $decoded = json_decode(json_encode($response->getOriginalContent()), true);

        // #then is_main_category decodes as bool true on root and child
        $this->assertTrue($decoded[0]['is_main_category']);
        $this->assertIsBool($decoded[0]['is_main_category']);
        $this->assertTrue($decoded[0]['children'][0]['is_main_category']);
        $this->assertIsBool($decoded[0]['children'][0]['is_main_category']);
    }

    private function bindDualRegistryMock(int $menuType): void
    {
        $mockCompanysRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getInfo'])
            ->getMock();
        $mockCompanysRepo->method('getInfo')
            ->willReturnCallback(function (array $filter) use ($menuType) {
                return [
                    'company_id' => $filter['company_id'],
                    'menu_type' => $menuType,
                ];
            });

        $mockItemsCategoryRepo = $this->getMockBuilder(ItemsCategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')
            ->willReturnCallback(function ($class) use ($mockCompanysRepo, $mockItemsCategoryRepo) {
                if ($class === Companys::class) {
                    return $mockCompanysRepo;
                }
                if ($class === ItemsCategory::class) {
                    return $mockItemsCategoryRepo;
                }

                return null;
            });

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getManager'])
            ->getMock();
        $mockRegistry->method('getManager')
            ->with('default')
            ->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }

    private function invokeGetCategoryList(array $params = [])
    {
        $request = Request::create('/h5app/wxapp/goods/category', 'GET', $params);
        $request->replace(array_merge(['auth' => ['company_id' => 1]], $params));

        $controller = new Category();

        return $controller->getCategoryList($request);
    }

    private function sampleCategoryTree(): array
    {
        return [
            'category_id' => 6536,
            'category_name' => 'root',
            'children' => [
                [
                    'category_id' => 6537,
                    'category_name' => 'child',
                ],
            ],
        ];
    }

    private function assertAllNodesHaveIsMainCategoryFlag(array $nodes, bool $expected): void
    {
        foreach ($nodes as $node) {
            $this->assertArrayHasKey('is_main_category', $node);
            $this->assertSame($expected, $node['is_main_category']);
            if (!empty($node['children'])) {
                $this->assertAllNodesHaveIsMainCategoryFlag($node['children'], $expected);
            }
        }
    }
}
