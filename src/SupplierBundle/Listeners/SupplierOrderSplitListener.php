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

namespace SupplierBundle\Listeners;

use EspierBundle\Listeners\BaseListeners;
use OrdersBundle\Events\NormalOrderAddEvent;
use SupplierBundle\Services\SupplierOrderService;

class SupplierOrderSplitListener extends BaseListeners
{

    /**
     * Handle the event.
     *
     * @param  NormalOrderAddEvent  $event
     * @return boolean
     */
    public function handle(NormalOrderAddEvent $event)
    {
        // XXX: review this code
        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];
        $supplierOrderService = new SupplierOrderService();
        $supplierOrderService->orderSplit($companyId, $orderId);
        return true;
    }
    
}
