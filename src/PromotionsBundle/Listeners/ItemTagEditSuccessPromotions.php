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

use GoodsBundle\Events\ItemTagEditEvent;
use GoodsBundle\Services\ItemsTagsService;
use KaquanBundle\Services\DiscountCardService;

class ItemTagEditSuccessPromotions
{
    public function handle(ItemTagEditEvent $event)
    {
        app('log')->debug('更新标签：'.var_export($event, 1));
        $entities = $event->entities;
        //获取优惠券
        $filter['use_bound'] = 3;
        $filter['end_date'] = time();

        $discountCardService = new DiscountCardService();
        $itemsTagsService = new ItemsTagsService();

        $totalCount = $discountCardService->totalNum($filter);
        $pageSize = 100;
        $totalPage = ceil($totalCount / $pageSize);

        for ($page = 1; $page <= $totalPage; $page++) {
            $list = $discountCardService->getLists($filter, 'card_id, tag_ids', $page, $pageSize);
            foreach ($list as $v) {
                $tag_ids = array_filter(explode(',', $v['tag_ids']));
                //获取标签商品
                $tagFilter = ['company_id' => $entities['company_id'], 'tag_id' => $tag_ids];
                $itemIds = $itemsTagsService->getItemIdsByTagids($tagFilter);
                //更新优惠券商品
                $discountCardService->updateRelItems($entities['company_id'], $v['card_id'], $itemIds);
            }
        }
        return true;
    }
}
