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
