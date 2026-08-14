<?php

declare(strict_types=1);

namespace Tests\OrdersBundle;

use OrdersBundle\Services\Orders\CommunityNormalOrderService;

/**
 * 社区订单 community_info.activity_trade_no null 规范为空串 — TC1–TC3
 * 计划：.tasks/plans/community-activity-trade-no-empty.md
 */
class CommunityActivityTradeNoEmptyTest extends \TestCase
{
    /**
     * TC1 / AC1：列表项 activity_trade_no 为 null 时响应为 ''。
     * #given community_info 含 activity_trade_no => null
     * #when normalizeCommunityInfoTradeNo
     * #then activity_trade_no 为 ''
     */
    public function testNormalizeCommunityInfoTradeNoNullToEmptyString(): void
    {
        $svc = new CommunityNormalOrderService();
        $result = $svc->normalizeCommunityInfoTradeNo(['activity_trade_no' => null]);

        $this->assertSame('', $result['activity_trade_no']);
    }

    /**
     * TC2 / AC2：activity_trade_no 有数值时保持不变。
     * #given community_info 含 activity_trade_no => 3
     * #when normalizeCommunityInfoTradeNo
     * #then activity_trade_no 仍为 3
     */
    public function testNormalizeCommunityInfoTradeNoPreservesNumericValue(): void
    {
        $svc = new CommunityNormalOrderService();
        $result = $svc->normalizeCommunityInfoTradeNo(['activity_trade_no' => 3]);

        $this->assertSame(3, $result['activity_trade_no']);
    }

    /**
     * TC3 / AC3：详情 community_info 未设置 activity_trade_no 时响应为 ''。
     * #given community_info 无 activity_trade_no 键（详情 rel 未带跟团号）
     * #when normalizeCommunityInfoTradeNo
     * #then activity_trade_no 为 ''
     */
    public function testNormalizeCommunityInfoTradeNoForOrderDetailWhenKeyMissing(): void
    {
        $svc = new CommunityNormalOrderService();
        $result = $svc->normalizeCommunityInfoTradeNo(['order_id' => 1001]);

        $this->assertSame('', $result['activity_trade_no']);
    }
}
