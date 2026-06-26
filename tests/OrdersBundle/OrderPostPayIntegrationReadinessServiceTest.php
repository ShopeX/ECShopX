<?php

declare(strict_types=1);

namespace Tests\OrdersBundle;

use OrdersBundle\Services\OrderPostPayIntegrationReadinessService;

class OrderPostPayIntegrationReadinessServiceTest extends \TestCase
{
    public function testIsOrderRowReadyWhenPayStatusPayed(): void
    {
        $svc = new OrderPostPayIntegrationReadinessService();
        $this->assertTrue($svc->isOrderRowReadyForPostPayIntegration([
            'order_status' => 'NOTPAY',
            'pay_status' => 'PAYED',
        ]));
    }

    public function testIsOrderRowReadyWhenOrderStatusDone(): void
    {
        $svc = new OrderPostPayIntegrationReadinessService();
        $this->assertTrue($svc->isOrderRowReadyForPostPayIntegration([
            'order_status' => 'DONE',
            'pay_status' => 'PAYED',
        ]));
    }

    public function testIsOrderRowNotReadyWhenAwaitingPayment(): void
    {
        $svc = new OrderPostPayIntegrationReadinessService();
        $this->assertFalse($svc->isOrderRowReadyForPostPayIntegration([
            'order_status' => 'NOTPAY',
            'pay_status' => 'NOTPAY',
        ]));
    }

    public function testIsOrderRowNotReadyWhenPartPayment(): void
    {
        $svc = new OrderPostPayIntegrationReadinessService();
        $this->assertFalse($svc->isOrderRowReadyForPostPayIntegration([
            'order_status' => 'PART_PAYMENT',
            'pay_status' => 'NOTPAY',
        ]));
    }
}
