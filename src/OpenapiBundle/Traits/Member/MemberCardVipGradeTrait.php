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

trait MemberCardVipGradeTrait
{
    protected function handleDataToList(array &$list)
    {
        foreach ($list as &$item) {
            // 阶段价格表
            $monthlyFee = null;
            $quarterFee = null;
            $yearFee = null;
            if (isset($item["price_list"])) {
                $item["price_list"] = (array)jsonDecode($item["price_list"] ?? null);
                foreach ($item["price_list"] as $priceItem) {
                    $name = $priceItem["name"] ?? "";
                    switch ($name) {
                        case "monthly":
                            $monthlyFee = $priceItem["price"] ?? null;
                            break;
                        case "quarter":
                            $quarterFee = $priceItem["price"] ?? null;
                            break;
                        case "year":
                            $yearFee = $priceItem["price"] ?? null;
                            break;

                    }
                }
            }
            // 会员权益
            if (isset($item["privileges"])) {
                $item["privileges"] = (array)jsonDecode($item["privileges"] ?? null);
            }
            // 创建时间
            if (isset($item["created"])) {
                $item["created"] = Carbon::createFromTimestamp((int)$item["created"])->toDateTimeString();
            }
            // 更新时间
            if (isset($item["updated"])) {
                $item["updated"] = Carbon::createFromTimestamp((int)$item["updated"])->toDateTimeString();
            }
            $item = [
                //付费等级ID
                "vip_grade_id" => (int)($item["vip_grade_id"] ?? 0),
                // 付费等级类型（vip:普通付费;svip:高级付费）
                "type" => (string)($item["lv_type"] ?? ""),
                //等级名称
                "grade_name" => (string)($item["grade_name"] ?? ""),
                // 30天付费会员，购买所需金额（以元为单位）
                "monthly_fee" => $monthlyFee,
                // 90天付费会员，购买所需金额（以元为单位）
                "quarter_fee" => $quarterFee,
                // 3650天付费会员，购买所需金额（以元为单位）
                "year_fee" => $yearFee,
                // 会员折扣
                "discount" => (string)($item["privileges"]["discount_desc"] ?? 0),
                // 购买引导语
                "guide_title" => (string)($item["guide_title"] ?? ""),
                // 详细说明
                "description" => (string)($item["description"] ?? ""),
                // 付费等级卡背景图
                "background_pic_url" => (string)($item["background_pic_url"] ?? ""),
                //是否默认（0.否 1.是）
                "is_default" => (int)($item["is_default"] ?? 0),
                // 是否禁用（0.否 1.是）
                "is_disabled" => (int)($item["is_disabled"] ?? 0),
                //外部唯一标识，外部调用方自定义的值	C10086
                "external_id" => (string)($item["external_id"] ?? ""),
                //创建时间（日期格式:yyyy-MM-dd HH:mm:ss）
                "created" => (string)($item["created"] ?? ""),
                //更新时间（日期格式:yyyy-MM-dd HH:mm:ss）
                "updated" => (string)($item["updated"] ?? ""),
            ];
        }
    }
}
