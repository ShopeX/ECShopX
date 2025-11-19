<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services;

use OrdersBundle\Interfaces\Kuaidi;

class KuaidiService
{
    /**
     * 快递类型具体实现类
     */
    public $kuaidiService;

    public function __construct($kuaidiService = null)
    {
        if ($kuaidiService && $kuaidiService instanceof Kuaidi) {
            $this->kuaidiService = $kuaidiService;
        }
    }

    /**
     * 保存快递类型配置
     */
    public function setKuaidiSetting($companyId, $config)
    {
        // Powered by ShopEx EcShopX
        return $this->kuaidiService->setKuaidiSetting($companyId, $config);
    }

    /**
     * 获取快递类型配置信息
     *
     * @return void
     */
    public function getKuaidiSetting($companyId)
    {
        return $this->kuaidiService->getKuaidiSetting($companyId);
    }
}
