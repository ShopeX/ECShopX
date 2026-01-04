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

namespace OpenapiBundle\Traits\Distributor;

use GoodsBundle\Services\ItemsService;
use OpenapiBundle\Services\Distributor\DistributorItemService;

trait DistributorItemTrait
{
    /**
     * 替换数据
     * @param array $list
     */
    protected function handleDataToList(array &$list, array $filter, array $distributorInfo)
    {
        $distributorItemService = new DistributorItemService();

        // 获取列表中的商品id
        $itemIds = (array)array_column($list, "item_id");
        // 设置店铺商品的列表数据
        $distributorItemList = [];
        // 用于判断商品的否上下架状态
        $itemGoodsCanSaleArray = [];
        if (!empty($itemIds)) {
            // 根据商品id与店铺信息，查询店铺商品
            $distributorItemFilter = [
                "company_id" => $distributorInfo["company_id"],
                "distributor_id" => $distributorInfo["distributor_id"],
                "default_item_id" => $itemIds
            ];
            $distributorItemResult = $distributorItemService->list($distributorItemFilter, 1, -1, [], "*", false);

            // 数据整理
            foreach ($distributorItemResult["list"] as $distributorItem) {
                // 获取spu id
                $defaultItemId = (int)$distributorItem["default_item_id"];
                // 获取自身的sku id
                $itemId = (int)$distributorItem["item_id"];
                // 获取上下架状态
                $goodsCanSale = (int)$distributorItem["goods_can_sale"];
                // 整理数据
                $distributorItemList[$defaultItemId][$itemId] = $distributorItem;
                // 如果是上架状态，则放入数组中
                if ($goodsCanSale) {
                    $itemGoodsCanSaleArray[$defaultItemId] = $goodsCanSale;
                }
            }
        }

        foreach ($list as &$item) {
            $item = [
                "distributor_id" => (int)$distributorInfo["distributor_id"],
                "item_id" => (int)($item["item_id"] ?? 0),
                "item_code" => (string)($item["item_bn"] ?? ""),
                "item_name" => (string)($item["item_name"] ?? ""),
                "store" => (int)($item["store"] ?? 0),
                "price" => (int)($item["price"] ?? 0),
                // "is_can_sale"    =>  0,
                "goods_can_sale" => 0,
                "is_total_store" => 1,
                "status" => (string)($item["approve_status"] ?? ""),
            ];
            // 如果该商品是店铺商品
            if (isset($distributorItemList[$item["item_id"]])) {
                // 设置店铺商品是否上下架
                // $item["is_can_sale"] = (int)$distributorItemList[$item["item_id"]]["is_can_sale"];
                // 设置店铺商品是否上下架
                $item["goods_can_sale"] = (int)($itemGoodsCanSaleArray[$item["item_id"]] ?? DistributorItemService::IS_FALSE);
                // 设置商品是否总部发货
                $item["is_total_store"] = (int)$distributorItemList[$item["item_id"]][$item["item_id"]]["is_total_store"];
                // 如果不从总部发货，则将商品的价格和库存改为店铺中的信息
                if ($item["is_total_store"] == 0) {
                    $item["store"] = (int)$distributorItemList[$item["item_id"]][$item["item_id"]]["store"];
                    $item["price"] = (int)$distributorItemList[$item["item_id"]][$item["item_id"]]["price"];
                }
            }
            // 价格单位从分变为元
            $item["price"] = (string)bcdiv((string)$item["price"], "100", 2);
        }
    }
}
