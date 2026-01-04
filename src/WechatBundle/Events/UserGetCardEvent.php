<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
