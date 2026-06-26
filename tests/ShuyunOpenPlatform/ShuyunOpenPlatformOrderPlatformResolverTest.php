<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderPlatformResolver;

class ShuyunOpenPlatformOrderPlatformResolverTest extends \TestCase
{
    public function testShopadminResolvesToOffline(): void
    {
        config(['shuyun_open_platform.default_plat_code' => 'SHOULD_NOT_USE_FOR_SHOPADMIN']);
        $r = new ShuyunOpenPlatformOrderPlatformResolver();
        $this->assertSame('offline', $r->resolvePlatformHeaderForOrderClass('shopadmin'));
        $this->assertSame('offline', $r->resolvePlatformHeaderForOrderClass(' ShopAdmin '));
    }

    public function testNonShopadminUsesDefaultPlatCodeLowercased(): void
    {
        config(['shuyun_open_platform.default_plat_code' => '  MyPlat  ']);
        $r = new ShuyunOpenPlatformOrderPlatformResolver();
        $this->assertSame('offline', $r->resolvePlatformHeaderForOrderClass('wxapp'));
        $this->assertSame('offline', $r->resolvePlatformHeaderForOrderClass('pointsmall'));
    }

    public function testEmptyDefaultPlatFallsBackToOffline(): void
    {
        config(['shuyun_open_platform.default_plat_code' => '   ']);
        $r = new ShuyunOpenPlatformOrderPlatformResolver();
        $this->assertSame('offline', $r->resolvePlatformHeaderForOrderClass('wxapp'));
    }
}
