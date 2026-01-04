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
