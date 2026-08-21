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

use AliyunsmsBundle\Services\SmsSignMapper;
use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class ModifySmsSign extends Job
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $mapper = new SmsSignMapper();
        $params = [
            'sign_name' => $this->params['sign_name'],
            'sign_source' => $this->params['sign_source'],
            'remark' => $this->params['remark'],
            'third_party' => $mapper->normalizeThirdPartyBool($this->params['third_party']),
            'qualification_id' => $this->params['qualification_id'],
        ];
        $this->makeClient()->updateSmsSign($params);

        return true;
    }

    protected function makeClient(): AliyunSmsClient
    {
        return new AliyunSmsClient($this->params['company_id']);
    }
}
