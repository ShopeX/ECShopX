<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncWechatBindResolver;

class HistoricalSyncWechatBindResolverTest extends \TestCase
{
    public function testIsRegisterAlreadyExistsErrorDetectsChineseMessage(): void
    {
        $e = new \RuntimeException('Shuyun member.register failed: 平台账号1或手机号已被占用');
        $this->assertTrue(HistoricalSyncWechatBindResolver::isRegisterAlreadyExistsError($e));
    }

    public function testIsRegisterAlreadyExistsErrorReturnsFalseForOtherErrors(): void
    {
        $e = new \RuntimeException('Shuyun member.register failed: invalid mobile');
        $this->assertFalse(HistoricalSyncWechatBindResolver::isRegisterAlreadyExistsError($e));
    }
}
