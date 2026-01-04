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
