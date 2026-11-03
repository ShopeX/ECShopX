<?php

declare(strict_types=1);

namespace Tests\PaymentBundle;

use PaymentBundle\Http\Api\V1\Action\Payment;
use TestCase;

/**
 * ECX-10766：支付配置读写在 pay_type 未识别时须抛业务异常，
 * 不得落到 Undefined variable $paymentsService。
 */
class PaymentSettingUnsupportedPayTypeTest extends TestCase
{
    public function testSetPaymentSettingThrowsForUnsupportedPayType(): void
    {
        $body = $this->methodBody(Payment::class, 'setPaymentSetting');

        $this->assertStringContainsString("trans('payment.unsupported_payment_type')", $body);
        $this->assertStringContainsString('ResourceException', $body);
        $this->assertTrue(
            $this->unsupportedGuardAppearsBeforePaymentsServiceUse($body),
            'setPaymentSetting must reject unknown pay_type before new PaymentsService($paymentsService)'
        );
    }

    public function testGetPaymentSettingThrowsForUnsupportedPayType(): void
    {
        $body = $this->methodBody(Payment::class, 'getPaymentSetting');

        $this->assertStringContainsString("trans('payment.unsupported_payment_type')", $body);
        $this->assertStringContainsString('ResourceException', $body);
        $this->assertTrue(
            $this->unsupportedGuardAppearsBeforePaymentsServiceUse($body),
            'getPaymentSetting must reject unknown pay_type before new PaymentsService($paymentsService)'
        );
    }

    private function unsupportedGuardAppearsBeforePaymentsServiceUse(string $body): bool
    {
        $guardPos = strpos($body, "trans('payment.unsupported_payment_type')");
        $usePos = strpos($body, 'new PaymentsService($paymentsService)');

        return $guardPos !== false && $usePos !== false && $guardPos < $usePos;
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
