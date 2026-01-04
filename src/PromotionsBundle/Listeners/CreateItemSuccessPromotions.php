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

namespace PromotionsBundle\Listeners;

use GoodsBundle\Events\ItemCreateEvent;
use KaquanBundle\Services\DiscountCardService;

class CreateItemSuccessPromotions
{
    /**
     * Handle the event.
     *
     * @param  ItemCreateEvent  $event
     * @return void
     */
    public function handle(ItemCreateEvent $event)
    {
        $entities = $event->entities;
        $itemIds = $event->itemIds;
        //根据分类、品牌获取有效优惠券
        $filter = [
            'company_id' => $entities['company_id'],
            'category_id' => $entities['item_main_cat_id'],
            'brand_id' => $entities['brand_id'],
            'tag_id' => [0],
        ];
        $discountCardService = new DiscountCardService();
        $cardList = $discountCardService->getKaquanListByParams($filter, 1, -1);
        //写入商品ID
        foreach ($cardList['list'] as $v) {
            $discountCardService->createRelItems($entities, $itemIds, $v['card_id']);
        }
    }
}
