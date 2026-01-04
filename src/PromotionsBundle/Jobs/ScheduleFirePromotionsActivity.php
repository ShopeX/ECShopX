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
