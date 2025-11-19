<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Interfaces;

interface TradeSettingInterface
{
    /**
     * 存储配置
     */
    public function setSetting($companyId, $params);

    /**
     * 获取配置
     */
    public function getSetting($companyId);
}
