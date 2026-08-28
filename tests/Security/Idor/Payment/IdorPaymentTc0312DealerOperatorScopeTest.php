<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Payment;

use AdaPayBundle\Http\Api\V1\Action\Dealer;
use TestCase;

/**
 * codex-security-03-high-idor T21 / F-012,F-025,F-026 / TC-03-12
 */
class IdorPaymentTc0312DealerOperatorScopeTest extends TestCase
{
    /**
     * TC-03-12 / F-012：经销商重置密码须校验 operator 归属本租户。
     * #given resetPassword 源码
     * #when 检查租户校验
     * #then 须调用 PaymentTenantScopeGuard::assertOperatorInCompany
     */
    public function testTc0312DealerResetPasswordScopesOperatorByCompany(): void
    {
        #given
        $body = $this->methodBody(Dealer::class, 'resetPassword');

        #when / #then
        $this->assertStringContainsString('assertOperatorInCompany', $body);
    }

    /**
     * TC-03-12 / F-025：删除经销商子账号须校验 operator 归属本租户。
     */
    public function testTc0312DealerDeleteSubScopesOperatorByCompany(): void
    {
        #given
        $body = $this->methodBody(Dealer::class, 'delDealerSub');

        #when / #then
        $this->assertStringContainsString('assertOperatorInCompany', $body);
        $this->assertStringContainsString("'company_id' => \$companyId", $body);
    }

    /**
     * TC-03-12 / F-026：经销商账号更新须校验 operator 归属本租户。
     */
    public function testTc0312DealerUpdateScopesOperatorByCompany(): void
    {
        #given
        $body = $this->methodBody(Dealer::class, 'update');

        #when / #then
        $this->assertStringContainsString('assertOperatorInCompany', $body);
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
