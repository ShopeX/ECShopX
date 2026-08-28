<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Payment;

use BsPayBundle\Http\Api\V1\Action\Withdraw;
use TestCase;

/**
 * codex-security-03-high-idor T21 / F-010 / TC-03-02
 */
class IdorPaymentTc0302BsPayWithdrawAuditTest extends TestCase
{
    /**
     * TC-03-02 / AC-03-01：BsPay withdraw 审核须绑定 auth company_id。
     * #given audit 与 WithdrawApplyService::auditWithdraw 源码
     * #when 检查跨租户校验
     * #then auditWithdraw 须带 company_id 或调用租户归属校验
     */
    public function testTc0302BsPayWithdrawAuditScopesByAuthCompanyId(): void
    {
        #given
        $auditBody = $this->methodBody(Withdraw::class, 'audit');
        $serviceBody = $this->methodBody(\BsPayBundle\Services\WithdrawApplyService::class, 'auditWithdraw');

        #when / #then
        $this->assertStringContainsString('company_id', $serviceBody);
        $this->assertMatchesRegularExpression(
            '/PaymentTenantScopeGuard|applyInfo\[[\'"]company_id[\'"]\]/',
            $serviceBody
        );
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }
}
