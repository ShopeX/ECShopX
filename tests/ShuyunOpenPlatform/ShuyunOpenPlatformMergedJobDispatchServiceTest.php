<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Carbon;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMergedJobDispatchService;

/** @see .tasks/plans/shuyun-open-platform-category-goods-sync.md A-MERGE-01、A-MERGE-02 */
class ShuyunOpenPlatformMergedJobDispatchServiceTest extends \TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testSecondDispatchWithinTtlIsMerged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-27 12:00:00'));
        $repo = new Repository(new ArrayStore());
        $sut = new ShuyunOpenPlatformMergedJobDispatchService($repo, 60);
        $n = 0;
        $inc = static function () use (&$n): void {
            ++$n;
        };
        $this->assertTrue($sut->dispatchUnlessMerged('k1', $inc));
        $this->assertFalse($sut->dispatchUnlessMerged('k1', $inc));
        $this->assertSame(1, $n);
    }

    public function testDifferentMergeKeysDoNotMerge(): void
    {
        $repo = new Repository(new ArrayStore());
        $sut = new ShuyunOpenPlatformMergedJobDispatchService($repo, 60);
        $n = 0;
        $this->assertTrue($sut->dispatchUnlessMerged('a', static function () use (&$n): void {
            ++$n;
        }));
        $this->assertTrue($sut->dispatchUnlessMerged('b', static function () use (&$n): void {
            ++$n;
        }));
        $this->assertSame(2, $n);
    }

    public function testAfterTtlExpiresDispatchRunsAgain(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-27 12:00:00'));
        $repo = new Repository(new ArrayStore());
        $sut = new ShuyunOpenPlatformMergedJobDispatchService($repo, 3);
        $n = 0;
        $inc = static function () use (&$n): void {
            ++$n;
        };
        $this->assertTrue($sut->dispatchUnlessMerged('k2', $inc));
        $this->assertFalse($sut->dispatchUnlessMerged('k2', $inc));
        Carbon::setTestNow(Carbon::parse('2026-03-27 12:00:05'));
        $this->assertTrue($sut->dispatchUnlessMerged('k2', $inc));
        $this->assertSame(2, $n);
    }

    public function testZeroTtlAlwaysDispatches(): void
    {
        $repo = new Repository(new ArrayStore());
        $sut = new ShuyunOpenPlatformMergedJobDispatchService($repo, 0);
        $n = 0;
        $inc = static function () use (&$n): void {
            ++$n;
        };
        $this->assertTrue($sut->dispatchUnlessMerged('k3', $inc));
        $this->assertTrue($sut->dispatchUnlessMerged('k3', $inc));
        $this->assertSame(2, $n);
    }

    public function testShopSyncMergeKeyFormat(): void
    {
        $this->assertSame('shop_sync:7:99', ShuyunOpenPlatformMergedJobDispatchService::shopSyncMergeKey(7, 99));
    }
}
