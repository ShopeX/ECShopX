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

namespace OpenapiBundle\Filter\Distributor;

use OpenapiBundle\Filter\BaseFilter;

/**
 * 店铺的过滤条件
 * Class DistributorFilter
 * @package OpenapiBundle\Filter\Distributor
 */
class DistributorFilter extends BaseFilter
{
    protected function init()
    {
        // 筛选 - 店铺ID
        if (isset($this->requestData["distributor_id"])) {
            $this->filter["distributor_id"] = $this->requestData["distributor_id"];
        }
        // 筛选 - 店铺号
        if (isset($this->requestData["shop_code"])) {
            $this->filter["shop_code"] = $this->requestData["shop_code"];
        }
        // 筛选 - 店铺名称
        if (isset($this->requestData["distributor_name"])) {
            $this->filter["name|contains"] = $this->requestData["distributor_name"];
        }
        // 筛选-店铺状态（0废弃、1启用、2禁用）
        if (isset($this->requestData["status"]) && $this->requestData["status"] !== "") {
            switch ($this->requestData["status"]) {
                case 0:
                    $this->filter["is_valid"] = "delete";
                    break;
                case 1:
                    $this->filter["is_valid"] = "true";
                    break;
                case 2:
                    $this->filter["is_valid"] = "false";
                    break;
                default:
                    $this->filter["is_valid"] = "";
            }
        }
        // 筛选 - 省（店铺所在省，需按管理后台对应标准名称进行填写）
        if (isset($this->requestData["province"])) {
            $this->filter["province|contains"] = $this->requestData["province"];
        }
        // 筛选 - 市（店铺所在省，需按管理后台对应标准名称进行填写）
        if (isset($this->requestData["city"])) {
            $this->filter["city|contains"] = $this->requestData["city"];
        }
        // 筛选 - 区（店铺所在省，需按管理后台对应标准名称进行填写）
        if (isset($this->requestData["area"])) {
            $this->filter["area|contains"] = $this->requestData["area"];
        }
        // 筛选 - 店铺联系人姓名
        if (isset($this->requestData["contact_username"])) {
            $this->filter["contact|contains"] = $this->requestData["contact_username"];
        }
        // 筛选 - 店铺联系人手机号
        if (isset($this->requestData["contact_mobile"])) {
            $this->filter["contract_phone"] = $this->requestData["contact_mobile"];
        }
    }
}
