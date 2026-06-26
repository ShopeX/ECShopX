<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;

/**
 * TC-CALLBACK-DB-05：Controller 不得使用全局 config/env 作为回调验签 secret（源码契约）。
 */
class ShuyunOpenPlatformTokenCallbackControllerConfigIsolationTest extends TestCase
{
    public function testControllerSourceDoesNotReferenceGlobalAppSecret(): void
    {
        $path = dirname(__DIR__, 2).'/src/ShuyunOpenPlatformBundle/Http/Controllers/ShuyunOpenPlatformTokenCallbackController.php';
        $src = (string) file_get_contents($path);
        $this->assertStringNotContainsString("config('shuyun_open_platform.app_secret')", $src);
        $this->assertStringNotContainsString('shuyun_open_platform.app_secret', $src);
        $this->assertStringNotContainsString('SHUYUN_OPEN_PLATFORM_APP_SECRET', $src);
    }
}
