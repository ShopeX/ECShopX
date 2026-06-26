<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;

class LoyaltyGradeCallbackRouteSourceTest extends TestCase
{
    public function testOpenapiRoutesRegisterLoyaltyGradeCallbackEndpoint(): void
    {
        $apiRoutes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/api/shuyun_open_platform.php');
        $this->assertStringContainsString('/callback/loyalty-grade', $apiRoutes);
        $this->assertStringContainsString('shuyun.open_platform.callback.loyalty_grade.default_api', $apiRoutes);
        $this->assertStringContainsString('ShuyunOpenPlatformLoyaltyGradeCallbackController@callback', $apiRoutes);

        $thirdRoutes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/thirdparty/shuyun_open_platform_callback.php');
        $this->assertStringContainsString('/third/shuyun/open-platform/callback/loyalty-grade', $thirdRoutes);
        $this->assertStringContainsString('shuyun.open_platform.callback.loyalty_grade', $thirdRoutes);
        $this->assertStringContainsString('ShuyunOpenPlatformLoyaltyGradeCallbackController@callback', $thirdRoutes);
    }
}

