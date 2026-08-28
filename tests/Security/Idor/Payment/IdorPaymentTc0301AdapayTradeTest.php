<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Payment;

use AdaPayBundle\Http\Api\V1\Action\AdapayTrade;
use TestCase;

/**
 * codex-security-03-high-idor T21 / F-013 / TC-03-01
 */
class IdorPaymentTc0301AdapayTradeTest extends TestCase
{
    /**
     * TC-03-01 / AC-03-01：AdaPay trade 详情须绑定 auth company_id，禁止跨租户读。
     * #given getTradeInfo 源码
     * #when 检查租户过滤
     * #then 须将 company_id 传入 service 或调用租户归属校验
     */
    public function testTc0301AdapayTradeInfoScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(AdapayTrade::class, 'getTradeInfo');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
        $this->assertMatchesRegularExpression(
            '/getTradeInfo\s*\(\s*\$trade_id\s*,\s*\$companyId\s*\)|PaymentTenantScopeGuard/',
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
