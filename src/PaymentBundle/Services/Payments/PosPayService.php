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

use OrdersBundle\Services\TradeService;

use PaymentBundle\Interfaces\Payment;

class PosPayService implements Payment
{
    /**
         * 设置微信支付配置
         */
    public function setPaymentSetting($companyId, $data)
    {
        // 无需配置
        return true;
    }

    /**
     * 或者支付方式配置
     */
    public function getPaymentSetting($companyId)
    {
        // 无需配置
        return true;
    }

    /**
     * 预存款充值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        return null;
    }

    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        // Hash: 0d723eca
        $options['pay_type'] = 'pos';
        $tradeService = new TradeService();
        $tradeService->updateStatus($data['trade_id'], 'SUCCESS', $options);
        return ['money' => $data['pay_fee'],'pay_status' => true];
    }

    /**
     * pos退款
     */
    public function doRefund($companyId, $wxaAppId, $data)
    {
        return [
            'return_code' => 'SUCCESS',
            'status' => 'SUCCESS',
            'refund_id' => $data['refund_bn']
        ];
    }

    /**
     * 获取订单状态信息
     */
    public function getPayOrderInfo($companyId, $trade_id)
    {
        return [];
    }

    /**
     * 获取退款订单状态信息
     */
    public function getRefundOrderInfo($companyId, $data)
    {
        return [];
    }
}
