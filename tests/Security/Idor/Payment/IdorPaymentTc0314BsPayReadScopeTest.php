<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Payment;

use BsPayBundle\Http\Api\V1\Action\Trade;
use BsPayBundle\Services\SubUserService;
use TestCase;

/**
 * codex-security-03-high-idor T21 / F-016,F-017 / TC-03-14
 */
class IdorPaymentTc0314BsPayReadScopeTest extends TestCase
{
    /**
     * TC-03-14 / F-017：BsPay trade 详情须绑定 auth company_id。
     */
    public function testTc0314BsPayTradeInfoScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(Trade::class, 'getTradeInfo');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
        $this->assertMatchesRegularExpression(
            '/getTradeInfo\s*\(\s*\$trade_id\s*,\s*\$companyId\s*\)|PaymentTenantScopeGuard/',
            $body
        );
    }

    /**
     * TC-03-14 / F-016：BsPay KYC 详情须校验 entry 归属本租户。
     */
    public function testTc0314BsPaySubApproveInfoScopesByCompanyId(): void
    {
        #given
        $body = $this->methodBody(SubUserService::class, 'getSubApproveInfo');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
        $this->assertMatchesRegularExpression(
            '/PaymentTenantScopeGuard|userEntryInfo\[[\'"]company_id[\'"]\]/',
            $body
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
