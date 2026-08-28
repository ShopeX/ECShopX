<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Payment;

use PaymentBundle\Http\Api\V1\Action\Payment;
use TestCase;

/**
 * codex-security-03-high-idor T21 / F-021,F-022 / TC-03-04
 */
class IdorPaymentTc0304PaymentSettingsTest extends TestCase
{
    /**
     * TC-03-04 / AC-03-03：支付设置读须校验 distributor 归属。
     * #given getPaymentSetting 源码
     * #when 检查 distributor 绑定
     * #then 须调用 PaymentTenantScopeGuard::resolvePaymentDistributorId
     */
    public function testTc0304PaymentSettingsReadResolvesAuthorizedDistributorId(): void
    {
        #given
        $body = $this->methodBody(Payment::class, 'getPaymentSetting');

        #when / #then
        $this->assertStringContainsString('resolvePaymentDistributorId', $body);
    }

    /**
     * TC-03-04 / AC-03-03：支付设置写须校验 distributor 归属。
     * #given setPaymentSetting 源码
     * #when 检查 distributor 绑定
     * #then 须调用 PaymentTenantScopeGuard::resolvePaymentDistributorId
     */
    public function testTc0304PaymentSettingsWriteResolvesAuthorizedDistributorId(): void
    {
        #given
        $body = $this->methodBody(Payment::class, 'setPaymentSetting');

        #when / #then
        $this->assertStringContainsString('resolvePaymentDistributorId', $body);
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
