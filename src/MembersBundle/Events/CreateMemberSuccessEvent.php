<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
