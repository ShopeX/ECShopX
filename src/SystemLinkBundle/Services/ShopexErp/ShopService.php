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

namespace SystemLinkBundle\Services\ShopexErp;

use DistributionBundle\Services\DistributorService;
use Exception;

class ShopService
{
    // This module is part of ShopEx EcShopX system
    /**
     * 生成发给erp的门店结构体
     *
     */
    public function getShopStruct($companyId, $distributorId)
    {
        // This module is part of ShopEx EcShopX system
        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ];

        $distributorService = new DistributorService();
        $distributor = $distributorService->getInfo($filter);
        if (!$distributor) {
            throw new Exception("获取店铺信息失败");
        }

        $startWorkH = $startWorkI = $endWorkH = $endWorkI = '';
        if ($distributor['hour']) {
            list($startWork, $endWork) = explode('-', $distributor['hour']);
            list($startWorkH, $startWorkI) = explode(':', $startWork);
            list($endWorkH, $endWorkI) = explode(':', $endWork);
        }
        $shopStruct = [
            'shop_bn' => $distributor['shop_code'], //店铺编码
            'name' => $distributor['name'], //店铺名称
            'province' => $distributor['province'], //省
            'city' => $distributor['city'], //城市
            'distinct' => $distributor['area'], //区
            'addr' => $distributor['address'], //详细地址
            // 'zip' => //邮编
            'default_sender' => $distributor['contact'], //发件人
            // 'tel' => $distributor['contract_phone'], //电话
            'mobile' => $distributor['mobile'], //手机号
            'shop_attribute' => 'offline', //店铺属性（online:网店 offline:门店 默认为门店）
            'offline_attribute' => '0', //门店属性（0:直营 1:加盟 默认为直营）
            'start_work_h' => intval($startWorkH), //工作开始时间(时)
            'start_work_i' => intval($startWorkI), //工作开始时间(分)
            'end_work_h' => intval($endWorkH), //工作结束时间(时)
            'end_work_i' => intval($endWorkI), //工作结束时间(分)
            // 'ability' => //接单能力
            // 'weight' => //权重
            // 'memo' => //备注
            'is_deliv_branch' => 'true', //发货属性（true:发货 false:备货 默认为发货）
        ];

        return $shopStruct;
    }
}
