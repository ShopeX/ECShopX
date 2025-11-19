<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Jobs;

use EspierBundle\Jobs\Job;

use PromotionsBundle\Services\PromotionActivity;

class SalespersonGiveUserCouponsActivity extends Job
{
    // conpanyId
    private $companyId;
    // 导购员id
    private $salespersonId;
    // 优惠券发放相关人员以及优惠券id
    private $sendData;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $salespersonId, $sendData)
    {
        $this->companyId = $companyId;
        $this->salespersonId = $salespersonId;
        $this->sendData = $sendData;
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
        $promotionActivity->giveUserCoupons($this->companyId, $this->salespersonId, $this->sendData);
    }
}
