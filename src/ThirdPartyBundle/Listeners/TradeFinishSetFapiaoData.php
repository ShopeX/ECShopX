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

namespace ThirdPartyBundle\Listeners;

use OrdersBundle\Events\TradeFinishEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

use OrdersBundle\Services\UserOrderInvoiceService;

use OrdersBundle\Traits\GetOrderServiceTrait;

class TradeFinishSetFapiaoData extends BaseListeners implements ShouldQueue
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
        //第三方发票处理
        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();
        $sourceType = $event->entities->getOrderType();
        app('log')->debug('订单号为' . $orderId . '统计开始');

        $orderService = $this->getOrderService($sourceType);
        $orderdata = $orderService->getOrderInfo($companyId, $orderId);
        if ($orderdata && isset($orderdata['orderInfo'])) {
            $orderdata = $orderdata['orderInfo'];
        }

        // 插入发票数据
        $orderInvoiceService = new UserOrderInvoiceService();
        if (isset($orderdata['invoice']) && $orderdata['invoice']) {
            $invoice = $orderdata['invoice'];
            $invoice_res = $orderInvoiceService->saveData($orderId, $invoice);
        }
    }
}
