<?php

declare(strict_types=1);

use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Repositories\NormalOrdersRepository;

/**
 * ECX-10762：getServiceOrderData 计算 auto_cancel_seconds 时不得因 string-int 运算抛 TypeError。
 */
class NormalOrdersAutoCancelSecondsTypeTest extends TestCase
{
    public function testEmptyAutoCancelTimeDoesNotThrowTypeError(): void
    {
        $repo = (new ReflectionClass(NormalOrdersRepository::class))->newInstanceWithoutConstructor();
        $order = new NormalOrders();
        $order->setAutoCancelTime('');

        $before = time();
        $result = $repo->getServiceOrderData($order);
        $after = time();

        $this->assertIsInt($result['auto_cancel_seconds']);
        // 空串转为 0 后：0 - time() ≈ -当前时间戳
        $this->assertLessThanOrEqual(0 - $before, $result['auto_cancel_seconds']);
        $this->assertGreaterThanOrEqual(0 - $after, $result['auto_cancel_seconds']);
    }

    public function testNumericAutoCancelTimeComputesRemainingSeconds(): void
    {
        $repo = (new ReflectionClass(NormalOrdersRepository::class))->newInstanceWithoutConstructor();
        $order = new NormalOrders();
        $deadline = time() + 600;
        $order->setAutoCancelTime((string) $deadline);

        $result = $repo->getServiceOrderData($order);

        $this->assertIsInt($result['auto_cancel_seconds']);
        $this->assertGreaterThan(590, $result['auto_cancel_seconds']);
        $this->assertLessThanOrEqual(600, $result['auto_cancel_seconds']);
    }
}
