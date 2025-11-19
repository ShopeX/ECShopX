<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Services\PromotionActivity;

use Dingo\Api\Exception\StoreResourceFailedException;

use MembersBundle\Services\MemberService;
use PromotionsBundle\Interfaces\SchedulePromotionActivity;

// 生日营销。用户生日的时候赠送指定的权益或者优惠券
class MemberBirthday implements SchedulePromotionActivity
{
    /**
     * 当前活动可以同时创建有效的营销次数
     */
    public $validNum = 1;

    /**
     * 发送短信模版名称
     */
    public $tmplName = 'member_birthday';

    /**
     * 保存会员生日营销活动检查
     *
     * @param array $data 保存的参数
     */
    public function checkActivityParams(array $data)
    {
        if (!in_array($data['trigger_condition']['trigger_time'], ['birthday_month', 'birthday_week', 'birthday_day'])) {
            throw new StoreResourceFailedException(trans('PromotionsBundle.please_select_gift_method'));
        }

        return true;
    }

    /**
     * 是否触发生日营销活动
     */
    public function isTrigger(array $activityInfo)
    {
        $triggerCondition = $activityInfo['trigger_condition'];

        // 如果是生日当月1日发送
        if (($triggerCondition['trigger_time'] == 'birthday_month' && date('d') == '01')
            || ($triggerCondition['trigger_time'] == 'birthday_week' && date('D') == 'Sun')
            || $triggerCondition['trigger_time'] == 'birthday_day'
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function getSourceFromStr()
    {
        return '会员生日送';
    }

    /**
     * 统计生日的会员数量
     *
     * 统计触发条件获取赠送的用户ID
     */
    public function countMembers($companyId, $triggerCondition, $triggerTime)
    {
        $memberService = new MemberService();

        $filter['company_id'] = $companyId;
        $filter = $this->getMemberFilter($filter, $triggerCondition, $triggerTime);

        $pageSize = 1;
        $page = 1;
        $data = $memberService->getList($page, $pageSize, $filter);
        return $data['total_count'];
    }

    private function getMemberFilter($filter, $triggerCondition, $triggerTime)
    {
        if ($triggerCondition['trigger_time'] == 'birthday_month') {
            $filter['month'] = intval(date('m', $triggerTime));
        } elseif ($triggerCondition['trigger_time'] == 'birthday_week') { // 生日当周周日赠送
            $filter['day|gte'] = intval(date('d', $triggerTime));
            $filter['day|lte'] = intval(date('d', $triggerTime)) + 6;
            $filter['month'] = intval(date('m', $triggerTime));
        } elseif ($triggerCondition['trigger_time'] == 'birthday_day') { // 生日当天赠送
            $filter['day'] = intval(date('d', $triggerTime));
            $filter['month'] = intval(date('m', $triggerTime));
        }
        return $filter;
    }

    /**
     * 获取生日的会员
     *
     * 根据触发条件获取赠送的用户ID
     */
    public function getMembers($companyId, $triggerCondition, $triggerTime, $pageSize, $page)
    {
        $memberService = new MemberService();

        $filter['company_id'] = $companyId;
        $filter = $this->getMemberFilter($filter, $triggerCondition, $triggerTime);

        $data = $memberService->getList($page, $pageSize, $filter);

        return $data;
    }
}
