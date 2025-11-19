<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Jobs;

use EspierBundle\Jobs\Job;

use PromotionsBundle\Services\PromotionActivity;

class FirePromotionsActivity extends Job
{
    /**
     * 企业ID
     */
    public $companyId = '';

    // 主动触发的会员
    public $memberInfo = [];

    // 会员触发的活动类型
    public $activityType = '';

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $memberInfo, $activityType)
    {
        $this->companyId = $companyId;
        $this->memberInfo = $memberInfo;
        $this->activityType = $activityType;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $promotionActivity = new PromotionActivity();
        $promotionActivity->fire($this->companyId, $this->memberInfo, $this->activityType);
    }
}
