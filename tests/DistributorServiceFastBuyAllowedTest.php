<?php

/**
 * dianwu-list-inventory-buttons：快买门禁 TC-BUY-05 总部发货不要求云仓开关
 */

use DistributionBundle\Services\DistributorService;
use Dingo\Api\Exception\ResourceException;

class DistributorServiceFastBuyAllowedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * 总部发货：云仓开关关闭时仍允许快买（不抛「该店铺未开启云仓可购买」）。
     */
    public function testHeadquartersShipmentAllowsFastBuyWhenPlatformStoreBuyOff(): void
    {
        DistributorService::assertShopadminFastBuyAllowedForItem(
            ['is_total_store' => true],
            false
        );
        $this->addToAssertionCount(1);
    }

    /**
     * 店铺发货：云仓开关关闭时不允许快买。
     */
    public function testStoreShipmentRequiresPlatformStoreBuyWhenOff(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('该店铺未开启云仓可购买');
        DistributorService::assertShopadminFastBuyAllowedForItem(
            ['is_total_store' => false],
            false
        );
    }

    /**
     * 店铺发货：云仓开关开启时允许快买。
     */
    public function testStoreShipmentAllowsWhenPlatformStoreBuyOn(): void
    {
        DistributorService::assertShopadminFastBuyAllowedForItem(
            ['is_total_store' => false],
            true
        );
        $this->addToAssertionCount(1);
    }
}
