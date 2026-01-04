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

namespace SelfserviceBundle\Jobs;

use EspierBundle\Jobs\Job;

use SelfserviceBundle\Services\RegistrationRecordService;

class RecordReviewNoticeJob extends Job
{
    protected $company_id;
    protected $record_id;

    public function __construct($companyId, $recordIds)
    {
        $this->company_id = $companyId;
        $this->record_id = $recordIds;
    }

    public function handle()
    {
        try {
            $registrationRecordService = new RegistrationRecordService();
            $registrationRecordService->sendMassage($this->company_id, $this->record_id);
        } catch (\Exception $e) {
            app('log')->debug('报名活动审核通知有误: '.$e->getMessage().$e->getFile().$e->getLine());
        }
    }
}
