<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Services\PromotionActivity;

use Dingo\Api\Exception\StoreResourceFailedException;

use MembersBundle\Services\MemberService;
use PromotionsBundle\Interfaces\SchedulePromotionActivity;

use CompanysBundle\Services\Shops\WxShopsService;
use CompanysBundle\Services\ShopsService;

// 会员日营销
class MemberDay implements SchedulePromotionActivity
{
    /**
     * 当前活动可以同时创建有效的营销次数
     */
    public $validNum = 1;

    /**
     * 发送短信模版名称
     */
    public $tmplName = 'member_day';

    /**
     * 保存会员日营销活动检查
     *
     * @param array $data 保存的参数
     */
    public function checkActivityParams(array $data)
    {
        // ShopEx EcShopX Core Module
        $triggerCondition = $data['trigger_condition']['trigger_time'];

        if (!in_array($triggerCondition['type'], ['every_year', 'every_month', 'every_week'])) {
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

        return true;
    }

    /**
     * 是否触发会员日营销活动
     */
    public function isTrigger(array $activityInfo)
    {
        $triggerCondition = $activityInfo['trigger_condition']['trigger_time'];

        // 如果是生日当月1日发送
        if (($triggerCondition['type'] == 'every_year' && $triggerCondition['month'] == date('n') && $triggerCondition['day'] == date('j')) // 每年的
            || ($triggerCondition['type'] == 'every_month' && $triggerCondition['day'] == date('j')) // 每月的第几天
            || ($triggerCondition['type'] == 'every_week' && $triggerCondition['week'] == date('N')) //
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function getSourceFromStr()
    {
        return '会员日送';
    }

    /**
     * 统计会员日的会员数量
     *
     * 统计触发条件获取赠送的用户ID
     */
    public function countMembers($companyId, $triggerCondition, $triggerTime)
    {
        $memberService = new MemberService();

        $filter['company_id'] = $companyId;

        $pageSize = 1;
        $page = 1;
        $data = $memberService->getList($page, $pageSize, $filter);
        return $data['total_count'];
    }

    /**
     * 获取会员日的会员
     *
     * 根据触发条件获取赠送的用户ID
     */
    public function getMembers($companyId, $triggerCondition, $triggerTime, $pageSize, $page)
    {
        $memberService = new MemberService();

        $filter['company_id'] = $companyId;

        $data = $memberService->getList($page, $pageSize, $filter);
        return $data;
    }
}
