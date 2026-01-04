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

use GoodsBundle\Entities\ItemRelAttributes;
use GoodsBundle\Entities\Items;
use GoodsBundle\Events\ItemAddEvent;
use ThirdPartyBundle\Services\MarketingCenter\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;

class ItemAddPushMarketingCenter
{
    use GetOrderServiceTrait;
    /**
     * Handle the event.
     *
     * @param ItemAddEvent $event
     * @return void
     */
    public function handle(ItemAddEvent $event)
    {
        $company_id = $event->entities['company_id'];
        $item_id = $event->entities['item_id'];
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $itemInfo = $itemsRepository->list(['company_id' => $company_id, 'item_id' => $item_id]);

        $key = 0;
        $input['item_name'] = $itemInfo['list'][$key]['item_name'];
        $input['item_price'] = $itemInfo['list'][$key]['price'];
        $input['pic'] = $itemInfo['list'][$key]['pics'][0] ?? '';

        $itemRelAttributespository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
        $itemRelAttInfo = $itemRelAttributespository->lists(['company_id' => $company_id, 'item_id' => $itemInfo['list'][$key]['item_id']]);
        $sku = '';
        foreach ($itemRelAttInfo['list'] as $value) {
            $sku .= $value['custom_attribute_value'].' ';
        }

        $input['sku'] = $sku;
        $input['goods_bn'] = $itemInfo['list'][$key]['item_id'];
        $input['item_bn'] = $itemInfo['list'][$key]['item_bn'];
        $input['approve_status'] = $itemInfo['list'][$key]['approve_status'];

        foreach ($input as &$value) {
            if (is_int($value)) {
                $value = strval($value);
            }
            if (is_null($value)) {
                $value = '';
            }
            if (is_array($value) && empty($value)) {
                $value = '';
            }
        }
        $params[0] = $input;
        $request = new Request();
        $request->call($company_id, 'basics.item.proccess', $params);
    }
}
