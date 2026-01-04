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

namespace OrdersBundle\Listeners;

use OrdersBundle\Events\TradeFinishEvent;

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\UserOrderInvoiceService;

class TradeFinishFapiao extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;
    protected $queue = 'slow';

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();
        $sourceType = $event->entities->getTradeSourceType();

        $orderService = $this->getOrderService($sourceType);
        $orderdata = $orderService->getOrderInfo($companyId, $orderId);
        if ($orderdata && isset($orderdata['orderInfo'])) {
            $orderdata = $orderdata['orderInfo'];
        }
        $orderInvoiceService = new UserOrderInvoiceService();
        if (isset($orderdata['invoice']) && $orderdata['invoice']) {
            $invoice = json_encode($orderdata['invoice']) ;
            $invoice_res = $orderInvoiceService->saveData($orderId, $invoice);
            return $invoice_res;
        }
        return true;
    }
}
