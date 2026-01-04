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
