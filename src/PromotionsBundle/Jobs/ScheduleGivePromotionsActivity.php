<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
