<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PaymentBundle\Services\Payments;

use PaymentBundle\Interfaces\Payment;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AlipayAppService extends AlipayService implements Payment
{
    /**
     * 获取支付实例
     */
    public function getPayment($companyId, $returnUrl = '')
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            return app('alipay.app.paymentApp')->paymentApp($paymentSetting, $returnUrl);
        } else {
            throw new BadRequestHttpException('支付宝信息未配置，请联系商家');
        }
    }

    /**
     * 预存款充值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'alipayapp',
            'attach' => 'depositRecharge',
        ];
        $attributes = [
            'out_trade_no' => $data['deposit_trade_id'],
            'total_amount' => bcdiv($data['money'], 100, 2),
            'subject' => $data['shop_name'] . '充值',
            'passback_params' => urlencode(http_build_query($passbackParams)),
        ];

        return $this->configForPayment($attributes, $data['company_id'], $data['deposit_trade_id']);
    }

    /**
     * 获取小程序支付需要的参数
     * 小程序交易支付调用
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'alipayapp',
        ];
        $attributes = [
            'out_trade_no' => $data['trade_id'],
            'total_amount' => bcdiv($data['pay_fee'], 100, 2),
            'subject' => $data['body'],
            'passback_params' => urlencode(http_build_query($passbackParams)),
        ];

        return $this->configForPayment($attributes, $data['company_id'], $data['return_url']);
    }

    /**
     * 对微信进行统一下单
     * 并且获取小程序支付需要的参数
     */
    private function configForPayment($attributes, $companyId, $returnUrl)
    {
        $payment = $this->getPayment($companyId, $returnUrl);

        $result['config'] = $payment->app($attributes)->getContent();
//        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
        return $result;
//        } else {
//            app('log')->debug('wechat payment params:' . json_encode($attributes));
//            app('log')->debug('wechat payment Message Error result:' . $result);
//            throw new BadRequestHttpException('支付失败');
//        }
    }
}
