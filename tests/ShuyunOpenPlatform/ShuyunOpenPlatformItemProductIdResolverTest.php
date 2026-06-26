<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformItemProductIdResolver;

class ShuyunOpenPlatformItemProductIdResolverTest extends \TestCase
{
    public function testResolvePrefersGoodsIdOverDefaultItemId(): void
    {
        $this->assertSame('5000', ShuyunOpenPlatformItemProductIdResolver::resolve(5000, 5016, 5017));
    }

    public function testResolveFallsBackToDefaultItemIdWhenGoodsIdMissing(): void
    {
        $this->assertSame('5016', ShuyunOpenPlatformItemProductIdResolver::resolve(0, 5016, 5017));
    }

    public function testResolveFallsBackToLineItemIdWhenBothMissing(): void
    {
        $this->assertSame('5017', ShuyunOpenPlatformItemProductIdResolver::resolve(0, 0, 5017));
    }

    public function testResolveFromItemRowUsesGoodsIdFromRow(): void
    {
        $actual = ShuyunOpenPlatformItemProductIdResolver::resolveFromItemRow([
            'goods_id' => 5000,
            'default_item_id' => 5016,
            'item_id' => 5017,
        ], 9999);

        $this->assertSame('5000', $actual);
    }
}
