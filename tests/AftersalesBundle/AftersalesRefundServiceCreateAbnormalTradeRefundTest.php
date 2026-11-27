<?php

declare(strict_types=1);

namespace Tests\AftersalesBundle;

use AftersalesBundle\Services\AftersalesRefundService;
use Dingo\Api\Exception\ResourceException;

/**
 * ECX-11406：支付完结异常退款建单工厂与幂等。
 */
class AftersalesRefundServiceCreateAbnormalTradeRefundTest extends \TestCase
{
    private array $order = [
        'company_id' => '1',
        'order_id' => '5412622000140002',
        'user_id' => '2',
        'shop_id' => 0,
        'distributor_id' => 0,
        'pay_type' => 'wxpayjs',
        'point' => 0,
    ];

    private array $trade = [
        'trade_id' => '5412622000130002',
        'pay_type' => 'wxpayh5',
        'pay_fee' => 8800,
        'fee_type' => 'CNY',
        'cur_fee_type' => 'CNY',
        'cur_fee_rate' => '1.0000',
        'cur_fee_symbol' => '¥',
        'cur_pay_fee' => 8800,
        'merchant_id' => 10,
    ];

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testCreatePayAfterCancelRefundUsesTradeFieldsNotReadyOrAuditSuccess(): void
    {
        $repo = \Mockery::mock();
        $repo->shouldReceive('getList')
            ->once()
            ->andReturn(['total_count' => 0, 'list' => []]);
        $repo->shouldReceive('create')
            ->once()
            ->with(\Mockery::on(function (array $data): bool {
                $this->assertSame(1, $data['refund_type']);
                $this->assertSame('PAY_AFTER_CANCEL', $data['refund_status']);
                $this->assertSame('5412622000130002', $data['trade_id']);
                $this->assertSame(8800, $data['refund_fee']);
                $this->assertSame(0, $data['return_freight']);
                $this->assertSame('original', $data['refund_channel']);
                $this->assertSame('wxpayh5', $data['pay_type']);
                $this->assertNotSame('READY', $data['refund_status']);
                $this->assertNotSame('AUDIT_SUCCESS', $data['refund_status']);

                return true;
            }))
            ->andReturn(['refund_bn' => '2202511250001', 'refund_status' => 'PAY_AFTER_CANCEL']);

        $service = new AftersalesRefundService();
        $service->aftersalesRefundRepository = $repo;

        $result = $service->createAbnormalTradeRefund($this->order, $this->trade, 'PAY_AFTER_CANCEL');

        $this->assertSame('PAY_AFTER_CANCEL', $result['refund_status']);
    }

    public function testCreateDupPayOfflinePayUsesOfflineChannel(): void
    {
        $trade = array_merge($this->trade, ['pay_type' => 'offline_pay']);
        $repo = \Mockery::mock();
        $repo->shouldReceive('getList')->once()->andReturn(['total_count' => 0, 'list' => []]);
        $repo->shouldReceive('create')
            ->once()
            ->with(\Mockery::on(function (array $data): bool {
                $this->assertSame('DUP_PAY', $data['refund_status']);
                $this->assertSame('offline', $data['refund_channel']);
                $this->assertSame('offline_pay', $data['pay_type']);

                return true;
            }))
            ->andReturn(['refund_status' => 'DUP_PAY']);

        $service = new AftersalesRefundService();
        $service->aftersalesRefundRepository = $repo;

        $service->createAbnormalTradeRefund($this->order, $trade, 'DUP_PAY');
    }

    public function testIdempotentWhenAbnormalRefundAlreadyExists(): void
    {
        $existing = [
            'refund_bn' => '2202511250099',
            'refund_status' => 'DUP_PAY',
            'trade_id' => '5412622000130002',
        ];
        $repo = \Mockery::mock();
        $repo->shouldReceive('getList')
            ->once()
            ->with([
                'company_id' => '1',
                'trade_id' => '5412622000130002',
                'refund_status' => ['PAY_AFTER_CANCEL', 'DUP_PAY', 'PROCESSING', 'SUCCESS'],
            ], 0, 1)
            ->andReturn(['total_count' => 1, 'list' => [$existing]]);
        $repo->shouldNotReceive('create');

        $service = new AftersalesRefundService();
        $service->aftersalesRefundRepository = $repo;

        $result = $service->createAbnormalTradeRefund($this->order, $this->trade, 'PAY_AFTER_CANCEL');

        $this->assertSame($existing, $result);
    }

    public function testIdempotentWhenRefundAlreadySuccessDoesNotCreate(): void
    {
        $existing = [
            'refund_bn' => '2202511250100',
            'refund_status' => 'SUCCESS',
        ];
        $repo = \Mockery::mock();
        $repo->shouldReceive('getList')->once()->andReturn(['total_count' => 1, 'list' => [$existing]]);
        $repo->shouldNotReceive('create');

        $service = new AftersalesRefundService();
        $service->aftersalesRefundRepository = $repo;

        $result = $service->createAbnormalTradeRefund($this->order, $this->trade, 'DUP_PAY');

        $this->assertSame('SUCCESS', $result['refund_status']);
    }

    public function testRejectsInvalidRefundStatus(): void
    {
        $service = new AftersalesRefundService();
        $this->expectException(ResourceException::class);
        $service->createAbnormalTradeRefund($this->order, $this->trade, 'READY');
    }
}