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

use Dingo\Api\Exception\StoreResourceFailedException;




use MembersBundle\Entities\MembersInfo;
use KaquanBundle\Entities\SalespersonGiveCoupons;
use KaquanBundle\Entities\DiscountCards;
use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Entities\ExtraPointActivity;
use MembersBundle\Services\MemberService;

// 营销活动
class ExtraPointActivityService
{
    public $pageSize = 50;

    public $entityRepository;
    public $discountCardsRepository;
    public $salespersonGiveCouponsRepositor;
    public $memberInfoRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(ExtraPointActivity::class);
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
        $this->checkActiveValid($data);
        $this->checkActivityParams($data);
        // 检查对应的活动参数
        // $activityObject = new $this->activityArr['member_day'];
        $data['type'] = 'member_day';
        $data['activity_status'] = 'valid';
        $data['created'] = time();
        if (isset($data['trigger_amount']) && floatval($data['trigger_amount']) > 0) {
            $data['trigger_condition']['trigger_amount'] = $data['trigger_amount'];
            unset($data['trigger_amount']);
        }
        if ($data['activity_id'] ?? 0) {
            return $this->updateOneBy(['activity_id' => $data['activity_id']], $data);
        } else {
            return $this->entityRepository->create($data);
        }
    }


    /**
     * 保存会员日营销活动检查
     *
     * @param array $data 保存的参数
     */
    public function checkActivityParams(array $data)
    {
        if (floatval($data['trigger_amount']) < 0) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.order_amount_must_positive'));
        }
        $triggerCondition = $data['trigger_condition']['trigger_time'];

        if (!in_array($triggerCondition['type'], ['every_year', 'every_month', 'every_week', 'date'])) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.please_select_gift_method'));
        }

        if ($triggerCondition['type'] == 'every_year' && (!$triggerCondition['month'] || !$triggerCondition['day'])) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.please_select_specific_gift_date'));
        }

        if ($triggerCondition['type'] == 'every_month' && !$triggerCondition['day']) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.please_select_specific_gift_date'));
        }

        if ($triggerCondition['type'] == 'every_week' && !$triggerCondition['week']) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.please_select_specific_gift_date'));
        }

        if ($triggerCondition['type'] == 'every_week' && !$triggerCondition['week']) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.please_select_specific_gift_date'));
        }

        if ($triggerCondition['type'] == 'date' && !$triggerCondition['begin_time']) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.please_select_specific_gift_date'));
        }
        return true;
    }


    /**
     * 检查当前活动的有效性
     *
     * @param string $activityType 活动类型
     */
    public function checkActiveValid($data)
    {
        $filter = ['company_id' => $data['company_id'], 'activity_status' => 'valid', 'begin_time|lte' => $data['end_time'], 'end_time|gte' => $data['begin_time']];
        if ($data['activity_id'] ?? 0) {
            $filter['activity_id|neq'] = $data['activity_id'];
        }
        $isValid = $this->entityRepository->count($filter);
        if ($isValid) {
            throw new ResourceException(trans('PromotionsBundle.same_time_only_one_activity'));
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


    public function getActivityInfo($id)
    {
        return $this->entityRepository->getInfoById($id);
    }

    public function getActiveActivity()
    {
        $now = time();
        $filter = [
            'activity_status' => 'valid',
            'begin_time|lt' => $now,
            'end_time|gt' => $now
        ];
        $activity = $this->entityRepository->lists($filter);
        if ($activity['list']) {
            return $activity['list'][0];
        }
        return [];
    }

    public function getExtrapoints($filter, $point)
    {
        // 离线购买不参与活动
        if (!$filter['user_id']) {
            return 0;
        }

        $activity = $this->getActiveActivity();
        //获取指定会员的等级信息
        $memberService = new MemberService();
        $userGrade = $memberService->getValidUserGradeUniqueByUserId($filter['user_id'], $filter['company_id']);
        if ($activity) {
            //店铺是否符合条件
            if ($activity['shop_ids'] && !in_array($filter['distributor_id'], $activity['shop_ids'])) {
                return 0;
            }
            //等级是否符合条件
            if ($activity['valid_grade'] && (!in_array($userGrade['id'], $activity['valid_grade']) && !in_array($userGrade['lv_type'], $activity['valid_grade']))) {
                return 0;
            }
            //订单金额(支付金额-运费)是否符合条件
            if (isset($activity['trigger_condition']['trigger_amount']) && $activity['trigger_condition']['trigger_amount'] * 100 > $filter['total_fee']) {
                return 0;
            }
            $trigger_time = $activity['trigger_condition']['trigger_time'];
            // 时间是否符合条件
            if (($trigger_time['type'] == 'every_year' && $trigger_time['month'] == date('n') && $trigger_time['day'] == date('j')) // 每年的
                || ($trigger_time['type'] == 'every_month' && $trigger_time['day'] == date('j')) // 每月的第几天
                || ($trigger_time['type'] == 'every_week' && $trigger_time['week'] == date('N')) //
                || ($trigger_time['type'] == 'date' && strtotime($trigger_time['begin_time']) <= time() && time() <= strtotime($trigger_time['end_time'] . ' 23:59:59'))
            ) {
                return $this->{$activity['condition_type'].'Point'}($point, $activity['condition_value']);
            }
        }
        return 0;
    }

    //倍数赠送积分
    public function multiplePoint($point, $condition_value)
    {
        return $point * ($condition_value - 1);
    }

    //指定数量赠送额外积分 todo
    public function plusPoint($point, $condition_value)
    {
    }


    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
