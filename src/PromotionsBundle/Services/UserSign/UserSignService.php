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

namespace PromotionsBundle\Services\UserSign;

use Carbon\Carbon;
use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Entities\UserSignIn;
use PromotionsBundle\Entities\UserSignInLogs;
use PromotionsBundle\Entities\UserSignInRules;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableFactory;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableWinningPrizeCoupon;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableWinningPrizeCoupons;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableWinningPrizePoint;
use PromotionsBundle\Repositories\UserSigninLogsRepository;
use PromotionsBundle\Repositories\UserSigninRepository;
use PromotionsBundle\Repositories\UserSigninRulesRepository;

class UserSignService
{
    /**
     * @var $userSignRepository UserSigninRepository
     */
    private $userSignRepository;

    /**
     * @var $userSignRuleRepository UserSigninRulesRepository
     */
    private $userSignRuleRepository;

    /**
     * @var $userSignLogsRepository UserSigninLogsRepository
     */
    private $userSignLogsRepository;

    // 1日常签到，2，规则打标，3活动规则达标
    public const FROM_DAILY_SIGN = 1;
    public const FROM_HIT_RULE_SIGN = 2;

    public const FROM_ACTIVITY_SIGN = 3;

    public function __construct()
    {

    }

    public function signIn(int $userId,int $companyId)
    {
        $today = Carbon::now()->toDateString();
        $exit = $this->getUserSignInRepository()->getInfo([
            'user_id'=>$userId,
            'sign_date'=>$today
        ]);
        if(!empty($exit)){
            throw new ResourceException(trans('PromotionsBundle.error_already_signed_today'));
        }
        $insertData = [
            'user_id'=>$userId,
            'sign_date'=>$today
        ];
        $this->getUserSignInRepository()->create($insertData);
        $today = Carbon::now()->toDateString();
        $prize = $this->checkAndGrantRewards($userId,$today,$companyId);
        return $prize;
    }

    public function createUserSignRule(array $data)
    {
        $exit = $this->getUserSignInRuleRepository()->getInfo([
            'days_required'=>$data['days_required'],
        ]);
        if(empty($exit)){
            throw new ResourceException(trans('PromotionsBundle.error_prize_already_exists'));
        }
        $this->getUserSignInRuleRepository()->create($data);
    }

    public function getUserSignRule(array $filter)
    {
        return $this->getUserSignInRuleRepository()->lists(['company_id'=>$filter],'*',1,-1);
    }

    public function getConsecutiveDays(int $userId, string $upToDate = '')
    {
        if(!empty($upToDate)){
           $date =  Carbon::parse($upToDate);
        }else{
            $date = Carbon::now();
        }
//        $date = $upToDate ? Carbon::parse($upToDate) : Carbon::now();
        $days = 0;

        while (true) {
            $exists = $this->getUserSignInRepository()->getInfo([
                'sign_date'=>$date->toDateString(),
                'user_id'=>$userId
            ]);
            if ($exists) {
                $days++;
                $date->subDay();
            } else {
                break;
            }
        }
        return $days;
    }

    public function getWeeklySignInStatus(int $userId)
    {
        $today = Carbon::today();
        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->addDays(6);
        $begin = $startOfWeek->startOfDay()->getTimestamp();
        $end = $endOfWeek->endOfDay()->getTimestamp();
        $logsList = $this->getUserSignInRepository()->lists(['user_id'=>$userId,'created|gte'=>$begin,'created|lte'=>$end],'*',1,-1);
        $logs = $logsList['list'];


        // 构建已签到日期哈希表
        $signedDates = [];
        foreach ($logs as $log) {
            $signedDates[$log['sign_date']] = true;
        }

        // 构建周一到周日数据
        $weekData = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $dateStr = $date->toDateString();

            $weekData[] = [
                'date' => $dateStr,
                'is_today' => $dateStr === $today->toDateString(),
                'signed' => isset($signedDates[$dateStr])
            ];
        }

        return $weekData;
    }



    //日常签到奖励机制
    public function checkAndGrantRewards(int $userId, string $today,int $companyId)
    {
        $todayCarbon = Carbon::parse($today);

        //获取连续签到时间
        $day = $this->getConsecutiveDays($userId, $today);
        $dataRule = $this->userSignRuleRepository->getInfo(['days_required'=>$day]);
        if(empty($dataRule)){
            return [];
        }
        $reward = $dataRule['reward_text'];
        if(!is_array($reward)){
            $reward = json_decode($reward,true);
        }
        //发奖励
        $this->giveAward($reward,$userId,$companyId);
        //记录日志
        $this->recordPrizeLog($userId,$dataRule,$companyId,self::FROM_HIT_RULE_SIGN);
    }

    public function delUserRule(int $ruleId)
    {
        $this->getUserSignInRuleRepository()->deleteBy(['id'=>$ruleId]);

    }

    public function giveAward(array $rewards,int $userId,int $companyId)
    {
        $userInfo = ['user_id'=>$userId,'company_id'=>$companyId];
        foreach ($rewards as $index => $reward){
            if ($reward['prize_type'] === 'points') { //积分
                //加积分
                $turntable_factory = new TurntableFactory(new TurntableWinningPrizePoint($reward, $userInfo));
                $rewards[$index]['grant_res'] = $turntable_factory->doPrize();
            } elseif ($reward['prize_type'] === 'coupon') { //优惠券
                $turntable_factory = new TurntableFactory(new TurntableWinningPrizeCoupon($reward, $userInfo));
                $rewards[$index]['grant_res'] = $turntable_factory->doPrize();
            } elseif ($reward['prize_type'] === 'coupons') { //优惠券包
                $turntable_factory = new TurntableFactory(new TurntableWinningPrizeCoupons($reward, $userInfo));
                $rewards[$index]['grant_res'] = $turntable_factory->doPrize();
            }
        }
        return $rewards;

    }

    private function recordPrizeLog(int $userId,array $ruleData,int $companyId,int $from, int $activityId = 0)
    {
        $logData = [
            'company_id'=>$companyId,
            'user_id'=>$userId,
            'from'=>$from,
            'reward_title'=>$ruleData['rule_name'],
            'activity_id' =>$activityId,
            'reward_text'=>$ruleData['reward_text'],
        ];
        $this->getUserSignInLosRepository()->create($logData);
    }

    private function getUserSignInRepository(): UserSigninRepository
    {
        if (empty($this->userSignRepository)) {
            $this->userSignRepository = app('registry')->getManager('default')->getRepository(UserSignIn::class);
        }
        return $this->userSignRepository;
    }

    private function getUserSignInRuleRepository(): UserSigninRulesRepository
    {
        if (empty($this->userSignRuleRepository)) {
            $this->userSignRepository = app('registry')->getManager('default')->getRepository(UserSignInRules::class);
        }
        return $this->userSignRuleRepository;
    }

    private function getUserSignInLosRepository(): UserSigninLogsRepository
    {
        if (empty($this->userSignLogsRepository)) {
            $this->userSignLogsRepository = app('registry')->getManager('default')->getRepository(UserSignInLogs::class);
        }
        return $this->userSignLogsRepository;
    }


}
