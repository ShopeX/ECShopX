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

trait MemberCardVipGradeOrderTrait
{
    /**
     * 处理数据
     * @param array $list
     */
    protected function handleDataToList(array &$list)
    {
        foreach ($list as &$item) {
            $item = [
                // 订单号
                "order_id" => (string)($item["order_id"] ?? ""),
                // 实付金额（以元为单位） 下文会做换算，这里现在单位为分
                "price" => (int)($item["price"] ?? 0),
                // 会员ID
                "user_id" => (int)($item["user_id"] ?? 0),
                // 会员手机号
                "mobile" => (string)($item["mobile"] ?? ""),
                // 付费会员卡等级ID
                "vip_grade_id" => (int)($item["vip_grade_id"] ?? 0),
                // 付费等级类型（vip:普通付费;svip:高级付费）
                "lv_type" => (string)($item["lv_type"] ?? ""),
                // 付费会员卡等级名称
                "title" => (string)($item["title"] ?? ""),
                // 付费会员卡有效天数（30、90、365）	30
                "card_type" => (int)($item["card_type"]["day"] ?? 0),
                // 会员折扣
                "discount" => (string)bcdiv(100 - (int)($item["discount"] ?? 0), 10, 1),
                //创建时间（日期格式:yyyy-MM-dd HH:mm:ss）
                "created" => Carbon::createFromTimestamp((int)($item["created"] ?? 0))->toDateTimeString(),
                //更新时间（日期格式:yyyy-MM-dd HH:mm:ss）
                "updated" => Carbon::createFromTimestamp((int)($item["updated"] ?? 0))->toDateTimeString(),
            ];
            // 价格换算成元
            $item["price"] = (string)bcdiv($item["price"], 100, 2);
        }
    }
}
