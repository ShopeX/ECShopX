<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncTimeEstimator;

class HistoricalSyncTimeEstimatorTest extends \TestCase
{
    public function testEstimateReturnsMinMaxWithinFormulaTolerance(): void
    {
        $counts = [
            'shops' => 2,
            'categories' => 10,
            'product_units' => 128,
            'members' => 254,
            'orders' => 95,
            'refunds' => 8,
            'points' => 50,
        ];
        $rt = 0.4;
        $range = HistoricalSyncTimeEstimator::estimateSecondsRange($counts, $rt);

        $raw = (2 / 100 + 10 / 50 + ceil(128 / 50) + 254 + 95 / 50 + ceil(8 / 50) + 50) * $rt;
        $this->assertGreaterThanOrEqual((int) floor($raw * 0.8), $range['min']);
        $this->assertLessThanOrEqual((int) ceil($raw * 2.4), $range['max']);
    }
}
