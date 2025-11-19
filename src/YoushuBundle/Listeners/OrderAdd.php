<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Listeners;

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use OrdersBundle\Events\NormalOrderAddEvent;
use YoushuBundle\Services\SrDataService;

class OrderAdd extends BaseListeners implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  NormalOrderAddEvent $event
     * @return boolean
     */
    public function handle(NormalOrderAddEvent $event)
    {
        $company_id = $event->entities['company_id'];
        $order_id = $event->entities['order_id'];
        $params = [
            'company_id' => $company_id,
            'object_id' => $order_id,
        ];

        $srdata_service = new SrDataService($company_id);
        $srdata_service->sync($params, 'order');

        return true;
    }
}
