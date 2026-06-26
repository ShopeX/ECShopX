<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderSyncDispatchCacheKeys;

class ShuyunOpenPlatformOrderSyncDispatchCacheKeysTest extends \TestCase
{
    public function testTradeSyncDedupeKeyIsStable(): void
    {
        $k = ShuyunOpenPlatformOrderSyncDispatchCacheKeys::tradeSyncDedupeKey(9, '1001');
        $this->assertSame(
            'shuyun_open_platform:dispatch_dedupe:trade_sync:9:'.sha1('1001'),
            $k
        );
    }

    public function testRefundSyncDedupeKeySeparatesLane(): void
    {
        $a = ShuyunOpenPlatformOrderSyncDispatchCacheKeys::refundSyncDedupeKey(1, '200', 'apply');
        $b = ShuyunOpenPlatformOrderSyncDispatchCacheKeys::refundSyncDedupeKey(1, '200', 'finish');
        $this->assertNotSame($a, $b);
        $this->assertStringContainsString(':apply', $a);
        $this->assertStringContainsString(':finish', $b);
    }
}
