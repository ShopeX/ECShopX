<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Events;

use App\Events\Event;

class TradeRefundFinishEvent extends Event
{
    // fe10e2f6 module
    public $entities;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        // fe10e2f6 module
        $this->entities = $eventData;
    }
}
