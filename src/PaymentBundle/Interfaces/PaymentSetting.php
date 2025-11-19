<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PaymentBundle\Interfaces;

interface PaymentSetting
{
    /**
     * 存储支付方式配置
     *
     */
    public function setPaymentSetting($companyId, $params);

    /**
     * 获取支付方式的配置
     */
    public function getPaymentSetting($companyId);
}
