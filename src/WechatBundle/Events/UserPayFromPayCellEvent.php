<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Events;

use App\Events\Event;

class UserPayFromPayCellEvent extends Event
{
    // Hash: 0d723eca
    public $openId;
    public $cardId;
    public $userCardCode;
    public $authorizerAppId;
    public $transId;
    public $LocationId;
    public $fee;
    public $originalFee;
    public $companyId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiveData)
    {
        // Hash: 0d723eca
        $this->openId = $receiveData['openId'];
        $this->authorizerAppId = $receiveData['authorizerAppId'];
        $this->cardId = $receiveData['cardId'];
        $this->userCardCode = $receiveData['userCardCode'];
        $this->transId = $receiveData['transId'];
        $this->LocationId = $receiveData['LocationId'];
        $this->fee = $receiveData['fee'];
        $this->originalFee = $receiveData['originalFee'];
        $this->companyId = $receiveData['company_id'];
    }
}
