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

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Entities\Cart;
use DistributionBundle\Entities\Distributor;
use GoodsBundle\Services\ItemsService;
use EmployeePurchaseBundle\Services\ActivitiesService;
use OrdersBundle\Services\CartService as NormalCartService;
use EmployeePurchaseBundle\Services\EmployeesService;
use EmployeePurchaseBundle\Services\RelativesService;
use EmployeePurchaseBundle\Services\ActivityItemsService;

class CartService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Cart::class);
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
        // Built with ShopEx Framework
        return $this->entityRepository->$method(...$parameters);
    }


    public function addCartdata($filter, $params, $isAccumulate = true)
    {
        // HACK: temporary solution
        $this->_checkAddCartParams($filter, $params);
        $params = $this->_checkAddCartItems($filter, $params);

        $cartType = (isset($params['cart_type']) && $params['cart_type'] == 'fastbuy') ? 'fastbuy' : 'cart';
        if ($cartType == 'fastbuy') {
            $params['is_checked'] = true;
            $params = array_merge($filter, $params);
            return $this->setFastBuyCart($filter['company_id'], $filter['enterprise_id'], $filter['activity_id'], $filter['user_id'], $params);
        }

        $cartInfo = $this->entityRepository->getInfo($filter);
        if (!$cartInfo && ($params['num'] ?? 0) <= 0) {
            throw new ResourceException('加入购物车的数据有误');
        }
        if ($cartInfo && ($params['num'] ?? 0) <= 0) {
            $this->entityRepository->deleteBy($filter);
            return [];
        }
        if ($cartInfo) {
            //$isAccumulate=true 累增; =false 覆盖
            $params['num'] = (!$isAccumulate || $isAccumulate === 'false') ? $params['num'] : ($params['num'] + $cartInfo['num']) ;
            return $this->entityRepository->updateOneBy($filter, $params);
        }
        $params = array_merge($filter, $params);
        return $this->entityRepository->create($params);
    }

    public function updateCartdata($filter, $params)
    {
        // HACK: temporary solution
        $this->_checkAddCartParams($filter, $params);
        $cartInfo = $this->entityRepository->getInfo($filter);
        if (!$cartInfo || ($params['num'] ?? 0) <= 0) {
            throw new ResourceException('更新购物车的数据有误');
        }
        $filter['item_id'] = $cartInfo['item_id'];
        $params = $this->_checkAddCartItems($filter, $params);
        if ($cartInfo && ($params['num'] ?? 0) <= 0) {
            $this->entityRepository->deleteBy($filter);
            return [];
        }
        return $this->entityRepository->updateOneBy($filter, $params);
    }

    private function _checkAddCartParams($filter, $params)
    {
        $params = array_merge($filter, $params);
        $rules = [
            'company_id' => ['required', '公司ID必填'],
            'enterprise_id' => ['required', '企业ID必填'],
            'activity_id' => ['required', '活动ID必填'],
            'user_id' => ['required', '用户ID必填'],
            'item_id' => ['required_without:cart_id', '商品ID必填'],
            'cart_id' => ['required_without:item_id', '购物车ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        //判断店铺是否失效
        if (($params['shop_id'] ?? 0) && $params['shop_type'] == 'distributor') {
            $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
            $distributorInfo = $distributorRepository->getInfoById($params['shop_id']);
            if (!$distributorInfo || $distributorInfo['is_valid'] != 'true') {
                throw new ResourceException('当前店铺已失效');
            }
        }
        return true;
    }

    private function _checkAddCartItems($filter, $params = [])
    {
        $activitiesService = new ActivitiesService;
        $activity = $activitiesService->getInfo(['company_id' => $filter['company_id'], 'id' => $filter['activity_id']]);

        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['status'] == 'cancel') {
            throw new ResourceException('活动已取消');
        }

        if ($activity['status'] == 'pending') {
            throw new ResourceException('活动已暂停');
        }

        if ($activity['status'] == 'over') {
            throw new ResourceException('活动已结束');
        }

        if (!in_array($filter['enterprise_id'], $activity['enterprise_id'])) {
            throw new ResourceException('企业不参与该活动');
        }

        $employeesService = new EmployeesService();
        $employee = $employeesService->check($filter['company_id'], $filter['enterprise_id'], $filter['user_id']);
        if ($employee) {
            if ($activity['employee_begin_time'] > time() || $activity['employee_end_time'] < time()) {
                throw new ResourceException('非员工购买时段');
            }
        } else {
            if ($activity['if_relative_join']) {
                $relativesService = new RelativesService();
                $relative = $relativesService->check($filter['company_id'], $filter['enterprise_id'], $filter['activity_id'], $filter['user_id']);
                if ($relative) {
                    if ($activity['relative_begin_time'] > time() || $activity['relative_end_time'] < time()) {
                        throw new ResourceException('非亲友购买时段');
                    }
                } else {
                    throw new ResourceException('既不是员工也不是亲友，无权购买');
                }
            } else {
                throw new ResourceException('不是员工，无权购买');
            }
        }

        $itemService = new ItemsService();
        $itemInfo = $itemService->getItemsSkuDetail($filter['item_id']);
        if (!$itemInfo || $itemInfo['company_id'] != $filter['company_id'] || $itemInfo['approve_status'] != 'onsale') {
            throw new ResourceException('无效商品');
        }

        if ($itemInfo['medicine_data'] ?? []) {
            if ($itemInfo['medicine_data']['max_num'] > 0 && $params['num'] > $itemInfo['medicine_data']['max_num']) {
                throw new ResourceException('超出药品单次最大可购买数量,最大可购买:' . $itemInfo['medicine_data']['max_num'] . '个');
            }
        }

        $activityItemsService = new ActivityItemsService();
        $activityItemInfo = $activityItemsService->getInfo(['company_id' => $filter['company_id'], 'activity_id' => $filter['activity_id'], 'item_id' => $filter['item_id']]);
        if (!$activityItemInfo) {
            throw new ResourceException('商品未参加内购活动');
        }

        if ($activity['if_share_store']) {
            if ($itemInfo['store'] < $params['num']) {
                throw new ResourceException('活动库存不足');
            }
        } else {
            if ($activityItemInfo['activity_store'] < $params['num']) {
                throw new ResourceException('活动库存不足');
            }
        }

        return $params;
    }

    /**
     * @brief 导购员获取购物车数据，并且计算指定会员的优惠
     *
     * @param $filter
     * @param $isSubmit   //是否提交结算
     *
     * @return
     */
    public function getCartdataList($filter, $isSubmit = false)
    {
        $activitiesService = new ActivitiesService;
        $activity = $activitiesService->getInfo(['company_id' => $filter['company_id'], 'id' => $filter['activity_id']]);
        if (!$activity) {
            if ($isSubmit) {
                throw new ResourceException('活动不存在');
            } else {
                return ['invalid_cart' => [], 'valid_cart' => []];
            }
        }

        if ($activity['status'] == 'cancel') {
            if ($isSubmit) {
                throw new ResourceException('活动已取消');
            } else {
                return ['invalid_cart' => [], 'valid_cart' => []];
            }
        }

        if ($activity['status'] == 'pending') {
            if ($isSubmit) {
                throw new ResourceException('活动已暂停');
            } else {
                return ['invalid_cart' => [], 'valid_cart' => []];
            }
        }

        if ($activity['status'] == 'over') {
            if ($isSubmit) {
                throw new ResourceException('活动已结束');
            } else {
                return ['invalid_cart' => [], 'valid_cart' => []];
            }
        }

        if ($isSubmit) {
            $filter['is_checked'] = 1;
        }

        $cartType = (isset($filter['cart_type']) && $filter['cart_type'] == 'fastbuy') ? 'fastbuy' : 'cart';
        if ($cartType == 'fastbuy') {
            $cartList = $this->getFastBuyCart($filter['company_id'], $filter['enterprise_id'], $filter['activity_id'], $filter['user_id']);
        } else {
            unset($filter['cart_type']);
            $cartList = $this->entityRepository->getLists($filter);
        }
        if (!$cartList && $isSubmit) {
            throw new ResourceException('购物车为空');
        } elseif (!$cartList) {
            return ['invalid_cart' => [], 'valid_cart' => []];
        }

        $cartList = array_column($cartList, null, 'cart_id');
        $itemIds = array_column($cartList, 'item_id');

        $companyId = $filter['company_id'];
        $userId = $filter['user_id'];
        //获取购物车中商品的数据列表
        $itemFilter = [
            'item_id' => $itemIds,
            'company_id' => $companyId,
        ];
        $itemService = new ItemsService();
        $itemList = $itemService->getSkuItemsList($itemFilter);
        if ($isSubmit && $itemList['total_count'] <= 0) {
            throw new ResourceException('商品已失效');
        } elseif ($itemList['total_count'] <= 0) {
            return ['invalid_cart' => [], 'valid_cart' => []];
        }
        $itemList = array_column($itemList['list'], null, 'item_id');

        $activityItemsService = new ActivityItemsService();
        $activityItemList = $activityItemsService->getLists(['company_id' => $filter['company_id'], 'activity_id' => $filter['activity_id'], 'item_id' => $itemIds]);
        $activityItemList = array_column($activityItemList, null, 'item_id');

        foreach ($itemList as $key => $item) {
            if (isset($activityItemList[$item['item_id']])) {
                $itemList[$key]['sale_price'] = $item['price'];
                $itemList[$key]['price'] = $activityItemList[$item['item_id']]['activity_price'];
                if (!$activity['if_share_store']) {
                    $itemList[$key]['store'] = $activityItemList[$item['item_id']]['activity_store'];
                }
            } else {
                $itemList[$key]['sale_price'] = $item['price'];
                $itemList[$key]['store'] = 0;
            }
        }

        foreach ($cartList as $key => $cart) {
            if (!isset($itemList[$cart['item_id']])) {
                unset($cartList[$key]);
                $this->entityRepository->deleteById($cart['cart_id']);
            } else {
                $cartList[$key]['price'] = $itemList[$cart['item_id']]['price'];
                $cartList[$key]['sale_price'] = $itemList[$cart['item_id']]['sale_price'];
                $cartList[$key]['item_name'] = $itemList[$cart['item_id']]['item_name'];
                $cartList[$key]['pics'] = $itemList[$cart['item_id']]['pics'] ? reset($itemList[$cart['item_id']]['pics']) : '';
            }
        }

        $cartService = new NormalCartService();
        $cartData = $cartService->HandleValidCart($companyId, $userId, $cartList, $itemList, 'employee_purchase');

        $cartTotalPrice = 0;
        $cartTotalNum = 0;
        $cartTotalCount = 0;
        foreach ($cartData['valid_cart'] as $key => $cart) {
            $cartData['valid_cart'][$key]['is_checked'] = isset($cart['is_checked']) && $cart['is_checked'];
            if (isset($cart['is_checked']) && $cart['is_checked']) {
                $cartTotalPrice += ($cart['price'] * $cart['num']);
                $cartTotalNum += $cart['num'];
                $cartTotalCount += 1;
            }
        }
        if ($activity['distributor_id'] > 0) {
            $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
            $distributorInfo = $distributorRepository->getInfoById($activity['distributor_id']);
        }
        
        $validCart['shop_id'] = $activity['distributor_id'];
        $validCart['shop_name'] = $distributorInfo['name'] ?? "";
        $validCart['is_ziti'] = false;
        $validCart['is_delivery'] = true;
        $validCart['item_fee'] = $cartTotalPrice;
        $validCart['cart_total_price'] = $cartTotalPrice;
        $validCart['cart_total_num'] = $cartTotalNum;
        $validCart['cart_total_count'] = $cartTotalCount;
        $validCart['total_fee'] = $cartTotalPrice;
        $validCart['list'] = $cartData['valid_cart'];

        if ($cartData['invalid_cart']) {
            $cartIds = array_column($cartData['invalid_cart'], 'cart_id');
            $this->entityRepository->updateBy(['cart_id' => $cartIds], ['is_checked' => 0]);
        }

        return ['invalid_cart' => $cartData['invalid_cart'], 'valid_cart' => [$validCart]];
    }

    public function setFastBuyCart($companyId, $enterpriseId, $activityId, $userId, $params)
    {
        $key = "employee_purchase_fastbuy:" . sha1($companyId . $enterpriseId . $activityId . $userId);
        if ($params) {
            $params['cart_id'] = 0;
        }
        app('redis')->setex($key, 600, json_encode($params));
        return $params;
    }

    public function getFastBuyCart($companyId, $enterpriseId, $activityId, $userId)
    {
        $key = "employee_purchase_fastbuy:" . sha1($companyId . $enterpriseId . $activityId . $userId);
        $cartList = app('redis')->get($key);
        if ($cartList) {
            $cartList = json_decode($cartList, true);
            if ($cartList) {
                return [$cartList];
            }
        }
        return [];
    }
}
