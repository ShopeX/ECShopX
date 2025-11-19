<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Listeners;

use EspierBundle\Listeners\BaseListeners;
use GoodsBundle\Events\ItemCategoryAddEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use YoushuBundle\Services\SrDataService;

class ItemCategory extends BaseListeners implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  ItemCategoryAddEvent $event
     * @return boolean
     */
    public function handle(ItemCategoryAddEvent $event)
    {
        $company_id = $event->entities['company_id'];
        $params = [
            'company_id' => $company_id
        ];
        $srdata_service = new SrDataService($company_id);
        $srdata_service->sync($params, 'category');

        return true;
    }
}
