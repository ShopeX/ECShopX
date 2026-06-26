<?php

/**
 * dianwu-list-inventory-buttons：TC-BUY-05 总部发货时 store 不计入门店库存
 */

use CompanysBundle\Services\OperatorCartService;
use CompanysBundle\Services\OperatorFastBuyStockValidator;

class OperatorCartResolveShopStockForFastBuyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * TC-BUY-05：总部发货 is_total_store=true 时 shopStock=0，platformStock 用 store，快买可通过。
     */
    public function testTcBuy05HeadquartersShipmentShopStockIsZero(): void
    {
        $shopStock = OperatorCartService::resolveShopStockForFastBuyFromItemInfo(
            ['distributor_id' => 289, 'company_id' => 38],
            ['store' => 10, 'is_total_store' => true],
            'standard'
        );
        $this->assertSame(0, $shopStock);

        $platformStock = 10;
        OperatorFastBuyStockValidator::validate($shopStock, $platformStock, 1);
        $this->addToAssertionCount(1);
    }

    /**
     * TC-BUY-06：店铺发货 store>0 时 shopStock>0，快买应引导收银。
     */
    public function testTcBuy06StoreShipmentShopStockBlocksFastBuy(): void
    {
        $shopStock = OperatorCartService::resolveShopStockForFastBuyFromItemInfo(
            ['distributor_id' => 289, 'company_id' => 38],
            ['store' => 5, 'is_total_store' => false, 'distributor_store' => 5],
            'standard'
        );
        $this->assertSame(5, $shopStock);

        $this->expectException(\Dingo\Api\Exception\ResourceException::class);
        $this->expectExceptionMessage('当前商品门店有库存，请从收银加购');
        OperatorFastBuyStockValidator::validate($shopStock, 8, 1);
    }
}
