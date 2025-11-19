<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Events;

use App\Events\Event;

class WechatSubscribeEvent extends Event
{
    public $openId;

    public $authorizerAppId;

    public $event;

    public $companyId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiveData)
    {
        $this->openId = $receiveData['openId'];
        $this->authorizerAppId = $receiveData['authorizerAppId'];
        $this->companyId = $receiveData['company_id'];
        $this->event = $receiveData['event'];
    }
}
