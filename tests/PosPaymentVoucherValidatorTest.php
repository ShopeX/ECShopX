<?php

/**
 * store-ops-buy-now-cloud-stock：POS 凭证 URL 校验（TC-PAY-01、TC-PAY-02）
 */

use Dingo\Api\Exception\ResourceException;
use PaymentBundle\Services\PosPaymentVoucherValidator;

class PosPaymentVoucherValidatorTest extends TestCase
{
    /**
     * TC-PAY-01：合法 https URL 通过。
     */
    public function testTcPay01ValidHttpsPasses(): void
    {
        PosPaymentVoucherValidator::validateNonEmpty('https://cdn.example.com/voucher/abc.png');
        $this->addToAssertionCount(1);
    }

    /**
     * TC-PAY-02：非法 scheme 拒绝（不传凭证由调用方不调用本方法即可，等价于无凭证）。
     */
    public function testTcPay02InvalidSchemeThrows(): void
    {
        $this->expectException(ResourceException::class);
        PosPaymentVoucherValidator::validateNonEmpty('javascript:alert(1)');
    }

    public function testTooLongThrows(): void
    {
        $this->expectException(ResourceException::class);
        PosPaymentVoucherValidator::validateNonEmpty('https://x.com/' . str_repeat('a', PosPaymentVoucherValidator::MAX_LENGTH));
    }
}
