<?php

declare(strict_types=1);

namespace Tests\OrdersBundle;

use Dingo\Api\Exception\ResourceException;
use Mockery;
use OrdersBundle\Services\Orders\PointsmallNormalOrderService;
use PointBundle\Services\PointMemberService;

/**
 * 积分商城下单扣 order.point — TC1/TC3/TC4/TC5
 * 计划：.tasks/plans/pointsmall-mix-pay-deduct-at-order.md
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PointsmallOrderCreateDeductPointTest extends \TestCase
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
        $this->mockRegistryForOrderService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * TC1 / AC1：混合下单 point>0 时扣减一次 order.point。
     * #given orderData 含 point>0、user_id、company_id、order_id（混合：total_fee>0）
     * #when 调用 PointsmallNormalOrderService::beforeOrderCreateCommit
     * #then addPoint 调用一次，journalType=6，points_off_cash，文案「购物扣减积分」
     */
    public function testPointsmallOrderCreateDeductsOrderPointOnce(): void
    {
        #given 混合支付订单 orderData
        $userId = 1001;
        $companyId = 1;
        $orderId = 'PS202608070001';
        $point = 500;
        $orderData = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'order_id' => $orderId,
            'point' => $point,
            'total_fee' => 9900,
            'order_class' => 'pointsmall',
        ];
        $params = [];

        $pointMemberMock = Mockery::mock('overload:PointBundle\Services\PointMemberService');
        $pointMemberMock->shouldReceive('addPoint')
            ->once()
            ->with(
                $userId,
                $companyId,
                $point,
                6,
                false,
                '购物扣减积分',
                $orderId,
                ['point_type' => 'points_off_cash']
            )
            ->andReturn(true);

        #when 事务提交前钩子扣减积分
        $service = new PointsmallNormalOrderService();
        $service->beforeOrderCreateCommit($orderData, $params);

        #then Mockery 断言 addPoint 被调用一次
        $this->addToAssertionCount(1);
    }

    /**
     * TC2 / AC3：落单强制 point_use=0，type=6 仅扣一次（beforeOrderCreateCommit）。
     * #given 请求 point_use=309、order.point=309、order_class=pointsmall、pay_type=offline_pay
     * #when 模拟 OrderService L942 赋值 + L263 扣减 + beforeOrderCreateCommit
     * #then 落单 point_use=0；addPoint(journalType=6) 恰好 1 次，金额为 order.point
     */
    public function testPointsmallCreateForcesPointUseZeroAndDeductsOnce(): void
    {
        #given 双扣复现场景的积分商城落单参数
        $userId = 1001;
        $companyId = 1;
        $orderId = '5332580000270002';
        $point = 309;
        $params = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'point_use' => $point,
            'pay_type' => 'offline_pay',
        ];
        $orderClass = 'pointsmall';

        $pointUseForOrder = $this->resolveProductionPointUseForOrder($params, $orderClass);
        $this->assertSame(0, $pointUseForOrder, 'pointsmall 落单应强制 point_use=0');

        $orderData = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'order_id' => $orderId,
            'point' => $point,
            'point_use' => $pointUseForOrder,
            'pay_type' => 'offline_pay',
            'order_class' => $orderClass,
        ];

        $addPointCalls = 0;
        $pointMemberMock = Mockery::mock('overload:PointBundle\Services\PointMemberService');
        $pointMemberMock->shouldReceive('addPoint')
            ->andReturnUsing(function (
                $uid,
                $cid,
                $points,
                $journalType,
                $incr,
                $memo,
                $oid,
                $otherParams
            ) use (
                &$addPointCalls,
                $userId,
                $companyId,
                $point,
                $orderId
            ) {
                $addPointCalls++;
                $this->assertSame($userId, $uid);
                $this->assertSame($companyId, $cid);
                $this->assertSame($point, $points);
                $this->assertSame(6, $journalType);
                $this->assertFalse($incr);
                $this->assertSame('购物扣减积分', $memo);
                $this->assertSame($orderId, $oid);
                $this->assertSame(['point_type' => 'points_off_cash'], $otherParams);

                return true;
            });

        #when 模拟 OrderService 落单扣减路径（L263 + beforeOrderCreateCommit）
        if ($this->productionLine263WouldDeduct($orderData)) {
            $otherParams = ['point_type' => 'points_off_cash'];
            (new PointMemberService())->addPoint(
                $orderData['user_id'],
                $orderData['company_id'],
                $orderData['point_use'],
                6,
                false,
                '购物扣减积分',
                $orderData['order_id'],
                $otherParams
            );
        }
        $service = new PointsmallNormalOrderService();
        $service->beforeOrderCreateCommit($orderData, $params);

        #then type=6 扣减恰好 1 次
        $this->assertSame(1, $addPointCalls);
    }

    /**
     * TC3 / AC3：会员积分不足时下单校验失败。
     * #given order.point=500，会员余额=100
     * #when 调用 formatOrderData(..., $isCheck=true)
     * #then 抛出 ResourceException，不调用 addPoint
     */
    public function testPointsmallOrderCreateFailsWhenPointInsufficient(): void
    {
        #given 积分不足以支付订单
        $userId = 1001;
        $companyId = 1;
        $orderPoint = 500;
        $memberPoint = 100;
        $orderData = [
            'point' => $orderPoint,
        ];
        $params = [
            'user_id' => $userId,
            'company_id' => $companyId,
        ];

        $pointMemberMock = Mockery::mock('overload:PointBundle\Services\PointMemberService');
        $pointMemberMock->shouldReceive('getInfo')
            ->once()
            ->with(['user_id' => $userId, 'company_id' => $companyId])
            ->andReturn(['point' => $memberPoint]);
        $pointMemberMock->shouldReceive('addPoint')->never();

        #when 下单前校验积分
        $service = new PointsmallNormalOrderService();
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('当前积分不足以支付本次订单费用!');
        $service->formatOrderData($orderData, $params, true);

        #then 异常抛出，无扣减
    }

    /**
     * TC1 / AC1：NOTPAY 手动取消时 type=9 返还恰好 1 次（order.point）。
     * #given order_class=pointsmall、point=309、point_use=309、pay_type=offline_pay
     * #when 模拟 parent::__noPayOrderCancel + 子类 backPoint（cancelOrder NOTPAY 路径）
     * #then addPoint(..., 9, true, ...) 仅 1 次，金额为 order.point
     */
    public function testPointsmallCancelNotPayRefundsOrderPointOnce(): void
    {
        #given 双退复现场景的 NOTPAY 积分商城订单
        $userId = 1001;
        $companyId = 1;
        $orderId = '5332580000270002';
        $point = 309;
        $orderInfo = [
            'order_id' => $orderId,
            'user_id' => $userId,
            'company_id' => $companyId,
            'distributor_id' => 0,
            'order_type' => 'normal',
            'order_status' => 'NOTPAY',
            'cancel_status' => 'NO_APPLY_CANCEL',
            'delivery_status' => 'PENDING',
            'order_class' => 'pointsmall',
            'point' => $point,
            'point_use' => $point,
            'pay_type' => 'offline_pay',
            'total_fee' => 9900,
        ];
        $params = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'cancel_from' => 'buyer',
        ];

        $addPointCalls = 0;
        $pointMemberMock = Mockery::mock('overload:PointBundle\Services\PointMemberService');
        $pointMemberMock->shouldReceive('cancelOrderReturnBackPoints')->never();
        $pointMemberMock->shouldReceive('addPoint')
            ->andReturnUsing(function (
                $uid,
                $cid,
                $points,
                $journalType,
                $incr,
                $memo,
                $oid,
                $otherParams
            ) use (&$addPointCalls, $userId, $companyId, $point, $orderId) {
                $addPointCalls++;
                $this->assertSame($userId, $uid);
                $this->assertSame($companyId, $cid);
                $this->assertSame($point, $points);
                $this->assertSame(9, $journalType);
                $this->assertTrue($incr);
                $this->assertSame('取消订单' . $orderId . '返还', $memo);
                $this->assertSame($orderId, $oid);
                $this->assertSame(['point_type' => 'points_refund'], $otherParams);

                return true;
            });

        #when 父类未付取消 + 子类 backPoint（与 cancelOrder NOTPAY 一致）
        $service = new PointsmallNormalOrderService();
        $service->__noPayOrderCancel($orderInfo, $params);
        $service->backPoint($orderInfo);

        #then type=9 返还恰好 1 次
        $this->assertSame(1, $addPointCalls);
    }

    /**
     * TC4 / AC4：未付取消时 backPoint 按 order.point 返还积分。
     * #given 含 order.point 的订单数据
     * #when 调用 backPoint
     * #then addPoint(..., journalType=9, true, ...) 返还一次
     */
    public function testPointsmallCancelNotPayReturnsOrderPoint(): void
    {
        #given NOTPAY 取消需退还的订单
        $order = [
            'user_id' => 1001,
            'company_id' => 1,
            'order_id' => 'PS202608070001',
            'point' => 500,
        ];

        $pointMemberMock = Mockery::mock('overload:PointBundle\Services\PointMemberService');
        $pointMemberMock->shouldReceive('addPoint')
            ->once()
            ->with(
                $order['user_id'],
                $order['company_id'],
                $order['point'],
                9,
                true,
                '取消订单' . $order['order_id'] . '返还',
                $order['order_id'],
                ['point_type' => 'points_refund']
            )
            ->andReturn(true);

        #when 取消订单退还积分
        $service = new PointsmallNormalOrderService();
        $service->backPoint($order);

        #then addPoint 返还一次
        $this->addToAssertionCount(1);
    }

    /**
     * TC5 / AC5：order.point=0 时 beforeOrderCreateCommit 不扣减。
     * #given orderData point=0
     * #when 调用 beforeOrderCreateCommit
     * #then 不调用 addPoint
     */
    public function testPointsmallOrderCreateSkipsDeductWhenPointZero(): void
    {
        #given point=0 的订单
        $orderData = [
            'user_id' => 1001,
            'company_id' => 1,
            'order_id' => 'PS202608070001',
            'point' => 0,
        ];
        $params = [];

        $pointMemberMock = Mockery::mock('overload:PointBundle\Services\PointMemberService');
        $pointMemberMock->shouldReceive('addPoint')->never();

        #when 事务提交前钩子
        $service = new PointsmallNormalOrderService();
        $service->beforeOrderCreateCommit($orderData, $params);

        #then 不扣减
        $this->addToAssertionCount(1);
    }

    /**
     * 读取 OrderService L942，镜像落单 point_use 赋值逻辑。
     *
     * @param array<string,mixed> $params
     */
    private function resolveProductionPointUseForOrder(array $params, string $orderClass): int
    {
        $line = $this->orderServiceLine(942);
        if (strpos($line, 'pointsmall') !== false && strpos($line, '? 0 :') !== false) {
            return ($orderClass === 'pointsmall') ? 0 : (int) ($params['point_use'] ?? 0);
        }

        return (int) ($params['point_use'] ?? 0);
    }

    /**
     * 读取 OrderService L263，判断是否会按 point_use 扣减。
     *
     * @param array<string,mixed> $orderData
     */
    private function productionLine263WouldDeduct(array $orderData): bool
    {
        $line = $this->orderServiceLine(263);
        $base = !empty($orderData['point_use']) && ($orderData['pay_type'] ?? '') !== 'point';
        if (strpos($line, 'pointsmall') !== false) {
            return $base && ($orderData['order_class'] ?? '') !== 'pointsmall';
        }

        return $base;
    }

    private function orderServiceLine(int $lineNumber): string
    {
        $path = dirname(__DIR__, 2) . '/src/OrdersBundle/Services/OrderService.php';
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);

        return trim($lines[$lineNumber - 1] ?? '');
    }

    private function mockRegistryForOrderService(): void
    {
        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['create', 'getList', 'count', 'get'])
            ->getMock();
        $mockRepo->method('create')->willReturn(['cancel_id' => 1]);

        $mockManager = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getRepository'])
            ->getMock();
        $mockManager->method('getRepository')->willReturn($mockRepo);

        $mockRegistry = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getManager'])
            ->getMock();
        $mockRegistry->method('getManager')->with('default')->willReturn($mockManager);

        $this->app->instance('registry', $mockRegistry);
    }
}
