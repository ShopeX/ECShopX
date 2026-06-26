<?php

/**
 * store-ops-buy-now-cloud-stock：在售列表云仓字段（platform_store）
 * 对应 GET /goods/items/onsale → Items@getOnsaleItemsList 使用的列表装配。
 */

use GoodsBundle\Services\ItemsService;

class GoodsOnsaleItemsPlatformStoreTest extends TestCase
{
    /**
     * TC-LIST-01 / S1：平台列表与经销商列表路径下，每条 SKU 含 platform_store 且为非负整数。
     *
     * #given 模拟平台直连行、经销商 logistics_store 行、经销商总部发货行
     * #when appendPlatformStoreForOnsaleSkuList
     * #then platform_store 存在且 >= 0，并与预期平台库存一致
     */
    public function testTcList01PlatformAndDistributorPathsExposeNonNegativePlatformStore(): void
    {
        $service = new ItemsService();
        $list = [
            // 平台 getSkuItemsList：items.store 即平台库存
            ['item_id' => 101, 'store' => 15],
            // 经销商：门店发货时 logistics_store 承载原平台 store（见 DistributorItemsService::replaceItemInfo）
            ['item_id' => 102, 'store' => 2, 'logistics_store' => 9, 'distributor_store' => 2, 'is_total_store' => false],
            // 经销商：总部发货，store 未被替换为门店库存
            ['item_id' => 103, 'store' => 20, 'is_total_store' => true, 'distributor_store' => 0],
        ];

        $out = $service->appendPlatformStoreForOnsaleSkuList($list);

        $this->assertArrayHasKey('platform_store', $out[0]);
        $this->assertSame(15, $out[0]['platform_store']);
        $this->assertSame(9, $out[1]['platform_store']);
        $this->assertSame(20, $out[2]['platform_store']);
        foreach ($out as $row) {
            $this->assertGreaterThanOrEqual(0, $row['platform_store']);
        }
    }

    /**
     * TC-LIST-02 / S2：经销商路径无平台库存快照时 platform_store 为 0（不把门店 store 误作云仓）。
     *
     * #given 含 distributor_store、非总部发货、无 logistics_store
     * #when appendPlatformStoreForOnsaleSkuList
     * #then platform_store === 0
     */
    public function testTcList02DistributorWithoutPlatformSourceUsesZeroPlatformStore(): void
    {
        $service = new ItemsService();
        $list = [
            ['item_id' => 201, 'store' => 5, 'distributor_store' => 5, 'is_total_store' => false],
        ];

        $out = $service->appendPlatformStoreForOnsaleSkuList($list);

        $this->assertSame(0, $out[0]['platform_store']);
    }

    /**
     * 店铺未开启云仓可购买时：不注入 platform_store 字段。
     */
    public function testWhenInjectDisabledOmitsPlatformStoreKey(): void
    {
        $service = new ItemsService();
        $list = [['item_id' => 301, 'store' => 10]];

        $out = $service->appendPlatformStoreForOnsaleSkuList($list, false);

        $this->assertArrayNotHasKey('platform_store', $out[0]);
    }
}
