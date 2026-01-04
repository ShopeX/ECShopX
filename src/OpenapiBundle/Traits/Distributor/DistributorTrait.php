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

namespace OpenapiBundle\Traits\Distributor;

use Carbon\Carbon;
use OpenapiBundle\Services\Distributor\DistributorService;

trait DistributorTrait
{
    /**
     * 处理店铺的数据格式
     * @param array $list
     */
    protected function handleDataToList(array &$list)
    {
        foreach ($list as &$item) {
            // 创建时间
            if (isset($item["created"])) {
                $item["created"] = Carbon::createFromTimestamp((int)$item["created"])->toDateTimeString();
            }
            // 更新时间
            if (isset($item["updated"])) {
                $item["updated"] = Carbon::createFromTimestamp((int)$item["updated"])->toDateTimeString();
            }
            // 状态
            $status = -1;
            if (isset($item["is_valid"])) {
                $status = DistributorService::getStatusByIsValid((string)$item["is_valid"]);
            }
            $item = [
                //店铺ID
                "distributor_id" => (int)($item["distributor_id"] ?? 0),
                //店铺号
                "shop_code" => (string)($item["shop_code"] ?? ""),
                //店铺状态（0废弃、1启用、2禁用）
                "status" => $status, //
                //店铺名称
                "distributor_name" => (string)($item["name"] ?? ""),
                //联系人姓名
                "contact_username" => (string)($item["contact"] ?? ""),
                //联系方式
                "contact_mobile" => (string)($item["mobile"] ?? ""),
                //省（店铺所在省，需按管理后台对应标准名称进行填写）
                "province" => (string)($item["province"] ?? ""),
                //市（店铺所在市，需按管理后台对应标准名称进行填写）
                "city" => (string)($item["city"] ?? ""),
                //区（店铺所在区，需按管理后台对应标准名称进行填写）
                "area" => (string)($item["area"] ?? ""),
                //国家行政区划编码（数组：省,市,区）	[“310000”, “310100”, “310104”]
                "region_codes" => (array)($item["regions_id"] ?? []),
                //行政区划名称（数组：省,市,区）	[“上海市”, “上海市”, “徐汇区”]
                "region_names" => (array)($item["regions"] ?? []),
                //详细地址（店铺所在详细地址）
                "address" => (string)($item["address"] ?? ""),
                //经度（店铺所在地址经度）
                "lng" => (string)($item["lng"] ?? ""),
                //纬度（店铺所在地址经度）
                "lat" => (string)($item["lat"] ?? ""),
                //经营时间（开始-结束）	08:00-23:00
                "hour" => (string)($item["hour"] ?? ""),
                //店铺Logo图片Url（url地址需已加入小程序域名白名单）
                "logo" => (string)($item["logo"] ?? ""),
                //是否支持自提（0否，1是，默认0）
                "is_ziti" => (int)($item["is_ziti"] ?? 0),
                // 是否支持快递（0否，1是，默认1）
                "is_delivery" => (int)($item["is_delivery"] ?? 1),
                //是否自动同步总部商品（0否，1是，默认0）	0
                "is_auto_sync_goods" => (int)($item["auto_sync_goods"] ?? 0),
                //是否开启同城配（0否，1是，默认0）	0
                "is_dada" => (int)($item["is_dada"] ?? 0),
                //是否默认店铺（0否，1是，默认0）	0
                "is_default" => (int)($item["is_default"] ?? 0),
                //创建时间（日期格式:yyyy-MM-dd HH:mm:ss）
                "created" => (string)($item["created"] ?? ""),
                //更新时间（日期格式:yyyy-MM-dd HH:mm:ss）
                "updated" => (string)($item["updated"] ?? ""),
            ];
        }
    }
}
