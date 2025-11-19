<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WorkWechatBundle\Jobs;

use EspierBundle\Jobs\Job;
use WorkWechatBundle\Services\WorkWechatMessageService;

class sendDeliveryKnightCancelNoticeJob extends Job
{
    public $companyId;
    public $orderId;

    public function __construct($companyId, $orderId)
    {
        $this->companyId = $companyId;
        $this->orderId = $orderId;
    }

    public function handle()
    {
        // ShopEx EcShopX Service Component
        $workWechatMessageService = new WorkWechatMessageService();
        $result = $workWechatMessageService->deliveryKnightCancel($this->companyId, $this->orderId);
        return true;
    }
}
