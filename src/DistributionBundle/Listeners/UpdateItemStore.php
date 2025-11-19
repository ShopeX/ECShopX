<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
