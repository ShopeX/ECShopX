<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

/** @see .tasks/plans/shuyun-offline-only.md TC-API-DEL-01 */
class ShuyunOpenPlatformRoutesTest extends \TestCase
{
    public function testPlatformRegisterRouteRemoved(): void
    {
        $apiRoutes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/api/shuyun_open_platform.php');
        $this->assertStringNotContainsString('platform/register', $apiRoutes);
        $this->assertStringNotContainsString('shuyun.open_platform.platform.register', $apiRoutes);
        $this->assertStringNotContainsString('postCreatePlatform', $apiRoutes);
    }
}
