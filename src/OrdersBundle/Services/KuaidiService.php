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
