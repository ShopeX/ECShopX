<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PaymentBundle\Services\Payments;

use PaymentBundle\Interfaces\Payment;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OrdersBundle\Services\TradeService;

class WechatPosPayService extends WechatPayService implements Payment
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
     * 获取小程序支付需要的参数
     * 小程序交易支付调用
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        if (!isset($data['auth_code']) || !$data['auth_code']) {
            throw new BadRequestHttpException('扫码失败');
        }

        // 判断支付方式是否配置
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        $data['mch_id'] = $paymentSetting['merchant_id'];
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'wxpaypos',
        ];
        $attributes = [
            'body' => $data['body'],
            'detail' => $data['detail'],
            'out_trade_no' => $data['trade_id'],
            'total_fee' => $data['pay_fee'], // 单位：分
            'time_expire' => date('YmdHis', (time() + 300)),
            'attach' => urlencode(http_build_query($passbackParams)),
            'spbill_create_ip' => get_client_ip(),
            'auth_code' => $data['auth_code'],
        ];

        return $this->configForPayment($attributes, $authorizerAppId, $wxaAppId, $data['company_id']);
    }

    public function query($data)
    {
        $payment = $this->getPayment('', '', $data['company_id']);
        $result = $payment->order->queryByOutTradeNumber($data['trade_id']);

        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS' && $result['trade_state'] == 'SUCCESS') {
            app('log')->debug('wechatpos payment params:'. json_encode($data));
            app('log')->debug('wechatpos payment Message Success result:'. json_encode($result));
            return [
                'status' => 'SUCCESS',
                'msg' => '支付成功',
                'pay_type' => 'wxpaypos',
                'transaction_id' => $result['transaction_id'],
                'trade_id' => $result['out_trade_no'],
            ];
        } else {
            if ($result['trade_state'] == 'USERPAYING') {
                return [
                    'status' => 'USERPAYING',
                    'msg' => '支付中，请稍后再查询支付结果',
                    'pay_type' => 'wxpaypos'
                ];
            }

            throw new BadRequestHttpException('支付失败');
        }
    }

    /**
     * 对微信进行统一下单
     * 并且获取小程序支付需要的参数
     */
    private function configForPayment($attributes, $authorizerAppId, $wxaAppId, $companyId)
    {
        $payment = $this->getPayment($authorizerAppId, $wxaAppId, $companyId);
        $result = $payment->base->pay($attributes);
        if ($result['return_code'] == 'SUCCESS') {
            if ($result['result_code'] == 'SUCCESS') {
                $tradeService = new TradeService();
                $options['pay_type'] = 'wxpaypos';
                $options['transaction_id'] = $result['transaction_id'];
                $tradeService->updateStatus($result['out_trade_no'], 'SUCCESS', $options);

                app('log')->debug('wechatpos payment params:'. json_encode($attributes));
                app('log')->debug('wechatpos payment Message Success result:'. json_encode($result));
                return [
                    'status' => 'SUCCESS',
                    'msg' => '支付成功',
                    'pay_type' => 'wxpaypos'
                ];
            } else {
                app('log')->debug('wechatpos payment params:'. json_encode($attributes));
                app('log')->debug('wechatpos payment Message Error result:'. json_encode($result));

                if ($result['err_code'] == 'USERPAYING') {
                    return [
                        'status' => 'USERPAYING',
                        'msg' => '支付中，请稍后再查询支付结果',
                        'pay_type' => 'wxpaypos'
                    ];
                }
                throw new BadRequestHttpException($result['err_code_des'] ?? '支付失败');
            }
        } else {
            throw new BadRequestHttpException($result['return_msg']);
        }
    }

}
