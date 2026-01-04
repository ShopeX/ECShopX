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

namespace OrdersBundle\Services\Orders;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\CartService;
use OrdersBundle\Traits\SeckillStoreTicket;

class DrugNormalOrderService extends AbstractNormalOrder
{
    use SeckillStoreTicket;

    // 订单种类
    public $orderClass = 'drug';

    // 订单类型 实体类订单 服务类订单 等其他订单
    public $orderType = 'normal';

    // 订单是否支持优惠券优惠
    public $isSupportCouponDiscount = true;

    // 订单是否需要进行门店验证
    public $isCheckShopValid = true;

    // 积分兑换
    public $isCheckPoint = true;

    // 订单是否需要进行店铺验证
    public $isCheckDistributorValid = true;

    public $isSupportCart = true;

    // 订单是否需要验证白名单
    public $isCheckWhitelistValid = true;

    //商品使用的商品促销id集合
    public $usedActivity = [];

    //有效的商品促销列表
    public $validPromotion = [];

    public $TotalFee = [];

    public $TotalDiscountFee = [];

    public $orderItemPrcie = [];

    //订单参与活动的详情
    public $joinActvityInfo = [];

    public $validLimitedTimeSaleAct = [];

    public $itemCart = [];


    public function checkoutCartItems($params)
    {
        $params['order_third_params'] = [
            'drug_buyer_name' => $params['drug_buyer_name'] ?? '',
            'drug_buyer_id_card' => $params['drug_buyer_id_card'] ?? '',
            'drug_list_image' => $params['drug_list_image'] ?? '',
        ];

        $cartDataService = $this->getCartTypeService('distributor');
        $cartType = 'cart';
        $shopId = $params['distributor_id'] ?? 0;
        $cartService = new CartService();
        $cartData = $cartService->getCartList($params['company_id'], $params['user_id'], $shopId, $cartType, 'drug', true, $params['pay_type']);
        $cartlist = reset($cartData['valid_cart']);
        if (!$cartlist) {
            throw new ResourceException(trans('OrdersBundle/Order.cart_empty'));
        }

        //单笔订单有效应用的活动id集合
        $this->usedActivity = $cartlist['used_activity_ids'] ?? [];
        $userId = $params['user_id'];
        $params['items'] = [];
        foreach ($cartlist['list'] as $cart) {
            if ($cart['is_checked']) {
                $params['items'][] = [   //订单中的商品数据
                    'item_id' => $cart['item_id'],
                    'num' => $cart['num'],
                ];

                $this->itemCart[$cart['item_id']] = $cart; //根据商品取购物车数据
                $this->orderItemPrcie[$cart['item_id']] = $cart['price'];
                if (in_array($cart['activity_id'], $this->usedActivity) && isset($cart['activity_info']) && $cart['activity_info']) {
                    $this->joinActvityInfo[$userId][$cart['activity_id']] = $cart['activity_info'];
                }
                if ($cart['promotions'] ?? []) {
                    foreach ($cart['promotions'] as $promotion) {
                        $this->validPromotion[$promotion['marketing_id']] = $promotion; //有效的促销活动列表
                    }
                }
                if (isset($cart['limitedTimeSaleAct'])) {
                    $this->validLimitedTimeSaleAct[$cart['item_id']] = $cart['limitedTimeSaleAct'];
                }
            }
        }
        $this->TotalFee[$userId] = $cartlist['total_fee'];
        $this->TotalDiscountFee[$userId] = $cartlist['discount_fee'] ?? 0;

        return $params;
    }

    public function getOrderItemPrice($itemId)
    {
        return $this->orderItemPrcie[$itemId];
    }

    public function getOrderItemPromotion($orderData)
    {
        $userId = $orderData['user_id'];
        if (!($orderData['items'] ?? [])) {
            throw new ResourceException(trans('OrdersBundle/Order.item_data_error'));
        }
        $giftItemArr = [];
        foreach ($orderData['items'] as $key => $orderitem) {
            $itemId = $orderitem['item_id'];
            $cart = $this->itemCart[$itemId] ?? [];
            if (!$cart) {
                throw new ResourceException(trans('OrdersBundle/Order.prescription_drugs_changed'));
            }
            $orderData['items'][$key]['total_fee'] -= (int)($cart['discount_fee'] ?? 0);
            $orderData['items'][$key]['discount_fee'] = (int)($cart['discount_fee'] ?? 0);

            $activityId = $cart['activity_id'] ?? 0;
            if (in_array($activityId, $this->usedActivity)) {
                $orderData['items'][$key]['discount_info'][$activityId] = $cart['activity_info'] ?? null;
                $activity = $this->validPromotion[$activityId] ?? [];
                if ($activity) {
                    $activity['activity_id'] = $activityId;
                    $orderData = $this->__preItemsPromotion($orderData, $orderitem, $userId, $itemId, $activity, $cart);
                }
                if ($activity['marketing_type'] == 'full_gift' && ($activity['gifts'] ?? [])) {
                    $giftItemArr[$activityId] = $activity['gifts'];
                }
            }

            if ($this->validLimitedTimeSaleAct[$itemId] ?? []) {
                $activity = $this->validLimitedTimeSaleAct[$itemId];
                $orderData = $this->__preItemsPromotion($orderData, $orderitem, $userId, $itemId, $activity, $cart);
            }
        }

        if ($giftItemArr) {
            $orderGiftItemArr = $this->getGiftItemArr($giftItemArr, $orderData);
            foreach ($orderGiftItemArr as $orderItem) {
                array_push($orderData['items'], $orderItem);
                $orderData['totalItemNum'] += $orderItem['num'];
            }
        }
        $orderData['discount_fee'] = $this->TotalDiscountFee[$userId];
        $orderData['goods_discount'] = $this->TotalDiscountFee[$userId];
        $orderData['discount_info'] = $this->joinActvityInfo[$userId] ?? null;
        $orderData['total_fee'] = $this->TotalFee[$userId];
        return $orderData;
    }

    private function __preItemsPromotion($orderData, $orderitem, $userId, $itemId, $activity, $cart)
    {
        $orderData['items_promotion'][] = [
            'company_id' => $orderitem['company_id'],
            'user_id' => $userId,
            'shop_id' => $orderitem['distributor_id'] ?? 0,
            'item_id' => $itemId,
            'item_name' => $orderitem['item_name'],
            'item_type' => 'normal',
            'order_type' => 'normal',
            'activity_id' => $activity['activity_id'],
            'activity_type' => $activity['marketing_type'],
            'activity_name' => $activity['marketing_name'],
            'activity_tag' => $activity['promotion_tag'],
            'activity_desc' => $cart['activity_info'] ?? [],
        ];
        return $orderData;
    }

    private function getGiftItemArr($gifts, $orderData)
    {
        $giftItemList = [];
        foreach ($gifts as $giftItemData) {
            $giftItemList = array_merge($giftItemList, $giftItemData);
        }
        unset($gifts);
        $result = [];
        foreach ($giftItemList as $itemInfo) {
            $gift = $itemInfo['gift'];
            $giftNum[$gift['item_id']] = $gift['gift_num'];
            $result[] = [
                'order_id' => $orderData['order_id'],
                'item_id' => $gift['item_id'],
                'item_bn' => $itemInfo['itemBn'],
                'company_id' => $orderData['company_id'],
                'user_id' => $orderData['user_id'],
                'item_name' => $itemInfo['itemName'],
                'templates_id' => $itemInfo['templates_id'] ?: 0,
                'pic' => isset($itemInfo['pics'][0]) ? $itemInfo['pics'][0] : '',
                'num' => $giftNum[$gift['item_id']], // 购买数量
                'price' => 0, // 单价
                'discount_fee' => 0, // 单价
                'item_fee' => 0, // 商品总金额
                'cost_fee' => 0, // 商品总金额
                'item_unit' => $itemInfo['item_unit'],
                'total_fee' => 0, // 商品总金额
                'rebate' => 0, // 单个商品店奖金金额
                'total_rebate' => 0, // 商品总店铺奖金金额
                'distributor_id' => $orderData['distributor_id'] ?? 0,
                'mobile' => $orderData['mobile'] ?? '',
                'is_total_store' => $itemInfo['is_total_store'] ?? true,
                'shop_id' => $orderData['shop_id'] ?? 0,
                'fee_rate' => $orderData['fee_rate'] ?? '',
                'fee_type' => $orderData['fee_type'] ?? '',
                'fee_symbol' => $orderData['fee_symbol'] ?? '',
                'order_item_type' => 'gift',
                'item_spec_desc' => $itemInfo['item_spec_desc'] ?? '',
                'volume' => 0,
                'cost_price' => $itemInfo['cost_price'] ?? 0,
                'market_price' => $itemInfo['market_price'] ?? 0,
            ];
        }
        return $result;
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->normalOrdersRepository->$method(...$parameters);
    }
}
