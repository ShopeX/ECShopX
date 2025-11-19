<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
