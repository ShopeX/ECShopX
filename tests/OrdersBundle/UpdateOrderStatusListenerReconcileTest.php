<?php

declare(strict_types=1);

namespace Tests\OrdersBundle;

use AftersalesBundle\Services\AftersalesRefundService;
use OrdersBundle\Listeners\UpdateOrderStatusListener;

/**
 * ECX-11406：TradeFinish 对账状态机（Spec §7.1–4、建单幂等 §7.9）。
 */
class UpdateOrderStatusListenerReconcileTest extends \TestCase
{
    private UpdateOrderStatusListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new UpdateOrderStatusListener();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testNotPayWithinAutoCancelWindowIsPayable(): void
    {
        $branch = $this->invokeResolve([
            'order_status' => 'NOTPAY',
            'auto_cancel_time' => time() + 3600,
            'company_id' => 1,
            'order_id' => '5412622000140002',
        ], '5412622000130002');

        $this->assertSame('payable', $branch);
    }

    public function testPartPaymentIsPayableRegardlessOfAutoCancel(): void
    {
        $branch = $this->invokeResolve([
            'order_status' => 'PART_PAYMENT',
            'auto_cancel_time' => time() - 1,
            'company_id' => 1,
            'order_id' => '5412622000140002',
        ], '5412622000130002');

        $this->assertSame('payable', $branch);
    }

    public function testNotPayTimedOutCreatesPayAfterCancelBranch(): void
    {
        $now = time();
        $this->assertTrue($this->invokeIsNotPayTimedOut([
            'order_status' => 'NOTPAY',
            'auto_cancel_time' => $now - 60,
        ], $now));

        $branch = $this->invokeResolve([
            'order_status' => 'NOTPAY',
            'auto_cancel_time' => $now - 60,
            'company_id' => 1,
            'order_id' => '5412622000140002',
        ], '5412622000130002');

        $this->assertSame('pay_after_cancel', $branch);
    }

    public function testCancelOrderCreatesPayAfterCancelBranch(): void
    {
        $branch = $this->invokeResolve([
            'order_status' => 'CANCEL',
            'company_id' => 1,
            'order_id' => '5412622000140002',
        ], '5412622000130002');

        $this->assertSame('pay_after_cancel', $branch);
    }

    public function testPostPaidSameTradeIgnoresWhenNoOtherSuccessTrade(): void
    {
        $listener = new class extends UpdateOrderStatusListener {
            protected function hasOtherSuccessfulNonPointTrade(int $companyId, string $orderId, string $excludeTradeId): bool
            {
                return false;
            }

            public function exposeResolve(array $order, string $tradeId): string
            {
                return $this->resolveReconcileBranch($order, $tradeId);
            }
        };

        $branch = $listener->exposeResolve([
            'order_status' => 'PAYED',
            'company_id' => 1,
            'order_id' => '5412622000140002',
        ], '5412622000130002');

        $this->assertSame('same_trade_ignore', $branch);
    }

    public function testPostPaidOtherSuccessTradeCreatesDupPayBranch(): void
    {
        $listener = new class extends UpdateOrderStatusListener {
            protected function hasOtherSuccessfulNonPointTrade(int $companyId, string $orderId, string $excludeTradeId): bool
            {
                return true;
            }

            public function exposeResolve(array $order, string $tradeId): string
            {
                return $this->resolveReconcileBranch($order, $tradeId);
            }
        };

        $tradeId = '5412622000130003';
        $branch = $listener->exposeResolve([
            'order_status' => 'DONE',
            'company_id' => 1,
            'order_id' => '5412622000140002',
        ], $tradeId);

        $this->assertSame('dup_pay', $branch);
    }

    public function testHasOtherSuccessfulNonPointTradeIgnoresPointAndSelf(): void
    {
        $tradeRepo = \Mockery::mock();
        $tradeRepo->shouldReceive('lists')
            ->once()
            ->andReturn(['list' => [
                ['trade_id' => '5412622000130003', 'pay_type' => 'wxpayjs'],
                ['trade_id' => '5412622000130099', 'pay_type' => 'point'],
            ]]);

        $em = \Mockery::mock();
        $em->shouldReceive('getRepository')->andReturn($tradeRepo);

        $registry = \Mockery::mock();
        $registry->shouldReceive('getManager')->with('default')->andReturn($em);
        app()->instance('registry', $registry);

        $hasOther = $this->invokeHasOther(1, '5412622000140002', '5412622000130003');

        $this->assertFalse($hasOther);
    }

    public function testPayAfterCancelBranchDelegatesCreateAbnormalTradeRefund(): void
    {
        $refundService = \Mockery::mock(AftersalesRefundService::class);
        $refundService->shouldReceive('createAbnormalTradeRefund')
            ->once()
            ->with(
                \Mockery::on(fn (array $order) => $order['order_status'] === 'CANCEL'),
                \Mockery::type('array'),
                'PAY_AFTER_CANCEL'
            )
            ->andReturn(['refund_bn' => '2202511250001', 'refund_status' => 'PAY_AFTER_CANCEL']);
        app()->instance(AftersalesRefundService::class, $refundService);

        $result = $this->invokeHandleNonPayable(
            'pay_after_cancel',
            ['company_id' => 1, 'order_id' => '5412622000140002', 'order_status' => 'CANCEL', 'user_id' => '2'],
            ['trade_id' => '5412622000130002', 'pay_type' => 'wxpayjs', 'pay_fee' => 100],
            '5412622000130002'
        );

        $this->assertFalse($result);
    }

    public function testDupPayBranchCreateAbnormalTradeRefundIsIdempotentOnDuplicateCallback(): void
    {
        $refundService = \Mockery::mock(AftersalesRefundService::class);
        $refundService->shouldReceive('createAbnormalTradeRefund')
            ->twice()
            ->with(
                \Mockery::type('array'),
                \Mockery::type('array'),
                'DUP_PAY'
            )
            ->andReturn(['refund_bn' => '2202511250001', 'refund_status' => 'DUP_PAY']);
        app()->instance(AftersalesRefundService::class, $refundService);

        $order = ['company_id' => 1, 'order_id' => '5412622000140002', 'order_status' => 'PAYED', 'user_id' => '2'];
        $trade = ['trade_id' => '5412622000130003', 'pay_type' => 'wxpayjs', 'pay_fee' => 100];

        $this->invokeHandleNonPayable('dup_pay', $order, $trade, '5412622000130003');
        $this->invokeHandleNonPayable('dup_pay', $order, $trade, '5412622000130003');

        $this->addToAssertionCount(1);
    }

    public function testPayableBranchInvokesTradeSuccUpdateOrderStatusAndPaySuccessEvent(): void
    {
        $order = [
            'order_type' => 'normal',
            'order_class' => 'normal',
            'order_id' => '5412622000140002',
            'company_id' => 1,
            'user_id' => '2',
            'order_status' => 'NOTPAY',
            'monitor_id' => 0,
            'source_id' => 0,
            'total_fee' => 100,
            'auto_cancel_time' => time() + 3600,
        ];
        $tradeId = '5412622000130002';

        $orderService = \Mockery::mock();
        $orderService->shouldReceive('tradeSuccUpdateOrderStatus')
            ->once()
            ->with(\Mockery::type('array'), 'wxpayjs')
            ->andReturn(false);

        $listener = new class($orderService) extends UpdateOrderStatusListener {
            /** @var object */
            public $orderService;

            public function __construct($orderService)
            {
                $this->orderService = $orderService;
            }

            public function getOrderServiceByOrderInfo($order)
            {
                return $this->orderService;
            }
        };

        $this->assertSame('cas_retry', $this->invokeHandlePayable(
            $listener,
            $order,
            'wxpayjs',
            1,
            '5412622000140002',
            '2',
            $tradeId
        ));

        $path = dirname(__DIR__, 2) . '/src/OrdersBundle/Listeners/UpdateOrderStatusListener.php';
        $src = (string) file_get_contents($path);
        $this->assertStringContainsString('tradeSuccUpdateOrderStatus', $src);
        $this->assertStringContainsString('NormalOrderPaySuccessEvent', $src);
        $this->assertStringContainsString("'trade_id' => \$tradeId", $src);
    }

    public function testCasMissThenReReadPayAfterCancelOrSuccessOnSecondAttempt(): void
    {
        $tradeId = '5412622000130002';
        $orderId = '5412622000140002';
        $notPayOrder = [
            'order_id' => $orderId,
            'company_id' => 1,
            'user_id' => '2',
            'order_type' => 'normal',
            'order_class' => 'normal',
            'order_status' => 'NOTPAY',
            'auto_cancel_time' => time() + 3600,
            'monitor_id' => 0,
            'source_id' => 0,
        ];

        $orderService = \Mockery::mock();
        $orderService->shouldReceive('tradeSuccUpdateOrderStatus')->once()->andReturn(false);
        $orderService->shouldReceive('tradeSuccUpdateOrderStatus')->once()->andReturn(true);

        $listener = new class($orderService) extends UpdateOrderStatusListener {
            /** @var object */
            public $orderService;

            public function __construct($orderService)
            {
                $this->orderService = $orderService;
            }

            public function getOrderServiceByOrderInfo($order)
            {
                return $this->orderService;
            }
        };

        $this->assertSame('cas_retry', $this->invokeHandlePayable(
            $listener,
            $notPayOrder,
            'wxpayjs',
            1,
            $orderId,
            '2',
            $tradeId
        ));

        $this->assertSame('pay_after_cancel', $this->invokeResolve([
            'order_status' => 'CANCEL',
            'company_id' => 1,
            'order_id' => $orderId,
        ], $tradeId));

        $this->assertSame('success', $this->invokeHandlePayable(
            $listener,
            $notPayOrder,
            'wxpayjs',
            1,
            $orderId,
            '2',
            $tradeId
        ));
    }

    public function testListenerSourceRemovesCancelOrderConfirmCancelPath(): void
    {
        $path = dirname(__DIR__, 2) . '/src/OrdersBundle/Listeners/UpdateOrderStatusListener.php';
        $src = (string) file_get_contents($path);

        $this->assertStringNotContainsString('cancelOrder', $src);
        $this->assertStringNotContainsString('confirmCancelOrder', $src);
        $this->assertStringContainsString('trade_finish_reconcile_', $src);
        $this->assertStringContainsString('createAbnormalTradeRefund', $src);
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function invokeResolve(array $order, string $tradeId): string
    {
        $ref = new \ReflectionMethod(UpdateOrderStatusListener::class, 'resolveReconcileBranch');
        $ref->setAccessible(true);

        return (string) $ref->invoke($this->listener, $order, $tradeId);
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function invokeIsNotPayTimedOut(array $order, int $now): bool
    {
        $ref = new \ReflectionMethod(UpdateOrderStatusListener::class, 'isNotPayTimedOut');
        $ref->setAccessible(true);

        return (bool) $ref->invoke($this->listener, $order, $now);
    }

    private function invokeHasOther(int $companyId, string $orderId, string $excludeTradeId): bool
    {
        $ref = new \ReflectionMethod(UpdateOrderStatusListener::class, 'hasOtherSuccessfulNonPointTrade');
        $ref->setAccessible(true);

        return (bool) $ref->invoke($this->listener, $companyId, $orderId, $excludeTradeId);
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>  $trade
     */
    private function invokeHandleNonPayable(string $branch, array $order, array $trade, string $tradeId): bool
    {
        $ref = new \ReflectionMethod(UpdateOrderStatusListener::class, 'handleNonPayableBranch');
        $ref->setAccessible(true);

        return (bool) $ref->invoke(
            $this->listener,
            $branch,
            $order,
            $trade,
            $order['company_id'],
            $order['order_id'],
            $tradeId
        );
    }

  /**
     * @param  array<string, mixed>  $order
     */
    private function invokeHandlePayable(
        UpdateOrderStatusListener $listener,
        array $order,
        string $payType,
        int $companyId,
        string $orderId,
        $userId,
        string $tradeId
    ): string {
        $ref = new \ReflectionMethod(UpdateOrderStatusListener::class, 'handlePayableBranch');
        $ref->setAccessible(true);

        return (string) $ref->invoke($listener, $order, $payType, $companyId, $orderId, $userId, $tradeId);
    }
}