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

class WechatAppPayService extends WechatPayService implements Payment
{
    /**
     * 获取支付实例
     */
    public function getPayment($authorizerAppId, $wxaAppId, $companyId)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            if (isset($paymentSetting['is_servicer']) && $paymentSetting['is_servicer'] == 'true') {
                return app('easywechat.manager')->paymentApp($paymentSetting['app_app_id'], $paymentSetting['merchant_id'], $paymentSetting['key'], '', '', $paymentSetting['servicer_app_id'], $paymentSetting['servicer_merchant_id']);
            } else {
                return app('easywechat.manager')->paymentApp($paymentSetting['app_app_id'], $paymentSetting['merchant_id'], $paymentSetting['key']);
            }
        } else {
            throw new BadRequestHttpException('微信支付信息未配置，请联系商家');
        }
    }

    /**
     * 预存款充值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'wxpayapp',
            'attach' => 'depositRecharge',
        ];
        $attributes = [
            'trade_type' => 'APP',
            'body' => $data['shop_name'].'充值',
            'detail' => $data['detail'],
            'out_trade_no' => $data['deposit_trade_id'],
            'total_fee' => $data['money'], // 单位：分
            'notify_url' => config('common.wechat_payment_notify'),
            'attach' => urlencode(http_build_query($passbackParams)),
            'spbill_create_ip' => get_client_ip(),
        ];

        return $this->configForPayment($attributes, $authorizerAppId, $wxaAppId, $data['company_id']);
    }

    /**
     * 获取APP支付需要的参数
     * APP交易支付调用
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        // 判断支付方式是否配置
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        $data['mch_id'] = $paymentSetting['merchant_id'];
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'wxpayapp',
        ];
        $attributes = [
            'trade_type' => 'APP',
            'body' => $data['body'],
            'detail' => $data['detail'],
            'out_trade_no' => $data['trade_id'],
            'total_fee' => $data['pay_fee'], // 单位：分
            'notify_url' => config('common.wechat_payment_notify'),
            'time_expire' => date('YmdHis', (time() + 300)),
            'attach' => urlencode(http_build_query($passbackParams)),
            'spbill_create_ip' => get_client_ip(),
        ];

        return $this->configForPayment($attributes, $authorizerAppId, $wxaAppId, $data['company_id']);
    }

    /**
     * 对微信进行统一下单
     * 并且获取小程序支付需要的参数
     */
    private function configForPayment($attributes, $authorizerAppId, $wxaAppId, $companyId)
    {
        //服务商模式openid要换成sub_openid
        $paymentSetting = $this->getPaymentSetting($companyId);
        if (isset($paymentSetting['is_servicer']) && $paymentSetting['is_servicer'] == 'true') {
            $attributes['sub_openid'] = $attributes['openid'];
            unset($attributes['openid']);
        }

        $payment = $this->getPayment($authorizerAppId, $wxaAppId, $companyId);
        $result = $payment->order->unify($attributes);

        if ($result['return_code'] == 'SUCCESS') {
            $config = [
                'appid' => $paymentSetting['app_app_id'],
                'partnerid' => $paymentSetting['merchant_id'],
                'prepayid' => $result['prepay_id'],
                'noncestr' => uniqid(),
                'timestamp' => strval(time()),
                'package' => 'Sign=WXPay',
            ];

            $config['sign'] = $this->generateSign($config, $paymentSetting['key'], 'md5');

            $return['config'] = json_encode($config);
            return $return;
        } else {
            app('log')->debug('wechat payment params:'. json_encode($attributes));
            app('log')->debug('wechat payment Message Error result:'. json_encode($result));
            throw new BadRequestHttpException($result['err_code_des'] ?? '支付失败');
        }
    }

}
