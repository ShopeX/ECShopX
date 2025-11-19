<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
