<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOfflinePlatGatewaySuffixApplier;

class ShuyunOpenPlatformOfflinePlatGatewaySuffixApplierTest extends \TestCase
{
    public function testApplyDisplayNameSuffixUsesConfigAndSkipsDuplicate(): void
    {
        config(['shuyun_open_platform.offline_plat_name_suffix' => '-线下']);
        $this->assertSame('类目A-线下', ShuyunOpenPlatformOfflinePlatGatewaySuffixApplier::applyDisplayNameSuffix('类目A'));
        $this->assertSame('类目A-线下', ShuyunOpenPlatformOfflinePlatGatewaySuffixApplier::applyDisplayNameSuffix('类目A-线下'));
    }

    public function testApplyExternalIdSuffixUsesConfigAndSkipsDuplicate(): void
    {
        config(['shuyun_open_platform.offline_plat_id_suffix' => '-offline']);
        $this->assertSame('100-offline', ShuyunOpenPlatformOfflinePlatGatewaySuffixApplier::applyExternalIdSuffix('100'));
        $this->assertSame('100-offline', ShuyunOpenPlatformOfflinePlatGatewaySuffixApplier::applyExternalIdSuffix('100-offline'));
    }
}
