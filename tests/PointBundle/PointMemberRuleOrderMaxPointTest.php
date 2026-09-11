<?php

declare(strict_types=1);

namespace Tests\PointBundle;

use Mockery;
use PointBundle\Services\PointMemberRuleService;

/**
 * orderMaxPoint 与 moneyOutLimit 应对齐，避免结算 max_point 大于下单可抵扣积分。
 */
class PointMemberRuleOrderMaxPointTest extends \TestCase
{
    private const COMPANY_ID = 38;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 分段 ceil 曾导致 max_point=151 但 moneyOutLimit(151) 失败（total_fee=2999, 50%, deduct_point=10）。
     */
    public function testOrderMaxPointNeverExceedsMoneyOutLimitWithFreightDeduct(): void
    {
        $this->mockPointRule([
            'isOpenMemberPoint' => 'true',
            'isOpenDeductPoint' => 'true',
            'deduct_proportion_limit' => 50,
            'deduct_point' => 10,
            'can_deduct_freight' => 1,
        ]);

        $service = new PointMemberRuleService(self::COMPANY_ID);
        $orderData = [
            'total_fee' => 2999,
            'freight_fee' => 10,
        ];

        $result = $service->orderMaxPoint(self::COMPANY_ID, 10000, 2999, $orderData, 10);

        $this->assertFalse($service->moneyOutLimit(151, 2999));
        $this->assertTrue($service->moneyOutLimit($result['max_point'], 2999));
        $this->assertSame(150, $result['max_point']);
    }

    /**
     * @param array<string,mixed> $rule
     */
    private function mockPointRule(array $rule): void
    {
        $payload = json_encode($rule);
        $mockConnection = Mockery::mock();
        $mockConnection->shouldReceive('get')->andReturn($payload);

        $mockRedis = Mockery::mock();
        $mockRedis->shouldReceive('connection')->with('default')->andReturn($mockConnection);

        $this->app->instance('redis', $mockRedis);
    }
}
