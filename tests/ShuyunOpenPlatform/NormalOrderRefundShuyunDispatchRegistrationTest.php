<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

class NormalOrderRefundShuyunDispatchRegistrationTest extends \TestCase
{
    public function testThirdPartyEventServiceProviderRegistersRefundListeners(): void
    {
        $path = dirname(__DIR__, 2).'/src/ThirdPartyBundle/Providers/EventServiceProvider.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('ThirdPartyBundle\\Events\\TradeRefundEvent', $src);
        $this->assertStringContainsString(
            'ShuyunOpenPlatformBundle\\Listeners\\DispatchNormalOrderRefundSyncToShuyunOpenPlatformListener@handleTradeRefund',
            $src
        );
        $this->assertStringContainsString('ThirdPartyBundle\\Events\\TradeRefundFinishEvent', $src);
        $this->assertStringContainsString(
            'ShuyunOpenPlatformBundle\\Listeners\\DispatchNormalOrderRefundSyncToShuyunOpenPlatformListener@handleTradeRefundFinish',
            $src
        );
    }

    public function testSystemLinkEventServiceProviderRegistersRefundListener(): void
    {
        $path = dirname(__DIR__, 2).'/src/SystemLinkBundle/Providers/EventServiceProvider.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('SystemLinkBundle\\Events\\TradeRefundEvent', $src);
        $this->assertStringContainsString(
            'ShuyunOpenPlatformBundle\\Listeners\\DispatchNormalOrderRefundSyncToShuyunOpenPlatformListener@handleTradeRefund',
            $src
        );
        $this->assertStringContainsString('SystemLinkBundle\\Events\\TradeRefundFinishEvent', $src);
        $this->assertStringContainsString(
            'ShuyunOpenPlatformBundle\\Listeners\\DispatchNormalOrderRefundSyncToShuyunOpenPlatformListener@handleTradeRefundFinish',
            $src
        );
    }

    public function testSyncNormalOrderRefundJobReferencesAssemblerAndRefundSync(): void
    {
        $path = dirname(__DIR__, 2).'/src/ShuyunOpenPlatformBundle/Jobs/SyncNormalOrderRefundToShuyunOpenPlatformJob.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('class SyncNormalOrderRefundToShuyunOpenPlatformJob', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformNormalOrderRefundPayloadAssembler', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformRefundSyncService', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformOrderPlatformResolver', $src);
        $this->assertStringContainsString('refund_status', $src);
        $this->assertStringContainsString('\'SUCCESS\'', $src);
        $this->assertStringContainsString('refund.sync skipped: refund_status not SUCCESS', $src);
    }

    public function testRefundDispatchListenerRequiresSuccessStatusBeforeDedupe(): void
    {
        $path = dirname(__DIR__, 2).'/src/ShuyunOpenPlatformBundle/Listeners/DispatchNormalOrderRefundSyncToShuyunOpenPlatformListener.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('refund_status', $src);
        $this->assertStringContainsString('\'SUCCESS\'', $src);
    }
}
