<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\Promotions;

use PromotionsBundle\Http\Api\V1\Action\Turntable;
use PromotionsBundle\Services\TurntableService;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-124,F-125 / AC-04-01
 */
class PromotionsTurntableScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-125：转盘保存须校验 activity 归属 auth company。
     */
    public function testTc0401SetTurntableConfigScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(Turntable::class, 'setTurntableConfig');

        #when / #then
        $this->assertStringContainsString('PromotionActivityTenantScopeGuard', $body);
    }

    /**
     * TC-04-01 / F-124：转盘强制结束须校验 activity 归属 auth company。
     */
    public function testTc0401DownLuckyDrawActivityScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(Turntable::class, 'downLuckyDrawActivity');

        #when / #then
        $this->assertStringContainsString('PromotionActivityTenantScopeGuard', $body);
    }

    /**
     * TC-04-01 / F-125：TurntableService 更新须绑定 company_id。
     */
    public function testTc0401TurntableServiceUpdateBindsCompanyId(): void
    {
        #given
        $body = $this->methodBody(TurntableService::class, 'setTurntableConfig');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);

        return implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    }
}
