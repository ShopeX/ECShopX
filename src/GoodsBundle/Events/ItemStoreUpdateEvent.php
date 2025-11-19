<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Events;

use App\Events\Event;

class ItemStoreUpdateEvent extends Event
{
    public $item_id;
    public $store;
    public $distributor_id;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($item_id, $store, $distributor_id)
    {
        $this->item_id = $item_id;
        $this->store = $store;
        $this->distributor_id = $distributor_id;
    }
}
