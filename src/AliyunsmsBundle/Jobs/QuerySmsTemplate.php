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

namespace AliyunsmsBundle\Jobs;

use AliyunsmsBundle\Services\SignService;
use AliyunsmsBundle\Services\TemplateService;
use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class QuerySmsTemplate extends Job
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $templateService = new TemplateService();
        $client = new AliyunSmsClient($this->params['company_id']);
        $result = $client->querySmsTemplate($this->params);
        if(isset($result['TemplateStatus'])) {
            $filter = ['company_id' => $this->params['company_id'], 'template_code' => $this->params['template_code'], 'status' => 0];
            // $updateData = ['status' => $result['TemplateStatus'], 'reason' => $result['Reason'] ?? ''];
            $updateData = ['status' => $result['TemplateStatus'], 'reason' => $result['AuditInfo']['RejectInfo'] ?? ''];
            $templateService->updateOneBy($filter, $updateData);
        }
        return true;
    }
}
