<?php

use DistributionBundle\Services\DistributorItemsService;

/**
 * @covers \DistributionBundle\Services\DistributorItemsService::applyMultiSpecTotalStoreForDistributorRelItemList
 */
final class ApplyMultiSpecTotalStoreForDistributorRelItemListTest extends TestCase
{
    public function testOnlyDefaultSkuRowShowsAggregatedStore(): void
    {
        $this->bindMockRegistryToAvoidDb();

        $service = new DistributorItemsService();

        $itemsRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getLists'])
            ->getMock();
        $itemsRepo->method('getLists')
            ->willReturn([
                ['goods_id' => 500, 'item_id' => 100, 'store' => 0],
                ['goods_id' => 500, 'item_id' => 101, 'store' => 0],
            ]);

        $entityRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['lists'])
            ->getMock();
        $entityRepo->method('lists')
            ->willReturn([
                'list' => [
                    ['item_id' => 100, 'store' => 5, 'is_total_store' => false],
                    ['item_id' => 101, 'store' => 8, 'is_total_store' => false],
                ],
            ]);

        $service->itemsRepository = $itemsRepo;
        $service->entityRepository = $entityRepo;

        $list = [
            [
                'company_id' => 1,
                'distributor_id' => 306,
                'goods_id' => 500,
                'item_id' => 100,
                'default_item_id' => 100,
                'nospec' => false,
                'store' => 5,
            ],
            [
                'company_id' => 1,
                'distributor_id' => 306,
                'goods_id' => 500,
                'item_id' => 101,
                'default_item_id' => 100,
                'nospec' => false,
                'store' => 8,
            ],
        ];

        $result = $service->applyMultiSpecTotalStoreForDistributorRelItemList($list);

        $this->assertSame(13, $result[0]['store'], 'default SKU row should show aggregated store');
        $this->assertSame(8, $result[1]['store'], 'non-default SKU row should keep its own store');
    }

    public function testSingleSpecItemStoreUnchanged(): void
    {
        $this->bindMockRegistryToAvoidDb();

        $service = new DistributorItemsService();
        $service->itemsRepository = $this->getMockBuilder(\stdClass::class)->addMethods(['getLists'])->getMock();
        $service->entityRepository = $this->getMockBuilder(\stdClass::class)->addMethods(['lists'])->getMock();

        $list = [
            [
                'company_id' => 1,
                'distributor_id' => 306,
                'goods_id' => 600,
                'item_id' => 200,
                'default_item_id' => 200,
                'nospec' => true,
                'store' => 99,
            ],
        ];

        $result = $service->applyMultiSpecTotalStoreForDistributorRelItemList($list);

        $this->assertSame(99, $result[0]['store'], 'single-spec item store should not be modified');
    }

    private function bindMockRegistryToAvoidDb(): void
    {
        $mockRepo = $this->getMockBuilder(\stdClass::class)->getMock();
        $mockManager = $this->getMockBuilder(\stdClass::class)->addMethods(['getRepository'])->getMock();
        $mockManager->method('getRepository')->willReturn($mockRepo);
        $mockRegistry = $this->getMockBuilder(\stdClass::class)->addMethods(['getManager'])->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);
        $this->app->instance('registry', $mockRegistry);
    }
}
