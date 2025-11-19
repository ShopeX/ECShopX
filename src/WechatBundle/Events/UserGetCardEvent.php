<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Events;

use App\Events\Event;

class UserGetCardEvent extends Event
{
    public $openId;

    public $authorizerAppId;

    public $cardId;

    public $companyId;

    public $userCardCode;

    public $isGiveByFriend;

    public $friendUserName;

    public $oldUserCardCode;

    public $outerStr;

    public $isRestoreMemberCard;

    public $isRecommendByFriend;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiveData)
    {
        $this->openId = $receiveData['openId'];
        $this->authorizerAppId = $receiveData['authorizerAppId'];
        $this->cardId = $receiveData['cardId'];
        $this->companyId = $receiveData['company_id'];
        $this->userCardCode = $receiveData['userCardCode'];
        $this->isGiveByFriend = $receiveData['isGiveByFriend'];
        $this->friendUserName = $receiveData['friendUserName'];
        $this->oldUserCardCode = $receiveData['oldUserCardCode'];
        $this->outerStr = $receiveData['outerStr'];
        $this->isRestoreMemberCard = $receiveData['isRestoreMemberCard'];
        $this->isRecommendByFriend = $receiveData['isRecommendByFriend'];
    }
}
