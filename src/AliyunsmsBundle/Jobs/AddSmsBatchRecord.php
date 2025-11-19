<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AliyunsmsBundle\Jobs;

use AliyunsmsBundle\Entities\Template;
use AliyunsmsBundle\Services\RecordService;
use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class AddSmsBatchRecord extends Job
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $recordService = new RecordService();
        foreach ($this->params['mobile'] as $mobile) {
            $data = $this->params;
            $data['mobile'] = $mobile;
            $recordService->addRecord($data);
        }
        return true;
    }
}
