<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PointsmallBundle\Events;

use App\Events\Event;

class ItemDeleteEvent extends Event
{
    // TODO: optimize this method
    public $entities;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        // TODO: optimize this method
        $this->entities = $eventData;
    }
}
