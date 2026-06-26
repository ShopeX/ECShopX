<?php

/**
 * dianwu-list-inventory-buttons：TC-API-01/02 onsale 列表 meta is_platform_store_buy
 */

use DistributionBundle\Services\DistributorService;
use GoodsBundle\Services\ItemsService;

class GoodsOnsaleItemsListMetaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * TC-API-01：开启云仓可购买时 inject platform_store。
     */
    public function testTcApi01ExposePlatformStoreWhenBuyEnabled(): void
    {
        $service = new ItemsService();
        $list = [['item_id' => 101, 'store' => 15]];
        $out = $service->appendPlatformStoreForOnsaleSkuList($list, true);

        $this->assertArrayHasKey('platform_store', $out[0]);
        $this->assertSame(15, $out[0]['platform_store']);
    }

    /**
     * TC-API-02：关闭云仓可购买时不注入 platform_store。
     */
    public function testTcApi02OmitsPlatformStoreWhenBuyDisabled(): void
    {
        $service = new ItemsService();
        $list = [['item_id' => 301, 'store' => 10]];
        $out = $service->appendPlatformStoreForOnsaleSkuList($list, false);

        $this->assertArrayNotHasKey('platform_store', $out[0]);
    }

    /**
     * is_platform_store_buy 与 inject 开关语义一致（供 Items@getOnsaleItemsList 使用）。
     */
    public function testIsPlatformStoreBuyEnabledRawMatchesInjectFlag(): void
    {
        $this->assertTrue(DistributorService::isPlatformStoreBuyEnabledRaw(['is_platform_store_buy' => true]));
        $this->assertTrue(DistributorService::isPlatformStoreBuyEnabledRaw(['is_platform_store_buy' => 1]));
        $this->assertFalse(DistributorService::isPlatformStoreBuyEnabledRaw(['is_platform_store_buy' => false]));
        $this->assertFalse(DistributorService::isPlatformStoreBuyEnabledRaw(['is_platform_store_buy' => 0]));
    }
}
