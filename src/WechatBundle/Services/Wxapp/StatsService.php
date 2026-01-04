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

namespace WechatBundle\Services\Wxapp;

use WechatBundle\Services\OpenPlatform;
use Exception;

/**
 * 小程序统计
 */
class StatsService
{
    // 小程序调用实例化
    public $wxa;

    // 小程序appid
    public $wxaAppId;

    public function __construct($wxaAppId = null)
    {
        if ($wxaAppId) {
            $this->wxaAppId = $wxaAppId;

            $openPlatform = new OpenPlatform();
            $this->wxa = $openPlatform->getAuthorizerApplication($wxaAppId);
        }
    }

    /**
     * 获取昨日概况
     */
    public function getSummaryByDate($date)
    {
        $timestamp = strtotime($date);
        $data = $this->__summaryData($date);
        $yesterday = date('Ymd', strtotime('-1 days', $timestamp));
        $yesterdayData = $this->__summaryData($yesterday);
        $lastWeek = date('Ymd', strtotime('-7 days', $timestamp));
        $lastWeekData = $this->__summaryData($lastWeek);
        $lastMonth = date('Ymd', strtotime('-1 months', $timestamp));
        $lastMonthData = $this->__summaryData($lastMonth);

        $result = $data;
        $keyArr = ['session_cnt', 'visit_pv', 'visit_uv', 'visit_uv_new', 'share_pv', 'share_uv'];
        foreach ($keyArr as $key) {
            $dayIndex = $key.'_dayRate';
            $weekIndex = $key.'_weekRate';
            $monthIndex = $key.'_monthRate';
            $result[$dayIndex] = ($yesterdayData[$key] == 0) ? '' : ($data[$key] - $yesterdayData[$key]) * 100 / $yesterdayData[$key];
            $result[$dayIndex] = ($yesterdayData[$key] == 0) ? '' : round($result[$dayIndex], 2);
            $result[$weekIndex] = ($lastWeekData[$key] == 0) ? '' : ($data[$key] - $lastWeekData[$key]) * 100 / $lastWeekData[$key];
            $result[$weekIndex] = ($lastWeekData[$key] == 0) ? '' : round($result[$weekIndex], 2);
            $result[$monthIndex] = ($lastMonthData[$key] == 0) ? '' : ($data[$key] - $lastMonthData[$key]) * 100 / $lastMonthData[$key];
            $result[$monthIndex] = ($lastMonthData[$key] == 0) ? '' : round($result[$monthIndex], 2);
        }

        return $result;
    }

    private function __summaryData($date)
    {
        // ModuleID: 76fe2a3d
        $summaryTrendData = $this->wxa->data_cube->summaryTrend($date, $date);
        $visitTrendData = $this->wxa->data_cube->dailyVisitTrend($date, $date);
        if (isset($summaryTrendData['list']) && $summaryTrendData['list']) {
            $summaryTrend = $summaryTrendData['list'][0];
        } else {
            $summaryTrend = [
                'ref_date' => $date,
                'visit_total' => 0,
                'share_pv' => 0,
                'share_uv' => 0
            ];
        }
        if (isset($visitTrendData['list']) && $visitTrendData['list']) {
            $visitTrend = $visitTrendData['list'][0];
        } else {
            $visitTrend = [
                'ref_date' => $date,
                'session_cnt' => 0,
                'visit_pv' => 0,
                'visit_uv' => 0,
                'visit_uv_new' => 0,
                'stay_time_session' => 0,
                'visit_depth' => 0,
            ];
        }

        return array_merge($visitTrend, $summaryTrend);
    }


    /**
     * 获取小程序概况趋势
     */
    public function getSummarytrend($type = 'yesterday')
    {
        $dateInfo = $this->getDateInfo($type, 'summaryTrend');
        $result = [];
        if ($dateInfo['beginDate'] == $dateInfo['endDate']) {
            $data = $this->wxa->data_cube->summaryTrend($dateInfo['beginDate'], $dateInfo['endDate']);
            if (isset($data['list']) && $data['list']) {
                $result = $data['list'];
                $dateFormat = date('Y-m-d', $dateInfo['beginTimestamp']);
                $result[0]['ref_date'] = $dateFormat;
            }
        } else {
            for ($i = 0; $i < $dateInfo['days'] ; $i++) {
                $nextDate = date('Ymd', strtotime('+'.$i.' days', $dateInfo['beginTimestamp']));
                $nextDateFormat = date('Y-m-d', strtotime('+'.$i.' days', $dateInfo['beginTimestamp']));
                if ($nextDate <= $dateInfo['endDate']) {
                    $info = $this->wxa->data_cube->summaryTrend($nextDate, $nextDate);
                    if (isset($info['list']) && $info['list']) {
                        $info['list'][0]['ref_date'] = $nextDateFormat;
                        $result[] = $info['list'][0];
                    } else {
                        $result[] = [
                            'ref_date' => $nextDateFormat,
                            'visit_total' => 0,
                            'share_pv' => 0,
                            'share_uv' => 0
                        ];
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 获取小程序访问趋势
     */
    public function getVisitTrend($type = 'yesterday')
    {
        $result = [];
        $dateInfo = $this->getDateInfo($type, 'visitTrend');
        if ($dateInfo['beginDate'] == $dateInfo['endDate']) {
            $data = $this->wxa->data_cube->dailyVisitTrend($dateInfo['beginDate'], $dateInfo['endDate']);
            if (isset($data['list']) && $data['list']) {
                $result = $data['list'];
            }
        } elseif ($type == 'weekly' && (date('w', $dateInfo['beginTimestamp']) == '1')) {
            $data = $this->wxa->data_cube->weeklyVisitTrend($dateInfo['beginDate'], $dateInfo['endDate']);
            $result = $data['list'];
        } elseif ($type == 'monthly' && date('d', $dateInfo['beginTimestamp']) == '01') {
            $data = $this->wxa->data_cube->monthlyVisitTrend($dateInfo['beginDate'], $dateInfo['endDate']);
            if (isset($data['list']) && $data['list']) {
                $result = $data['list'];
            }
        } else {
            $list = [];
            for ($i = 0; $i < $dateInfo['days'] ; $i++) {
                $nextDate = date('Ymd', strtotime('+'.$i.' days', $dateInfo['beginTimestamp']));
                $nextDateFormat = date('Y-m-d', strtotime('+'.$i.' days', $dateInfo['beginTimestamp']));
                if ($nextDate <= $dateInfo['endDate']) {
                    $info = $this->wxa->data_cube->dailyVisitTrend($nextDate, $nextDate);
                    if (isset($info['list']) && $info['list']) {
                        $info[0]['ref_date'] = $nextDateFormat;
                        $list[] = $info['list'][0];
                    } else {
                        $list[] = [
                            'ref_date' => $nextDateFormat,
                            'session_cnt' => 0,
                            'visit_pv' => 0,
                            'visit_uv' => 0,
                            'visit_uv_new' => 0,
                            'stay_time_session' => 0,
                            'visit_depth' => 0
                        ];
                    }
                }
            }
            $result = $list;
        }
        foreach ($result as $k => $v) {
            $dateFormat = date('Y-m-d', strtotime($v['ref_date']));
            $result[$k]['ref_date'] = $dateFormat;
        }

        return $result;
    }

    /**
     * 获取小程序访问分布
     */
    public function getVisitDistribution($type = 'yesterday')
    {
        $dateInfo = $this->getDateInfo($type, 'visitDistribution');
        $result = [];
        if ($dateInfo['beginDate'] == $dateInfo['endDate']) {
            $result = $this->wxa->data_cube->visitDistribution($dateInfo['beginDate'], $dateInfo['endDate']);
        } else {
            for ($i = 0; $i < $dateInfo['days'] ; $i++) {
                $nextDate = date('Ymd', strtotime('+'.$i.' days', $dateInfo['beginTimestamp']));
                if ($nextDate <= $dateInfo['endDate']) {
                    $result = $this->wxa->data_cube->visitDistribution($nextDate, $nextDate);
                }
            }
        }
        return $result;
    }

    /**
     * 获取小程序访问留存
     */
    public function getRetainInfo($type = 'yesterday')
    {
        $dateInfo = $this->getDateInfo($type, 'visitTrend');
        if ($dateInfo['beginDate'] == $dateInfo['endDate']) {
            $result = $this->wxa->data_cube->dailyRetainInfo($dateInfo['beginDate'], $dateInfo['endDate']);
        } elseif ($type == 'weekly' && date('w', $dateInfo['beginTimestamp']) == '1') {
            $result = $this->wxa->data_cube->weeklyRetainInfo($dateInfo['beginDate'], $dateInfo['endDate']);
        } elseif ($type == 'monthly' && date('d', $dateInfo['beginTimestamp']) == '01') {
            $result = $this->wxa->data_cube->montylyRetainInfo($dateInfo['beginDate'], $dateInfo['endDate']);
        } else {
            $result = [];
            for ($i = 0; $i < $dateInfo['days'] ; $i++) {
                $nextDate = date('Ymd', strtotime('+'.$i.' days', $dateInfo['beginTimestamp']));
                if ($nextDate <= $dateInfo['endDate']) {
                    $info = $this->wxa->data_cube->dailyRetainInfo($nextDate, $nextDate);
                    $info['ref_date'] = isset($info['ref_date']) ? $info['ref_date'] : $nextDate;
                    $result[] = $info;
                }
            }
        }

        return $result;
    }

    /**
     * 获取小程序访问页面
     */
    public function getVisitpage($type = 'yesterday')
    {
        $dateInfo = $this->getDateInfo($type, 'visitPage');
        $result = [];
        if ($dateInfo['beginDate'] == $dateInfo['endDate']) {
            $result = $this->wxa->data_cube->visitPage($dateInfo['beginDate'], $dateInfo['endDate']);
        } else {
            $result['ref_date'] = $dateInfo['beginDate'].'-'.$dateInfo['endDate'];
            $result['list'] = [];
            for ($i = 0; $i < $dateInfo['days'] ; $i++) {
                $nextDate = date('Ymd', strtotime('+'.$i.' days', $dateInfo['beginTimestamp']));
                if ($nextDate <= $dateInfo['endDate']) {
                    $data = $this->wxa->data_cube->visitPage($nextDate, $nextDate);
                    if (isset($data['list']) && $data['list']) {
                        foreach ($data['list'] as $key => $value) {
                            if (isset($result['list'][$key])) {
                                //列表页数据
                                $result['list'][$key]['page_visit_pv'] += $value['page_visit_pv'];
                                $result['list'][$key]['page_visit_uv'] += $value['page_visit_uv'];
                                $result['list'][$key]['page_staytime_pv'] += $value['page_staytime_pv'];
                                $result['list'][$key]['entrypage_pv'] += $value['entrypage_pv'];
                                $result['list'][$key]['exitpage_pv'] += $value['exitpage_pv'];
                                $result['list'][$key]['page_share_pv'] += $value['page_share_pv'];
                                $result['list'][$key]['page_share_uv'] += $value['page_share_uv'];
                            } else {
                                $result['list'][$key]['page_visit_pv'] = $value['page_visit_pv'];
                                $result['list'][$key]['page_visit_uv'] = $value['page_visit_uv'];
                                $result['list'][$key]['page_staytime_pv'] = $value['page_staytime_pv'];
                                $result['list'][$key]['entrypage_pv'] = $value['entrypage_pv'];
                                $result['list'][$key]['exitpage_pv'] = $value['exitpage_pv'];
                                $result['list'][$key]['page_share_pv'] = $value['page_share_pv'];
                                $result['list'][$key]['page_share_uv'] = $value['page_share_uv'];
                            }
                            $result['list'][$key]['page_path'] = $value['page_path'];
                        }
                    }
                }
            }
        }

        $keyArr = ['entrypage_pv', 'exitpage_pv', 'page_visit_pv', 'page_visit_uv', 'page_staytime_pv', 'page_share_pv', 'page_share_uv'];
        foreach ($keyArr as $key) {
            $result['total'][$key] = isset($result['list']) ? array_sum(array_column($result['list'], $key)) : 0;
        }

        return $result;
    }

    /**
     * 获取小程序用户画像
     */
    public function getUserPortrait($type = 'yesterday')
    {
        $dateInfo = $this->getDateInfo($type, 'visitPage');
        $data = $this->wxa->data_cube->userPortrait($dateInfo['beginDate'], $dateInfo['endDate']);
        if (isset($data['code']) && $data['code'] == -1) {
            throw new Exception("微信系统繁忙，请稍候再试");
        } else {
            $sumArray = ['genders', 'province', 'city', 'platforms', 'devices', 'ages'];
            foreach ($sumArray as $index) {
                $data['visit_uv_new'][$index] = array_filter($data['visit_uv_new'][$index], function ($a) {
                    return $a['value'] != 0;
                });
                $data['visit_uv'][$index] = array_filter($data['visit_uv'][$index], function ($a) {
                    return $a['value'] != 0;
                });
                $data['visit_uv_new'][$index] = array_values($data['visit_uv_new'][$index]);
                $data['visit_uv'][$index] = array_values($data['visit_uv'][$index]);
                usort($data['visit_uv_new'][$index], function ($a, $b) {
                    if ($a['value'] == $b['value']) {
                        return 0;
                    } else {
                        return ($a['value'] > $b['value']) ? -1 : 1;
                    }
                });
                usort($data['visit_uv'][$index], function ($a, $b) {
                    if ($a['value'] == $b['value']) {
                        return 0;
                    } else {
                        return ($a['value'] > $b['value']) ? -1 : 1;
                    }
                });
                if ($index == 'devices') {
                    foreach ($data['visit_uv_new'][$index] as $k => $v) {
                        if ($v['name'] == '未知') {
                            $unknown = $v;
                            array_splice($data['visit_uv_new'][$index], $k, 1);
                            array_push($data['visit_uv_new'][$index], $unknown);
                        }
                    }
                    foreach ($data['visit_uv'][$index] as $k => $v) {
                        if ($v['name'] == '未知') {
                            $unknown = $v;
                            array_splice($data['visit_uv'][$index], $k, 1);
                            array_push($data['visit_uv'][$index], $unknown);
                        }
                    }
                    if (count($data['visit_uv_new'][$index]) > 9) {
                        $output = array_slice($data['visit_uv_new'][$index], 0, 9);
                        $other = array_slice($data['visit_uv_new'][$index], 9);
                        $otherCount = array_sum(array_column($other, 'value'));
                        $data['visit_uv_new'][$index] = array_merge($output, [['name' => '其他','value' => $otherCount]]);
                    }
                    if (count($data['visit_uv'][$index]) > 9) {
                        $output = array_slice($data['visit_uv'][$index], 0, 9);
                        $other = array_slice($data['visit_uv'][$index], 9);
                        $otherCount = array_sum(array_column($other, 'value'));
                        $data['visit_uv'][$index] = array_merge($output, [['name' => '其他','value' => $otherCount]]);
                    }
                }
                $data['visit_uv_new']['total'][$index] = array_sum(array_column($data['visit_uv_new'][$index], 'value'));
                $data['visit_uv']['total'][$index] = array_sum(array_column($data['visit_uv'][$index], 'value'));
            }
        }

        return $data;
    }

    /**
     * 根据类型获取时间相关信息
     */
    private function getDateInfo($type, $statMethod)
    {
        if ($statMethod != 'userPortrait') {
            $dateFormat = 'Ymd';
        } else {
            $dateFormat = 'Y-m-d';
        }
        $info['endDate'] = date($dateFormat, strtotime("-1 day"));
        switch ($type) {
            case 'yesterday':
                $info['beginDate'] = date($dateFormat, strtotime("-1 day"));
                $info['days'] = 1;
                break;
            case 'weekly':
                $info['beginDate'] = date($dateFormat, strtotime('-7 days'));
                $info['days'] = 7;
                break;
            case 'monthly':
                $info['beginDate'] = date($dateFormat, strtotime('-30 days'));
                $info['days'] = 30;
                break;
            default:
                throw new Exception("时间间隔不符合要求！");
                break;
        }

        $info['beginTimestamp'] = strtotime($info['beginDate']);
        $info['endTimestamp'] = strtotime($info['endDate']);

        return $info;
    }
}
