<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformMemberSyncState;

final class ShuyunOpenPlatformMemberSyncStateTest extends TestCase
{
    public function testIsRegisteredWhenWxappSyncAtPositive(): void
    {
        $this->assertTrue(ShuyunOpenPlatformMemberSyncState::isRegisteredWithOpenPlatform([
            'shuyun_open_online_wxapp_sync_at' => 1,
            'offline_reg_distributor' => null,
        ]));
    }

    public function testIsRegisteredWhenOfflineRegDistributorPositive(): void
    {
        $this->assertTrue(ShuyunOpenPlatformMemberSyncState::isRegisteredWithOpenPlatform([
            'shuyun_open_online_wxapp_sync_at' => null,
            'offline_reg_distributor' => 99,
        ]));
    }

    public function testIsNotRegisteredWhenBothMarkersEmpty(): void
    {
        $this->assertFalse(ShuyunOpenPlatformMemberSyncState::isRegisteredWithOpenPlatform([]));
        $this->assertFalse(ShuyunOpenPlatformMemberSyncState::isRegisteredWithOpenPlatform([
            'shuyun_open_online_wxapp_sync_at' => 0,
            'offline_reg_distributor' => 0,
        ]));
    }

    public function testNeedsWxappBindPushOnlyWhenOfflineRegisteredButWxappNotSynced(): void
    {
        $this->assertTrue(ShuyunOpenPlatformMemberSyncState::needsWxappBindPushOnly([
            'offline_reg_distributor' => 10,
            'shuyun_open_online_wxapp_sync_at' => null,
        ]));
        $this->assertFalse(ShuyunOpenPlatformMemberSyncState::needsWxappBindPushOnly([
            'offline_reg_distributor' => 10,
            'shuyun_open_online_wxapp_sync_at' => 100,
        ]));
        $this->assertFalse(ShuyunOpenPlatformMemberSyncState::needsWxappBindPushOnly([
            'offline_reg_distributor' => 0,
            'shuyun_open_online_wxapp_sync_at' => null,
        ]));
    }
}
