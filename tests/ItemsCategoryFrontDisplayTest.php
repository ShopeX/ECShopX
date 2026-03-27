<?php

/**
 * 计划：.tasks/plans/items-category-front-display.md TODO-0
 * 验收用例 RED：实体 is_show_front 读写、Repository 映射、FrontApi getCategoryList filter 含 is_show_front=>1。
 * TC7（后台列表不过滤）、TC8（迁移历史数据）为手工或后续补充。
 */

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use GoodsBundle\Entities\ItemsCategory;
use GoodsBundle\Repositories\ItemsCategoryRepository;
use GoodsBundle\Http\FrontApi\V1\Action\Category;
use GoodsBundle\ApiServices\ItemsCategoryService as ApiServicesItemsCategoryService;
use EspierBundle\Services\Bus\TestBus;
use Illuminate\Http\Request;

class ItemsCategoryFrontDisplayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestBus::resetLastPostData();
    }

    /**
     * TC1/TC2：实体 ItemsCategory 具备 is_show_front 的 getter/setter，且可读写。
     * #given 实体未实现 is_show_front 时
     * #when 调用 getIsShowFront/setIsShowFront
     * #then 当前 RED：实体无 getIsShowFront/setIsShowFront，测试失败。
     */
    public function testEntityHasIsShowFrontGetterAndSetter(): void
    {
        $entity = new ItemsCategory();
        $entity->setIsShowFront(0);
        $this->assertSame(0, $entity->getIsShowFront());
        $entity->setIsShowFront(1);
        $this->assertSame(1, $entity->getIsShowFront());
    }

    /**
     * Repository getColumnNamesData 返回数组包含 is_show_front（TC1–TC4 读路径）。
     * #given 实体有 getIsShowFront 时，getColumnNamesData 应包含 is_show_front
     * #when 用带 getIsShowFront 的 stub 调用 getColumnNamesData（反射，不连 DB）
     * #then 当前 RED：getColumnNamesData 未返回 is_show_front。
     */
    public function testRepositoryGetColumnNamesDataIncludesIsShowFront(): void
    {
        $entity = new class extends ItemsCategory {
            public $is_show_front = 1;
            public function getIsShowFront() { return $this->is_show_front; }
        };
        $repo = $this->createRepositoryWithoutDb();
        $ref = new \ReflectionClass($repo);
        $method = $ref->getMethod('getColumnNamesData');
        $method->setAccessible(true);
        $data = $method->invoke($repo, $entity);
        $this->assertArrayHasKey('is_show_front', $data, 'getColumnNamesData must include is_show_front (TC1–TC4)');
        $this->assertSame(1, $data['is_show_front']);
    }

    /**
     * Repository setColumnNamesData 将 data['is_show_front'] 写入实体（TC1–TC4 写路径）。
     * #given 传入 data 含 is_show_front
     * #when 调用 setColumnNamesData（反射）后读取实体
     * #then 当前 RED：setColumnNamesData 未映射 is_show_front 或实体无 getter。
     */
    public function testRepositorySetColumnNamesDataMapsIsShowFront(): void
    {
        $entity = new class extends ItemsCategory {
            public $is_show_front = 1;
            public function getIsShowFront() { return $this->is_show_front; }
            public function setIsShowFront($v) { $this->is_show_front = $v; return $this; }
        };
        $repo = $this->createRepositoryWithoutDb();
        $ref = new \ReflectionClass($repo);
        $method = $ref->getMethod('setColumnNamesData');
        $method->setAccessible(true);
        $data = [
            'company_id' => 1,
            'category_name' => 'Test',
            'parent_id' => 0,
            'is_show_front' => 0,
        ];
        $method->invoke($repo, $entity, $data);
        $this->assertSame(0, $entity->getIsShowFront(), 'setColumnNamesData must set is_show_front (TC1–TC4)');
    }

    /**
     * TC1：后台创建时 form 含 is_show_front=0，ApiServices saveItemsCategory 写入时传给 Repository create。
     * #given ApiServices 使用 mock Repository 与 mock Connection
     * #when 调用 saveItemsCategory，form 中含 is_show_front=0
     * #then repository create() 被调用且参数包含 is_show_front=>0
     */
    public function testCreateCategoryPassesIsShowFrontToRepositoryWhenPresent(): void
    {
        $mockDispatcher = $this->getMockBuilder(\Illuminate\Contracts\Events\Dispatcher::class)->getMock();
        $mockDispatcher->method('dispatch')->willReturn(null);
        $this->app->instance('events', $mockDispatcher);

        $createParams = null;
        $mockRepo = $this->getMockBuilder(ItemsCategoryRepository::class)->disableOriginalConstructor()->getMock();
        $mockRepo->method('create')->willReturnCallback(function ($params) use (&$createParams) {
            $createParams = $params;
            return ['category_id' => 1, 'path' => ''];
        });
        $mockRepo->method('updateOneBy')->willReturn(true);

        $mockManager = $this->getMockBuilder(\stdClass::class)->addMethods(['getRepository'])->getMock();
        $mockManager->method('getRepository')->with(ItemsCategory::class)->willReturn($mockRepo);

        $mockConn = $this->getMockBuilder(\stdClass::class)->addMethods(['beginTransaction', 'commit', 'rollBack'])->getMock();
        $mockConn->method('beginTransaction')->willReturn(null);
        $mockConn->method('commit')->willReturn(null);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)->addMethods(['getManager', 'getConnection'])->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);
        $mockRegistry->method('getConnection')->with('default')->willReturn($mockConn);

        $this->app->instance('registry', $mockRegistry);

        $service = new ApiServicesItemsCategoryService();
        $form = [
            [
                'category_name' => 'TC1',
                'sort' => 0,
                'image_url' => '',
                'is_show_front' => 0,
            ],
        ];
        $service->saveItemsCategory($form, 1, 1);

        $this->assertNotNull($createParams, 'create() must be called (TC1)');
        $this->assertArrayHasKey('is_show_front', $createParams, 'create params must include is_show_front when provided (TC1)');
        $this->assertSame(0, $createParams['is_show_front']);
    }

    /**
     * TC2：后台创建时 form 不传 is_show_front，ApiServices 不写入该字段，由 DB 默认 1。
     * #given ApiServices 使用 mock Repository
     * #when 调用 saveItemsCategory，form 中不含 is_show_front
     * #then repository create() 被调用且参数不包含 is_show_front
     */
    public function testCreateCategoryOmitsIsShowFrontWhenNotProvided(): void
    {
        $mockDispatcher = $this->getMockBuilder(\Illuminate\Contracts\Events\Dispatcher::class)->getMock();
        $mockDispatcher->method('dispatch')->willReturn(null);
        $this->app->instance('events', $mockDispatcher);

        $createParams = null;
        $mockRepo = $this->getMockBuilder(ItemsCategoryRepository::class)->disableOriginalConstructor()->getMock();
        $mockRepo->method('create')->willReturnCallback(function ($params) use (&$createParams) {
            $createParams = $params;
            return ['category_id' => 1, 'path' => ''];
        });
        $mockRepo->method('updateOneBy')->willReturn(true);

        $mockManager = $this->getMockBuilder(\stdClass::class)->addMethods(['getRepository'])->getMock();
        $mockManager->method('getRepository')->with(ItemsCategory::class)->willReturn($mockRepo);

        $mockConn = $this->getMockBuilder(\stdClass::class)->addMethods(['beginTransaction', 'commit', 'rollBack'])->getMock();
        $mockConn->method('beginTransaction')->willReturn(null);
        $mockConn->method('commit')->willReturn(null);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)->addMethods(['getManager', 'getConnection'])->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);
        $mockRegistry->method('getConnection')->with('default')->willReturn($mockConn);

        $this->app->instance('registry', $mockRegistry);

        $service = new ApiServicesItemsCategoryService();
        $form = [
            [
                'category_name' => 'TC2',
                'sort' => 0,
                'image_url' => '',
            ],
        ];
        $service->saveItemsCategory($form, 1, 1);

        $this->assertNotNull($createParams, 'create() must be called (TC2)');
        $this->assertArrayNotHasKey('is_show_front', $createParams, 'create params must not include is_show_front when not provided so DB default 1 applies (TC2)');
    }

    /**
     * TC3：后台更新时 body 传 is_show_front=0，ApiServices updateOneBy 将 data 透传给 Repository。
     * #given ApiServices 使用 mock Repository
     * #when 调用 updateOneBy，data 中含 is_show_front=0
     * #then repository updateOneBy() 被调用且第二个参数包含 is_show_front=>0
     */
    public function testUpdateCategoryPassesIsShowFrontZeroToRepository(): void
    {
        $updateData = null;
        $mockRepo = $this->getMockBuilder(ItemsCategoryRepository::class)->disableOriginalConstructor()->getMock();
        $mockRepo->method('updateOneBy')->willReturnCallback(function ($filter, $data) use (&$updateData) {
            $updateData = $data;
            return ['category_id' => 1, 'is_show_front' => 0];
        });
        $mockRepo->method('findOneBy')->willReturn(new ItemsCategory());

        $mockManager = $this->getMockBuilder(\stdClass::class)->addMethods(['getRepository'])->getMock();
        $mockManager->method('getRepository')->with(ItemsCategory::class)->willReturn($mockRepo);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)->addMethods(['getManager'])->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);

        $service = new ApiServicesItemsCategoryService();
        $service->updateOneBy(
            ['category_id' => 1, 'company_id' => 1],
            ['category_name' => 'Updated', 'is_show_front' => 0]
        );

        $this->assertNotNull($updateData, 'updateOneBy must be called (TC3)');
        $this->assertArrayHasKey('is_show_front', $updateData, 'update data must include is_show_front when provided (TC3)');
        $this->assertSame(0, $updateData['is_show_front']);
    }

    /**
     * TC5/TC6：FrontApi getCategoryList 调用 getItemsCategory 时 filter 含 is_show_front=>1。
     * #given 使用 TestBus 捕获 post payload，并 mock registry 避免 DB
     * #when 调用 Category::getCategoryList(Request) 且 config goods 使用 test bus
     * #then 当前 RED：getCategoryList 未在 filter 中加 is_show_front=>1。
     */
    public function testFrontApiGetCategoryListPassesFilterWithIsShowFrontOne(): void
    {
        config(['services.goods' => ['rpc_type' => 'test', 'base_url' => '', 'sign' => '123']]);
        $this->bindMockRegistryToAvoidDb();
        $request = Request::create('/h5app/wxapp/goods/category', 'GET');
        $request->replace(['auth' => ['company_id' => 1]]);
        $request->setUserResolver(function () { return null; });

        $controller = new Category();
        $controller->getCategoryList($request);

        $captured = TestBus::getLastPostData();
        $this->assertNotNull($captured, 'getCategoryList must call Bus post (TC5/TC6)');
        $this->assertArrayHasKey('filter', $captured);
        $this->assertArrayHasKey('is_show_front', $captured['filter'], 'FrontApi getCategoryList filter must include is_show_front=>1 (TC5/TC6)');
        $this->assertSame(1, $captured['filter']['is_show_front']);
    }

    private function createRepositoryWithoutDb(): ItemsCategoryRepository
    {
        $mockEm = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $metadata = new ClassMetadata(ItemsCategory::class);
        return new ItemsCategoryRepository($mockEm, $metadata);
    }

    private function bindMockRegistryToAvoidDb(): void
    {
        $mockRepo = $this->getMockBuilder(ItemsCategoryRepository::class)->disableOriginalConstructor()->getMock();
        $mockManager = $this->getMockBuilder(\stdClass::class)->addMethods(['getRepository'])->getMock();
        $mockManager->method('getRepository')->with(ItemsCategory::class)->willReturn($mockRepo);
        $mockRegistry = $this->getMockBuilder(\stdClass::class)->addMethods(['getManager'])->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);
        $this->app->instance('registry', $mockRegistry);
    }
}
