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
