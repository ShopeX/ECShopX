<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 订单域开放网关 Action 真源（trade / refund），供服务层与 Job 引用，避免魔法字符串分散。
 */
final class ShuyunOpenPlatformOrderGatewayActions
{
    public const GATEWAY_ACTION_TRADE_SYNC = 'shuyun.base.trade.sync';

    public const GATEWAY_ACTION_REFUND_SYNC = 'shuyun.base.refund.sync';

    private function __construct()
    {
    }
}
