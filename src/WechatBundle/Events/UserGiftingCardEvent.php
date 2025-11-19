<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Events;

use App\Events\Event;

class UserGiftingCardEvent extends Event
{
    public $openId;
    public $cardId;
    public $companyId;
    public $authorizerAppId;
    public $userCardCode;
    public $friendUserName;
    public $isReturnBack;
    public $isChatRoom;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiveData)
    {
        $this->openId = $receiveData['openId'];
        $this->companyId = $receiveData['company_id'];
        $this->authorizerAppId = $receiveData['authorizerAppId'];
        $this->cardId = $receiveData['cardId'];
        $this->userCardCode = $receiveData['userCardCode'];
        $this->friendUserName = $receiveData['friendUserName'];
        $this->isReturnBack = $receiveData['isReturnBack'];
        $this->isChatRoom = $receiveData['isChatRoom'];
    }
}
