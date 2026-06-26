<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncCheckpointStore;

class HistoricalSyncCheckpointStoreTest extends \TestCase
{
    public function testWriteAndReadResumeCursor(): void
    {
        $base = sys_get_temp_dir().'/shuyun_historical_sync_test_'.uniqid('', true);
        $store = new HistoricalSyncCheckpointStore($base);
        $store->write(1, 'members', '100');
        $this->assertSame('100', $store->read(1, 'members'));
        $store->clear(1, 'members');
        $this->assertNull($store->read(1, 'members'));
    }
}
