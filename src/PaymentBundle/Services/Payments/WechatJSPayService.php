<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PaymentBundle\Services\Payments;

use PaymentBundle\Interfaces\Payment;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WechatJSPayService extends WechatPayService implements Payment
{
    /**
     * 获取支付实例
     */
    public function getPayment($authorizerAppId, $wxaAppId, $companyId)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            if (isset($paymentSetting['is_servicer']) && $paymentSetting['is_servicer'] == 'true') {
                return app('easywechat.manager')->paymentH5($paymentSetting['app_id'], $paymentSetting['merchant_id'], $paymentSetting['key'], '', '', $paymentSetting['servicer_app_id'], $paymentSetting['servicer_merchant_id']);
            } else {
                return app('easywechat.manager')->paymentH5($paymentSetting['app_id'], $paymentSetting['merchant_id'], $paymentSetting['key']);
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
            'pay_type' => 'wxpayjs',
            'attach' => 'depositRecharge',
        ];
        $attributes = [
            'trade_type' => 'JSAPI',
            'body' => $data['shop_name'].'充值',
            'detail' => $data['detail'],
            'out_trade_no' => $data['deposit_trade_id'],
            'total_fee' => $data['money'], // 单位：分
            'notify_url' => config('common.wechat_payment_notify'),
            'openid' => $data['open_id'],
            'attach' => urlencode(http_build_query($passbackParams)),
            'spbill_create_ip' => get_client_ip(),
        ];

        return $this->configForPayment($attributes, $authorizerAppId, $wxaAppId, $data['company_id']);
    }

    /**
     * 获取小程序支付需要的参数
     * 小程序交易支付调用
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        // 判断支付方式是否配置
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        $data['mch_id'] = $paymentSetting['merchant_id'];
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'wxpayjs',
        ];
        $attributes = [
            'trade_type' => 'JSAPI',
            'body' => $data['body'],
            'detail' => $data['detail'],
            'out_trade_no' => $data['trade_id'],
            'total_fee' => $data['pay_fee'], // 单位：分
            'notify_url' => config('common.wechat_payment_notify'),
            'time_expire' => date('YmdHis', (time() + 300)),
            'attach' => urlencode(http_build_query($passbackParams)),
            'openid' => $data['open_id'],
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
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            $config = $payment->jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组
            return $config;
        } else {
            app('log')->debug('wechat payment params:'. json_encode($attributes));
            app('log')->debug('wechat payment Message Error result:'. json_encode($result));
            // throw new BadRequestHttpException($result->err_code_des);
            throw new BadRequestHttpException($result['err_code_des'] ?? '支付失败');
        }
    }

}
