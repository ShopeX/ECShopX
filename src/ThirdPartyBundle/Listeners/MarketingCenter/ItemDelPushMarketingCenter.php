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

namespace ThirdPartyBundle\Listeners\MarketingCenter;

use GoodsBundle\Events\ItemDeleteEvent;
use ThirdPartyBundle\Services\MarketingCenter\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;

class ItemDelPushMarketingCenter
{
    use GetOrderServiceTrait;
    /**
     * Handle the event.
     *
     * @param ItemDeleteEvent $event
     * @return void
     */
    public function handle(ItemDeleteEvent $event)
    {
        $company_id = $event->entities['company_id'];
        $input['del_ids'] = $event->entities['del_ids'];
        $input['item_bn'] = $event->entities['item_info']['item_bn'];
        $params[0] = $input;
        $request = new Request();
        $request->call($company_id, 'basics.item.proccess', $params);
    }
}
