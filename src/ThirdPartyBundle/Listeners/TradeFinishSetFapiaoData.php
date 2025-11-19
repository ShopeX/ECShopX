<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
