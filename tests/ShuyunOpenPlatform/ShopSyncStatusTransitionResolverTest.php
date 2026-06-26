<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShopSyncStatusTransitionResolver;

class ShopSyncStatusTransitionResolverTest extends \TestCase
{
    public function testSkipsWhenStatusNoChange(): void
    {
        $sut = new ShopSyncStatusTransitionResolver();
        $ret = $sut->resolve('false', '0', 'PL');
        $this->assertFalse($ret['should_sync']);
        $this->assertSame([], $ret['target_plat_codes']);
        $this->assertSame(ShopSyncStatusTransitionResolver::REASON_SKIP_NO_CHANGE, $ret['reason']);
    }

    public function testDisabledToClosedSkipsByRule(): void
    {
        $sut = new ShopSyncStatusTransitionResolver();
        $ret = $sut->resolve('false', 'closed', 'PL');
        $this->assertFalse($ret['should_sync']);
        $this->assertSame([], $ret['target_plat_codes']);
        $this->assertSame(ShopSyncStatusTransitionResolver::REASON_SKIP_DISABLED_TO_CLOSED, $ret['reason']);
    }

    public function testEnableOrDisableTransitionUsesOnlineOnly(): void
    {
        $sut = new ShopSyncStatusTransitionResolver();
        $up = $sut->resolve('false', 'true', 'pl');
        $down = $sut->resolve('true', 'false', 'PL');

        $this->assertTrue($up['should_sync']);
        $this->assertSame(['PL'], $up['target_plat_codes']);
        $this->assertSame(ShopSyncStatusTransitionResolver::REASON_SYNC_ONLINE_ONLY, $up['reason']);

        $this->assertTrue($down['should_sync']);
        $this->assertSame(['PL'], $down['target_plat_codes']);
        $this->assertSame(ShopSyncStatusTransitionResolver::REASON_SYNC_ONLINE_ONLY, $down['reason']);
    }

    public function testClosedAndDeleteUseOfflineAndOnline(): void
    {
        $sut = new ShopSyncStatusTransitionResolver();
        $closed = $sut->resolve('true', 'closed', 'pl');
        $deleted = $sut->resolve('true', 'delete', 'PL');

        $this->assertTrue($closed['should_sync']);
        $this->assertSame(['OFFLINE', 'PL'], $closed['target_plat_codes']);
        $this->assertSame(ShopSyncStatusTransitionResolver::REASON_SYNC_OFFLINE_AND_ONLINE, $closed['reason']);

        $this->assertTrue($deleted['should_sync']);
        $this->assertSame(['OFFLINE', 'PL'], $deleted['target_plat_codes']);
        $this->assertSame(ShopSyncStatusTransitionResolver::REASON_SYNC_OFFLINE_AND_ONLINE, $deleted['reason']);
    }

    public function testFallsBackToOfflineWhenOnlinePlatMissing(): void
    {
        $sut = new ShopSyncStatusTransitionResolver();
        $ret = $sut->resolve('true', 'delete', '');
        $this->assertTrue($ret['should_sync']);
        $this->assertSame(['OFFLINE'], $ret['target_plat_codes']);
    }
}
