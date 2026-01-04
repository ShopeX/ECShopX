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

namespace OrdersBundle\Services;

use Dingo\Api\Exception\ResourceException;

use OrdersBundle\Entities\Cart;
use GoodsBundle\Services\ItemsService;

use OrdersBundle\Interfaces\CartInterface;

class CartDataService
{
    /** @var CartInterface */
    public $cartInterface;
    public $shopData;
    public $shopId;
    public $entityRepository;

    /**
     * KaquanService
     */
    public function __construct(CartInterface $cartInterface)
    {
        $this->cartInterface = $cartInterface;
    }

    public function getCartList($companyId, $userId, $shopId = 0, $cartType = 'cart')
    {
        // This module is part of ShopEx EcShopX system
        $result = ['invalid_cart' => [], 'valid_cart' => []];
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Cart::class);
        $filter = [
            'company_id' => $companyId,
            'user_id' => $userId,
            'shop_type' => $this->cartInterface->shopType,
        ];
        $filter['shop_id'] = $shopId;
        if ($cartType == 'cart') {
            $cartList = $this->entityRepository->lists($filter)['list'];
            if (!$cartList) {
                return $result;
            }
        } elseif ($cartType == 'fastbuy') {
            $list = $this->getFastBuyCart($companyId, $userId);
            if (!$list) {
                return $result;
            }
            $cartList = [$list];
        }
        //获取购物车商品详细信息
        $itemIds = array_unique(array_column($cartList, 'item_id'));
        $itemService = new ItemsService();
        $filter = [
            'company_id' => $companyId,
            'item_id' => $itemIds
        ];
        $itemlist = $itemService->getSkuItemsList($filter, 1, 100)['list'];
        $itemlist = array_column($itemlist, null, 'item_id');

        $validCart = []; //购物车有效的商品
        $invalidCart = []; //购物车失效商品
        foreach ($cartList as $k => $cartdata) {
            $itemId = $cartdata['item_id'];
            if (!isset($itemlist[$itemId])) {
                $invalidCart[] = $cartdata;
                continue;
            }
            if ($itemlist[$itemId]['approve_status'] != 'onsale') {
                $invalidCart[] = $cartdata;
                continue;
            }
            $cartdata['price'] = $itemlist[$itemId]['price'];
            $cartdata['discount_fee'] = 0;
            $cartdata['total_fee'] = $itemlist[$itemId]['price'] * $cartdata['num'];
            $cartdata['store'] = $itemlist[$itemId]['store'];
            $cartdata['market_price'] = $itemlist[$itemId]['market_price'];
            $cartdata['brief'] = $itemlist[$itemId]['brief'];
            $cartdata['item_type'] = $itemlist[$itemId]['item_type'];
            $cartdata['approve_status'] = $itemlist[$itemId]['approve_status'];
            $cartdata['item_name'] = $itemlist[$itemId]['itemName'];
            $cartdata['pics'] = $itemlist[$itemId]['pics'] ? reset($itemlist[$itemId]['pics']) : '';
            $cartdata['item_spec_desc'] = $itemlist[$itemId]['item_spec_desc'] ?? '';
            $validCart[] = $cartdata;
        }

        //检测是否有活动商品，是否是有效的活动商品
        if (method_exists($this->cartInterface, 'getItemData')) {
            $validCart = $this->cartInterface->getItemData($companyId, $userId, $validCart);
        }

        if ($validCart) {
            if (method_exists($this->cartInterface, 'getShopData')) {
                $shopIds = $shopId ? $shopId : array_unique(array_column($validCart, 'shop_id'));
                $this->shopData = $this->cartInterface->getShopData($companyId, $shopIds);
            }
            $result['valid_cart'] = $this->getTotalCart($validCart);
        } else {
            $result['valid_cart'] = [];
        }

        if (method_exists($this->cartInterface, 'getInvalidCart')) {
            $newInvalidCart = $this->cartInterface->getInvalidCart();
            $result['invalid_cart'] = array_merge($invalidCart, $newInvalidCart);
        } else {
            $result['invalid_cart'] = $invalidCart;
        }
        return $result;
    }

    private function getTotalCart($validCart)
    {
        $newCartData = [];
        if ($validCart) {
            foreach ($validCart as $cart) {
                $newCartData[$cart['shop_id']][$cart['cart_id']] = $cart;
            }
        }
        $result = [];
        if ($newCartData) {
            foreach ($newCartData as $shopId => $cartdata) {
                if (isset($this->shopData[$shopId])) {
                    $data = $this->shopData[$shopId];
                }
                $data ['shop_id'] = $shopId;
                if (method_exists($this->cartInterface, 'getTotalCart')) {
                    $totalCart = $this->cartInterface->getTotalCart($cartdata);
                    $data['cart_total_price'] = $totalCart['item_fee'] ?? 0; //计算商品促销之前的购物车总价
                    $data['item_fee'] = $totalCart['item_fee'] ?? 0; //计算商品促销之前的购物车总价
                    $data['cart_total_num'] = $totalCart['cart_total_num'] ?? 0;
                    $data['cart_total_count'] = $totalCart['cart_total_count'] ?? 0;
                    $data['discount_fee'] = $totalCart['discount_fee'] ?? 0;//购物车商品促销总优惠金额
                    $data['total_fee'] = $totalCart['total_fee'] ?? 0; //购物车减去优惠金额的总金额
                    foreach ($totalCart['cart_list'] as $cartId => $cart) {
                        $data['list'][] = $cart;
                    }
                    $data['used_activity'] = $totalCart['used_activity'];
                    $data['used_activity_ids'] = $totalCart['used_activity_ids'];
                    $data['activity_grouping'] = [];
                    foreach ($totalCart['activity_grouping'] as $activityId => $usedActivity) {
                        $data['activity_grouping'][] = $usedActivity;
                    }
                } else {
                    $cartTotalPrice = 0;
                    $cartTotalNum = 0;
                    $cartTotalCount = 0;
                    foreach ($cartdata as $cart) {
                        if (isset($cart['is_checked']) && $cart['is_checked']) {
                            $cartTotalPrice += ($cart['price'] * $cart['num']);
                            $cartTotalNum += $cart['num'];
                            $cartTotalCount += 1 ;
                        }
                    }
                    $data['item_fee'] = $cartTotalPrice;
                    $data['cart_total_price'] = $cartTotalPrice;
                    $data['cart_total_num'] = $cartTotalNum;
                    $data['cart_total_count'] = $cartTotalCount;
                    foreach ($cartdata as $cartId => $cart) {
                        $data['list'][] = $cart;
                    }
                }
                $result[] = $data;
            }
        }
        return $result;
    }

    public function getFastBuyCart($companyId, $userId)
    {
        $key = "fastbuy:".sha1($companyId.$userId);
        $cartList = app('redis')->get($key);
        if ($cartList) {
            return json_decode($cartList, true);
        }
        return [];
    }

    public function setFastBuyCart($companyId, $userId, $params)
    {
        $key = "fastbuy:".sha1($companyId.$userId);
        $params['cart_id'] = 0;
        app('redis')->setex($key, 600, json_encode($params));
        return $params;
    }


    /**
        * @brief 临时购物车
        *
        * @param $companyId
        * @param $userId
        * @param $items
        * @param $shopId
        *
        * @return
     */
    public function getFastBuy($companyId, $userId, $items, $shopId = 0)
    {
        $itemIds = array_column($items, 'item_id');
        $cartitem = array_column($items, 'num', 'item_id');
        $itemService = new ItemsService();
        $filter = [
            'company_id' => $companyId,
            'item_id' => $itemIds
        ];
        $itemlist = $itemService->getItemsList($filter, 1, 100)['list'];
        $itemlist = array_column($itemlist, null, 'itemId');
        if (!$itemlist) {
            throw new ResourceException('找不到商品');
        }
        $cartdata = [];
        foreach ($itemlist as $itemId => $item) {
            if ($item['approve_status'] != 'onsale') {
                continue;
            }
            $cart['item_id'] = $itemId;
            $cart['num'] = $cartitem[$itemId];
            $cart['shop_id'] = $shopId;
            $cart['user_id'] = $userId;
            $cart['company_id'] = $companyId;
            $cart['price'] = $itemlist[$itemId]['price'];
            $cart['store'] = $itemlist[$itemId]['store'];
            $cart['is_checked'] = true;
            $cart['cart_id'] = 0;
            $cart['discount_fee'] = 0;
            $cart['total_fee'] = $itemlist[$itemId]['price'] * $cartitem[$itemId];
            $cart['is_medicine'] = $itemlist[$itemId]['is_medicine'] ?? 0;
            if ($cart['is_medicine'] == 1) {
                $cart['is_prescription'] = $itemInfo['medicine_info']['is_prescription'] ?? 0;
            }
            $cartdata[] = $cart;
        }
        if (!$cartdata) {
            throw new ResourceException('商品已失效');
        }
        //检测是否有活动商品，是否是有效的活动商品
        if (method_exists($this->cartInterface, 'getItemData')) {
            $cartdata = $this->cartInterface->getItemData($companyId, $userId, $cartdata);
        }
        if (!$cartdata) {
            throw new ResourceException('商品已失效');
        }
        $cartTotalPrice = 0;
        $cartTotalNum = 0;
        $cartTotalCount = 0;

        if (method_exists($this->cartInterface, 'getTotalCart')) {
            $totalCart = $this->cartInterface->getTotalCart($cartdata);
            $data['cart_total_price'] = $totalCart['item_fee'] ?? 0; //计算商品促销之前的购物车总价
            $data['item_fee'] = $totalCart['item_fee'] ?? 0; //计算商品促销之前的购物车总价
            $data['cart_total_num'] = $totalCart['cart_total_num'] ?? 0;
            $data['cart_total_count'] = $totalCart['cart_total_count'] ?? 0;
            $data['discount_fee'] = $totalCart['discount_fee'] ?? 0;//购物车商品促销总优惠金额
            $data['total_fee'] = $totalCart['total_fee'] ?? 0; //购物车减去优惠金额的总金额
            foreach ($totalCart['cart_list'] as $cartId => $cart) {
                $data['list'][] = $cart;
            }
            $data['used_activity'] = $totalCart['used_activity'];
            $data['used_activity_ids'] = $totalCart['used_activity_ids'];
            $data['activity_grouping'] = [];
            foreach ($totalCart['activity_grouping'] as $activityId => $usedActivity) {
                $data['activity_grouping'][] = $usedActivity;
            }
        } else {
            foreach ($cartdata as $cart) {
                if (isset($cart['is_checked']) && $cart['is_checked']) {
                    $cartTotalPrice += ($cart['price'] * $cart['num']);
                    $cartTotalNum += $cart['num'];
                    $cartTotalCount += 1 ;
                }
            }
            $data['item_fee'] = $cartTotalPrice;
            $data['cart_total_price'] = $cartTotalPrice;
            $data['cart_total_num'] = $cartTotalNum;
            $data['cart_total_count'] = $cartTotalCount;
            $data['list'] = $cartdata;
        }
        return $data;
    }


    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->cartInterface->$method(...$parameters);
    }
}
