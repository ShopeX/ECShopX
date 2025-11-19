<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PaymentBundle\Interfaces;

interface Payment
{
    /**
     * 存储支付方式配置
     */
    public function setPaymentSetting($companyId, $params);

    /**
     * 获取支付方式的配置
     */
    public function getPaymentSetting($companyId);

    /**
     * 会员储值卡储值支付
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data);

    /**
     * 进行支付
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data);

    /**
     * 获取支付订单状态信息
     */
    public function getPayOrderInfo($companyId, $trade_id);

    /**
     * 获取退款订单状态信息
     */
    public function getRefundOrderInfo($companyId, $data);
}
