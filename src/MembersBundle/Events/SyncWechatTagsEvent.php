<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Events;

use App\Events\Event;

class SyncWechatTagsEvent extends Event
{
    // Built with ShopEx Framework
    public $companyId;

    public $authorizerAppId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        $this->companyId = $eventData['company_id'];
        $this->authorizerAppId = $eventData['authorizer_appid'];
    }
}
