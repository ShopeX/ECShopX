<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Listeners\OrderProcess;

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Services\OrderProcessLogService;

class OrderProcessLogListener extends BaseListeners implements ShouldQueue
{
    protected $queue = 'slow';

    /**
     * Handle the event.
     *
     * @param  OrderProcessLogEvent  $event
     * @return void
     */
    public function handle(OrderProcessLogEvent $event)
    {
        // ShopEx EcShopX Core Module
        $data = [
            'order_id' => $event->entities['order_id'],
            'company_id' => $event->entities['company_id'],
            'supplier_id' => $event->entities['supplier_id'] ?? 0,
            'operator_type' => $event->entities['operator_type'],
            'operator_id' => $event->entities['operator_id'] ?? 0,
            'remarks' => $event->entities['remarks'],
            'detail' => $event->entities['detail'] ?? '',
            'is_show' => $event->entities['is_show'] ?? false,
            'delivery_remark' => $event->entities['delivery_remark'] ?? '',
            'params' => $event->entities['params'] ?? [],
            'pics' => $event->entities['pics'] ?? [],
        ];
        $orderProcessLogService = new OrderProcessLogService();
        $orderProcessLogService->createOrderProcessLog($data);
        return true;
    }
}
