<?php

/**
 * store-ops-buy-now-cloud-stock：立即购买库存校验（店务）
 * TC-BUY-01～TC-BUY-04
 */

use CompanysBundle\Services\OperatorFastBuyStockValidator;
use Dingo\Api\Exception\ResourceException;

class OperatorFastBuyStockValidatorTest extends TestCase
{
    /**
     * TC-BUY-01 / S3：门店 0、云仓足够 → 通过（含较大 num 边界）。
     */
    public function testTcBuy01ShopZeroPlatformEnoughPasses(): void
    {
        OperatorFastBuyStockValidator::validate(0, 100, 1);
        OperatorFastBuyStockValidator::validate(0, 1000, 1000);
        $this->addToAssertionCount(1);
    }

    /**
     * TC-BUY-02 / S4：门店>0 → 引导收银。
     */
    public function testTcBuy02ShopHasStockThrowsCashierMessage(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('当前商品门店有库存，请从收银加购');
        OperatorFastBuyStockValidator::validate(1, 999, 1);
    }

    /**
     * TC-BUY-03 / S5：双 0 → 库存不足。
     */
    public function testTcBuy03BothZeroThrowsInsufficient(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('库存不足');
        OperatorFastBuyStockValidator::validate(0, 0, 1);
    }

    /**
     * TC-BUY-04 / S6：云仓 n、num n+1 → 库存不足。
     */
    public function testTcBuy04PlatformLessThanNumThrowsInsufficient(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('库存不足');
        OperatorFastBuyStockValidator::validate(0, 5, 6);
    }
}
