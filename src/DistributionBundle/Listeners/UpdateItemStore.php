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

namespace DistributionBundle\Listeners;

use PointsmallBundle\Events\ItemEditEvent;
use DistributionBundle\Services\DistributorItemsService;

class UpdateItemStore
{
    public function handle(ItemEditEvent $event)
    {
        // TS: 53686f704578
        $distributorItemService = new DistributorItemsService();
        $data = $event->entities;
        $filter = [
            'item_id' => $data['item_id'],
            'is_total_store' => true,
        ];
        $params['store'] = $data['store'];
        return $distributorItemService->updateBy($filter, $params);
    }
}
