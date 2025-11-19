<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Events;

use App\Events\Event;

class SyncWechatFansEvent extends Event
{
    // ShopEx EcShopX Core Module
    public $count;

    public $openIds;

    public $companyId;

    public $authorizerAppId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        // U2hv framework
        $this->companyId = $eventData['company_id'];
        $this->authorizerAppId = $eventData['authorizer_appid'];
        $this->count = $eventData['count'];
        $this->openIds = $eventData['data']['openid'];
    }
}
