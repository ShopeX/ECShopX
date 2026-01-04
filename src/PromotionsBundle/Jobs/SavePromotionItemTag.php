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

namespace PromotionsBundle\Jobs;

use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\PromotionItemTagService;
use GoodsBundle\Services\ItemsService;

class SavePromotionItemTag extends Job
{
    protected $params = [];
    protected $itemsIds = [];
    protected $activityPrice = [];

    /**
        * @brief
        *
        * @param $companyId
        * @param $promotionId
        * @param $tagType
        * @param $beginTime
        * @param $endTime
        * @param $Items
        *
        * @return void
     */
    public function __construct($companyId, $promotionId, $tagType, $beginTime, $endTime, $Items = [], $activityPrice = 0)
    {
        $this->params = [
            'company_id' => $companyId,
            'promotion_id' => $promotionId,
            'tag_type' => $tagType,
            'start_time' => $beginTime,
            'end_time' => $endTime,
        ];
        $this->activityPrice = $activityPrice;
        $this->itemsIds = $Items;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle()
    {
        // ShopEx EcShopX Service Component
        app('log')->debug('SavePromotionItemTag ==> ');
        try {
            $params = $this->params;
            $promotionItemTagService = new PromotionItemTagService();
            $promotionItemTagService->deleteBy(['promotion_id' => $params['promotion_id'],'tag_type' => $params['tag_type']]);
            app('log')->debug('$this->'.json_encode($this->itemsIds, 256));

            if (isset($this->itemsIds['item_type'])) {
                $itemsIds = $this->itemsIds['item_ids'];
                $itemType = $this->itemsIds['item_type'];
            } else {
                $itemsIds = $this->itemsIds;
                $itemType = 'normal';
            }

            if ($itemsIds) {
                $activityPrice = $this->activityPrice;
                $itemList = $this->getItemList($itemsIds, $itemType);//获取商品明细
                app('log')->debug(json_encode($itemList, 256));
                foreach ($itemList as $value) {
                    $params['item_id'] = $value['item_id'];
                    $params['goods_id'] = $value['goods_id'];
                    $params['item_type'] = $value['item_type'];
                    $params['is_all_items'] = 2;
                    $params['activity_price'] = $activityPrice[$value['item_id']] ?? 0;
                    $promotionItemTagService->create($params);
                }
            } else {
                $params['is_all_items'] = 1;
                $promotionItemTagService->create($params);
            }
            return true;
        } catch (\Exception $e) {
            app('log')->debug('记录商品促销标签出错'.$e->getMessage());
        }
        return true;
    }

    private function getItemList($itemIds = [], $itemType = 'normal')
    {
        $filter = [];
        $itemList = [];
        $filter['item_id'] = $itemIds;

        if (in_array($itemType, ['tag','category','brand'])) {
            //模拟商品的数据结构
            foreach ($filter['item_id'] as $item_id) {
                $itemList[] = [
                    'item_id' => $item_id,
                    'goods_id' => 0,
                    'item_type' => $itemType,
                ];
            }
        } else {
            $itemsService = new ItemsService();
            $itemList = $itemsService->getItemsLists($filter, 'item_id,goods_id,item_type');
        }

        return $itemList;
    }
}
