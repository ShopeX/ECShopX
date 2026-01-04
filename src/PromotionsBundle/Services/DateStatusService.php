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

namespace PromotionsBundle\Services;

use Carbon\Carbon;
use PromotionsBundle\Constants\DateStatusConstant;

/**
 * 日起状态的服务
 */
class DateStatusService
{
    /**
     * 获取日期状态
     * @param string $beginDate 开始时间
     * @param string $endDate 结束时间
     * @return int 时间状态
     */
    public static function getDateStatus(string $beginDate, string $endDate): int
    {
        // 开始时间或结束时间为空时，不做处理
        if (empty($beginDate) || empty($endDate)) {
            return DateStatusConstant::UNKNOWN;
        }

        // 时间格式有误，直接返回 unknown
        try {
            $beginCarbon = is_numeric($beginDate) ? Carbon::createFromTimestamp($beginDate) : Carbon::parse($beginDate);
            $endCarbon = is_numeric($endDate) ? Carbon::createFromTimestamp($endDate) : Carbon::parse($endDate);
        } catch (\Exception $exception) {
            return DateStatusConstant::UNKNOWN;
        }

        $now = Carbon::now();
        // 开始时间 > 结束时间，返回unknown
        // if ($beginCarbon->greaterThan($endCarbon)) {
        //     return DateStatusConstant::UNKNOWN;
        // }
        // 结束时间 < 当前时间，返回已过期
        if ($endCarbon->lessThan($now)) {
            return DateStatusConstant::FINISHED;
        }
        // 开始时间 > 当前时间，返回未开始
        if ($beginCarbon->greaterThan($now)) {
            return DateStatusConstant::COMING_SOON;
        }
        // 正在进行中
        return DateStatusConstant::ON_GOING;
    }
}