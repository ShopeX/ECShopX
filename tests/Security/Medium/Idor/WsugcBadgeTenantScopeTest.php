<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor;

use WsugcBundle\Http\Api\V1\Action\BadgeController;
use TestCase;

/**
 * codex-security-04-medium T31-RED / F-093..095 / TC-04-01c
 */
class WsugcBadgeTenantScopeTest extends TestCase
{
    /**
     * TC-04-01c：角标创建/删除须校验 badge 归属当前租户。
     * #given BadgeController createBadge/deleteBadge
     * #when 检查租户 scope
     * #then 须调用 WsugcTenantScopeGuard
     */
    public function testTc0401cBadgeMutationsScopeByAuthCompanyId(): void
    {
        #given
        $createBody = $this->methodBody(BadgeController::class, 'createBadge');
        $deleteBody = $this->methodBody(BadgeController::class, 'deleteBadge');

        #when / #then
        $this->assertStringContainsString('WsugcTenantScopeGuard', $createBody);
        $this->assertStringContainsString('WsugcTenantScopeGuard', $deleteBody);
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
