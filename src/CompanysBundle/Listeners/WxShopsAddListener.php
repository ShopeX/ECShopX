<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Listeners;

use WechatBundle\Events\WxShopsAddEvent;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;

class WxShopsAddListener
{
    /**
     * Handle the event.
     *
     * @param  WxShopsAddEvent  $event
     * @return void
     */
    public function handle(WxShopsAddEvent $event)
    {
        $data = [
            'audit_id' => $event->audit_id,
            'status' => $event->status,
            'errmsg' => $event->reason,
            'is_upgrade' => $event->is_upgrade,
            'poi_id' => $event->poiid,
        ];

        $shopsService = new ShopsService(new WxShopsService());
        return $shopsService->WxShopsAddEvent($data);
    }
}
