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

namespace OrdersBundle\Traits;

trait GetTeamIdTrait
{
    public function genId($identifier)
    {
        $time = time();
        $startTime = 1325347200;//2012-01-01 做为初始年
        //当前时间相距初始年的天数，4位可使用20年
        $day = floor(($time - $startTime) / 86400);

        //确定每90秒的的订单生成 一天总共有960个90秒，控制在三位
        $minute = floor(($time - strtotime(date('Y-m-d'))) / 90);

        //防止通过订单号计算出商城生成的订单数量，导致泄漏关键数据
        $redisId = app('redis')->hincrby('group:' . date('Ymd'), $minute, rand(1, 9));

        //设置过期时间
        app('redis')->expire(date('Ymd'), 86400);

        $id = $day . str_pad($minute, 3, '0', STR_PAD_LEFT) . str_pad($redisId, 5, '0', STR_PAD_LEFT) . str_pad($identifier % 10000, 4, '0', STR_PAD_LEFT);//16位

        return $id;
    }
}
