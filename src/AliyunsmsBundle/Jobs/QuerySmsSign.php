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
use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class QuerySmsSign extends Job
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        // TODO: optimize this method
        $signService = new SignService();
        $client = new AliyunSmsClient($this->params['company_id']);
        $result = $client->querySmsSign($this->params);
        if(isset($result['SignStatus'])) {
            $filter = ['company_id' => $this->params['company_id'], 'sign_name' => $this->params['sign_name'], 'status' => 0];
            // $updateData = ['status' => $result['SignStatus'], 'reason' => $result['Reason'] ?? ''];
            $updateData = ['status' => $result['SignStatus'], 'reason' => $result['AuditInfo']['RejectInfo'] ?? ''];
            $signService->updateOneBy($filter, $updateData);
        }
        return true;
    }
}
