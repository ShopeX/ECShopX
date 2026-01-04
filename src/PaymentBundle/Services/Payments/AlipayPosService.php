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
use OrdersBundle\Services\TradeService;

class AlipayPosService extends AlipayService implements Payment
{
    /**
     * 获取支付实例
     */
    public function getPayment($companyId, $returnUrl = '')
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            return app('alipay.app.paymentPos')->paymentPos($paymentSetting, $returnUrl);
        } else {
            throw new BadRequestHttpException('支付宝信息未配置，请联系商家');
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

        $attributes = [
            'out_trade_no' => $data['trade_id'],
            'total_amount' => bcdiv($data['pay_fee'], 100, 2),
            'subject' => $data['body'],
            'auth_code' => $data['auth_code'],
        ];

        return $this->configForPayment($attributes, $data['company_id'], $data['return_url']);
    }

    public function query($data)
    {
        $payment = $this->getPayment($data['company_id']);
        try {
            $result = $payment->find($data['trade_id']);
            if ($result->code == '10000') {
                app('log')->debug('alipaypos payment params:'. json_encode($data));
                app('log')->debug('alipaypos payment Message Success result:'. json_encode($result));
                if ($result->trade_status == 'TRADE_SUCCESS' || $result->trade_status == 'TRADE_FINISHED') {
                    return [
                        'status' => 'SUCCESS',
                        'msg' => '支付成功',
                        'transaction_id' => $result->trade_no,
                        'trade_id' => $result->out_trade_no,
                        'pay_type' => 'alipaypos',
                    ];
                } elseif ($result->trade_status == 'WAIT_BUYER_PAY') {
                    return [
                        'status' => 'USERPAYING',
                        'msg' => '支付中，请稍后再查询支付结果',
                        'pay_type' => 'alipaypos'
                    ];
                }
            }
            app('log')->debug('alipaypos payment Message Error result:' . json_encode($result));
            throw new BadRequestHttpException('支付失败');
        } catch (\Exception $e) {
            app('log')->debug('alipaypos payment Message Error result:' . json_encode($e->getMessage()));
            throw new BadRequestHttpException('支付失败');
        }
    }

    /**
     * 对微信进行统一下单
     * 并且获取小程序支付需要的参数
     */
    private function configForPayment($attributes, $companyId, $returnUrl)
    {
        $payment = $this->getPayment($companyId, $returnUrl);
        try {
            $result = $payment->pos($attributes);
            if ($result->code == '10000') {
                $tradeService = new TradeService();
                $options['pay_type'] = 'alipaypos';
                $options['transaction_id'] = $result->trade_no;
                $tradeService->updateStatus($result->out_trade_no, 'SUCCESS', $options);
                app('log')->debug('alipaypos payment params:' . json_encode($attributes));
                app('log')->debug('alipaypos payment Message Success result:' . json_encode($result));
                return [
                    'status' => 'SUCCESS',
                    'msg' => '支付成功',
                    'pay_type' => 'alipaypos'
                ];
            } elseif ($result->code == '10003') {
                app('log')->debug('alipaypos payment params:' . json_encode($attributes));
                app('log')->debug('alipaypos payment Message Error result:' . json_encode($result));
                return [
                    'status' => 'USERPAYING',
                    'msg' => '支付中，请稍后再查询支付结果',
                    'pay_type' => 'alipaypos'
                ];
            }
        } catch (\Exception $e) {
            app('log')->debug('alipaypos payment params:' . json_encode($attributes));
            app('log')->debug('alipaypos payment Message Error result:' . $e->getMessage());
            throw new BadRequestHttpException('支付失败');
        }
    }
}
