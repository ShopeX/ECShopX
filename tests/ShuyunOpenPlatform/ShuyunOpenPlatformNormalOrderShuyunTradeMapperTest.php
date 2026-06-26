<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformNormalOrderShuyunTradeMapper;

class ShuyunOpenPlatformNormalOrderShuyunTradeMapperTest extends \TestCase
{
    public function testMapOrderStatusPayedAwaitingShipment(): void
    {
        $this->assertSame(
            'WAIT_SELLER_SEND_GOODS',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus([
                'order_status' => 'PAYED',
                'delivery_status' => 'PENDING',
            ])
        );
    }

    public function testMapOrderStatusPayedDelivered(): void
    {
        $this->assertSame(
            'WAIT_BUYER_CONFIRM_GOODS',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus([
                'order_status' => 'PAYED',
                'delivery_status' => 'DONE',
            ])
        );
    }

    public function testMapOrderStatusPayedPartialShippedMatchesDict61(): void
    {
        $this->assertSame(
            'SELLER_CONSIGNED_PART',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus([
                'order_status' => 'PAYED',
                'delivery_status' => 'PARTAIL',
            ])
        );
    }

    public function testMapOrderStatusWaitBuyerConfirmUsesDict61(): void
    {
        $this->assertSame(
            'WAIT_BUYER_CONFIRM_GOODS',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus([
                'order_status' => 'WAIT_BUYER_CONFIRM',
                'delivery_status' => 'DONE',
            ])
        );
    }

    public function testMapOrderStatusDoneFinished(): void
    {
        $this->assertSame(
            'TRADE_FINISHED',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus([
                'order_status' => 'DONE',
                'delivery_status' => 'DONE',
            ])
        );
    }

    public function testMapOrderStatusCancelClosed(): void
    {
        $this->assertSame(
            'TRADE_CLOSED',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus([
                'order_status' => 'CANCEL',
                'delivery_status' => 'PENDING',
            ])
        );
    }

    public function testMapOrderStatusWaitGroupsSuccess(): void
    {
        $this->assertSame(
            'WAIT_BUYER_PAY',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus([
                'order_status' => 'WAIT_GROUPS_SUCCESS',
                'delivery_status' => 'PENDING',
            ])
        );
    }

    public function testMapOrderStatusWaitPaidConfirmCod(): void
    {
        $this->assertSame(
            'WAIT_BUYER_CONFIRM_PAY',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus([
                'order_status' => 'WAIT_PAID_CONFIRM',
                'delivery_status' => 'DONE',
            ])
        );
    }

    public function testMapOrderStatusReviewPassLikePayed(): void
    {
        $this->assertSame(
            'SELLER_CONSIGNED_PART',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus([
                'order_status' => 'REVIEW_PASS',
                'delivery_status' => 'PARTAIL',
            ])
        );
    }

    public function testMapOrderStatusRefundSuccessAllRefundClosed(): void
    {
        $this->assertSame(
            'TRADE_CLOSED_ALL_REFUND',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus([
                'order_status' => 'REFUND_SUCCESS',
                'delivery_status' => 'PENDING',
            ])
        );
        $this->assertTrue(
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::shouldSendEndTime('TRADE_CLOSED_ALL_REFUND')
        );
    }

    public function testMapOrderStatusRefundProcessConservative(): void
    {
        $this->assertSame(
            'WAIT_SELLER_SEND_GOODS',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapOrderStatus([
                'order_status' => 'REFUND_PROCESS',
                'delivery_status' => 'DONE',
            ])
        );
    }

    public function testMapDeliveryZiti(): void
    {
        $this->assertSame(
            'SY_SELFLIFT',
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::mapDeliveryType(['receipt_type' => 'ziti'])
        );
    }

    public function testShouldSendEndTimeForFinished(): void
    {
        $this->assertTrue(
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::shouldSendEndTime('TRADE_FINISHED')
        );
        $this->assertFalse(
            ShuyunOpenPlatformNormalOrderShuyunTradeMapper::shouldSendEndTime('WAIT_SELLER_SEND_GOODS')
        );
    }
}
