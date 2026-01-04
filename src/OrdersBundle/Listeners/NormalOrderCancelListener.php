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
