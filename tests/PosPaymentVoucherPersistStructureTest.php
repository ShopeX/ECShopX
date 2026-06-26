<?php

/**
 * store-ops-buy-now-cloud-stock：POS 凭证落库与支付入口（结构断言）
 */

class PosPaymentVoucherPersistStructureTest extends TestCase
{
    public function testPaymentServicePersistsVoucherAfterPosSuccess(): void
    {
        $src = file_get_contents(__DIR__ . '/../src/PaymentBundle/Services/PaymentService.php');
        $this->assertStringContainsString('pos_payment_voucher_url', $src);
        $this->assertStringContainsString('PosPaymentVoucherValidator', $src);
        $this->assertStringContainsString("=== 'pos'", $src);
    }
}
