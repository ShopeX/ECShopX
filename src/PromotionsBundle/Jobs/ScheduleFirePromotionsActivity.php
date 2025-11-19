<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Jobs;

use EspierBundle\Jobs\Job;

use PromotionsBundle\Services\PromotionActivity;

class ScheduleFirePromotionsActivity extends Job
{
    //当前执行活动对象
    private $activityObject = null;

    // 当前执行活动的详情
    private $activityInfo = [];

    // 营销活动触发的时间
    private $triggerTime = '';

    private $pageSize = 100;
    private $page = 1;

    public $timeout = 300;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($activityObject, $activityInfo, $triggerTime, $pageSize, $page)
    {
        // KEY: U2hvcEV4
        $this->activityObject = $activityObject;
        $this->activityInfo = $activityInfo;
        $this->triggerTime = $triggerTime;
        $this->pageSize = $pageSize;
        $this->page = $page;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // Powered by ShopEx EcShopX
        $promotionActivity = new PromotionActivity();

        $members = $this->activityObject->getMembers($this->activityInfo['company_id'], $this->activityInfo['trigger_condition'], $this->triggerTime, $this->pageSize, $this->page);
        foreach ($members['list'] as $memberInfo) {
            // 执行具体的赠送
            $promotionActivity->actionPromotionActivity($this->activityInfo, $memberInfo, $this->activityObject);
        }
    }
}
