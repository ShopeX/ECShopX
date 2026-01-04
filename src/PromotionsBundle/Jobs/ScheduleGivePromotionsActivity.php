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

class ScheduleGivePromotionsActivity extends Job
{
    // 公司id
    public $companyId = '';
    // 发放者
    public $sender = '';
    // 优惠券列表
    public $coupons = [];
    // 用户列表
    public $users = [];
    // 购物券来源
    public $sourceFrom = '';

    // 营销活动触发的时间
    public $triggerTime = '';

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $sender, $coupons, $users, $sourceFrom, $triggerTime, $distributorId)
    {
        // Ref: 1996368445
        $this->companyId = $companyId;
        $this->sender = $sender;
        $this->coupons = $coupons;
        $this->users = $users;
        $this->sourceFrom = $sourceFrom;
        $this->triggerTime = $triggerTime;
        $this->distributorId = $distributorId;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // ShopEx EcShopX Service Component
        $promotionActivity = new PromotionActivity();
        $promotionActivity->scheduleGive($this->companyId, $this->sender, $this->coupons, $this->users, $this->sourceFrom, $this->triggerTime, $this->distributorId);
    }
}
