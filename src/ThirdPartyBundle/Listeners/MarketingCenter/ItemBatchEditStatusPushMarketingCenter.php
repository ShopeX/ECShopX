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

use GoodsBundle\Entities\Items;
use GoodsBundle\Events\ItemBatchEditStatusEvent;

use ThirdPartyBundle\Services\MarketingCenter\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;

class ItemBatchEditStatusPushMarketingCenter
{
    use GetOrderServiceTrait;
    /**
     * Handle the event.
     *
     * @param ItemBatchEditStatusEvent $event
     * @return void
     */
    public function handle(ItemBatchEditStatusEvent $event)
    {
        $company_id = $event->entities['company_id'];
        $goods_id = $event->entities['goods_id'];
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $itemInfo = $itemsRepository->list(['company_id' => $company_id, 'goods_id' => $goods_id]);

        foreach ($itemInfo['list'] as $key => $value) {
            $input['item_bn'] = $value['item_bn'];
            $input['approve_status'] = $value['approve_status'];

            $params[$key] = $input;
        }

        $request = new Request();
        $request->call($company_id, 'basics.item.proccess', $params);
    }
}
