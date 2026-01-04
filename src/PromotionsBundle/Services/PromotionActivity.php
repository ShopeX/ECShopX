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

use PromotionsBundle\Entities\PromotionActivity as ActivityEntities;
use GoodsBundle\Services\ItemsService;
use KaquanBundle\Services\UserDiscountService;

use Dingo\Api\Exception\StoreResourceFailedException;

use CompanysBundle\Services\CompanysService;
use PromotionsBundle\Jobs\SalespersonGiveUserCouponsActivity;
use PromotionsBundle\Jobs\ScheduleGivePromotionsActivity;

use PromotionsBundle\Jobs\ScheduleFirePromotionsActivity;
use PromotionsBundle\Interfaces\SchedulePromotionActivity;
use PromotionsBundle\Interfaces\PromotionActivityInterface;

use KaquanBundle\Services\VipGradeOrderService;
use WechatBundle\Services\OpenPlatform;

use MembersBundle\Entities\MembersInfo;
use KaquanBundle\Entities\SalespersonGiveCoupons;
use KaquanBundle\Entities\DiscountCards;
use SalespersonBundle\Services\SalespersonTaskRecordService;
use SalespersonBundle\Services\SalespersonCouponStatisticsService;

// 营销活动
class PromotionActivity
{
    public $pageSize = 50;

    public $activityArr = [
        'member_birthday' => \PromotionsBundle\Services\PromotionActivity\MemberBirthday::class,
        'member_anniversary' => \PromotionsBundle\Services\PromotionActivity\MemberAnniversary::class,
        'member_day' => \PromotionsBundle\Services\PromotionActivity\MemberDay::class,
        'member_upgrade' => \PromotionsBundle\Services\PromotionActivity\MemberUpgrade::class,
        'member_vip_upgrade' => \PromotionsBundle\Services\PromotionActivity\MemberVipUpgrade::class,
    ];

    public $entityRepository;
    public $discountCardsRepository;
    public $salespersonGiveCouponsRepositor;
    public $memberInfoRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(ActivityEntities::class);
        $this->discountCardsRepository = app('registry')->getManager('default')->getRepository(DiscountCards::class);
        $this->salespersonGiveCouponsRepositor = app('registry')->getManager('default')->getRepository(SalespersonGiveCoupons::class);
        $this->memberInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
    }

    /**
     * 保存营销活动
     */
    public function createActivity($data)
    {
        //检查是否可以添加
        $this->checkActiveValidNum($data['company_id'], $data['activity_type']);

        $this->checkActivityParams($data);

        // 检查对应的活动参数
        $activityObject = new $this->activityArr[$data['activity_type']]();
        $activityObject->checkActivityParams($data);

        $data['activity_status'] = 'valid';
        $data['created'] = time();
        return $this->entityRepository->create($data);
    }

    private function checkActivityParams($data)
    {
        if (!isset($data['discount_config']['coupons']) && empty($data['discount_config']['coupons']) && !isset($data['discount_config']['goods']) && empty($data['discount_config']['goods'])) {
            throw new StoreResourceFailedException('请进行优惠设置');
        }

        if (isset($data['discount_config']['coupons']) && count($data['discount_config']['coupons']) > 10) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.max_10_coupons_allowed'));
        }

        if (isset($data['discount_config']['goods']) && count($data['discount_config']['goods']) > 10) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.max_10_products_allowed'));
        }

        return true;
    }

    /**
     * 检查当前营销活动的有效数量，用于判断是否客添加
     *
     * @param string $activityType 活动类型
     */
    public function checkActiveValidNum($companyId, $activityType)
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(ActivityEntities::class);

        if (!array_key_exists($activityType, $this->activityArr)) {
            throw new StoreResourceFailedException('请选择正确的营销活动');
        }

        // 检查当前活动同时添加的次数
        $activityObject = new $this->activityArr[$activityType]();
        $count = $this->entityRepository->count(['activity_type' => $activityType, 'company_id' => $companyId, 'activity_status' => 'valid']);
        if ($count >= $activityObject->validNum) {
            throw new StoreResourceFailedException('当前营销活动不可再添加');
        }

        return true;
    }

    public function activityInvalid()
    {
        $filter = [
            'end_time|lt' => time(),
            'activity_status' => 'valid',
        ];

        return $this->entityRepository->updateBy($filter, ['activity_status' => 'invalid']);
    }

    /**
     * 触发营销活动
     */
    public function scheduleTrigger()
    {
        foreach ($this->activityArr as $activityType => $activityTypeClass) {
            $activityObject = new $activityTypeClass();
            if (!$activityObject instanceof SchedulePromotionActivity) {
                continue;
            }

            $filter = [
                'begin_time|lt' => time(),
                'end_time|gte' => time(),
                'activity_status' => 'valid',
                'activity_type' => $activityType
            ];
            $totalCount = $this->entityRepository->count($filter);
            if ($totalCount) {
                $totalPage = ceil($totalCount / $this->pageSize);
                for ($i = 1; $i <= $totalPage; $i++) {
                    $data = $this->entityRepository->lists($filter, ["created" => "DESC"], $this->pageSize, $i);
                    $this->scheduleTriggerFireToJob($activityObject, $data['list']);
                }
            }
        }// end foreach
    }

    private function scheduleTriggerFireToJob($activityObject, $activityList)
    {
        foreach ($activityList as $activityInfo) {
            $triggerCondition = $activityInfo['trigger_condition'];
            if ($activityObject->isTrigger($activityInfo)) {
                $this->scheduleFire($activityInfo, time());
            }
        }

        return true;
    }

    /**
     * 具体执行营销活动
     */
    public function scheduleFire(array $activityInfo, $triggerTime)
    {
        $pageSize = 100;

        $activityObject = new $this->activityArr[$activityInfo['activity_type']]();
        $totalMembers = $activityObject->countMembers($activityInfo['company_id'], $activityInfo['trigger_condition'], $triggerTime);
        if (!$totalMembers) {
            return true;
        }

        $totalPage = ceil($totalMembers / $pageSize);

        for ($i = 1; $i <= $totalPage; $i++) {
            $job = (new ScheduleFirePromotionsActivity($activityObject, $activityInfo, $triggerTime, $pageSize, $i));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
    }

    /**
     * 会员主动触发活动
     *
     * @param array $memberInfo 会员信息
     * @param array $activityType 触发的活动类型
     */
    public function fire($companyId, $memberInfo, $activityType)
    {
        $activityObject = new $this->activityArr[$activityType]();

        if (!$activityObject instanceof PromotionActivityInterface) {
            return true;
        }

        $filter = [
            'begin_time|lt' => time(),
            'end_time|gte' => time(),
            'activity_status' => 'valid',
            'activity_type' => $activityType,
            'company_id' => $companyId
        ];
        $totalCount = $this->entityRepository->count($filter);
        app('log')->info($activityType.' activity totalCount===>'.$totalCount);
        if (!$totalCount) {
            return true;
        }

        $totalPage = ceil($totalCount / $this->pageSize);
        for ($i = 1; $i <= $totalPage; $i++) {
            $data = $this->entityRepository->lists($filter, ["created" => "DESC"], $this->pageSize, $i);
            foreach ($data['list'] as $activityInfo) {
                // 执行具体的赠送
                $this->actionPromotionActivity($activityInfo, $memberInfo, $activityObject);
            }
        }

        return true;
    }

    // 执行具体的赠送
    public function actionPromotionActivity($activityInfo, $memberInfo, $activityObject)
    {
        $sendGoodsDiscount = false;
        $sendCouponsDiscount = false;

        $actionList = [];

        if (isset($memberInfo['grade_id'])) {
            // 普通会员赠送
            $actionList[] = [
                'index' => $memberInfo['grade_id'],
                'memberInfo' => $memberInfo,
            ];
        }

        // 判断用户是否为付费等级
        $vipGradeType = '';
        if (isset($memberInfo['vip_grade_type'])) {
            $vipGradeType = $memberInfo['vip_grade_type'];
        } else {
            $vipGradeOrderService = new VipGradeOrderService();
            $vipGrade = $vipGradeOrderService->userVipGradeGet($activityInfo['company_id'], $memberInfo['user_id']);
            if ($vipGrade['is_vip']) {
                $vipGradeType = $vipGrade['vip_type'];
                $memberInfo['grade_name'] = $vipGrade['grade_name'];
            }
        }

        if ($vipGradeType) {
            // 付费会员等级赠送
            $actionList[] = [
                'index' => $vipGradeType,
                'memberInfo' => $memberInfo,
            ];
        }

        if (!$actionList) {
            return true;
        }

        foreach ($actionList as $row) {
            $index = $row['index'];
            $newMemberInfo = $row['memberInfo'];

            $items = [];
            $coupons = [];
            // 如果存在商品对应的等级赠送配置
            if (isset($activityInfo['discount_config']['goods']) && isset($activityInfo['discount_config']['goods'][$index])) {
                $items = $activityInfo['discount_config']['goods'][$index];
            }
            if (isset($activityInfo['discount_config']['coupons']) && isset($activityInfo['discount_config']['coupons'][$index])) {
                $coupons = $activityInfo['discount_config']['coupons'][$index];
            }

            if ($items) {
                $sendGoodsDiscount = $this->actionItemsPromotions($activityInfo['company_id'], $items, $newMemberInfo, $activityObject->getSourceFromStr());
            }
            if ($coupons) {
                $sendCouponsDiscount = $this->actionItemsCoupons($activityInfo['company_id'], $coupons, $newMemberInfo, $activityObject->getSourceFromStr());
            }

            // 发送短信
            if ($activityInfo['sms_isopen'] == 'true') {
                try {
                    $smsManagerService = new SmsManagerService($activityInfo['company_id']);
                    $smsManagerService->send($newMemberInfo['mobile'], $activityInfo['company_id'], $activityObject->tmplName, []);
                } catch (\Exception $e) {
                    app('log')->debug('活动促销短信发送失败：'. $e->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * 执行商品优惠促销
     */
    public function actionItemsPromotions($companyId, $items, $memberInfo, $sourceFrom)
    {
        $itemsService = new ItemsService();
        $sendItems = false;
        foreach ($items as $itemInfo) {
            try {
                if ($memberInfo['user_id'] && $memberInfo['mobile']) {
                    $itemsService->addRightsByItemId($itemInfo['id'], $memberInfo['user_id'], $companyId, $memberInfo['mobile'], $sourceFrom, $itemInfo['count']);
                    $sendItems = true;
                }
            } catch (\Exception $e) {
                app('log')->debug($sourceFrom. '=>' .$e->getMessage(). var_export($itemInfo, true));
            }
        }

        return $sendItems;
    }

    /**
     * 赠送优惠券
     */
    public function actionItemsCoupons($companyId, $coupons, $memberInfo, $sourceFrom)
    {
        $userDiscountService = new UserDiscountService();
        $sendCoupons = false;
        foreach ($coupons as $couponRow) {
            for ($i = 1; $i <= $couponRow['count']; $i++) {
                try {
                    $userDiscountService->userGetCard($companyId, $couponRow['id'], $memberInfo['user_id'], $sourceFrom);
                    $sendCoupons = true;
                } catch (\Exception $e) {
                    app('log')->debug($sourceFrom. '=>' .$e->getMessage());
                }
            }
        }

        return $sendCoupons;
    }

    /**
     * 后台发放优惠券
     * @param $companyId Int 公司id
     * @param $coupons Array 优惠券列表
     * @param $users Array 用户列表
     * @param $sourceFrom String 优惠券来源
     */
    public function scheduleGiveToJob($companyId, $sender, $coupons, $users, $sourceFrom, $distributorId)
    {
        if (!$users || !is_array($users)) {
            throw new StoreResourceFailedException('请选择用户');
        }
        if (!$coupons || !is_array($coupons)) {
            throw new StoreResourceFailedException('请选择优惠券');
        }
        $job = (new ScheduleGivePromotionsActivity($companyId, $sender, $coupons, $users, $sourceFrom, time(), $distributorId))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return true;
    }

    /**
     * 后台发放优惠券
     * @param $companyId Int 公司id
     * @param $coupons Array 优惠券列表
     * @param $users Array 用户列表
     * @param $sourceFrom String 优惠券来源
     */
    public function scheduleGive($companyId, $sender, $coupons, $users, $sourceFrom, $triggerTime, $distributorId = 0)
    {
        $userDiscountService = new UserDiscountService();
        $sendCoupons = false;
        $couponGiveLogService = new CouponGiveLogService();
        $params = [
            'company_id' => $companyId,
            'sender' => $sender,
            'error' => 0,
            'number' => count($users) * count($coupons),
            'distributor_id' => $distributorId,
        ];
        $errorNum = 0;
        $result = $couponGiveLogService->createCouponGiveLog($params);
        foreach ($users as $userRow) {
            foreach ($coupons as $couponRow) {
                try {
                    $userDiscountService->userGetCard($companyId, $couponRow, $userRow, $sourceFrom);
                    $sendCoupons = true;
                } catch (\Exception $e) {
                    $errorNum++;
                    $couponGiveErrorLogService = new CouponGiveErrorLogService();
                    $params = [
                        'give_id' => $result['give_id'],
                        'uid' => $userRow,
                        'company_id' => $companyId,
                        'card_id' => $couponRow,
                        'note' => $e->getMessage()
                    ];
                    $couponGiveErrorLogService->createCouponGiveErrorLog($params);
                    app('log')->debug($sourceFrom. '=>' .$e->getMessage());
                }
            }
        }
        $couponGiveLogService->updateCouponGiveLog(['give_id' => $result['give_id']], ['error' => $errorNum]);
        return $sendCoupons;
    }

    /**
     * 拉取直播房间视频列表，回放列表
     */
    public function getliveRoomsList($authorizerAppId, $page, $limit = 10, $roomid = '', $action = 'get_replay')
    {
        $start = ($page - 1) * $limit;
        $limit = $limit;
        $openPlatform = new OpenPlatform();
        // 由于直播目前没有第三方资质，直连微信，不通过第三方平台接口调取，true==直连
        $app = $openPlatform->getAuthorizerApplication($authorizerAppId, true);
        if ($action == 'get_replay') {
            $result = $app->broadcast->getPlaybacks($roomid, $start, $limit);
        } else {
            $result = $app->broadcast->getRooms($start, $limit);
        }
        $return['list'] = [];
        if ($action == 'get_replay') {
            $return['list'] = $result['live_replay'] ?? [];
        } else {
            $return['list'] = isset($result['room_info']) ? $this->_calTimeText($result['room_info']) : [];
        }
        $return['total_count'] = $result['total'] ?? 0;

        return $return;
    }

    /**
     * 计算时间显示文案
     *
     * @param $list
     * @return array
     */
    private function _calTimeText($list): array
    {
        foreach ($list as $key => $item) {
            switch ($item['live_status']) {
                case 103:
                    $liveTime = $item['end_time'] - $item['start_time'];
                    $timeParam = $this->_getTimeParam($liveTime);
                    if ($liveTime > 86400) {
                        $list[$key]['live_time_text'] = $timeParam['day'] . '天' . $timeParam['hour'] . '小时' . $timeParam['minute'] . '分' . $timeParam['second'] . '秒';
                    } elseif ($liveTime < 86400 && $liveTime > 3600) {
                        $list[$key]['live_time_text'] = $timeParam['hour'] . '小时' . $timeParam['minute'] . '分' . $timeParam['second'] . '秒';
                    } elseif ($liveTime < 3600 && $liveTime > 60) {
                        $list[$key]['live_time_text'] = $timeParam['minute'] . '分' . $timeParam['second'] . '秒';
                    } else {
                        $list[$key]['live_time_text'] = $timeParam['second'] . '秒';
                    }
                    break;
                default:
                    $list[$key]['live_time_text'] = '';
            }
        }
        return $list;
    }

    /**
     * 获取时间参数
     *
     * @param $time
     * @return array
     */
    private function _getTimeParam($time): array
    {
        $day = intval($time / 86400);
        $calTime = $time - $day * 86400;
        $hour = intval($calTime / 3600);
        $calTime = $calTime - $hour * 3600;
        $minute = intval($calTime / 60);
        $calTime = $calTime - $minute * 60;

        return [
            'day' => $day,
            'hour' => str_pad($hour, 2, "0", STR_PAD_LEFT),
            'minute' => str_pad($minute, 2, "0", STR_PAD_LEFT),
            'second' => str_pad($calTime, 2, "0", STR_PAD_LEFT)
        ];
    }

    /**
     * 执行发放优惠券队列
     *
     * @param int $companyId
     * @param int $salespersonId
     * @param array $sendData
     * @return void
     */
    public function giveUserCouponsToJob($companyId, $salespersonId, array $sendData)
    {
        $job = (new SalespersonGiveUserCouponsActivity($companyId, $salespersonId, $sendData))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return true;
    }

    /**
     * 赠送优惠券
     *
     * @param int $companyId 公司id
     * @param int $salespersonId 导购id
     * @param array $sendData
     * @return void
     */
    public function giveUserCoupons($companyId, $salespersonId, array $sendData)
    {
        $userIds = array_column($sendData, 'user_id');
        $couponIds = array_column($sendData, 'coupon_id');

        $filter = [
            'card_id' => $couponIds,
            'company_id' => $companyId,
            'date_type' => 'DATE_TYPE_FIX_TERM',
            'end_date' => time(),
        ];

        $couponsEffective = $this->discountCardsRepository->effectiveFilterLists($filter, [], 10000);
        $tmpArr = [];
        foreach ($couponsEffective['list'] as &$value) {
            $userDiscountService = new UserDiscountService();
            $value['get_num'] = $userDiscountService->getCardGetNum($value['card_id'], $filter['company_id']);
            $tmpArr[] = $value['card_id'];
        }
        unset($value);
        $couponsEffectiveArr = array_column($couponsEffective['list'], null, 'card_id');

        foreach ($couponIds as $value) {
            if (!in_array($value, $tmpArr)) {
                app('log')->info('队列 giveUserCoupons=>优惠券不存在或已失效');
                return false;
            }
            if ($couponsEffectiveArr[$value]['get_num'] + count($userIds) > $couponsEffectiveArr[$value]['quantity']) {
                app('log')->info('队列 giveUserCoupons=>优惠券数量不足');
                return false;
            }
        }
        $userDiscountService = new UserDiscountService();
        foreach ($sendData as $value) {
            $giveCouponsStatus = $this->salespersonGiveCouponsRepositor->getInfo(['id' => $value['id'], 'status' => 1]);
            if ($giveCouponsStatus) {
                app('log')->info($value['user_id'] . '用户已成功发券' . $value['coupon_id']);
                continue;
            }
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                // 修改发券状态
                $filter = [
                    'id' => $value['id']
                ];
                $data = [
                    'status' => 1,
                ];
                $logStatus = $this->salespersonGiveCouponsRepositor->updateBy($filter, $data);
                if ($logStatus) {
                    // 发放用户优惠券
                    $sendResult = $userDiscountService->userGetCard($companyId, $value['coupon_id'], $value['user_id'], '导购员发放', $salespersonId);
                    if (!$sendResult) {
                        $error = '队列 giveUserCoupons=>coupon:' . $value['coupon_id'] . '发放失败';
                        app('log')->debug($error);
                    } else {
                        app('log')->info($value['user_id'] . '用户首次成功发券' . $value['coupon_id']);
                    }
                }
                $conn->commit();
            } catch (\Exception $exception) {
                app('log')->debug('giveUserCoupons=>coupon' . $exception->getMessage());
                $conn->rollback();
            }

            // 导购发放优惠券任务统计
            $salespersonTaskRecordService = new SalespersonTaskRecordService();
            $params = [
                'company_id' => $companyId,
                'salesperson_id' => $salespersonId,
                'user_id' => $value['user_id'],
                'type' => 'coupons',
                'id' => $value['coupon_id'],
            ];
            $result = $salespersonTaskRecordService->completeWelfare($params);

            // 导购发放优惠券统计
            $salespersonCouponStatisticsService = new SalespersonCouponStatisticsService();
            $couponParams = [
                'company_id' => $companyId,
                'salesperson_id' => $salespersonId,
                'coupon_id' => $value['coupon_id'],
            ];
            $salespersonCouponStatisticsService->completeCouponSend($couponParams);
        }
        return true;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
