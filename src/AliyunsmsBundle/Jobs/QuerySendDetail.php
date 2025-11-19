<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
