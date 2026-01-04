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

namespace PromotionsBundle\Services;

use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsTagsService;
use PromotionsBundle\Entities\PromotionsItemsTag;

class PromotionItemTagService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(PromotionsItemsTag::class);
    }

    /**
     * 根据 goods_id 获取符合条件的促销规则
     *
     * @param array $filterItems
     * @param int $companyId
     * @param array $orderBy ['activity_price' => 'desc']
     */
    public function getPromotions($filterItems = [], $companyId = 0, $orderBy = [])
    {
        //判断需要的字段是否存在，如果不存在，需要重新查询商品表
        $hasKeyFields = false;
        foreach ($filterItems as $v) {
            if (isset($v['brand_id']) && isset($v['item_main_cat_id']) && isset($v['default_item_id'])) {
                $hasKeyFields = true;
            }
            break;
        }

        $itemIds = array_column($filterItems, 'item_id');
        if (!$itemIds) {
            return false;
        }

        //商品id转换成主商品ID
        if ($hasKeyFields) {
            $items = $filterItems;
        } else {
            $itemsService = new ItemsService();
            $items = $itemsService->getItems($itemIds, $companyId);
            if (!$items) {
                return false;
            }
        }

        $itemFilter['default_item_id'] = [];
        $itemFilter['item_main_cat_id'] = [];
        $itemFilter['brand_id'] = [];
        //用来统计每个活动对应的商品
        foreach ($items as $v) {
            $defaultItemId = 0;
            $mainCatId = 0;
            $brandID = 0;
            if ($v['default_item_id'] ?? 0) {
                $defaultItemId = $v['default_item_id'];
            }
            if ($v['item_main_cat_id'] ?? 0) {
                $mainCatId = $v['item_main_cat_id'];
            }
            if ($v['brand_id'] ?? 0) {
                $brandID = $v['brand_id'];
            }

            $itemFilter['default_item_id'][$defaultItemId] = $defaultItemId;
            $itemFilter['item_main_cat_id'][$mainCatId] = $mainCatId;
            $itemFilter['brand_id'][$brandID] = $brandID;

            $itemInfo['normal'][$v['item_id']][] = $v['goods_id'];
            $itemInfo['default_item'][$defaultItemId][] = $v['goods_id'];
            $itemInfo['category'][$mainCatId][] = $v['goods_id'];
            $itemInfo['brand'][$brandID][] = $v['goods_id'];
        }

        //获取商品的标签
        $itemFilter['tag_ids'] = [];
        $tagFilter = [
            'item_id' => $itemFilter['default_item_id'],//商品标签只关联到主商品
            'company_id' => $companyId,
        ];
        $itemsTagsService = new ItemsTagsService();
        $tagList = $itemsTagsService->getItemsByTagidsLimit($tagFilter, 1, -1);
        if ($tagList) {
            $itemFilter['tag_ids'] = array_unique(array_column($tagList['list'], 'tag_id'));
            //用来统计每个活动标签对应的商品
            foreach ($tagList['list'] as $v) {
                if (!isset($itemInfo['default_item'][$v['item_id']])) {
                    continue;
                }
                foreach ($itemInfo['default_item'][$v['item_id']] as $item_id) {
                    $itemInfo['tag'][$v['tag_id']][] = $item_id;//主ID转换成Sku id
                }
            }
        }

        //指定商品查询
        if ($itemIds) {
            $filters[] = [
                'item_id' => $itemIds,
                'item_type' => 'normal',
            ];
        }

        //根据标签查询
        if ($itemFilter['tag_ids']) {
            $filters[] = [
                'item_id' => $itemFilter['tag_ids'],
                'item_type' => 'tag',
            ];
        }

        //根据品牌查询
        if ($itemFilter['brand_id']) {
            $filters[] = [
                'item_id' => $itemFilter['brand_id'],
                'item_type' => 'brand',
            ];
        }

        //根据主类目查询
        if ($itemFilter['item_main_cat_id']) {
            $filters[] = [
                'item_id' => $itemFilter['item_main_cat_id'],
                'item_type' => 'category',
            ];
        }

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('*')->from('promotions_items_tag');
        //全部商品可用
        $criteria = $criteria->orWhere(
            $criteria->expr()->andX(
                $criteria->expr()->eq('is_all_items', 1),
                $criteria->expr()->eq('company_id', $companyId),
                $criteria->expr()->lte('start_time', time()),
                $criteria->expr()->gte('end_time', time())
            )
        );
        foreach ($filters as $filter) {
            $criteria = $criteria->orWhere(
                $criteria->expr()->andX(
                    $criteria->expr()->in('item_id', $filter['item_id']),
                    $criteria->expr()->eq('item_type', $criteria->expr()->literal($filter['item_type'])),
                    $criteria->expr()->eq('company_id', $companyId),
                    $criteria->expr()->lte('start_time', time()),
                    $criteria->expr()->gte('end_time', time())
                )
            );
        }
        if ($orderBy) {
            foreach ($orderBy as $k => $v) {
                $criteria->addOrderBy($k, $v);
            }
        }
        $promotions = $criteria->execute()->fetchAll();

        $list = [];
        foreach ($promotions as $v) {
            if ($v['is_all_items'] == '1') {
                $list[] = $v;
                continue;
            }
            //处理可能出现的异常数据
            if (!isset($itemInfo[$v['item_type']]) or !isset($itemInfo[$v['item_type']][$v['item_id']])) {
                continue;
            }
            foreach ($itemInfo[$v['item_type']][$v['item_id']] as $goodsId) {
                $v['goods_id'] = $goodsId;
                $list[] = $v;
            }
        }

        return $list;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
