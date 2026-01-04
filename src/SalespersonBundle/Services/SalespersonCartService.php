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

namespace SalespersonBundle\Services;

use Dingo\Api\Exception\ResourceException;
use SalespersonBundle\Entities\SalespersonCart;
use GoodsBundle\Services\ItemsService;
use DistributionBundle\Services\DistributorItemsService;
use OrdersBundle\Services\CartService;

class SalespersonCartService
{
    public $entityRepository;
    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(SalespersonCart::class);
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
        // This module is part of ShopEx EcShopX system
        return $this->entityRepository->$method(...$parameters);
    }


    public function addCartdata($filter, $params, $isAccumulate = true)
    {
        $this->_checkAddCartParams($filter, $params);
        $params = $this->_checkAddCartItems($filter, $params);

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
        $this->_checkAddCartParams($filter, $params);
        $params = $this->_checkAddCartItems($filter, $params);
        $cartInfo = $this->entityRepository->getInfo($filter);
        if (!$cartInfo || ($params['num'] ?? 0) <= 0) {
            throw new ResourceException('更新购物车的数据有误');
        }
        if ($cartInfo && ($params['num'] ?? 0) <= 0) {
            $this->entityRepository->deleteBy($filter);
            return [];
        }
        if ($cartInfo) {
            //return $this->entityRepository->updateBy($filter, $params);
            return $this->entityRepository->updateOneBy($filter, $params);
        }
        $params = array_merge($filter, $params);
        return $this->entityRepository->create($params);
    }

    private function _checkAddCartParams($filter, $params)
    {
        $params = array_merge($filter, $params);
        $rules = [
            'salesperson_id' => ['required', '导购员信息有误'],
            'distributor_id' => ['required', '店铺信息有误'],
            'company_id' => ['required', '企业信息有误'],
            'item_id' => ['required', '购物车商品有误'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        return true;
    }

    private function _checkAddCartItems($filter, $params = [])
    {
        $itemService = new ItemsService();
        $distributorItemsService = new DistributorItemsService();

        if ($filter['distributor_id']) {
            $itemInfo = $distributorItemsService->getValidDistributorItemSkuInfo($filter['company_id'], $filter['item_id'], $filter['distributor_id']);
        } else {
            $itemInfo = $itemService->getItemsSkuDetail($filter['item_id']);
        }
        if (!$itemInfo || ($itemInfo['company_id'] != $filter['company_id'])) {
            throw new ResourceException('无效商品');
        }
        if ($itemInfo['store'] < $params['num']) {
            throw new ResourceException('库存不足');
        }
        $params['special_type'] = $itemInfo['special_type'];
        return $params;
    }

    /**
        * @brief 导购员获取购物车数据，并且计算指定会员的优惠
        *
        * @param $filter
        * @param $userId
        * @param $isSubmit   //是否提交结算
        *
        * @return
     */
    public function getCartdataList($filter, $userId = 0, $isSubmit = false, $isSalePromotion = false)
    {
        if ($isSubmit) {
            $filter['is_checked'] = 1;
        }
        if ($isSalePromotion) {
            $salesPromotionService = new SalesPromotionsService();
            $cartlist = $salesPromotionService->getSalesPromotionItems($filter['cxdid']);
        } else {
            $cartlist = $this->entityRepository->getLists($filter);
        }
        if (!$cartlist && $isSubmit) {
            throw new ResourceException('购物车为空');
        } elseif (!$cartlist) {
            return ['invalid_cart' => [], 'valid_cart' => []];
        }

        $cartlist = array_column($cartlist, null, 'cart_id');
        $itemIds = array_column($cartlist, 'item_id');

        $companyId = $filter['company_id'];
        $shopId = $filter['distributor_id'];
        //获取购物车中商品的数据列表
        $itemFilter = [
            'item_id' => $itemIds,
            'company_id' => $filter['company_id'],
        ];
        $itemService = new ItemsService();
        $itemList = $itemService->getSkuItemsList($itemFilter);
        if ($isSubmit && $itemList['total_count'] <= 0) {
            throw new ResourceException('商品已失效');
        } elseif ($itemList['total_count'] <= 0) {
            return ['invalid_cart' => [], 'valid_cart' => []];
        }

        if ($filter['distributor_id']) {
            $distributorItemsService = new DistributorItemsService();
            $itemList['list'] = $distributorItemsService->getDistributorSkuReplace($filter['company_id'], $filter['distributor_id'], $itemList['list']);
        }
        $itemList = array_column($itemList['list'], null, 'item_id');
        if ($isSubmit && !$itemList) {
            throw new ResourceException('购物车商品已失效或不存在');
        } elseif (!$itemList) {
            return ['invalid_cart' => [], 'valid_cart' => []];
        }

        $cartService = new CartService();
        $result = $cartService->HandleValidCart($filter['company_id'], $userId, $cartlist, $itemList, 'salesperson');

        $result['is_check_store'] = false;

        $cartTypeService = $cartService->getCartTypeService('distributor');
        if (method_exists($cartTypeService, 'formatCartList')) {
            $cartData = $cartTypeService->formatCartList($filter['company_id'], $userId, $result, $isSubmit);
        }
        if (!$cartData['is_check_store']) {
            foreach ($cartData['valid_cart'] as $row) {
                if ($row['store'] < $row['num'] && $isSubmit) {
                    throw new ResourceException($row['item_name'] . '库存不足');
                }
                // 组合商品子商品库存计算
                if (isset($row['children']) && is_array($row['children'])) {
                    foreach ($row['children'] as $rowchild) {
                        if ($rowchild['store'] < $rowchild['num'] && $isSubmit) {
                            throw new ResourceException($rowchild['item_name'] . '库存不足');
                        }
                    }
                }
            }
            $cartData['is_check_store'] = true;
        }
        //处理会员价
        $cartData['valid_cart'] = $cartService->getCartItemUserGradePrice($cartData['valid_cart'], $companyId, $userId);
        $cartData['valid_cart'] = $cartService->getTotalCart($cartData['valid_cart'], $cartTypeService, $shopId, $companyId);
        if ($cartData['invalid_cart']) {
            $cartIds = array_column($cartData['invalid_cart'], 'cart_id');
            $this->entityRepository->updateBy(['cart_id' => $cartIds], ['is_checked' => 0]);
        }
        return $cartData;
    }
}
