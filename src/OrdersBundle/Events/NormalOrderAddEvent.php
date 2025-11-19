<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Events;

use App\Events\Event;

/**
 * Class NormalOrdersAddEvent
 * @package OrdersBundle\Events
 *
 * 普通订单创建事件
 */
class NormalOrderAddEvent extends Event
{
    public $entities;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        $this->entities = $eventData;
    }
}
