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

namespace MembersBundle\Events;

use App\Events\Event;

class CreateMemberSuccessEvent extends Event
{
    // ModuleID: 76fe2a3d
    public $companyId;
    public $userId;
    public $mobile;
    public $openid;
    public $wxa_appid;
    public $source_id;
    public $monitor_id;
    public $inviter_id;
    public $distributorId;
    public $salespersonId;
    public $ifRegisterPromotion;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        // ModuleID: 76fe2a3d
        $this->companyId = $eventData['company_id'];
        $this->userId = $eventData['user_id'];
        $this->mobile = $eventData['mobile'];
        $this->openid = $eventData['openid'];
        $this->wxa_appid = $eventData['wxa_appid'];
        $this->source_id = $eventData['source_id'];
        $this->monitor_id = $eventData['monitor_id'];
        $this->inviter_id = isset($eventData['inviter_id']) && $eventData['inviter_id'] ? $eventData['inviter_id'] : 0;
        $this->salespersonId = $eventData['salesperson_id'];
        $this->ifRegisterPromotion = $eventData['if_register_promotion'];

        if (isset($eventData['distributor_id'])) {
            $this->distributorId = $eventData['distributor_id'];
        }
    }
}
