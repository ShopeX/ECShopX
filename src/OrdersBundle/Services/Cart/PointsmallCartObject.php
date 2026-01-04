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

namespace OrdersBundle\Services\Cart;

use Dingo\Api\Exception\ResourceException;
use PointsmallBundle\Services\ItemsService;
use OrdersBundle\Interfaces\CartInterface;

class PointsmallCartObject implements CartInterface
{
    public $shopType = 'pointsmall';

    public $invalidCart = [];

    public function getTotalCart($cartData)
    {
        $usedPromotions = [];
        $usedActivity = [];
        $usedActivityIds = [];
        $TotalDiscountFee = 0;

        $TotalDiscountFee = $itemNum = $itemTotalFee = $cartNum = 0;
        foreach ($cartData as $k => $cart) {
            if (isset($cart['is_checked']) && $cart['is_checked']) {
                $itemTotalFee += bcmul($cart['price'], $cart['num']);
                $itemNum += $cart['num'];
                $cartNum += 1;
                $TotalDiscountFee += $cart['discount_fee'];
            }
        }
        if ($TotalDiscountFee >= $itemTotalFee) {
            $totalPayFee = 0;
        } else {
            $totalPayFee = bcsub($itemTotalFee, $TotalDiscountFee);
        }
        $result = [
            'item_fee' => $itemTotalFee,
            'discount_fee' => $TotalDiscountFee,
            'total_fee' => $totalPayFee,
            'cart_total_num' => $itemNum,
            'cart_total_count' => $cartNum,
        ];
        $result['cart_list'] = $cartData;
        $result['used_activity'] = $usedActivity;
        $result['used_activity_ids'] = $usedActivityIds;
        $result['activity_grouping'] = $usedPromotions;
        $result['gift_activity'] = [];
        $result['plus_buy_activity'] = [];
        return $result;
    }

    public function getInvalidCart()
    {
        return $this->invalidCart;
    }

    /**
     * 组织加入购物车的数据类型 function
     *
     * @return array
     */
    public function formatAddCartData($params, $cartInfo)
    {
        // true 累增 false 覆盖
        $isAccumulate = true;
        if (isset($params['isAccumulate']) && ($params['isAccumulate'] === 'false' || !$params['isAccumulate'])) {
            $isAccumulate = false;
        }
        if ($cartInfo && $isAccumulate) {
            $params['num'] += $cartInfo['num'];
        }
        $itemsId = [$params['item_id']];
        $itemInfo = [];

        $itemService = new ItemsService();
        foreach ($itemsId as $itemId) {
            $itemInfo = $itemService->getItemsSkuDetail($itemId);
            if (!$itemInfo || ($itemInfo['company_id'] != $params['company_id']) || ($itemInfo['approve_status'] != 'onsale' && $itemInfo['approve_status'] != 'offline_sale')) {
                throw new ResourceException('无效商品');
            }

            if ($itemInfo && $itemInfo['special_type'] != 'drug' && ($params['shop_type'] ?? '') == 'drug') {
                throw new ResourceException('药品清单只支持处方药');
            }
            // 组合商品提前判断库存
            if ('package' == ($params['activity_type'] ?? '')) {
                $params['is_check_store'] = true;
                if ($itemInfo['store'] < $params['num']) {
                    throw new ResourceException('库存不足');
                }
            } else {
                $params['is_check_store'] = false;
            }
        }
        $params['item_name'] = $itemInfo['item_name'];
        $params['pics'] = $itemInfo['pics'] ? reset($itemInfo['pics']) : '';
        $params['price'] = $itemInfo['price'];
        $params['point'] = $itemInfo['point'];


        // 是否已经对库存进行了判断
        // 如果没有进行过自有活动的判断，那么则需要对商品本身的库存进行判断 // 总部发货总部有货也可以加入购物车
        $logisticsStore = 0;
        if ($params['isShopScreen'] ?? 0) {
            $logisticsStore = $itemInfo['logistics_store'] ?? 0;
        }
        if (!$params['is_check_store'] && ($itemInfo['store'] + $logisticsStore < $params['num'])) {
            throw new ResourceException('库存不足');
        }

        // 检查商品上下架
        $data = [
            'cart_id' => $cartInfo['cart_id'] ?? 0,
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'shop_type' => $params['shop_type'],
            'shop_id' => $params['shop_id'] ?? 0,
            'activity_type' => $params['activity_type'] ?? '',
            'activity_id' => $params['activity_id'] ?? 0,
            'item_id' => $params['item_id'],
            'items_id' => $params['items_id'] ?? [],
            'is_checked' => true,
            'item_name' => $params['item_name'],
            'pics' => $params['pics'],
            'num' => $params['num'],
            'price' => $params['price'],
            'point' => $params['point'],
            'wxa_appid' => $params['wxa_appid'] ?? '',
            'is_plus_buy' => $params['is_plus_buy'] ?? false,
            'isAccumulate' => false,
        ];
        return $data;
    }
}
