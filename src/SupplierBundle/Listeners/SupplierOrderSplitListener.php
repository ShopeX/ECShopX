<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SupplierBundle\Listeners;

use EspierBundle\Listeners\BaseListeners;
use OrdersBundle\Events\NormalOrderAddEvent;
use SupplierBundle\Services\SupplierOrderService;

class SupplierOrderSplitListener extends BaseListeners
{

    /**
     * Handle the event.
     *
     * @param  NormalOrderAddEvent  $event
     * @return boolean
     */
    public function handle(NormalOrderAddEvent $event)
    {
        // XXX: review this code
        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];
        $supplierOrderService = new SupplierOrderService();
        $supplierOrderService->orderSplit($companyId, $orderId);
        return true;
    }
    
}
