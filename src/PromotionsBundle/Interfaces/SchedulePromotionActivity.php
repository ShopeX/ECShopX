<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Interfaces;

interface SchedulePromotionActivity
{
    /**
     * 添加活动判断特有参数
     */
    public function checkActivityParams(array $data);

    /**
     * 是否触发活动
     */
    public function isTrigger(array $activityInfo);

    /**
     * 是否触发活动
     */
    public function getSourceFromStr();

    /**
     * 统计需要发送的会员数量
     */
    public function countMembers($companyId, $triggerCondition, $triggerTime);

    /**
     * 获取发送会员信息
     */
    public function getMembers($companyId, $triggerCondition, $triggerTime, $pageSize, $page);
}
