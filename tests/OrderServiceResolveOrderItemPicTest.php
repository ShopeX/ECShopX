<?php

/**
 * checkout / 下单：订单行 pic 优先购物车规格图。
 */

use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\Orders\ShopadminNormalOrderService;

class OrderServiceResolveOrderItemPicTest extends TestCase
{
    public function testPrefersItemCartPicsOverMainPics(): void
    {
        $orderInterface = new ShopadminNormalOrderService();
        $orderInterface->itemCart = [
            100 => ['pics' => 'https://spec-from-cart.jpg'],
        ];
        $ref = new ReflectionMethod(OrderService::class, 'resolveOrderItemPic');
        $ref->setAccessible(true);
        $svc = new OrderService($orderInterface);
        $pic = $ref->invoke($svc, [
            'itemId' => 100,
            'pics' => ['https://main.jpg'],
        ]);
        $this->assertSame('https://spec-from-cart.jpg', $pic);
    }

    public function testFormatNormalOrderItemUsesResolveOrderItemPic(): void
    {
        $ref = new ReflectionMethod(OrderService::class, '__formatNormalOrderItem');
        $lines = file($ref->getFileName(), FILE_IGNORE_NEW_LINES);
        $slice = implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
        $this->assertStringContainsString('resolveOrderItemPic', $slice);
    }
}
