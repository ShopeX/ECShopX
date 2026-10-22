<?php

declare(strict_types=1);

namespace Tests\PaymentBundle;

use PaymentBundle\Services\Payments\WechatAppPayService;
use PaymentBundle\Services\Payments\WechatH5PayService;
use PaymentBundle\Services\Payments\WechatWebPayService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WechatUnifyOrderResultCodeTest extends \TestCase
{
    /**
     * PC 扫码：return_code 成功但 result_code 失败且无 prepay_id 时，返回微信业务错误。
     */
    public function testWxpaypcBusinessFailureThrowsBadRequestInsteadOfMissingPrepayId(): void
    {
        $order = \Mockery::mock();
        $order->shouldReceive('unify')->once()->andReturn([
            'return_code' => 'SUCCESS',
            'result_code' => 'FAIL',
            'err_code' => 'NOAUTH',
            'err_code_des' => '商户号该产品权限未开通',
        ]);
        $jssdk = new class {
            public function bridgeConfig($prepayId, $json)
            {
                return ['package' => 'prepay_id='.$prepayId];
            }
        };
        $payment = new \stdClass();
        $payment->order = $order;
        $payment->jssdk = $jssdk;

        $service = \Mockery::mock(WechatWebPayService::class)->makePartial();
        $service->shouldReceive('getPaymentSetting')->andReturn(['merchant_id' => 'mch-1']);
        $service->shouldReceive('getPayment')->andReturn($payment);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('商户号该产品权限未开通');

        $service->doPay('', '', [
            'company_id' => 38,
            'body' => 'pos order',
            'detail' => 'pos order',
            'trade_id' => 'T5379566000140000',
            'pay_fee' => 100,
        ]);
    }

    /**
     * H5：return_code 成功但 result_code 失败且无 prepay_id 时，返回微信业务错误。
     */
    public function testWxpayh5BusinessFailureThrowsBadRequestInsteadOfMissingPrepayId(): void
    {
        $order = \Mockery::mock();
        $order->shouldReceive('unify')->once()->andReturn([
            'return_code' => 'SUCCESS',
            'result_code' => 'FAIL',
            'err_code' => 'NOAUTH',
            'err_code_des' => '商户号该产品权限未开通',
        ]);
        $jssdk = new class {
            public function bridgeConfig($prepayId, $json)
            {
                return ['package' => 'prepay_id='.$prepayId];
            }
        };
        $payment = new \stdClass();
        $payment->order = $order;
        $payment->jssdk = $jssdk;

        $service = \Mockery::mock(WechatH5PayService::class)->makePartial();
        $service->shouldReceive('getPaymentSetting')->andReturn(['merchant_id' => 'mch-1']);
        $service->shouldReceive('getPayment')->andReturn($payment);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('商户号该产品权限未开通');

        $service->doPay('', '', [
            'company_id' => 38,
            'body' => 'h5 order',
            'detail' => 'h5 order',
            'trade_id' => 'T5379566000140001',
            'pay_fee' => 100,
        ]);
    }

    /**
     * APP：return_code 成功但 result_code 失败且无 prepay_id 时，返回微信业务错误。
     */
    public function testWxpayappBusinessFailureThrowsBadRequestInsteadOfMissingPrepayId(): void
    {
        $order = \Mockery::mock();
        $order->shouldReceive('unify')->once()->andReturn([
            'return_code' => 'SUCCESS',
            'result_code' => 'FAIL',
            'err_code' => 'NOAUTH',
            'err_code_des' => '商户号该产品权限未开通',
        ]);
        $payment = new \stdClass();
        $payment->order = $order;

        $service = \Mockery::mock(WechatAppPayService::class)->makePartial();
        $service->shouldReceive('getPaymentSetting')->andReturn([
            'merchant_id' => 'mch-1',
            'app_app_id' => 'wx-app',
            'key' => 'pay-key',
        ]);
        $service->shouldReceive('getPayment')->andReturn($payment);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('商户号该产品权限未开通');

        $service->doPay('', '', [
            'company_id' => 38,
            'body' => 'app order',
            'detail' => 'app order',
            'trade_id' => 'T5379566000140002',
            'pay_fee' => 100,
        ]);
    }
}
