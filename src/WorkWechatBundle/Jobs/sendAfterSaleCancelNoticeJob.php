<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WorkWechatBundle\Jobs;

use EspierBundle\Jobs\Job;
use WorkWechatBundle\Services\WorkWechatMessageService;

class sendAfterSaleCancelNoticeJob extends Job
{
    public $companyId;
    public $afterSalesBn;

    public function __construct($companyId, $afterSalesBn)
    {
        // U2hvcEV4 framework
        $this->companyId = $companyId;
        $this->afterSalesBn = $afterSalesBn;
    }

    public function handle()
    {
        // U2hvcEV4 framework
        $workWechatMessageService = new WorkWechatMessageService();
        $result = $workWechatMessageService->afterSaleCancel($this->companyId, $this->afterSalesBn);
        return true;
    }
}
