<?php

use CommunityBundle\Entities\CommunityItems;
use CommunityBundle\Services\CommunityItemsService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\MultiLang\MultiLangService;

/**
 * @covers \CommunityBundle\Services\CommunityItemsService::getItemsList
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class CommunityItemsListMultiLangTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * TC1 / A1：非空 list + country_code=zh-CN 时 item_name/itemName 为中文，且 getListAddLang 参数正确。
     * #given joinItemsList 返回主表英文名；mock getListAddLang 返回中文 item_name
     * #when 调用 getItemsList
     * #then 输出行 item_name 与 itemName 均为中文；getListAddLang 参数含 items/item_id/item_name
     */
    public function testTc01AppliesChineseItemNameWhenTranslationExists(): void
    {
        #given
        $this->app->instance('request', \Illuminate\Http\Request::create('/', 'GET', ['country_code' => 'zh-CN']));
        $this->bindItemsServiceStub();
        $fixture = [[
            'item_id' => 101,
            'company_id' => 1,
            'item_name' => 'English Product Name',
            'item_category' => '100',
            'nospec' => 'true',
            'pics' => '[]',
        ]];
        $this->bindMockRegistryWithJoinItemsList($fixture);

        $chineseName = '中文商品名';
        $multiLangMock = \Mockery::mock('overload:' . MultiLangService::class);
        $multiLangMock->shouldReceive('getLang')->andReturn('zh-CN');
        $multiLangMock->shouldReceive('getListAddLang')
            ->once()
            ->with(
                \Mockery::on(function (array $list) use ($fixture) {
                    return $list[0]['item_name'] === $fixture[0]['item_name'];
                }),
                ['item_name'],
                'items',
                'zh-CN',
                'item_id'
            )
            ->andReturnUsing(function (array $list) use ($chineseName) {
                $list[0]['item_name'] = $chineseName;

                return $list;
            });

        $service = new CommunityItemsService();

        #when
        $result = $service->getItemsList(['company_id' => 1]);

        #then
        $this->assertSame($chineseName, $result['list'][0]['item_name'], 'TC1: item_name should be Chinese translation');
        $this->assertSame($chineseName, $result['list'][0]['itemName'], 'TC1: itemName should sync with item_name');
    }

    /**
     * TC2 / A2：list 为空时不调用 getListAddLang。
     * #given joinItemsList 返回空 list
     * #when 调用 getItemsList
     * #then getListAddLang 调用次数为 0
     */
    public function testTc02EmptyListSkipsGetListAddLang(): void
    {
        #given
        $this->app->instance('request', \Illuminate\Http\Request::create('/', 'GET', ['country_code' => 'zh-CN']));
        $this->bindItemsServiceStub();
        $this->bindMockRegistryWithJoinItemsList([], 0);

        $multiLangMock = \Mockery::mock('overload:' . MultiLangService::class);
        $multiLangMock->shouldReceive('getLang')->andReturn('zh-CN');
        $multiLangMock->shouldReceive('getListAddLang')->never();

        $service = new CommunityItemsService();

        #when
        $result = $service->getItemsList(['company_id' => 1]);

        #then
        $this->assertSame([], $result['list'], 'TC2: empty list should remain empty');
    }

    /**
     * TC3 / A3：无有效翻译时保留主表 item_name。
     * #given 主表 item_name 为非中文；mock getListAddLang 原样返回
     * #when 调用 getItemsList
     * #then item_name 仍为主表原值
     */
    public function testTc03PreservesMainTableItemNameWhenNoTranslation(): void
    {
        #given
        $this->app->instance('request', \Illuminate\Http\Request::create('/', 'GET', ['country_code' => 'zh-CN']));
        $this->bindItemsServiceStub();
        $mainTableName = 'English Product Name';
        $fixture = [[
            'item_id' => 102,
            'company_id' => 1,
            'item_name' => $mainTableName,
            'item_category' => '100',
            'nospec' => 'true',
            'pics' => '[]',
        ]];
        $this->bindMockRegistryWithJoinItemsList($fixture);

        $multiLangMock = \Mockery::mock('overload:' . MultiLangService::class);
        $multiLangMock->shouldReceive('getLang')->andReturn('zh-CN');
        $multiLangMock->shouldReceive('getListAddLang')
            ->once()
            ->andReturnUsing(function (array $list) {
                return $list;
            });

        $service = new CommunityItemsService();

        #when
        $result = $service->getItemsList(['company_id' => 1]);

        #then
        $this->assertSame($mainTableName, $result['list'][0]['item_name'], 'TC3: item_name should remain main table value');
        $this->assertSame($mainTableName, $result['list'][0]['itemName'], 'TC3: itemName should sync with preserved item_name');
    }

    /**
     * TC4 / A4：request country_code=en 时传入 getListAddLang 的 lang 为 en。
     * #given request country_code=en；非空 list fixture
     * #when 调用 getItemsList
     * #then getListAddLang 第 4 参数 lang === 'en'
     */
    public function testTc04PassesCountryCodeAsLangToGetListAddLang(): void
    {
        #given
        $this->app->instance('request', \Illuminate\Http\Request::create('/', 'GET', ['country_code' => 'en']));
        $this->bindItemsServiceStub();
        $fixture = [[
            'item_id' => 103,
            'company_id' => 1,
            'item_name' => 'Product Name',
            'item_category' => '100',
            'nospec' => 'true',
            'pics' => '[]',
        ]];
        $this->bindMockRegistryWithJoinItemsList($fixture);

        $multiLangMock = \Mockery::mock('overload:' . MultiLangService::class);
        $multiLangMock->shouldReceive('getLang')->andReturn('en');
        $multiLangMock->shouldReceive('getListAddLang')
            ->once()
            ->with(
                \Mockery::type('array'),
                ['item_name'],
                'items',
                'en',
                'item_id'
            )
            ->andReturnUsing(function (array $list) {
                return $list;
            });

        $service = new CommunityItemsService();

        #when
        $result = $service->getItemsList(['company_id' => 1]);

        #then
        $this->assertSame('Product Name', $result['list'][0]['item_name'], 'TC4: item_name should be returned from getListAddLang');
    }

    private function bindItemsServiceStub(): void
    {
        $mock = \Mockery::mock('overload:' . ItemsService::class);
        $mock->shouldReceive('getCategoryByItemId')->andReturn('');
        $mock->shouldReceive('getCategoryByItemIds')->andReturn([]);
    }

    private function bindMockRegistryWithJoinItemsList(array $listFixture, int $totalCount = null): void
    {
        $totalCount = $totalCount ?? count($listFixture);

        $entityRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['joinItemsList'])
            ->getMock();
        $entityRepo->method('joinItemsList')->willReturn([
            'total_count' => $totalCount,
            'list' => $listFixture,
        ]);

        $mockManager = $this->getMockBuilder(\stdClass::class)->addMethods(['getRepository'])->getMock();
        $mockManager->method('getRepository')->willReturnCallback(function ($class) use ($entityRepo) {
            if ($class === CommunityItems::class) {
                return $entityRepo;
            }

            return $entityRepo;
        });

        $mockRegistry = $this->getMockBuilder(\stdClass::class)->addMethods(['getManager'])->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }
}
