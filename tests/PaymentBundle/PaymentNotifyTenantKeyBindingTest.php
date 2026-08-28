<?php

declare(strict_types=1);

namespace Tests\PaymentBundle;

use PaymentBundle\Http\Controllers\PaymentNotify;

/**
 * codex-security-01-platform-baseline T06：PaymentNotify 验签密钥绑定 trade 租户（TC-01-09, TC-01-11）
 */
class PaymentNotifyTenantKeyBindingTest extends \TestCase
{
    /**
     * TC-01-09 / AC-01-08：passback 伪造 company_id 不得用于选验签密钥。
     * #given PaymentNotify::handle 源码
     * #when 检查 getPayment 入参
     * #then 不得使用 passback returnData 的 company_id
     */
    public function testTc0109DoesNotUsePassbackCompanyIdForVerificationKey(): void
    {
        #given
        $body = $this->methodBody(PaymentNotify::class, 'handle');

        #when / #then
        $this->assertStringNotContainsString(
            'getPayment($returnData[\'company_id\'])',
            $body,
            'Verification key must not be selected from passback company_id'
        );
        $this->assertStringNotContainsString(
            'getPayment($returnData["company_id"])',
            $body,
            'Verification key must not be selected from passback company_id'
        );
    }

    /**
     * TC-01-11 / AC-01-10：合法路径须从 trade/储值单解析租户后再 getPayment。
     * #given PaymentNotify::handle 源码
     * #when 检查租户解析与 getPayment 顺序
     * #then 先加载 trade，再用 trade company_id 调用 getPayment
     */
    public function testTc0111ResolvesTradeCompanyIdBeforeGetPayment(): void
    {
        #given
        $body = $this->methodBody(PaymentNotify::class, 'handle');

        #when
        $getPaymentPos = strpos($body, 'getPayment($companyId)');
        $tradeInfoPos = strpos($body, 'getInfo([');

        #then
        $this->assertNotFalse($getPaymentPos, 'handle must call getPayment with resolved company id');
        $this->assertNotFalse($tradeInfoPos, 'handle must load trade before verification');
        $this->assertLessThan($getPaymentPos, $tradeInfoPos);
        $this->assertStringContainsString('$tradeInfo[\'company_id\']', $body);
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
