<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

class NormalOrderPaySuccessShuyunTradeDispatchSourceTest extends \TestCase
{
    public function testThirdPartyEventServiceProviderRegistersTradeSyncListener(): void
    {
        $path = dirname(__DIR__, 2).'/src/ThirdPartyBundle/Providers/EventServiceProvider.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('OrdersBundle\\Events\\NormalOrderPaySuccessEvent', $src);
        $this->assertStringContainsString(
            'ShuyunOpenPlatformBundle\\Listeners\\DispatchNormalOrderTradeSyncToShuyunOpenPlatformListener',
            $src
        );
        $this->assertStringContainsString(
            'ShuyunOpenPlatformBundle\\Listeners\\ShuyunOfflineBenefitResultPushOnPaySuccessListener',
            $src
        );
    }

    public function testSyncNormalOrderTradeJobExistsAndUsesOrderAssociation(): void
    {
        $path = dirname(__DIR__, 2).'/src/ShuyunOpenPlatformBundle/Jobs/SyncNormalOrderTradeToShuyunOpenPlatformJob.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('class SyncNormalOrderTradeToShuyunOpenPlatformJob', $src);
        $this->assertStringContainsString('NormalOrdersRepository', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformNormalOrderTradePayloadAssembler', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformTradeSyncService', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformOrderTradeSourceResolver', $src);
        $this->assertStringContainsString('ShuyunOpenPlatformOrderPlatformResolver', $src);
        $this->assertStringContainsString('ORDER_ROW_MISSING_RETRY_MAX_ATTEMPTS', $src);
        $this->assertStringContainsString('ORDER_NOT_READY_RETRY_MAX_ATTEMPTS', $src);
        $this->assertStringContainsString('OrderPostPayIntegrationReadinessService', $src);
        $this->assertStringContainsString("\$attempt = (int) \$this->attempts();", $src);
        $this->assertStringContainsString("\$this->release(self::ORDER_ROW_MISSING_RETRY_DELAY_SEC);", $src);
    }

    public function testDispatchListenerDefersJobWhenConnectionTransactionOpen(): void
    {
        $path = dirname(__DIR__, 2).'/src/ShuyunOpenPlatformBundle/Listeners/DispatchNormalOrderTradeSyncToShuyunOpenPlatformListener.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('trade_sync_job_dispatched_delayed_for_open_transaction', $src);
        $this->assertStringContainsString('isTransactionActive()', $src);
    }
}
