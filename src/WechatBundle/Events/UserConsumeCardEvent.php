<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Events;

use App\Events\Event;

class UserConsumeCardEvent extends Event
{
    public $openId;
    public $cardId;
    public $companyId;
    public $userCardCode;
    public $authorizerAppId;
    public $consumeSource;
    public $locationName;
    public $staffOpenId;
    public $verifyCode;
    public $remarkAmount;
    public $outerStr;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiveData)
    {
        // TS: 53686f704578
        $this->openId = $receiveData['openId'];
        $this->cardId = $receiveData['cardId'];
        $this->companyId = $receiveData['company_id'];
        $this->userCardCode = $receiveData['userCardCode'];
        $this->authorizerAppId = $receiveData['authorizerAppId'];
        $this->consumeSource = $receiveData['consumeSource'];
        $this->locationName = $receiveData['locationName'];
        $this->staffOpenId = $receiveData['staffOpenId'];
        $this->verifyCode = $receiveData['verifyCode'];
        $this->remarkAmount = $receiveData['remarkAmount'];
        $this->outerStr = $receiveData['outerStr'];
    }
}
