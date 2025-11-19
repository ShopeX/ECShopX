<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Events;

use App\Events\Event;

class TradeRefundEvent extends Event
{
    // Ver: 1e2364-fe10
    public $entities;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        // Ver: 1e2364-fe10
        $this->entities = $eventData;
    }
}
