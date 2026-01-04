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

class DistributorItemFilter extends BaseFilter
{
    protected function init()
    {
        // 店铺ID
        if (isset($this->requestData["distributor_id"])) {
            $this->filter["distributor_id"] = $this->requestData["distributor_id"];
        }
        // 店铺号
        if (isset($this->requestData["shop_code"])) {
            $this->filter["shop_code"] = $this->requestData["shop_code"];
        }
        // 商品货号
        if (isset($this->requestData["item_code"])) {
            $this->filter["item_bn"] = $this->requestData["item_code"];
        }
        // 商品名称
        if (isset($this->requestData["item_name"])) {
            if (empty($this->requestData["item_name"])) {
                $this->filter["item_name"] = $this->requestData["item_name"];
            } else {
                $this->filter["item_name|like"] = $this->requestData["item_name"];
            }
        }
        // 商品条码
//        if (isset($this->requestData["barcode"])) {
//            $this->filter["barcode"] = $this->requestData["barcode"];
//        }
        // spu商品是否上架（0未上架，1已上架）
        if (isset($this->requestData["goods_can_sale"])) {
            $this->filter["goods_can_sale"] = $this->requestData["goods_can_sale"];
        }
        // 店铺商品是否总部发货（0否，1是）
        if (isset($this->requestData["is_total_store"])) {
            $this->filter["is_total_store"] = $this->requestData["is_total_store"];
        }
        // 商品状态（onsale 前台可销售，offline_sale前端不展示，instock 不可销售, only_show 前台仅展示）
        if (isset($this->requestData["status"])) {
            $this->filter["approve_status"] = $this->requestData["status"];
        }
    }
}
