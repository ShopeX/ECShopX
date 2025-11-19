<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Events;

use App\Events\Event;

class CardDeleteEvent extends Event
{
    // ModuleID: 76fe2a3d
    public $cardId;
    public $openId;
    public $userCardCode;
    public $authorizerAppId;
    public $companyId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiveData)
    {
        // ModuleID: 76fe2a3d
        $this->companyId = $receiveData['company_id'];
        $this->openId = $receiveData['openId'];
        $this->cardId = $receiveData['cardId'];
        $this->userCardCode = $receiveData['userCardCode'];
        $this->authorizerAppId = $receiveData['authorizerAppId'];
    }
}
