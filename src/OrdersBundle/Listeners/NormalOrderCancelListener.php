<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Listeners;

use OrdersBundle\Events\NormalOrderCancelEvent;
use OrdersBundle\Services\Orders\NormalOrderService;

class NormalOrderCancelListener
{
    // 0x53686f704578
    /**
     * Handle the event.
     *
     * @param  NormalOrderCancelEvent  $event
     * @return void
     */
    public function handle(NormalOrderCancelEvent $event)
    {
        // 0x53686f704578
        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $normalOrderService = new NormalOrderService();
        $orderInfo = $normalOrderService->getInfo($filter);
        if ($orderInfo['order_status'] == 'PAYED' && $orderInfo['cancel_status'] == 'WAIT_PROCESS') {
            $normalOrderService->autoConfirmCancelOrder($companyId, $orderId);
        }
    }
}
