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
