<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;

class ShuyunOpenPlatformGatewayShopIdResolverTest extends \TestCase
{
    public function testResolveAppendsDefaultOffSuffix(): void
    {
        config(['shuyun_open_platform.offline_plat_id_suffix' => '-off']);
        $sut = new ShuyunOpenPlatformGatewayShopIdResolver();
        $this->assertSame('7-off', $sut->resolve(['distributor_id' => 7, 'shop_code' => 'CODE']));
    }

    public function testResolveUsesDistributorIdAndIgnoresShopCode(): void
    {
        config(['shuyun_open_platform.offline_plat_id_suffix' => '-off']);
        $sut = new ShuyunOpenPlatformGatewayShopIdResolver();
        $v = $sut->resolve(['distributor_id' => 7, 'shop_code' => 'CODE']);
        $this->assertSame('7-off', $v);
    }

    public function testResolveDoesNotUseBareIdOrLegacyOfflineSuffix(): void
    {
        config(['shuyun_open_platform.offline_plat_id_suffix' => '-off']);
        $sut = new ShuyunOpenPlatformGatewayShopIdResolver();
        $result = $sut->resolve(['distributor_id' => 100]);
        $this->assertSame('100-off', $result);
        $this->assertNotSame('100', $result);
        $this->assertNotSame('100-offline', $result);
    }

    public function testResolveDoesNotDoubleAppendSuffix(): void
    {
        config(['shuyun_open_platform.offline_plat_id_suffix' => '-off']);
        $sut = new ShuyunOpenPlatformGatewayShopIdResolver();
        $this->assertSame('100-off', $sut->resolve(['distributor_id' => 100]));
    }

    public function testResolveWithEmptySuffixReturnsBareId(): void
    {
        config(['shuyun_open_platform.offline_plat_id_suffix' => '']);
        $sut = new ShuyunOpenPlatformGatewayShopIdResolver();
        $this->assertSame('7', $sut->resolve(['distributor_id' => 7]));
    }

    public function testMissingIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ShuyunOpenPlatformGatewayShopIdResolver())->resolve(['shop_code' => 'X']);
    }
}
