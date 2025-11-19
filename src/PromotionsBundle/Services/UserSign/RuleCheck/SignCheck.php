<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Services\UserSign\RuleCheck;

use Carbon\Carbon;
use PromotionsBundle\Entities\UserSignIn;
use PromotionsBundle\Entities\UserSignInLogs;
use PromotionsBundle\Repositories\UserSigninLogsRepository;
use PromotionsBundle\Repositories\UserSigninRepository;

class SignCheck extends BaseCheck
{
    //周期性任务状态重置时间，每天：当天0点，每周：每周一0点，每月：每月1号0点
    /**
     * @var $userSignLogsRepository UserSigninLogsRepository
     */
    private $userSignLogsRepository;


    /**
     * @var $userSignRepository UserSigninRepository
     */
    private $userSignRepository;

    public function finish(array $bag, array $ruleData): array
    {
        //解析参数，防止bag数组出错，先在此赋值
        $activityInfo = $bag['activity'];
        $userId = $bag['user_id'];
        //

        if ($ruleData['frequency'] === 1) {//每天的话，直接命中
            return $ruleData;
        }
        $dateRange = $this->getTimeRange($ruleData, $activityInfo);
        [$start, $end] = $dateRange;//

        $rangeDay = (int)$ruleData['common_condition'];
        if ((int)$ruleData['sign_type'] === 1) {//连续，，签到列表处理
            //需要计算两个日期之间的天数，AI计算同时，计算天数和这个匹配不匹配
            $startConsecutive = $this->getConsecutiveLastDay($userId);
            if ($startConsecutive >= $start) {
                $start = $startConsecutive;
                $listSign = $this->getUserSignInRepository()->getLists(['created|gte' => $start, 'created|lte' => $end, 'user_id' => $userId], '*', 1, -1);
                if (empty($listSign)) {
                    $listSign = [];
                }
                if (count($listSign) === $rangeDay) {
                    return $ruleData;
                }
            }
        } else {
            $listSign = $this->getUserSignInRepository()->getLists(['created|gte' => $start, 'created|lte' => $end, 'user_id' => $userId], '*', 1, -1);
            if (empty($listSign)) {
                $listSign = [];
            }
            if (count($listSign) === $rangeDay) {
                return $ruleData;
            }
        }

        return [];
    }

    public function getFinishStatus(int $userId,array $activityRule = [])
    {

    }

    private function getConsecutiveLastDay(int $userId, string $upToDate = '')
    {
        if (!empty($upToDate)) {
            $date = Carbon::parse($upToDate);
        } else {
            $date = Carbon::now();
        }

        while (true) {
            $exists = $this->getUserSignInRepository()->getInfo([
                'sign_date' => $date->toDateString(),
                'user_id' => $userId
            ]);
            if ($exists) {
                $date->subDay();
            } else {
                return $date->addDay()->startOfDay()->getTimestamp();
            }
        }
    }


    private function getUserSignInLosRepository(): UserSigninLogsRepository
    {
        if (empty($this->userSignLogsRepository)) {
            $this->userSignLogsRepository = app('registry')->getManager('default')->getRepository(UserSignInLogs::class);
        }
        return $this->userSignLogsRepository;
    }

    private function getUserSignInRepository(): UserSigninRepository
    {
        // ShopEx framework
        if (empty($this->userSignRepository)) {
            $this->userSignRepository = app('registry')->getManager('default')->getRepository(UserSignIn::class);
        }
        return $this->userSignRepository;
    }

}
