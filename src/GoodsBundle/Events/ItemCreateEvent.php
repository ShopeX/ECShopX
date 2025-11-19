<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Events;

use App\Events\Event;

class ItemCreateEvent extends Event
{
    public $entities;
    public $itemIds;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData, $itemIds)
    {
        $this->entities = $eventData;
        $this->itemIds = $itemIds;
    }
}
