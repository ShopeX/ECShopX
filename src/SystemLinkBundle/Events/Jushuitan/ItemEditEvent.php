<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Events\Jushuitan;

use App\Events\Event;

class ItemEditEvent extends Event
{
    public $entities;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $this->entities = $eventData;
    }
}

