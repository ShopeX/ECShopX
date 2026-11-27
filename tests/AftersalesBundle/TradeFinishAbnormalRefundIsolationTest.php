<?php

declare(strict_types=1);

namespace Tests\AftersalesBundle;

use AftersalesBundle\Services\AftersalesRefundService;
use Dingo\Api\Exception\ResourceException;

/**
 * ECX-11406：售前隔离、错误日志 resubmit、异常退款接口状态校验。
 */
class TradeFinishAbnormalRefundIsolationTest extends \TestCase
{
    public function testIsTradeFinishAbnormalRefundDetectsPendingStatuses(): void
    {
        $this->assertTrue(AftersalesRefundService::isTradeFinishAbnormalRefund([
            'refund_status' => 'PAY_AFTER_CANCEL',
        ]));
        $this->assertTrue(AftersalesRefundService::isTradeFinishAbnormalRefund([
            'refund_status' => 'DUP_PAY',
        ]));
    }

    public function testIsTradeFinishAbnormalRefundIgnoresPresaleReady(): void
    {
        $this->assertFalse(AftersalesRefundService::isTradeFinishAbnormalRefund([
            'refund_status' => 'READY',
            'refund_type' => 1,
            'return_freight' => 1,
        ]));
    }

    public function testIsTradeFinishAbnormalRefundDetectsAbnormalProcessing(): void
    {
        $this->assertTrue(AftersalesRefundService::isTradeFinishAbnormalRefund([
            'refund_status' => 'PROCESSING',
            'refund_type' => 1,
            'return_freight' => 0,
            'aftersales_bn' => '',
        ]));
        $this->assertFalse(AftersalesRefundService::isTradeFinishAbnormalRefund([
            'refund_status' => 'PROCESSING',
            'refund_type' => 1,
            'return_freight' => 1,
        ]));
    }

    public function testRefundAbnormalTradeRejectsNonAbnormalStatus(): void
    {
        $repo = \Mockery::mock(\AftersalesBundle\Repositories\AftersalesRefundRepository::class);
        $repo->shouldReceive('getInfo')
            ->once()
            ->andReturn([
                'company_id' => '1',
                'refund_bn' => '2202511250001',
                'refund_status' => 'READY',
            ]);

        $service = new class($repo) extends AftersalesRefundService {
            public function __construct($repo)
            {
                $this->aftersalesRefundRepository = $repo;
            }
        };

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('当前退款单状态不允许异常实退');
        $service->refundAbnormalTrade('1', '2202511250001');
    }

    public function testConfirmCancelOrderOnlyPicksReadyRefund(): void
    {
        $src = file_get_contents(__DIR__ . '/../../src/OrdersBundle/Services/Orders/AbstractNormalOrder.php');
        $this->assertStringContainsString("'refund_status' => 'READY'", $src);
        $this->assertStringNotContainsString(
            "'refund_status' => ['READY', 'AUDIT_SUCCESS'",
            $src
        );
    }

    public function testAbnormalRefundRouteRegistered(): void
    {
        $routes = file_get_contents(__DIR__ . '/../../routes/api/aftersales.php');
        $this->assertStringContainsString("'as' => 'refund.abnormal'", $routes);
        $this->assertStringContainsString('/refund/{refund_bn}/abnormal-refund', $routes);
    }

    public function testRefundErrorLogsResubmitGuardsAbnormal(): void
    {
        $src = file_get_contents(__DIR__ . '/../../src/OrdersBundle/Services/RefundErrorLogsService.php');
        $this->assertStringContainsString('isTradeFinishAbnormalRefund', $src);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}