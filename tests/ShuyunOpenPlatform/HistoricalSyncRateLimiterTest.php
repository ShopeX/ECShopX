<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncRateLimiter;

class HistoricalSyncRateLimiterTest extends \TestCase
{
    public function testOneQpsSpansAtLeastTwoSecondsForThreeCalls(): void
    {
        $limiter = new HistoricalSyncRateLimiter(1.0);
        $start = microtime(true);
        $limiter->throttle();
        $limiter->throttle();
        $limiter->throttle();
        $elapsed = microtime(true) - $start;
        $this->assertGreaterThanOrEqual(1.9, $elapsed);
    }
}
