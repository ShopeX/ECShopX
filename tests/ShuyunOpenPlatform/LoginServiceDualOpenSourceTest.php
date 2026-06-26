<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use PHPUnit\Framework\TestCase;

class LoginServiceDualOpenSourceTest extends TestCase
{
    public function testWxappPreLoginSkipsLegacyMemberRegisterJobWhenOpenPlatformEnabled(): void
    {
        $path = dirname(__DIR__, 2).'/src/EspierBundle/Services/LoginService.php';
        $src = (string) file_get_contents($path);

        $this->assertStringContainsString('function isShuyunOpenPlatformEnabledForCompany', $src);
        $this->assertStringContainsString('! $this->isShuyunOpenPlatformEnabledForCompany((int) $params[\'company_id\'])', $src);
        $this->assertStringContainsString('$gotoJob = (new MemberRegisterJob(', $src);
    }

    public function testOpenGateConditionAppearsBeforeLegacyMemberRegisterJobDispatch(): void
    {
        $path = dirname(__DIR__, 2).'/src/EspierBundle/Services/LoginService.php';
        $src = (string) file_get_contents($path);

        $gatePos = strpos($src, '! $this->isShuyunOpenPlatformEnabledForCompany((int) $params[\'company_id\'])');
        $jobPos = strpos($src, '$gotoJob = (new MemberRegisterJob(');
        $this->assertNotFalse($gatePos);
        $this->assertNotFalse($jobPos);
        $this->assertLessThan($jobPos, $gatePos);
    }
}
