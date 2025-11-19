<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
