<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\WorkWechat;

use WorkWechatBundle\Http\Api\V1\Action\WorkWechat;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-107 / AC-04-01
 */
class WorkWechatVerifyDomainScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-107：域名校验文件上传须防止跨租户覆盖。
     */
    public function testTc0401VerifyDomainScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(WorkWechat::class, 'verifyDomain');

        #when / #then
        $this->assertStringContainsString('WorkWechatTenantScopeGuard', $body);
        $this->assertStringContainsString('assertVerifyDomainFileAvailableForCompany', $body);
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
