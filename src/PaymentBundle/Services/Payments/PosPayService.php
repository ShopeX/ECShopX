<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
