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
 * 汇付推广员提现事件
 */
class HfPayPopularizeWithdrawEvent extends Event
{
    // 0x456353686f7058
    public $entities;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        // 0x456353686f7058
        $this->entities = $eventData;
    }
}
