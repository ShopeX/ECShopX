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

namespace OpenapiBundle\Traits\Member;

use Carbon\Carbon;
use OpenapiBundle\Services\Distributor\DistributorService;
use OpenapiBundle\Services\Order\OrdersNormalOrdersService;

trait MemberOrderTrait
{
    /**
     * 处理会员订单表的数据内容
     * @param array $memberOrderList
     */
    protected function handleDataToList(array &$memberOrderList)
    {
        foreach ($memberOrderList as &$item) {
            // 时间格式整合
            if (isset($item["create_time"])) {
                $item["create_time"] = Carbon::createFromTimestamp((int)$item["create_time"])->toDateTimeString();
                //$item["create_time"] = Carbon::createFromTimestamp($item["create_time"])->toDateTimeString();
            }
            // 门店id做转换
            if (isset($item["distributor_id"])) {
                $item["distributor_id"] = (int)$item["distributor_id"];
            }
            // 类型、状态的字段转换
            foreach ([
                         "order_class" => OrdersNormalOrdersService::ORDER_CLASS_MAP,
                         "order_type" => OrdersNormalOrdersService::ORDER_TYPE_MAP,
                         "type" => OrdersNormalOrdersService::TYPE_MAP,
                         "order_status" => OrdersNormalOrdersService::ORDER_STATUS_MAP,
                         "ziti_status" => OrdersNormalOrdersService::ZITI_STATUS_MAP,
                         "cancel_status" => OrdersNormalOrdersService::CANCEL_STATUS_MAP,
                         "pay_status" => OrdersNormalOrdersService::PAY_STATUS_MAP,
                         "audit_status" => OrdersNormalOrdersService::AUDIT_STATUS_MAP
                     ] as $field => $map) {
                if (!isset($data[$field])) {
                    continue;
                }
                $item[$field] = (string)$field;
            }
            // 价格转成元
            foreach ([
                         "total_fee", // 订单金额，以分为单位
                         "point_fee",  // 积分抵扣金额，以分为单位
                     ] as $column) {
                if (isset($item[$column])) {
                    $item[$column] = is_numeric($item[$column]) ? (string)bcdiv($item[$column], 100, 2) : "";
                }
            }
            // 积分相关的强类型转换
            foreach ([
                         "get_points", // 订单获取积分
                         "bonus_points", // 购物赠送积分
                         "extra_points", // 订单获取额外的积分
                         "point_use" // 积分抵扣使用的积分数
                     ] as $column) {
                if (isset($item[$column])) {
                    $item[$column] = (int)$item[$column];
                }
            }
        }
    }

    /**
     * 追加门店信息
     * @param int $companyId
     * @param array $memberOrderList
     */
    protected function appendDistributorInfoToList(int $companyId, array &$memberOrderList)
    {
        // 门店列表的信息
        $distributorArray = [];
        $distributorIds = (array)array_column($memberOrderList, "distributor_id");
        if (!empty($distributorIds)) {
            $distributorIds = array_unique($distributorIds);
            $result = (new DistributorService())->list(["company_id" => $companyId, "distributor_id" => $distributorIds], 1, count($distributorIds), [], "distributor_id,name");
            $distributorArray = (array)array_column($result["list"], "name", "distributor_id");
        }

        foreach ($memberOrderList as &$item) {
            $distributorId = $item["distributor_id"] ?? 0;
            $item["distributor_name"] = $distributorArray[$distributorId] ?? "";
        }
    }

    /**
     * 追加总积分数
     * @param array $memberOrderList
     */
    protected function appendTotalPointToList(array &$memberOrderList)
    {
        foreach ($memberOrderList as &$item) {
            $item["total_points"] = 0;
            foreach ([
                         "get_points", // 订单获取积分
                         "bonus_points", // 购物赠送积分
                         "extra_points", // 订单获取额外的积分
                     ] as $column) {
                if (isset($item[$column])) {
                    $item["total_points"] += (int)$item[$column];
                }
            }
        }
    }
}
