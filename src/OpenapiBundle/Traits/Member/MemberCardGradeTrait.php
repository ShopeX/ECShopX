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

trait MemberCardGradeTrait
{
    protected function handleDataToList(array &$list)
    {
        foreach ($list as &$item) {
            // 升级条件
            if (isset($item["promotion_condition"])) {
                $item["promotion_condition"] = (array)jsonDecode($item["promotion_condition"] ?? null);
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
                //等级ID
                "grade_id" => (int)($item["grade_id"] ?? 0),
                //是否默认（0.否 1.是）
                "is_default" => (int)($item["default_grade"] ?? 0),
                //等级名称
                "grade_name" => (string)($item["grade_name"] ?? ""),
                //会员折扣
                "discount" => (string)($item["privileges"]["discount_desc"] ?? 0),
                //升级条件 > 累计消费金额（以元为单位）
                "total_consumption" => (string)($item["promotion_condition"]["total_consumption"] ?? 0),
                //等级卡背景图
                "background_pic_url" => (string)($item["background_pic_url"] ?? ""),
                //外部唯一标识，外部调用方自定义的值	C10086
                "external_id" => (string)($item["external_id"] ?? ""),
                //创建时间（日期格式:yyyy-MM-dd HH:mm:ss）
                "created" => (string)($item["created"] ?? ""),
                //更新时间（日期格式:yyyy-MM-dd HH:mm:ss）
                "updated" => (string)($item["updated"] ?? ""),
            ];
        }
    }

    protected function handleShuyunDataToList(array &$list)
    {
        foreach ($list as &$item) {
            // 升级条件
            if (isset($item["promotion_condition"])) {
                $item["promotion_condition"] = (array)jsonDecode($item["promotion_condition"] ?? null);
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
                //等级ID(数云)
                "grade_id" => (string)($item["external_id"] ?? ""),
                //等级名称
                "grade_name" => (string)($item["grade_name"] ?? ""),
                "grade_level" => (string)($item["promotion_condition"]["total_consumption"] ?? 0),
                //创建时间（日期格式:yyyy-MM-dd HH:mm:ss）
                "created" => (string)($item["created"] ?? ""),
                //更新时间（日期格式:yyyy-MM-dd HH:mm:ss）
                "updated" => (string)($item["updated"] ?? ""),
            ];
        }
    }
}
