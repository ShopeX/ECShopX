<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 订单出站 platform Header（小写）：恒为 offline。
 */
final class ShuyunOpenPlatformOrderPlatformResolver
{
    public const LOG_CHANNEL = 'shuyun_open_platform';

    /**
     * @param  non-empty-string  $orderClass  商城 orders_normal_orders.order_class 等原始值
     * @return non-empty-string  网关 Header `platform`（小写）
     */
    public function resolvePlatformHeaderForOrderClass(string $orderClass): string
    {
        return 'offline';
    }
}
