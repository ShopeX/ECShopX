<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services;

use OrdersBundle\Interfaces\TradeSettingInterface;

class TradeSettingService
{
    /**
     * 类型具体实现类
     */
    public $tradeService;

    public function __construct($tradeService = null)
    {
        if ($tradeService && $tradeService instanceof TradeSettingInterface) {
            $this->tradeService = $tradeService;
        }
    }

    /**
     * 保存类型配置
     */
    public function setSetting($companyId, $config)
    {
        return $this->tradeService->setSetting($companyId, $config);
    }

    /**
     * 获取配置信息
     *
     * @return void
     */
    public function getSetting($companyId)
    {
        return $this->tradeService->getSetting($companyId);
    }
}
