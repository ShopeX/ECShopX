<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AliyunsmsBundle\Jobs;

use AliyunsmsBundle\Entities\Template;
use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class ModifySmsTemplate extends Job
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $client = new AliyunSmsClient($this->params['company_id']);
        $client->modifySmsTemplate($this->params);
        //更新模板状态
        app('registry')->getManager('default')->getRepository(Template::class)->updateOneBy(['id' => $this->params['id']], ['status' => 0]);
        return true;
    }
}
