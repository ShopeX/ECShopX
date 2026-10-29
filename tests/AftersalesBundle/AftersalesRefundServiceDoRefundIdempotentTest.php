<?php

declare(strict_types=1);

namespace Tests\AftersalesBundle;

use AftersalesBundle\Services\AftersalesRefundService;

/**
 * ECX-10613：售后退款 doRefund 幂等——退款单已 SUCCESS 时再次调用不得重复退款/写积分流水。
 */
class AftersalesRefundServiceDoRefundIdempotentTest extends \TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testDoRefundWhenAlreadySuccessReturnsImmediatelyWithoutUpdatingRefund(): void
    {
        $repo = \Mockery::mock();
        $repo->shouldReceive('getInfo')
            ->once()
            ->with([
                'refund_bn' => '2202610261015551531',
                'company_id' => '1',
            ])
            ->andReturn([
                'refund_bn' => '2202610261015551531',
                'company_id' => '1',
                'refund_status' => 'SUCCESS',
                'refund_id' => '2202610261015551531',
                'refund_fee' => 500,
                'refund_point' => 5,
                'refunded_point' => 5,
                'freight_type' => 'cash',
                'freight' => 0,
                'pay_type' => 'deposit',
                'trade_id' => '5412622000130002',
                'user_id' => '2',
                'order_id' => '5412622000140002',
                'aftersales_bn' => '202610261015452',
            ]);
        $repo->shouldNotReceive('updateOneBy');

        $service = new AftersalesRefundService();
        $service->aftersalesRefundRepository = $repo;

        $result = $service->doRefund([
            'refund_bn' => '2202610261015551531',
            'company_id' => '1',
        ]);

        $this->assertSame('SUCCESS', $result['status']);
        $this->assertSame('2202610261015551531', $result['refund_id']);
    }
}
