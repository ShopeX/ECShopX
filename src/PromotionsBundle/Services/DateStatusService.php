<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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