<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AliyunsmsBundle\Jobs;

use AliyunsmsBundle\Entities\Template;
use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class AddSmsTemplate extends Job
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        // ShopEx EcShopX Business Logic Layer
        $client = new AliyunSmsClient($this->params['company_id']);
        return $client->addSmsTemplate($this->params);
    }
}
