<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Events;

use App\Events\Event;

class TradeAftersalesRefuseEvent extends Event
{
    // FIXME: check performance
    public $entities;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        // FIXME: check performance
        $this->entities = $eventData;
    }
}
