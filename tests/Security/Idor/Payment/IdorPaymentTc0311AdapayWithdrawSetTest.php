<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Payment;

use AdaPayBundle\Http\Api\V1\Action\AdapayWithdrawSet;
use TestCase;

/**
 * codex-security-03-high-idor T21 / F-002 / TC-03-11
 */
class IdorPaymentTc0311AdapayWithdrawSetTest extends TestCase
{
    /**
     * TC-03-11 / AC-03-01：AdapayWithdrawSet save 须强制 auth distributor_id，禁止异店参数。
     * #given save 源码
     * #when 检查 distributor 绑定
     * #then 不得直接使用请求 distributor_id 覆盖 auth
     */
    public function testTc0311AdapayWithdrawSetSaveUsesAuthDistributorId(): void
    {
        #given
        $body = $this->methodBody(AdapayWithdrawSet::class, 'save');

        #when / #then
        $this->assertStringContainsString('$auth[\'distributor_id\']', $body);
        $this->assertMatchesRegularExpression(
            '/\$distributorId\s*=\s*\(int\)\s*\$auth\[\'distributor_id\'\]/',
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
