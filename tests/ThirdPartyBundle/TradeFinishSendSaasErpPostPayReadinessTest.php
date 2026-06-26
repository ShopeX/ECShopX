<?php

declare(strict_types=1);

namespace Tests\ThirdPartyBundle;

class TradeFinishSendSaasErpPostPayReadinessTest extends \TestCase
{
    public function testTradeFinishSendSaasErpRetriesWhenOrderNotReadyAfterPay(): void
    {
        $path = dirname(__DIR__, 2).'/src/ThirdPartyBundle/Listeners/TradeFinishSendSaasErp.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('OrderPostPayIntegrationReadinessService', $src);
        $this->assertStringContainsString('ORDER_NOT_READY_RETRY_MAX_ATTEMPTS', $src);
        $this->assertStringContainsString('saaserp TradeFinishSendSaasErp order not ready after pay, will retry.', $src);
    }
}
