<?php

declare(strict_types=1);

namespace Tests\PointBundle;

use Dingo\Api\Exception\ResourceException;
use PointBundle\Services\PointMemberShuyunOpenPlatformPointWriteService;

/**
 * 计划 T-POINT-12：addPoint → point.change 字段映射与门禁（与 T-POINT-04/05 网关单测互补）。
 */
class PointMemberShuyunOpenPlatformPointWriteServiceTest extends \TestCase
{
    /** T-POINT-12：订单积分抵扣（journal 6 扣减 + 订单号）→ CONSUME + operator=user_id */
    public function testTPoint12OrderPointDeductionPayloadUsesConsumeAndMemberOperator(): void
    {
        config(['shuyun_open_platform.default_plat_code' => 'zdy1']);

        $payload = PointMemberShuyunOpenPlatformPointWriteService::buildChangePayload(
            88001,
            1,
            50,
            false,
            6,
            '订单抵扣',
            'ORD-2026-001',
            [],
            [
                'user_id' => 88001,
                'reg_distributor' => 9301,
            ]
        );

        $this->assertSame('OFFLINE', $payload['platCode']);
        $this->assertSame('88001', $payload['id']);
        $this->assertSame('9301-off', $payload['shopId']);
        $this->assertSame('CONSUME', $payload['source']);
        $this->assertSame(-50, $payload['changePoint']);
        $this->assertSame('88001', $payload['operator']);
        $this->assertSame('订单抵扣', $payload['desc']);
        $this->assertStringStartsWith('sxop_', $payload['sequence']);
    }

    public function testCancelReturnUsesRefundAndSystemOperator(): void
    {
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);

        $payload = PointMemberShuyunOpenPlatformPointWriteService::buildChangePayload(
            1,
            1,
            100,
            true,
            9,
            '取消返还',
            'ORD-X',
            [],
            ['user_id' => 1, 'reg_distributor' => 2]
        );

        $this->assertSame('REFUND', $payload['source']);
        $this->assertSame(100, $payload['changePoint']);
        $this->assertSame('system', $payload['operator']);
    }

    public function testRegisterGiftUsesMarket(): void
    {
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);

        $payload = PointMemberShuyunOpenPlatformPointWriteService::buildChangePayload(
            1,
            1,
            10,
            true,
            1,
            '注册赠送积分',
            '',
            [],
            ['user_id' => 1, 'reg_distributor' => 3]
        );

        $this->assertSame('MARKET', $payload['source']);
        $this->assertSame('system', $payload['operator']);
    }

    public function testShuyunSequenceOverride(): void
    {
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);

        $payload = PointMemberShuyunOpenPlatformPointWriteService::buildChangePayload(
            1,
            1,
            1,
            true,
            1,
            'x',
            '',
            ['shuyun_sequence' => 'fixed-seq-001'],
            ['user_id' => 1, 'reg_distributor' => 1]
        );

        $this->assertSame('fixed-seq-001', $payload['sequence']);
    }

    public function testMissingRegDistributorThrows(): void
    {
        config(['shuyun_open_platform.default_plat_code' => 'OFFLINE']);

        $this->expectException(ResourceException::class);
        PointMemberShuyunOpenPlatformPointWriteService::buildChangePayload(
            1,
            1,
            1,
            true,
            1,
            'x',
            '',
            [],
            ['user_id' => 1, 'reg_distributor' => 0]
        );
    }

    public function testResolveSourceAndOperatorPure(): void
    {
        $this->assertSame('CONSUME', PointMemberShuyunOpenPlatformPointWriteService::resolveSource(6, false));
        $this->assertSame('REFUND', PointMemberShuyunOpenPlatformPointWriteService::resolveSource(9, true));
        $this->assertSame('88001', PointMemberShuyunOpenPlatformPointWriteService::resolveOperator(88001, 6, false, 'O1'));
        $this->assertSame('system', PointMemberShuyunOpenPlatformPointWriteService::resolveOperator(88001, 6, false, ''));
    }

    /** 开放网关 + 扣减 + 非零分：跳过本地 point_member 余额闸门 */
    public function testSkipsLocalPointMemberBalanceAfterOpenGatewayDeductWhenOpenPlatformAndDecrease(): void
    {
        $this->assertTrue(PointMemberShuyunOpenPlatformPointWriteService::skipsLocalPointMemberBalanceAfterOpenGatewayDeduct(true, false, 10));
    }

    public function testDoesNotSkipLocalBalanceWhenOpenPlatformIncrease(): void
    {
        $this->assertFalse(PointMemberShuyunOpenPlatformPointWriteService::skipsLocalPointMemberBalanceAfterOpenGatewayDeduct(true, true, 10));
    }

    public function testDoesNotSkipLocalBalanceWhenNotOpenPlatform(): void
    {
        $this->assertFalse(PointMemberShuyunOpenPlatformPointWriteService::skipsLocalPointMemberBalanceAfterOpenGatewayDeduct(false, false, 10));
    }

    public function testDoesNotSkipLocalBalanceWhenPointZero(): void
    {
        $this->assertFalse(PointMemberShuyunOpenPlatformPointWriteService::skipsLocalPointMemberBalanceAfterOpenGatewayDeduct(true, false, 0));
    }

    /** 开放网关积分变更恒 OFFLINE，shopId 经 GatewayShopIdResolver（config 后缀） */
    public function testBuildChangePayloadUsesConfiguredSuffixForShopId(): void
    {
        config([
            'shuyun_open_platform.default_plat_code' => 'NNORMALDTCUAT',
            'shuyun_open_platform.offline_plat_id_suffix' => '-offline',
        ]);
        $payload = PointMemberShuyunOpenPlatformPointWriteService::buildChangePayload(
            41,
            1,
            99,
            true,
            1,
            '注册赠送积分',
            '',
            ['shuyun_open_point_change_force_offline_plat' => true],
            ['user_id' => 41, 'reg_distributor' => 5]
        );

        $this->assertSame('OFFLINE', $payload['platCode']);
        $this->assertSame('5-offline', $payload['shopId']);
        $this->assertSame('MARKET', $payload['source']);
    }

    public function testBuildChangePayloadIgnoresDefaultPlatCode(): void
    {
        config([
            'shuyun_open_platform.default_plat_code' => 'nnormaldtcuat',
            'shuyun_open_platform.offline_plat_id_suffix' => '-offline',
        ]);
        $payload = PointMemberShuyunOpenPlatformPointWriteService::buildChangePayload(
            41,
            1,
            99,
            true,
            1,
            '注册赠送积分',
            '',
            [],
            ['user_id' => 41, 'reg_distributor' => 5]
        );

        $this->assertSame('OFFLINE', $payload['platCode']);
        $this->assertSame('5-offline', $payload['shopId']);
    }

    public function testBuildChangePayloadExplicitFalseForceOfflineStillOffline(): void
    {
        config(['shuyun_open_platform.default_plat_code' => 'Z1']);
        $payload = PointMemberShuyunOpenPlatformPointWriteService::buildChangePayload(
            1,
            1,
            1,
            true,
            1,
            'x',
            '',
            ['shuyun_open_point_change_force_offline_plat' => false],
            ['user_id' => 1, 'reg_distributor' => 5]
        );

        $this->assertSame('OFFLINE', $payload['platCode']);
        $this->assertSame('5-off', $payload['shopId']);
    }
}
