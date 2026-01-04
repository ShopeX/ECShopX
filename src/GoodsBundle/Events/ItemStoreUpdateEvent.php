<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
