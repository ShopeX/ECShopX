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
use PointBundle\Services\PointMemberService;

trait MemberPointTrait
{
    /**
     * 处理数据
     * @param array $memberOrderList
     */
    protected function handleDataToList(array &$memberOrderList)
    {
        foreach ($memberOrderList as &$item) {
            $newItem = [
                // 积分记录id
                "id" => (int)($item["id"] ?? ""),
                // 用户手机号
                "mobile" => (string)($item["mobile"] ?? ""),
                // 操作员姓名/昵称
                "operater" => (string)($item["operater"] ?? ""),
                // 积分交易类型
                "type" => (int)($item["journal_type"] ?? 0),
                // 记录描述
                "description" => "",
                // 增加的积分值
                "increase_point" => (int)($item["income"] ?? 0),
                // 减少的积分值
                "decrease_point" => (int)($item["outcome"] ?? 0),
                // 积分变动原因（备注）
                "record" => "",
                // 订单号
                "order_id" => (string)($item["order_id"] ?? ""),
                // 外部唯一标识，外部调用方自定义的值
                "external_id" => (string)($item["external_id"] ?? ""),
                // 创建时间
                "created" => isset($item["created"]) ? Carbon::createFromTimestamp($item["created"])->toDateTimeString() : "",
            ];

            // 设置 积分变动原因（备注）
            if ($newItem["type"] == PointMemberService::JOURNAL_TYPE_OPENAPI) {
                $newItem["record"] = (string)($item["operater_remark"] ?? "");
            } else {
                $newItem["record"] = (string)($item["point_desc"] ?? "");
            }

            // 积分值
            $point = "";
            if ($newItem["increase_point"] > 0) {
                $point = sprintf("+%d", $newItem["increase_point"]);
            } elseif ($newItem["decrease_point"] > 0) {
                $point = sprintf("-%d", $newItem["increase_point"]);
            }
            // 记录描述
            $newItem["description"] = sprintf("系统 于%s 给 会员(%s) %s %s", $item["created"] ?? "", $newItem["mobile"], PointMemberService::JOURNAL_TYPE_MAP[$item["journal_type"]] ?? "其他", $point);

            $item = $newItem;
        }
    }
}
