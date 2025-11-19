<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ReservationBundle\Events;

use App\Events\Event;

class ReservationFinishEvent extends Event
{
    // ShopEx EcShopX Service Component
    public $entities;

    public function __construct($eventData)
    {
        $this->entities = $eventData;
    }
}
