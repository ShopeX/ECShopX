<?php

/**
 * is_platform_store_buy 原始值解析（店务在售 / 立即购买）
 */

use DistributionBundle\Services\DistributorService;

class DistributorServicePlatformStoreBuyTest extends TestCase
{
    public function testIsPlatformStoreBuyEnabledRaw(): void
    {
        $this->assertTrue(DistributorService::isPlatformStoreBuyEnabledRaw(['is_platform_store_buy' => true]));
        $this->assertTrue(DistributorService::isPlatformStoreBuyEnabledRaw(['is_platform_store_buy' => 1]));
        $this->assertTrue(DistributorService::isPlatformStoreBuyEnabledRaw(['is_platform_store_buy' => '1']));
        $this->assertTrue(DistributorService::isPlatformStoreBuyEnabledRaw(['is_platform_store_buy' => 'true']));
        $this->assertFalse(DistributorService::isPlatformStoreBuyEnabledRaw(['is_platform_store_buy' => false]));
        $this->assertFalse(DistributorService::isPlatformStoreBuyEnabledRaw(['is_platform_store_buy' => 0]));
        $this->assertFalse(DistributorService::isPlatformStoreBuyEnabledRaw([]));
    }
}
