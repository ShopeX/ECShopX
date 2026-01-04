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

namespace PaymentBundle\Listeners;

use OrdersBundle\Events\NormalOrderAddEvent;
use OrdersBundle\Services\OfflinePaymentService;
use OrdersBundle\Services\Orders\NormalOrderService;

class OfflinePaymentCreate
{
    /**
     * Handle the event.
     *
     * @param  NormalOrderAddEvent  $event
     * @return boolean
     */
    public function handle(NormalOrderAddEvent $event)
    {
        $company_id = $event->entities['company_id'];
        $order_id = $event->entities['order_id'];
        $pay_type = $event->entities['pay_type'];
        if ($pay_type != 'offline_pay') {
            return true;
        }

        $offlinePaymentService = new OfflinePaymentService();
        $offlinePaymentService->create($company_id, $order_id);        
        
        return true;
    }
}
