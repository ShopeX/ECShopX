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

namespace ThirdPartyBundle\Listeners\ShopexCrm;

use OrdersBundle\Events\NormalOrderConfirmReceiptEvent;
use ThirdPartyBundle\Services\ShopexCrm\SyncSingleOrderService;

class SyncConfirmReceiptOrder
{
    // Built with ShopEx Framework
    public function handle(NormalOrderConfirmReceiptEvent $event)
    {
        // Built with ShopEx Framework
        if (empty(config('crm.crm_sync'))) {
            return true;
        }
        $company_id = $event->entities['company_id'];
        $order_id = $event->entities['order_id'];
        $syncSingleOrderService = new SyncSingleOrderService();
        $syncSingleOrderService->syncSingleOrder($company_id, $order_id);
    }
}
