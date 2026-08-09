<?php

declare(strict_types=1);

namespace Tests\PaymentBundle;

use Mockery;
use PaymentBundle\Services\Payments\PointPayService;

/**
 * 积分商城 PointPay 跳过二次扣减 — TC2（RED）
 * 计划：.tasks/plans/pointsmall-mix-pay-deduct-at-order.md
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PointPayPointsmallSkipDeductTest extends \TestCase
{
    /**
     * 最小 Lumen 容器，避免 bootstrap/app.php 触发 Doctrine DB 连接。
     */
    public function createApplication()
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->withFacades();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRegistryConnection();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * TC2 / AC2：积分商城 PointPay 跳过扣积分与余额校验，仍交易成功。
     * #given trade_source_type=normal_pointsmall，本地积分余额刚好等于 pay_fee（下单已扣后剩余）
     * #when 调用 PointPayService::doPay
     * #then 不调用 addPoint、不抛「积分不足」、返回 pay_status=true 且 updateStatus(SUCCESS)
     */
    public function testPointPaySkipsDeductForPointsmallAndSucceeds(): void
    {
        #given 纯积分自动支付入参（下单阶段已扣 order.point，余额刚好等于 pay_fee）
        $userId = 1001;
        $companyId = 1;
        $payFee = 500;
        $tradeId = 'T202608070001';
        $orderId = 'PS202608070001';
        $data = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'pay_fee' => $payFee,
            'trade_id' => $tradeId,
            'order_id' => $orderId,
            'trade_source_type' => 'normal_pointsmall',
        ];

        $pointMemberMock = Mockery::mock('overload:PointBundle\Services\PointMemberService');
        $pointMemberMock->shouldReceive('getInfo')
            ->once()
            ->with(['user_id' => $userId, 'company_id' => $companyId])
            ->andReturn(['point' => $payFee]);
        $pointMemberMock->shouldReceive('addPoint')->never();

        $dmCrmMock = Mockery::mock('overload:ThirdPartyBundle\Services\DmCrm\DmCrmSettingService');
        $dmCrmMock->shouldReceive('getDmCrmSetting')
            ->once()
            ->with($companyId)
            ->andReturn(['is_open' => '']);

        $depositTradeMock = Mockery::mock('overload:DepositBundle\Services\DepositTrade');
        $depositTradeMock->shouldReceive('getUserDepositTotal')
            ->once()
            ->with($companyId, $userId)
            ->andReturn(999999);

        $ruleMock = Mockery::mock('overload:PointBundle\Services\PointMemberRuleService');
        $ruleMock->shouldReceive('getUsePointRule')
            ->once()
            ->with($companyId)
            ->andReturn(0);

        $tradeMock = Mockery::mock('overload:OrdersBundle\Services\TradeService');
        $tradeMock->shouldReceive('updateStatus')
            ->once()
            ->with(
                $tradeId,
                'SUCCESS',
                Mockery::on(function (array $options): bool {
                    return ($options['bank_type'] ?? '') === '积分'
                        && ($options['pay_type'] ?? '') === 'point';
                })
            );

        #when 积分商城纯积分自动 PointPay
        $service = new PointPayService();
        $result = $service->doPay('', '', $data);

        #then 支付成功且不二次扣减
        $this->assertSame(['pay_status' => true], $result);
    }

    private function mockRegistryConnection(): void
    {
        $mockConn = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['beginTransaction', 'commit', 'rollback'])
            ->getMock();
        $mockConn->method('beginTransaction')->willReturn(null);
        $mockConn->method('commit')->willReturn(null);
        $mockConn->method('rollback')->willReturn(null);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getManager', 'getConnection'])
            ->getMock();
        $mockRegistry->method('getConnection')->with('default')->willReturn($mockConn);

        $this->app->instance('registry', $mockRegistry);
    }
}
