<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PaymentBundle\Listeners;

use OrdersBundle\Events\NormalOrderAddEvent;
use OrdersBundle\Services\OfflinePaymentService;
use OrdersBundle\Services\Orders\NormalOrderService;

class OfflinePaymentCreate
{
    /**
     * Handle the event.
     *
     * @param  NormalOrderAddEvent  $event
     * @return boolean
     */
    public function handle(NormalOrderAddEvent $event)
    {
        $company_id = $event->entities['company_id'];
        $order_id = $event->entities['order_id'];
        $pay_type = $event->entities['pay_type'];
        if ($pay_type != 'offline_pay') {
            return true;
        }

        $offlinePaymentService = new OfflinePaymentService();
        $offlinePaymentService->create($company_id, $order_id);        
        
        return true;
    }
}
