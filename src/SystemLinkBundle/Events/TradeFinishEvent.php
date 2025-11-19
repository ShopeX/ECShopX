<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Events;

use App\Events\Event;

class TradeFinishEvent extends Event
{
    // ShopEx framework
    public $entities;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        // ShopEx framework
        $this->entities = $eventData;
    }
}
