<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AliyunsmsBundle\Jobs;

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
        $addSmsSignJob = new AddSmsSign();
        $client = new AliyunSmsClient($this->params['company_id']);
        if ($this->params['sign_file'] ?? 0) {
            $this->params['sign_file'] = $addSmsSignJob->getImg($this->params['sign_file']);
        }
        if ($this->params['delegate_file'] ?? 0) {
            $this->params['delegate_file'] = $addSmsSignJob->getImg($this->params['delegate_file']);
        }
        if(!isset($this->params['sign_file']) && !isset($this->params['delegate_file'])) {
            $this->params['sign_file'] = $addSmsSignJob->getImg($addSmsSignJob->defaultImg);
        }
        $result = $client->modifySmsSign($this->params);
        return true;
    }
}
