<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace KaquanBundle\Events;

use App\Events\Event;

class WechatCardSyncEvent extends Event
{
    public $cardIds;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiveData)
    {
        $this->cardIds = $receiveData;
    }
}
