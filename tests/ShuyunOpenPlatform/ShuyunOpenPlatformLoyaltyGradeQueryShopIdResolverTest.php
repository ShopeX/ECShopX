<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformGatewayShopIdResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver;

class ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolverTest extends \TestCase
{
    /** @see .tasks/plans/shuyun-open-platform-member.md M-GRADE-SHOP-01 */
    public function testResolvesDistributorIdEvenWhenShopCodePresent(): void
    {
        config(['shuyun_open_platform.offline_plat_id_suffix' => '-off']);
        $sut = new ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver(new ShuyunOpenPlatformGatewayShopIdResolver());
        $value = $sut->resolveShopIdQueryValue([
            'distributor_id' => 112345566,
            'shop_code' => 'SHOULD_NOT_USE',
        ]);
        $this->assertSame('112345566-off', $value);
    }

    public function testMissingDistributorIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver(new ShuyunOpenPlatformGatewayShopIdResolver()))->resolveShopIdQueryValue([
            'shop_code' => 'ONLY_CODE',
        ]);
    }

    public function testNonPositiveDistributorIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ShuyunOpenPlatformLoyaltyGradeQueryShopIdResolver(new ShuyunOpenPlatformGatewayShopIdResolver()))->resolveShopIdQueryValue([
            'distributor_id' => 0,
        ]);
    }
}
