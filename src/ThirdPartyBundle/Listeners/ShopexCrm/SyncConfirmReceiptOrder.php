<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
