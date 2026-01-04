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

use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Entities\CardPackage;
use KaquanBundle\Entities\DiscountCards;
use KaquanBundle\Entities\UserDiscount;
use KaquanBundle\Repositories\CardPackageRepository;
use KaquanBundle\Repositories\DiscountCardsRepository;
use KaquanBundle\Repositories\UserDiscountRepository;
use KaquanBundle\Services\DiscountCardService as CardService;
use KaquanBundle\Services\KaquanService;
use KaquanBundle\Services\UserDiscountService;
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Repositories\MembersRepository;
use PointBundle\Entities\PointMember;
use PointBundle\Repositories\PointMemberRepository;
use PointBundle\Services\PointMemberService;
use PromotionsBundle\Entities\LuckyDrawActivity;
use PromotionsBundle\Entities\TurntableLog;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableFactory;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableWinningPrizeCoupon;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableWinningPrizeCoupons;
use PromotionsBundle\Http\FrontApi\V1\Action\TurntableWinningPrizePoint;
use CompanysBundle\Services\OperatorsService;
use PromotionsBundle\Repositories\LuckyDrawActivityRepository;
use PromotionsBundle\Repositories\TurntableLogRepository;

class TurntableService
{
    public $memberInfoRepository;
    public $discountCardsRepository;
    /**
     * @var $turntableLog TurntableLogRepository
     */
    public $turntableLog;

    /**
     * @var $memberRepository MembersRepository
     */
    public $memberRepository;
    public $redisConn;

    /**
     * @var $luckDrawActivityRepository LuckyDrawActivityRepository
     */
    private $luckDrawActivityRepository;

    public function __construct()
    {
        $this->memberInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
        $this->discountCardsRepository = app('registry')->getManager('default')->getRepository(DiscountCards::class);
        $this->turntableLog = app('registry')->getManager('default')->getRepository(TurntableLog::class);
        $this->memberRepository = app('registry')->getManager('default')->getRepository(Members::class);

        $this->luckDrawActivityRepository = app('registry')->getManager('default')->getRepository(LuckyDrawActivity::class);

        $this->redisConn = app('redis')->connection('default');
    }

    public function getDetail(int $id)
    {
        $detail = $this->luckDrawActivityRepository->getInfo(['id' => $id]);
        if(empty($detail)){
            throw new ResourceException(trans('PromotionsBundle.activity_not_exist_with_id', ['id' => $id]));
        }
        if (!is_array($detail['prize_data'])) {
            $detail['prize_data'] = json_decode($detail['prize_data'], true);
        }
        if (empty($detail)) {
            throw new ResourceException(trans('PromotionsBundle.activity_not_exist_or_wrong_id'));
        }
        $config = $detail['activity_template_config'];
        $prize = $detail['prize_data'];
        if(!is_array($config)){
            $config = json_decode($config,true);
        }
        $config['gameType'] = $detail['activity_type'];
        if(!is_array($prize)){
            $prize = json_decode($prize,true);
        }
        $prize = $this->getPirzeInfo($prize);
        $config['gameConfig']['prizes'] = $prize;
        $detail['prize_data'] = $prize;
        $detail['activity_template_config'] = $config;
        return $detail;
    }

    /**
     * 修改大转盘配置
     * @param $data
     * @return array
     */
    public function setTurntableConfig($data)
    {
//        $data['prize_data'] = json_encode($data['prize_data']);
//        $data['activity_template_config'] = json_encode($data['activity_template_config']);
        if (!empty($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $this->luckDrawActivityRepository->updateBy(['id' => $id], $data);
        } else {
            //存表
            $this->luckDrawActivityRepository->create($data);
        }
//        $key = 'turntableConfigCompany_'.$company_id;
//        $this->redisConn = app('redis')->connection('default');
//        $this->redisConn->del($key);
//        $this->redisConn->hmset($key, $datas);

        return ['result' => true];
    }

    /**
     * 获取大转盘配置
     * @param $company_id string 公司id
     * @return mixed 大转盘配置
     */
    public function getTurntableConfig($company_id, $id, $userId = null)
    {
        $data = $this->luckDrawActivityRepository->getInfo(['id' => $id]);
//        $this->redisConn = app('redis')->connection('default');
//
//        $result = $this->redisConn->hgetall('turntableConfigCompany_'.$company_id);
//        if ($result) {
//            $result['prizes'] = json_decode($result['prizes'], true);
//        }
//
//        if ($userId) {
//            //用户今日已抽奖次数
//            $result['today_times'] = intval($this->getUserTodayJoinTimes($userId));
//            //用户剩余抽奖次数
//            $result['surplus_times'] = intval($this->getUserSurplusTimes($company_id, $userId));
//        }


        return $data;
    }

    /**
     * 获取抽奖列表
     * @param array $filter
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getActivityList(array $filter, int $page, int $limit = 20)
    {
        $list = $this->luckDrawActivityRepository->lists($filter, $page, $limit);
        return $list;
    }

    /**
     * 用户参与转盘抽奖
     * @param $user_info array
     */
    public function joinTurntable($user_info)
    {
        $this->redisConn = app('redis')->connection('default');
        $turntable_config = $this->redisConn->hgetall('turntableConfigCompany_' . $user_info['company_id']);
        //检查大转盘是否已开启
        if ($turntable_config['turntable_open'] != 1) {
            throw new ResourceException(trans('PromotionsBundle.turntable_activity_not_enabled'));
        }

        if ($turntable_config['long_term'] == 0) {
            if ($turntable_config['start_time'] > time() || $turntable_config['end_time'] + 86400 < time()) {
                throw new ResourceException(trans('PromotionsBundle.not_in_activity_time'));
            }
        }

        //检查用户抽奖次数
        $surplus = $this->getUserSurplusTimes($user_info['company_id'], $user_info['user_id']); //剩余次数
        $joined = $this->getUserTodayJoinTimes($user_info['user_id']); //今日已抽奖次数
        if ($surplus <= 0) {
            throw new ResourceException(trans('PromotionsBundle.insufficient_draw_times'));
        }
        if (($turntable_config['max_times_day'] ?? '-1') != '-1') {
            if ($joined >= $turntable_config['max_times_day']) {
                throw new ResourceException(trans('PromotionsBundle.daily_draw_limit_reached'));
            }
        }
        //抽奖次数
        $this->subUserSurplusTimes($user_info['company_id'], $user_info['user_id']);
        $this->addUserTodayJoinTimes($user_info['user_id']);

        //空奖
        $nullPrize = [
            "prize_type" => "thanks",
            "prize_name" => "谢谢惠顾"
        ];

        //处理所有奖项
        $prizes = json_decode($turntable_config['prizes'], true);
        $i = 0;
        $prizes_arr = [];
        foreach ($prizes as &$prize) {
            $prize['id'] = $i;
            $prizes_arr[$i] = intval($prize['prize_probability']);
            $i++;
        }
        unset($prize);

        //执行抽奖
        $prize_id = $this->turntableRun($prizes_arr);
        //奖品信息
        $winning_prize = [];
        foreach ($prizes as $prize) {
            if ($prize['id'] == $prize_id) {
                $winning_prize = $prize;
            }
        }
        if (empty($winning_prize)) {
            return $nullPrize;
        }
        if ($winning_prize['prize_type'] === 'thanks') {
            return $nullPrize;
        }
        if ($winning_prize['prize_type'] === 'points') { //积分
            //加积分
            $turntable_factory = new TurntableFactory(new TurntableWinningPrizePoint($winning_prize, $user_info));
            $result = $turntable_factory->doPrize();
        } elseif ($winning_prize['prize_type'] === 'coupon') { //优惠券
            $turntable_factory = new TurntableFactory(new TurntableWinningPrizeCoupon($winning_prize, $user_info));
            $result = $turntable_factory->doPrize();
        } elseif ($winning_prize['prize_type'] === 'coupons') { //优惠券包
            $turntable_factory = new TurntableFactory(new TurntableWinningPrizeCoupons($winning_prize, $user_info));
            $result = $turntable_factory->doPrize();
        }
        if (!$result) {
            $winning_prize = $nullPrize;
        }

        //中奖记录
        $data = [
            'company_id' => $user_info['company_id'],
            'user_id' => $user_info['user_id'],
            'prize_title' => $winning_prize['prize_name'],
            'prize_type' => $winning_prize['prize_type']
        ];
        if ($data['prize_type'] === 'points') {
            $data['prize_value'] = $winning_prize['prize_value'];
        } elseif ($data['prize_type'] === 'coupon') {
            $tmp[] = $winning_prize['prize_value'];
            $data['prize_value'] = json_encode($tmp);
        } elseif ($data['prize_type'] === 'coupons') {
            $data['prize_value'] = json_encode($winning_prize['prize_value']);
        } elseif ($data['prize_type'] === 'thanks') {
            $data['prize_value'] = 0;
        }
        $this->turntableLog->create($data);


        return $winning_prize;
    }

    //david重构版本抽奖，抽奖的底层不变，只是兼容数据表
    public function doLuckyDraw(array $userInfo,int $actId)
    {
        $actInfo = $this->luckDrawActivityRepository->getInfo(['id'=>$actId]);
        $costPoint = $actInfo['cost_value'];
        $now = time();
        if($actInfo['end_time'] <= $now){
            throw new ResourceException(trans('PromotionsBundle.draw_activity_ended'));
        }
        if($actInfo['begin_time'] >= $now){
            throw new ResourceException(trans('PromotionsBundle.draw_activity_not_started'));
        }
        /**
         * @var $memberPointRepository PointMemberRepository
         */
        $memberPointRepository = app('registry')->getManager('default')->getRepository(PointMember::class);
        $memberPoint  = $memberPointRepository->getInfo(['user_id'=>$userInfo['user_id']]);
        if($memberPoint < $costPoint){
            throw new ResourceException(trans('PromotionsBundle.insufficient_user_points'));
        }
        $pointMemberService = new PointMemberService();

        //总次数直接拿log
        $surplus = (int)$this->turntableLog->count(['user_id'=>$userInfo['user_id'],'act_id'=>$actId]);
//        $surplus = (int)$this->turntableLog->count(['act_id'=>$actId]);
        if(empty($actInfo['limit_total'])){
            $limitTotal = 0;
        }else{
            $limitTotal = $actInfo['limit_total'];
        }
        if($surplus >= $limitTotal){
            throw new ResourceException(trans('PromotionsBundle.draw_times_reach_activity_limit'));
        }
        //判断限制
        //检查用户抽奖次数
        $joined = $this->getUserTodayJoinTimes($userInfo['user_id'],null,$actId); //今日已抽奖次数
//        if ($surplus <= 0) {
//            throw new ResourceException(trans('PromotionsBundle.insufficient_draw_times'));
//        }
        if (($actInfo['limit_day'] ?? '-1') != '-1') {
            if ($joined >= $actInfo['limit_day']) {
                throw new ResourceException(trans('PromotionsBundle.daily_draw_limit_reached'));
            }
        }
        //抽奖次数
//        $this->subUserSurplusTimes($userInfo['company_id'], $userInfo['user_id']);
        $this->addUserTodayJoinTimes($userInfo['user_id'],null,$actId);

        $prizes = json_decode($actInfo['prize_data'], true);
        $i = 0;
        $prizes_arr = [];
        //改成按照数组索引方式进入遍历
        foreach ($prizes as $index => &$prize) {
            $prize['id'] = $index;
            $prizes_arr[$i] = intval($prize['prize_probability']);
            $i++;
        }
        unset($prize);

        //执行抽奖
        $prize_id = $this->turntableRun($prizes_arr);
        $relData = $prizes[$prize_id];
        app('log')->debug('david:draw:indeed:'.json_encode($relData));
        $nullPrize = [
            "prize_type" => "thanks",
            "fonts"=>['text'=>'谢谢惠顾'],
            "prize_text" => "谢谢惠顾",
            "prize_title" => "谢谢惠顾"
        ];
        if($relData['prize_type'] !== 'thanks'){
            $todayPrizeNum = $this->getStock($actId,$relData);
//            app('log')->debug('david:draw:$todayPrizeNum:'.$todayPrizeNum);
//            app('log')->debug('david:draw:$relData[stock]:'.$relData['stock']);
            if(empty($relData['stock'])){
                $dailyStock = 0;
            }else{
                $dailyStock = (int)$relData['stock'];
            }
            if($dailyStock !== 0){
                if($todayPrizeNum >= $dailyStock){
                    $relData = $nullPrize;
                }
            }

        }
        //进行发券等操作，并且进日志
        if ($relData['prize_type'] === 'points') { //积分
            //加积分
            $turntable_factory = new TurntableFactory(new TurntableWinningPrizePoint($relData, $userInfo));
            $result = $turntable_factory->doPrize();
        } elseif ($relData['prize_type'] === 'coupon') { //优惠券
            $turntable_factory = new TurntableFactory(new TurntableWinningPrizeCoupon($relData, $userInfo));
            $result = $turntable_factory->doPrize();
        } elseif ($relData['prize_type'] === 'coupons') { //优惠券包
            $turntable_factory = new TurntableFactory(new TurntableWinningPrizeCoupons($relData, $userInfo));
            $result = $turntable_factory->doPrize();
        }

        if (empty($result)) {
            $relData = $nullPrize;
        }
        app('log')->debug('david:draw:realData:'.json_encode($relData));


        $data = [
            'company_id' => $userInfo['company_id'],
            'user_id' => $userInfo['user_id'],
            'act_id'=>$actId,
            'prize_title' => $relData['fonts']['text'],
            'prize_type' =>  $relData['prize_type']
        ];
        if ($data['prize_type'] === 'points') {
            $data['prize_value'] = $relData['prize_value'];
        } elseif ($data['prize_type'] === 'coupon') {
            $tmp[] = $relData['prize_value'];
            $data['prize_value'] = json_encode($tmp);
        } elseif ($data['prize_type'] === 'coupons') {
            $data['prize_value'] = json_encode($relData['prize_value']);
        } elseif ($data['prize_type'] === 'thanks') {
            $data['prize_value'] = 0;
        }
        $this->turntableLog->create($data);

        //最后执行扣几分
        $pointMemberService->addPoint($userInfo['user_id'], $userInfo['company_id'], $costPoint, 11, false, '大转盘抽奖扣除');
        if($relData['prize_type'] !== 'thanks'){
            app('log')->debug('david:draw:add stock');
            $this->addStock($actId,$relData);
        }
        if(empty($relData['prize_title'])){
            $relData['prize_title'] = $relData['fonts']['text'] ?? '';
        }
        return $relData;
    }

    public function addStock(int $actId,array $prizeData,$date = null)
    {
        if(empty($date)){
            $date = date('Y-m-d');
        }
        $field = $date.'_'.$prizeData['prize_type'].'_'.$prizeData['prize_value'];
        $this->redisConn->hincrby('stock_lucky_draw_'.$actId, $field, 1);

    }

    public function getStock(int $actId,array $prizeData,$date = null)
    {
        if(empty($date)){
            $date = date('Y-m-d');
        }
        $field = $date.'_'.$prizeData['prize_type'].'_'.$prizeData['prize_value'];
        $num = $this->redisConn->hget('stock_lucky_draw_'.$actId, $field);
        if(empty($num)){
            return 0;
        }
        return (int)$num;
    }

    public function getFrontActivityInfo(int $activityId)
    {
        $actInfo = $this->luckDrawActivityRepository->getInfo(['id'=>$activityId]);

        $now = time();
        if($actInfo['end_time'] <= $now){
            throw new ResourceException(trans('PromotionsBundle.draw_activity_ended_error'));
        }
        if($actInfo['begin_time'] >= $now){
            throw new ResourceException(trans('PromotionsBundle.draw_activity_not_started_error'));
        }
        return $actInfo;

    }

    /**
     * 获取用户剩余抽奖次数
     * @param $userId
     * @param $companyId
     * @return mixed
     */
    public function getUserSurplusTimes($companyId, $userId)
    {
        $key = self::getUserSurplusTimesKey($companyId);
        $field = self::getUserSurplusTimesField($userId);
        return $this->redisConn->hget($key, $field);
    }

    /**
     * 获取指定年月日用户的抽奖次数
     * @param $userId
     * @param $date string 日期Ymd
     * @return mixed
     */
    public function getUserTodayJoinTimes($userId, $date = null,$actId)
    {
        $key = self::getUserTodayJoinTimesKey($date,$actId);
        $field = self::getUserTodayJoinTimesField($userId);
        return $this->redisConn->hget($key, $field);
    }

    /**
     * 增加今日已抽奖次数
     * @param $userId
     */
    public function addUserTodayJoinTimes($userId, $date = null,$actId)
    {
        $key = self::getUserTodayJoinTimesKey(null,$actId); //今日抽奖次数
        $field = self::getUserTodayJoinTimesField($userId);
        $this->redisConn->hincrby($key, $field, 1);
    }

    /**
     * 增加用户剩余抽奖次数
     * @param $userId
     * @param int $times
     */
    public function addUserSurplusTimes($companyId, $userId, $times = 1)
    {
        $key = self::getUserSurplusTimesKey($companyId);
        $field = self::getUserSurplusTimesField($userId);
        $result = $this->redisConn->hincrby($key, $field, $times);
        return $result;
    }

    /**
     * 减少用户抽奖次数
     * @param $userId
     * @param int $times
     */
    public function subUserSurplusTimes($companyId, $userId, $times = 1)
    {
        $key = self::getUserSurplusTimesKey($companyId);
        $field = self::getUserSurplusTimesField($userId);
        $result = $this->redisConn->hincrby($key, $field, -$times);
        return $result;
    }

    /**
     * 剩余抽奖次数key
     * @param $userId
     * @return string
     */
    private static function getUserSurplusTimesKey($companyId)
    {
        return 'turntableUserSurplusTimes:CompanyId:' . $companyId;
    }

    /**
     * 剩余抽奖次数field
     * @param $userId
     * @return string
     */
    private static function getUserSurplusTimesField($userId)
    {
        return 'UserId:' . $userId;
    }

    /**
     * 今日已抽奖次数key
     * @param null $date
     * @return string
     */
    private static function getUserTodayJoinTimesKey($date = null,$actId)
    {
        if (!$date) {
            $date = date('Ymd');
        }
        return 'turntableUserJoinTimes_' . $date.'_'.$actId;
    }

    /**
     * 今日已抽奖次数field
     * @param null $userId
     * @return string
     */
    private static function getUserTodayJoinTimesField($userId)
    {
        return 'UserId:' . $userId;
    }

    /**
     * 用户购物金额累计数键值
     * @param $companyId int 公司id
     * @return string
     */
    private function getShoppingFullKey($companyId)
    {
        return 'ShoppingFull:Company:' . $companyId;
    }

    /**
     * 用户购物金额累计数字段
     * @param $userId int 用户id
     * @return string
     */
    private function getShoppingFullFiled($userId)
    {
        return 'UserId:' . $userId;
    }

    /**
     * 登陆赠送次数
     * @param $companyId
     * @param $userId
     * @return array
     */
    public function loginAddSurplusTimes($companyId, $userId)
    {
        $date = date('Ymd');
        $key = 'turntableLoginAddTimes_' . $date;
        $res = $this->redisConn->sadd($key, $userId);
        if ($res) {
            $result = $this->getTurntableConfig($companyId);
            if ($result['login_get_times'] > 0) {
                $addRes = $this->addUserSurplusTimes($companyId, $userId, $result['login_get_times']);
            }
        }
        if ($addRes ?? 0) {
            return [
                'result' => $result['login_get_times'] ?? 0
            ];
        } else {
            return [
                'result' => 0
            ];
        }
    }

    /**
     * 购物满送抽奖次数
     * @param $userId
     * @param $companyId
     * @param $totalFee
     */
    public function payGetTurntableTimes($userId, $companyId, $totalFee)
    {
        $config = $this->getTurntableConfig($companyId);

        if (($config['shopping_full'] ?? -1) != -1) {
            $configShoppingFull = bcmul($config['shopping_full'], 100);
            $div = bcdiv($totalFee, $configShoppingFull, 0);
            if ($div >= 1) {
                //增加抽奖次数
                $this->addUserSurplusTimes($companyId, $userId, $div);
            }
        }
    }

    /**
     * 执行大转盘抽奖
     * @param $proArr ['id'=>'probability']
     * @return int|string
     */
    private function turntableRun($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum); //返回随机整数

            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        return $result;
    }

//    /**
//     * 检查获奖奖品信息
//     * @param $turntable_config array 原转盘配置
//     * @param $winning_prize array 获奖奖项
//     * @param $prizes array 所有奖项
//     * @param $company_id string 公司id
//     * @return bool
//     */
//    private function checkPrizeInfo($turntable_config, $winning_prize, $prizes, $company_id)
//    {
//        if ($winning_prize['prize_surplus'] <= 0) {
//            return false;
//        } else { //减少奖品余量
//            foreach ($prizes as &$prize) {
//                if ($prize['id'] == $winning_prize['id']) {
//                    $prize['prize_surplus']--;
//                    unset($prize['id']);
//                }
//            }
//            unset($prize);
//            $turntable_config['prizes'] = json_encode($prizes);
//            $this->setTurntableConfig($company_id, $turntable_config);
//        }
//
//        return true;
//    }

    /**
     * 检查优惠券余量
     * @param $card_id string 优惠券id
     * @param $company_id string 公司id
     * @return bool
     */
    private function checkCouponsSurplus($card_id, $company_id)
    {
        //检查优惠券余量
        $discountCardService = new KaquanService(new CardService());
        $filter['card_id'] = $card_id;
        $filter['company_id'] = $company_id;
        $card_info = $discountCardService->getKaquanDetail($filter);
        $discountCardService = new UserDiscountService();
        $coupon_num = $discountCardService->getCardGetNum($card_id, $company_id);

        if (!$card_info) { //无优惠券信息
            return false;
        } elseif ($card_info['quantity'] - $coupon_num <= 0) { //优惠券数量不足
            return false;
        }

        return true;
    }

    /**
     * 大转盘活动结束时清空抽奖次数
     */
    public function scheduleClearTurntableTimesOver()
    {
        $operatorsService = new OperatorsService();
        $orderBy = ['created' => 'DESC'];
        $operatorList = $operatorsService->lists([], $orderBy, 2000, 1);
        if ($operatorList) {
            foreach ($operatorList['list'] as $key => $value) {
                $this->clearTurntableTimesOver($value['company_id']);
            }
        }
        return true;
    }

    /**
     * 大转盘活动结束时清空抽奖次数
     * @param $companyId
     */
    public function clearTurntableTimesOver($companyId)
    {
        $config = $this->getTurntableConfig($companyId);
        $config['long_term'] = $config['long_term'] ?? '0';
        $config['clear_times_after_end'] = $config['clear_times_after_end'] ?? '0';
        $config['end_time'] = $config['end_time'] ?? 0;
        if ($config['long_term'] != "1" && $config['clear_times_after_end'] == '1' && $config['end_time'] < time()) {
            $key = self::getUserSurplusTimesKey($companyId);
            $this->redisConn->del($key);
        }
    }

    public function getLuckyDrawLog(int $userId, int $actId, int $page =1, int $limit = 20)
    {
        $list = $this->turntableLog->getLists([
            'user_id'=>$userId,
            'act_id'=>$actId,
        ],'*',$page,$limit);
        return $list;

    }

    public function getUserInfo(int $userId)
    {
        /**
         * @var $pointMemberRepository PointMemberRepository
         */
        $pointMemberRepository = app('registry')->getManager('default')->getRepository(PointMember::class);
        $userInfo = $pointMemberRepository->getInfo(['user_id'=>$userId]);
        return $userInfo;
    }

    public function getPirzeInfo(array $prizeDataList)
    {
        /**
         * @var $discountCardsRepository DiscountCardsRepository
         */
        $discountCardsRepository = app('registry')->getManager('default')->getRepository(DiscountCards::class);
        /**
         * @var $cardPackage CardPackageRepository
         */
        $cardPackage = app('registry')->getManager('default')->getRepository(CardPackage::class);
        foreach ($prizeDataList as $index=> $prizeData) {
            if($prizeData['prize_type'] === 'coupon'){

                $info = $discountCardsRepository->getInfo(['card_id'=>$prizeData['prize_value']]);
                $prizeDataList[$index]['prize_detail'] = $info;
            }

            if($prizeData['prize_type'] === 'coupons'){

                $info = $cardPackage->getInfo(['package_id'=>$prizeData['prize_value']]);
                $prizeDataList[$index]['prize_detail'] = $info;
            }
        }

        return $prizeDataList;


    }

    public function getLuckyDrawLogByActId(int $actId, int $page = 1 ,int $limit = 20)
    {
        $logList = $this->turntableLog->lists(['act_id' => $actId],'*',$page,$limit);
        if(empty($logList['list'])){
            return $logList;
        }
        $batchMember = array_column($logList['list'],'user_id');
        $listMember = $this->memberRepository->getDataList(['user_id'=>$batchMember],'user_id, mobile,user_card_code',1,-1);
        $structMember = array_column($listMember,null,'user_id');
        foreach ($logList['list'] as $index => $rd){
            $userId = $rd['user_id'];
            if(!empty($structMember[$userId])){
                $logList['list'][$index]['user_card_code'] = $structMember[$userId]['user_card_code'];
                $logList['list'][$index]['mobile'] = $structMember[$userId]['mobile'];
            }
        }
        return $logList;



        //具体优惠券
//        $batchCoupon = [];
//        $batchCouponPackage = [];
//        $resCoupon = [];
//        $resPackage = [];
//        foreach ($logList['list'] as $value) {
//            if($value['prize_type'] === 'coupon'){
//                $batchCoupon[] = $value['prize_value'];
//            }
//            if($value['prize_type'] === 'coupons'){
//                $batchCouponPackage[] = $value['prize_value'];
//            }
//        }
//
//        //获取名字
//        /**
//         * @var $discountCardsRepository DiscountCardsRepository
//         */
//        $discountCardsRepository = app('registry')->getManager('default')->getRepository(DiscountCards::class);
//        /**
//         * @var $cardPackage CardPackageRepository
//         */
//        $cardPackage = app('registry')->getManager('default')->getRepository(CardPackage::class);
//        if(!empty($batchCoupon)){
//            $couponList = $discountCardsRepository->getLists(['card_id'=>$batchCoupon],'*',1,-1);
//            $resCoupon = array_column($couponList,null,'card_id');
//        }
//        /**
//         * @var $cardPackage CardPackageRepository
//         */
//        $cardPackage = app('registry')->getManager('default')->getRepository(CardPackage::class);
//        if(!empty($batchCouponPackage)){
//            $couponPackageList = $cardPackage->getLists(['package_id'=>$batchCouponPackage],'*',1,-1);
//            $resPackage = array_column($couponPackageList,null,'package_id');
//        }

    }

    public function getTotalCount(int $activityId)
    {
        $totalSql = "select count(1) from promotions_turntable_log where act_id=".$activityId;
        $totalUserSql = "select count(distinct user_id) from promotions_turntable_log where act_id=".$activityId;
        $totalGetSql = "select count(1) from promotions_turntable_log where prize_type <> 'thanks' and act_id=".$activityId;
        $totalGetUserSql = "select count(distinct user_id) from promotions_turntable_log where prize_type <> 'thanks' and act_id=".$activityId;
        $conn = app('registry')->getConnection('default');
        $total = $conn->fetchColumn($totalSql);
        $totalUser = $conn->fetchColumn($totalUserSql);
        $totalGet = $conn->fetchColumn($totalGetSql);
        $totalGetUser = $conn->fetchColumn($totalGetUserSql);
        return ['total'=>$total,'totalUser'=>$totalUser,'totalGet'=>$totalGet,'totalGetUser'=>$totalGetUser];
    }

    public function downActvity(int $actId)
    {
        $updateData = [
            'end_time'=>time(),
        ];
        $this->luckDrawActivityRepository->updateBy(['id' => $actId], $updateData);
    }
}
