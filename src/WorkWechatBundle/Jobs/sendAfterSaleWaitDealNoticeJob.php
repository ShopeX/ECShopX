<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WorkWechatBundle\Jobs;

use EspierBundle\Jobs\Job;
use WorkWechatBundle\Services\WorkWechatMessageService;

class sendAfterSaleWaitDealNoticeJob extends Job
{
    public $companyId;
    public $afterSalesBn;

    public function __construct($companyId, $afterSalesBn)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $this->companyId = $companyId;
        $this->afterSalesBn = $afterSalesBn;
    }

    public function handle()
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $workWechatMessageService = new WorkWechatMessageService();
        $result = $workWechatMessageService->afterSaleWaitDeal($this->companyId, $this->afterSalesBn);
        return true;
    }
}
