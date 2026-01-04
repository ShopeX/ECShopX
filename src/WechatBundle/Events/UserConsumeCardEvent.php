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
