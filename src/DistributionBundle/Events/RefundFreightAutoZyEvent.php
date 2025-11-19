<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DistributionBundle\Events;


use App\Events\Event;

class RefundFreightAutoZyEvent extends Event
{
    public $entities;


    public function __construct($eventData)
    {
        $this->entities = $eventData;
    }

}
