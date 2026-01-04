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
