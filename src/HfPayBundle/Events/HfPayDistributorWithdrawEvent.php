<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace HfPayBundle\Events;

use App\Events\Event;

/**
 * Class HfPayCashEvent
 * @package HfPayBundle\Events
 *
 * 汇付店铺提现事件
 */
class HfPayDistributorWithdrawEvent extends Event
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
