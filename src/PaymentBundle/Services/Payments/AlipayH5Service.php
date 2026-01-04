<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace PaymentBundle\Services\Payments;

use PaymentBundle\Interfaces\Payment;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AlipayH5Service extends AlipayService implements Payment
{
    /**
     * 获取支付实例
     */
    public function getPayment($companyId, $returnUrl = '')
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            return app('alipay.app.paymentH5')->paymentH5($paymentSetting, $returnUrl);
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
            'pay_type' => 'alipayh5',
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
            'pay_type' => 'alipayh5',
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

        $result['payment'] = $payment->wap($attributes)->getContent();
//        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
        return $result;
//        } else {
//            app('log')->debug('wechat payment params:' . json_encode($attributes));
//            app('log')->debug('wechat payment Message Error result:' . $result);
//            throw new BadRequestHttpException('支付失败');
//        }
    }
}
