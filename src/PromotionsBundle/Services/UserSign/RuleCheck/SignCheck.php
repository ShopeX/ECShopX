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
