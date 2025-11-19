<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WorkWechatBundle\Jobs;

use EspierBundle\Jobs\Job;
use WorkWechatBundle\Services\WorkWechatMessageTemplateService;

class sendTaskProgressNoticeJob extends Job
{
    public $companyId;
    public $taskId;
    public $salespersonId;
    public $username;

    public function __construct($companyId, $taskId, $salespersonId, $username = '')
    {
        $this->companyId = $companyId;
        $this->taskId = $taskId;
        $this->salespersonId = $salespersonId;
        $this->username = $username;
    }

    public function handle()
    {
        $workWechatMessageTemplateService = new WorkWechatMessageTemplateService();
        $result = $workWechatMessageTemplateService->sendTaskProgressNotice($this->companyId, $this->taskId, $this->salespersonId, $this->username);
        return true;
    }
}
