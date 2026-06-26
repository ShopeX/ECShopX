<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use TestCase;

class ShuyunOfflineBenefitCancelEventRegistrationSourceTest extends TestCase
{
    public function testOrdersEventServiceProviderRegistersOfflineBenefitCancelListener(): void
    {
        $path = dirname(__DIR__, 2).'/src/OrdersBundle/Providers/EventServiceProvider.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('OrdersBundle\\Events\\NormalOrderCancelEvent', $src);
        $this->assertStringContainsString(
            'ShuyunOpenPlatformBundle\\Listeners\\ShuyunOfflineBenefitResultPushOnOrderCancelListener',
            $src
        );
    }
}
