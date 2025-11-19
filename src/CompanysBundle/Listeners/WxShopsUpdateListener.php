<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Listeners;

use WechatBundle\Events\WxShopsUpdateEvent;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;

class WxShopsUpdateListener
{
    /**
     * Handle the event.
     *
     * @param  WxShopsUpdateEvent  $event
     * @return void
     */
    public function handle(WxShopsUpdateEvent $event)
    {
        $data = [
            'audit_id' => $event->audit_id,
            'errmsg' => $event->reason,
            'status' => $event->status,
        ];

        $shopsService = new ShopsService(new WxShopsService());
        return $shopsService->WxShopsUpdateEvent($data);
    }
}
