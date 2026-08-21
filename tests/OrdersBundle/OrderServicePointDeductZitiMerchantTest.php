<?php

declare(strict_types=1);

namespace Tests\OrdersBundle;

use MembersBundle\Services\MemberService;
use Mockery;
use OrdersBundle\Interfaces\OrderInterface;
use OrdersBundle\Services\OrderService;

/**
 * OrderService::_formatOrderPointDeduct 积分计算逻辑单元测试。
 *
 * 聚焦自提（ziti）与商家自配送（merchant）两个配送方式的积分字段：
 *   - max_point_ziti：自提最大可抵扣积分，自提关闭时为 0
 *   - max_point_merchant：商家自配送最大可抵扣积分，商家自配送关闭时为 0
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class OrderServicePointDeductZitiMerchantTest extends \TestCase
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
        $this->mockShuyunMemberService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testZitiOnReturnsZitiPointAndZeroMerchant(): void
    {
        $result = $this->runFormatOrderPointDeduct(
            $this->makeOrderData(true, false),
            $this->makeUsePoint()
        );

        $this->assertSame(600, $result['max_point_ziti']);
        $this->assertSame(0, $result['max_point_merchant']);
        $this->assertTrue($result['is_open_deduct_point']);
    }

    public function testMerchantOnReturnsMerchantPointAndZeroZiti(): void
    {
        $result = $this->runFormatOrderPointDeduct(
            $this->makeOrderData(false, true),
            $this->makeUsePoint()
        );

        $this->assertSame(0, $result['max_point_ziti']);
        $this->assertSame(650, $result['max_point_merchant']);
    }

    public function testBothOnReturnsBothPoints(): void
    {
        $result = $this->runFormatOrderPointDeduct(
            $this->makeOrderData(true, true),
            $this->makeUsePoint()
        );

        $this->assertSame(600, $result['max_point_ziti']);
        $this->assertSame(650, $result['max_point_merchant']);
    }

    public function testBothOffReturnsZeroPoints(): void
    {
        $result = $this->runFormatOrderPointDeduct(
            $this->makeOrderData(false, false),
            $this->makeUsePoint()
        );

        $this->assertSame(0, $result['max_point_ziti']);
        $this->assertSame(0, $result['max_point_merchant']);
    }

    public function testZitiPointFromTryMaxPointWhenAvailable(): void
    {
        $result = $this->runFormatOrderPointDeduct(
            $this->makeOrderData(true, true),
            $this->makeUsePoint(),
            ['real_use_point' => 777, 'real_use_point_ziti' => 666]
        );

        $this->assertSame(777, $result['max_point']);
        $this->assertSame(666, $result['max_point_ziti']);
        $this->assertSame(650, $result['max_point_merchant']);
    }

    /**
     * @param array<string,mixed> $orderData
     * @param array<string,mixed> $usePoint  orderMaxPoint 返回值
     * @param array<string,mixed> $tryMaxPoint getPointDeduction 返回值，空数组表示走 else 分支
     * @return array<string,mixed>
     */
    private function runFormatOrderPointDeduct(array $orderData, array $usePoint, array $tryMaxPoint = []): array
    {
        $companyId = $orderData['company_id'];
        $userId = $orderData['user_id'];

        $pointMemberMock = Mockery::mock('overload:PointBundle\Services\PointMemberService');
        $pointMemberMock->shouldReceive('getInfo')
            ->andReturn(['point' => 1000]);

        $ruleMock = Mockery::mock('overload:PointBundle\Services\PointMemberRuleService');
        $ruleMock->shouldReceive('getPointRule')
            ->andReturn([
                'isOpenMemberPoint' => 'true',
                'isOpenDeductPoint' => 'true',
                'deduct_proportion_limit' => 100,
                'deduct_point' => 1,
                'can_deduct_freight' => 1,
            ]);
        $ruleMock->shouldReceive('orderMaxPoint')
            ->andReturn($usePoint);

        // 非积分翻倍活动
        $upvaluationMock = Mockery::mock('overload:PromotionsBundle\Services\PointupvaluationActivityService');
        $upvaluationMock->shouldReceive('getEligibleActivity')
            ->andReturn([]);

        $orderInterface = Mockery::mock(OrderInterface::class);
        $svc = Mockery::mock(OrderService::class, [$orderInterface]);
        $svc->makePartial();
        $svc->shouldReceive('getPointDeduction')
            ->andReturn($tryMaxPoint);

        $params = [
            'company_id' => $companyId,
            'user_id' => $userId,
            'pay_type' => '',
            'point_use' => 0,
        ];

        return $svc->_formatOrderPointDeduct($params, $orderData);
    }

    /**
     * @return array<string,mixed>
     */
    private function makeOrderData(bool $isZiti, bool $isSelfDelivery): array
    {
        return [
            'company_id' => 1,
            'user_id' => 1001,
            'total_fee' => 10000,
            'freight_fee' => 500,
            'is_ziti' => $isZiti,
            'is_self_delivery' => $isSelfDelivery,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function makeUsePoint(): array
    {
        return [
            'limit_point' => 800,
            'max_point' => 700,
            'max_point_ziti' => 600,
            'max_point_merchant' => 650,
            'max_money' => 10000,
        ];
    }

    private function mockShuyunMemberService(): void
    {
        $memberService = Mockery::mock(MemberService::class);
        $memberService->shouldReceive('isShuyunOpenPlatformMemberEnabled')
            ->andReturn(false);
        $this->app->instance(MemberService::class, $memberService);
    }
}
