<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Interfaces;

interface Kuaidi
{
    /**
     * 存储快递配置
     */
    public function setKuaidiSetting($companyId, $params);

    /**
     * 获取快递的配置
     */
    public function getKuaidiSetting($companyId);
}
