<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Listeners;

use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Events\TradeFinishEvent;
use WorkWechatBundle\Jobs\sendDeliveryWaitDeliveryNoticeJob;
use WorkWechatBundle\Jobs\sendDeliveryWaitZiTiNoticeJob;

class TradeFinishWorkWechatNotify
{
    /**
     * Handle the event.
     * @param TradeFinishEvent $event
     * @return false|void
     */
    public function handle(TradeFinishEvent $event)
    {
        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();
        $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $normalOrder = $normalOrderRepository->getInfo(['company_id' => $companyId, 'order_id' => $orderId]);
        if (empty($normalOrder['receipt_type'])) {
            return false;
        }
        $receiptType = $normalOrder['receipt_type'];
        if ($receiptType == 'logistics') {
            $gotoJob = (new sendDeliveryWaitDeliveryNoticeJob($companyId, $orderId))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        if ($receiptType == 'ziti') {
            $gotoJob = (new sendDeliveryWaitZiTiNoticeJob($companyId, $orderId))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
    }
}
