<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
