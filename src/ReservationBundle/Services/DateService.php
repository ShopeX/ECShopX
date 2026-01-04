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

namespace ReservationBundle\Services;

class DateService
{
    /**
     * 获取指定年的周数 和 每周开始结束日期
     */
    public function getWeeks($year = null)
    {
        if (!$year) {
            $year = date('Y');
        }
        $year_start = $year. "-01-01";
        $year_end = $year. "-12-31";
        $startday = strtotime($year_start);
        if (intval(date('N', $startday)) != '1') {
            //$startday = strtotime("next monday", strtotime($year_start)); //获取年第一周的第一天日期(时间戳)
            $w = strftime('%w', $startday);
            $startday = $startday - ($w - 1) * 86400;
        }
        $year_mondy = date("Y-m-d", $startday); //获取年第一周的第一天日期(日期字符串)

        $num = 52;

        for ($i = 1; $i <= $num; $i++) {
            $j = $i - 1;
            $startDate = strtotime("$year_mondy $j week ");
            $start_date = date("Y-m-d", $startDate);
            $endDay = strtotime("$start_date +6 day");
            $week_array[$i] = ['begin_date' => $startDate, 'end_date' => $endDay];
        }
        return $week_array;
    }

    /**
     * 获取指定日期所在周的开始结束日期
     */
    public function getWeek($dateDay = null)
    {
        if (!$dateDay) {
            $dateDay = date('Y-m-d');
        }
        $beginDate = strtotime($dateDay." Sunday");
        $lastDay = date("Y-m-d", $beginDate);
        $firstDay = strtotime($lastDay." - 6 days");

        $week['begin_date'] = $firstDay;
        $week['end_date'] = $beginDate;
        return $week;
    }

    /**
     * 获取一天之内指定间隔的时间段数组
     *
     * @param dateDay 日期时间戳 1507564800
     * @param beginTime 开始时间 08:00
     * @param endTime 结束时间 20:00
     * @param timeInterval 时间间隔 60m
     * return array
     */
    public function getTimePeriod($dateDay, $beginTime, $endTime, $timeInterval = 30)
    {
        $date = date('Y-m-d', $dateDay);
        $begin = strtotime($date." ".$beginTime);
        $end = strtotime($date." ".$endTime);

        $Interval = $timeInterval * 60;
        $i = 1;
        while ($i) {
            $data['begin'] = $begin;
            $data['end'] = $begin = $begin + $Interval;
            $result[] = $data;
            if ($begin >= $end) {
                $i = 0;
            }
        }
        return $result;
    }
}
