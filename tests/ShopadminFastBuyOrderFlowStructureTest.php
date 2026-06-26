<?php

/**
 * store-ops-buy-now-cloud-stock：店务 checkout/create 分流与 Q8 落库结构（TC-CHK-04/05、TC-CRT-03、TC-ORD-01 辅助断言）
 */

use OrdersBundle\Services\Orders\ShopadminNormalOrderService;

class ShopadminFastBuyOrderFlowStructureTest extends TestCase
{
    private function methodBody(string $class, string $method): string
    {
        $ref = new ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }

    /**
     * TC-CHK-04：立即购买 checkout 走 Redis 分桶路径（getFastBuyCartdataList），不依赖 OperatorCart DB 行。
     */
    public function testCheckoutCartItemsFastBuyUsesRedisBucketPath(): void
    {
        $body = $this->methodBody(ShopadminNormalOrderService::class, 'checkoutCartItems');
        $this->assertStringContainsString('getFastBuyCartdataList', $body);
        $this->assertStringContainsString('fastbuy', $body);
    }

    /**
     * TC-CHK-05 / TC-CRT-03：购物车模式仍走 getCartdataList（DB 桶）。
     */
    public function testCheckoutCartItemsCartModeStillUsesOperatorCartList(): void
    {
        $body = $this->methodBody(ShopadminNormalOrderService::class, 'checkoutCartItems');
        $this->assertStringContainsString('getCartdataList', $body);
    }

    public function testEmptyCartFastBuyClearsRedisService(): void
    {
        $body = $this->methodBody(ShopadminNormalOrderService::class, 'emptyCart');
        $this->assertStringContainsString('OperatorShopFastBuyRedisService', $body);
    }

    public function testCheckFastbuyRevalidatesPlatformStock(): void
    {
        $body = $this->methodBody(ShopadminNormalOrderService::class, 'check');
        $this->assertStringContainsString('revalidateFastBuyPlatformStock', $body);
    }

    public function testOrderServicePersistsUsesPlatformItemStockForShopadminFastbuy(): void
    {
        $src = file_get_contents(__DIR__ . '/../src/OrdersBundle/Services/OrderService.php');
        $this->assertStringContainsString('uses_platform_item_stock', $src);
        $this->assertStringContainsString("=== 'fastbuy'", $src);
    }

    /**
     * 支付成功回调：店务立即购买保持 PAYED，收银台购物车仍为 DONE。
     */
    public function testTradeSuccShopadminImmediateBuyStaysPayed(): void
    {
        $src = file_get_contents(__DIR__ . '/../src/OrdersBundle/Services/OrderService.php');
        $this->assertStringContainsString('shopadminImmediateBuy', $src);
        $this->assertStringContainsString("if (!\$shopadminImmediateBuy) {\n                    \$orderStatus = 'DONE';", $src);
    }
}
