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

use AliyunsmsBundle\Services\RecordService;
use AliyunsmsBundle\Services\TaskService;
use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class QuerySendDetail extends Job
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $client = new AliyunSmsClient($this->params['company_id']);
        $result = $client->querySendDetail($this->params);
        $taskServicce = new TaskService();
        if(isset($result['SmsSendDetailDTOs']) && $result['SmsSendDetailDTOs']) {
            $updateData = [
                'status' => $result['SmsSendDetailDTOs']['SmsSendDetailDTO'][0]['SendStatus'],
                'sms_content' => $result['SmsSendDetailDTOs']['SmsSendDetailDTO'][0]['Content']
            ];
            (new RecordService())->updateOneBy(['id' => $this->params['id']], $updateData);
        }
        return true;
    }
}
