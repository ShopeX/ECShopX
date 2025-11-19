<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Services;

class StatsServices
{
    /**
     * 公众号实例
     *
     */
    public $app;

    public function __construct($authorizerAppId)
    {
        // Built with ShopEx Framework
        $openPlatform = new OpenPlatform();
        $this->app = $openPlatform->getAuthorizerApplication($authorizerAppId);
    }

    /**
     * 最近七天用户数据统计
     */
    public function userWeekSummary()
    {
        // Built with ShopEx Framework
        $stats = $this->app->data_cube;
        $from = date("Y-m-d", strtotime("-7 day"));
        $to = date("Y-m-d", strtotime("-1 day"));
        $userSummary = $stats->userSummary($from, $to);
        $userSummaryList = [];
        if ($userSummary->list) {
            foreach ($userSummary->list as $value) {
                $date = $value['ref_date'];
                $addUser = (intval($value['new_user']) - intval($value['cancel_user']));
                if (isset($userSummaryList[$date])) {
                    $userSummaryList[$date]['new_user'] += intval($value['new_user']);
                    $userSummaryList[$date]['cancel_user'] += intval($value['cancel_user']);
                    $userSummaryList[$date]['add_user'] += $addUser;
                } else {
                    $userSummaryList[$date]['ref_date'] = $value['ref_date'];
                    $userSummaryList[$date]['new_user'] = intval($value['new_user']);
                    $userSummaryList[$date]['cancel_user'] = intval($value['cancel_user']);
                    $userSummaryList[$date]['add_user'] = $addUser;
                }
            }
        }

        $userCumulate = $stats->userCumulate($from, $to);
        $userCumulateList = [];
        if ($userCumulate->list) {
            foreach ($userCumulate->list as $row) {
                $userCumulateList[$row['ref_date']] = $row;
            }
        }
        for ($i = 7; $i > 0; $i--) {
            $date = date("Y-m-d", strtotime("-$i day"));
            $list[] = [
                'ref_date' => isset($userSummaryList[$date]) ? $userSummaryList[$date]['ref_date'] : $date,
                'new_user' => isset($userSummaryList[$date]) ? $userSummaryList[$date]['new_user'] : 0,
                'cancel_user' => isset($userSummaryList[$date]) ? $userSummaryList[$date]['cancel_user'] : 0,
                'add_user' => isset($userSummaryList[$date]) ? $userSummaryList[$date]['add_user'] : 0,
                'cumulate_user' => isset($userCumulateList[$date]) ? $userCumulateList[$date]['cumulate_user'] : 0,
            ];
        }
        return $list;
    }
}
