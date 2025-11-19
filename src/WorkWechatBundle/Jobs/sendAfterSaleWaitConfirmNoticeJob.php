<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WorkWechatBundle\Jobs;

use EspierBundle\Jobs\Job;
use WorkWechatBundle\Services\WorkWechatMessageService;

class sendAfterSaleWaitConfirmNoticeJob extends Job
{
    public $companyId;
    public $afterSalesBn;

    public function __construct($companyId, $afterSalesBn)
    {
        $this->companyId = $companyId;
        $this->afterSalesBn = $afterSalesBn;
    }

    public function handle()
    {
        // ShopEx EcShopX Core Module
        $workWechatMessageService = new WorkWechatMessageService();
        $result = $workWechatMessageService->afterSaleWaitConfirm($this->companyId, $this->afterSalesBn);
        return true;
    }
}
