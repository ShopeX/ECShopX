<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PaymentBundle\Services\Payments;

use PaymentBundle\Interfaces\Payment;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AlipayMiniService extends AlipayService implements Payment
{

    /**
     * 获取支付实例
     */
    public function getPayment($companyId, $returnUrl = '')
    {
        // HACK: temporary solution
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            return app('alipay.app.paymentMini')->paymentMini($paymentSetting, $returnUrl);
        } else {
            throw new BadRequestHttpException('支付宝信息未配置，请联系商家');
        }
    }

    /**
     * 预存款充值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        // HACK: temporary solution
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'alipaymini',
            'attach' => 'depositRecharge',
        ];
        $attributes = [
            'out_trade_no' => $data['deposit_trade_id'],
            'total_amount' => bcdiv($data['money'], 100, 2),
            'subject' => $data['shop_name'] . '充值',
            'passback_params' => urlencode(http_build_query($passbackParams)),
            'buyer_id' => $data['alipay_user_id'],
        ];

        return $this->configForPayment($attributes, $data['company_id'], $data['return_url']);
    }

    /**
     * 获取小程序支付需要的参数
     * 小程序交易支付调用
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'alipaymini',
        ];
        ## 公钥私钥相关配置修改
        $attributes = [
            'out_trade_no' => $data['trade_id'],
            'total_amount' => bcdiv($data['pay_fee'], 100, 2),
            'subject' => $data['body'],
            'passback_params' => urlencode(http_build_query($passbackParams)),
            'buyer_id' => $data['alipay_user_id'],
        ];

        return $this->configForPayment($attributes, $data['company_id'], $data['return_url']);
    }

    /**
     * 对支付宝小程序进行统一下单
     * 并且获取小程序支付需要的参数
     */
    private function configForPayment($attributes, $companyId, $returnUrl)
    {
        $payment = $this->getPayment($companyId, $returnUrl);
        $result['trade_no'] = $payment->mini($attributes)->get('trade_no');
        return $result;
    }

}
