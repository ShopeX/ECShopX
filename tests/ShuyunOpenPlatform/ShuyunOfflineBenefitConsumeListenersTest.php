<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use OrdersBundle\Events\NormalOrderCancelEvent;
use OrdersBundle\Events\NormalOrderPaySuccessEvent;
use ShuyunOpenPlatformBundle\Listeners\ShuyunOfflineBenefitResultPushOnOrderCancelListener;
use ShuyunOpenPlatformBundle\Listeners\ShuyunOfflineBenefitResultPushOnPaySuccessListener;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitConsumePushService;
use TestCase;

class ShuyunOfflineBenefitConsumeListenersTest extends TestCase
{
    public function testPaySuccessListenerDelegatesWithNumericStringOrderId(): void
    {
        $mock = $this->createMock(ShuyunOfflineBenefitConsumePushService::class);
        $mock->expects($this->once())->method('handlePaySuccess')->with(10, 2001);
        $this->app->instance(ShuyunOfflineBenefitConsumePushService::class, $mock);

        (new ShuyunOfflineBenefitResultPushOnPaySuccessListener())->handle(
            new NormalOrderPaySuccessEvent(['company_id' => 10, 'order_id' => '2001'])
        );
    }

    public function testPaySuccessListenerIgnoresNonNumericOrderId(): void
    {
        $mock = $this->createMock(ShuyunOfflineBenefitConsumePushService::class);
        $mock->expects($this->never())->method('handlePaySuccess');
        $this->app->instance(ShuyunOfflineBenefitConsumePushService::class, $mock);

        (new ShuyunOfflineBenefitResultPushOnPaySuccessListener())->handle(
            new NormalOrderPaySuccessEvent(['company_id' => 10, 'order_id' => 'ORD-X'])
        );
    }

    public function testCancelListenerDelegates(): void
    {
        $mock = $this->createMock(ShuyunOfflineBenefitConsumePushService::class);
        $mock->expects($this->once())->method('handleOrderCancel')->with(3, 400);
        $this->app->instance(ShuyunOfflineBenefitConsumePushService::class, $mock);

        (new ShuyunOfflineBenefitResultPushOnOrderCancelListener())->handle(
            new NormalOrderCancelEvent(['company_id' => 3, 'order_id' => 400])
        );
    }
}
