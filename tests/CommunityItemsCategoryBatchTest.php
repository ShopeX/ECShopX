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
final class CommunityItemsCategoryBatchTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * TC-P1-1：list 为空时不触发分类查询。
     * #given joinItemsList 返回空 list
     * #when 调用 getItemsList
     * #then getCategoryByItemIds 与 getCategoryByItemId 均不被调用
     */
    public function testTcP11EmptyListSkipsCategoryQuery(): void
    {
        #given
        $this->bindMultiLangPassthrough();
        $itemsServiceMock = $this->bindItemsServiceCategoryBatchMock();
        $itemsServiceMock->shouldReceive('getCategoryByItemIds')->never();
        $itemsServiceMock->shouldReceive('getCategoryByItemId')->never();
        $this->bindMockRegistryWithJoinItemsList([], 0);

        $service = new CommunityItemsService();

        #when
        $result = $service->getItemsList(['company_id' => 1]);

        #then
        $this->assertSame([], $result['list'], 'TC-P1-1: empty list should remain empty');
    }

    /**
     * TC-P1-2：多 item 批量回填 item_cat_id 正确。
     * #given 两条商品；mock getCategoryByItemIds 返回 item_id => category_id[] map
     * #when 调用 getItemsList
     * #then 各行 item_cat_id 与 map 一致
     */
    public function testTcP12BatchBackfillsItemCatIdForMultipleItems(): void
    {
        #given
        $companyId = 1;
        $fixture = [
            [
                'item_id' => 201,
                'company_id' => $companyId,
                'item_name' => 'Product A',
                'item_category' => '100',
                'nospec' => 'true',
                'pics' => '[]',
            ],
            [
                'item_id' => 202,
                'company_id' => $companyId,
                'item_name' => 'Product B',
                'item_category' => '200',
                'nospec' => 'true',
                'pics' => '[]',
            ],
        ];
        $categoryMap = [
            201 => [11, 12],
            202 => [21],
        ];

        $this->bindMultiLangPassthrough();
        $itemsServiceMock = $this->bindItemsServiceCategoryBatchMock();
        $itemsServiceMock->shouldReceive('getCategoryByItemId')->never();
        $itemsServiceMock->shouldReceive('getCategoryByItemIds')
            ->once()
            ->with(
                \Mockery::on(function (array $itemIds) {
                    sort($itemIds);

                    return $itemIds === [201, 202];
                }),
                $companyId
            )
            ->andReturn($categoryMap);
        $this->bindMockRegistryWithJoinItemsList($fixture);

        $service = new CommunityItemsService();

        #when
        $result = $service->getItemsList(['company_id' => $companyId]);

        #then
        $this->assertSame([11, 12], $result['list'][0]['item_cat_id'], 'TC-P1-2: first item_cat_id should match batch map');
        $this->assertSame([21], $result['list'][1]['item_cat_id'], 'TC-P1-2: second item_cat_id should match batch map');
    }

    /**
     * TC-P1-3：无分类时 item_cat_id 为 []。
     * #given 单条商品；mock getCategoryByItemIds 返回空 map 或无该 item 键
     * #when 调用 getItemsList
     * #then item_cat_id === []
     */
    public function testTcP13NoCategoryReturnsEmptyArray(): void
    {
        #given
        $companyId = 1;
        $fixture = [[
            'item_id' => 301,
            'company_id' => $companyId,
            'item_name' => 'Uncategorized Product',
            'item_category' => '100',
            'nospec' => 'true',
            'pics' => '[]',
        ]];

        $this->bindMultiLangPassthrough();
        $itemsServiceMock = $this->bindItemsServiceCategoryBatchMock();
        $itemsServiceMock->shouldReceive('getCategoryByItemId')->never();
        $itemsServiceMock->shouldReceive('getCategoryByItemIds')
            ->once()
            ->with([301], $companyId)
            ->andReturn([]);
        $this->bindMockRegistryWithJoinItemsList($fixture);

        $service = new CommunityItemsService();

        #when
        $result = $service->getItemsList(['company_id' => $companyId]);

        #then
        $this->assertSame([], $result['list'][0]['item_cat_id'], 'TC-P1-3: item_cat_id should be empty array when no category');
    }

    /**
     * TC-P1-4：N 条商品时 getCategoryByItemIds 仅调用 1 次（非 N 次 getCategoryByItemId）。
     * #given 5 条商品 fixture
     * #when 调用 getItemsList
     * #then getCategoryByItemIds 调用 1 次；getCategoryByItemId 调用 0 次
     */
    public function testTcP14CategoryQueryCalledOnceForManyItems(): void
    {
        #given
        $companyId = 1;
        $itemIds = [401, 402, 403, 404, 405];
        $fixture = [];
        foreach ($itemIds as $itemId) {
            $fixture[] = [
                'item_id' => $itemId,
                'company_id' => $companyId,
                'item_name' => 'Product ' . $itemId,
                'item_category' => '100',
                'nospec' => 'true',
                'pics' => '[]',
            ];
        }

        $categoryMap = [];
        foreach ($itemIds as $itemId) {
            $categoryMap[$itemId] = [$itemId + 1000];
        }

        $this->bindMultiLangPassthrough();
        $itemsServiceMock = $this->bindItemsServiceCategoryBatchMock();
        $itemsServiceMock->shouldReceive('getCategoryByItemId')->never();
        $itemsServiceMock->shouldReceive('getCategoryByItemIds')
            ->once()
            ->with(
                \Mockery::on(function (array $passedItemIds) use ($itemIds) {
                    sort($passedItemIds);
                    $expected = $itemIds;
                    sort($expected);

                    return $passedItemIds === $expected;
                }),
                $companyId
            )
            ->andReturn($categoryMap);
        $this->bindMockRegistryWithJoinItemsList($fixture);

        $service = new CommunityItemsService();

        #when
        $result = $service->getItemsList(['company_id' => $companyId]);

        #then
        $this->assertCount(5, $result['list'], 'TC-P1-4: should return all 5 items');
    }

    private function bindItemsServiceCategoryBatchMock(): \Mockery\MockInterface
    {
        return \Mockery::mock('overload:' . ItemsService::class);
    }

    private function bindMultiLangPassthrough(): void
    {
        $multiLangMock = \Mockery::mock('overload:' . MultiLangService::class);
        $multiLangMock->shouldReceive('getLang')->andReturn('zh-CN');
        $multiLangMock->shouldReceive('getListAddLang')
            ->andReturnUsing(function (array $list) {
                return $list;
            });
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
