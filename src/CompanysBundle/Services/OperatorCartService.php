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

namespace CompanysBundle\Services;

use Dingo\Api\Exception\ResourceException;
use CompanysBundle\Entities\OperatorCart;
use GoodsBundle\Services\ItemsService;
use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;
use OrdersBundle\Services\CartService;
use CompanysBundle\Ego\CompanysActivationEgo;

class OperatorCartService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(OperatorCart::class);
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


    /**
     * 店务立即购买：写入 Redis 分桶，不写入 OperatorCart 表；库存规则见 OperatorFastBuyStockValidator。
     *
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $params
     * @param bool|string $isAccumulate
     * @return array<string, mixed>
     */
    public function addFastBuyCartdata($filter, $params, $isAccumulate = true)
    {
        $this->_checkAddCartParams($filter, $params);
        [$itemInfo, $params] = $this->loadItemSkuInfoForOperatorAddCart($filter, $params);
        DistributorService::assertShopadminFastBuyAllowed(
            (int) $filter['company_id'],
            (int) $filter['distributor_id'],
            $itemInfo
        );
        $itemsService = new ItemsService();
        $platformStock = $itemsService->resolvePlatformStoreForSkuRow($itemInfo);
        $shopStock = $this->resolveShopStockForFastBuy($filter, $itemInfo);
        OperatorFastBuyStockValidator::validate($shopStock, $platformStock, (int) ($params['num'] ?? 0));

        $redis = new OperatorShopFastBuyRedisService();
        $existing = $redis->get((int) $filter['company_id'], (int) $filter['operator_id'], (int) $filter['distributor_id']);
        $row = [
            'company_id' => (int) $filter['company_id'],
            'operator_id' => (int) $filter['operator_id'],
            'distributor_id' => (int) $filter['distributor_id'],
            'item_id' => (int) $filter['item_id'],
            'is_checked' => true,
            'special_type' => $params['special_type'] ?? null,
        ];
        $accumulate = $isAccumulate !== false && $isAccumulate !== 'false';
        if ($accumulate && $existing && !empty($existing['item_id']) && (int) $existing['item_id'] === (int) $filter['item_id']) {
            $row['num'] = (int) ($existing['num'] ?? 0) + (int) $params['num'];
        } else {
            $row['num'] = (int) $params['num'];
        }
        $redis->set((int) $filter['company_id'], (int) $filter['operator_id'], (int) $filter['distributor_id'], $row);
        $row['cart_id'] = 0;

        return $row;
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
            'operator_id' => ['required', '管理员信息有误'],
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

    /**
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $params
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function loadItemSkuInfoForOperatorAddCart(array $filter, array $params): array
    {
        $itemService = new ItemsService();
        $distributorItemsService = new DistributorItemsService();

        $company = (new CompanysActivationEgo())->check($filter['company_id']);
        if ($filter['distributor_id'] == 0 || $company['product_model'] == 'platform') {
            $itemInfo = $itemService->getItemsSkuDetail($filter['item_id']);
        } else {
            $itemInfo = $distributorItemsService->getValidDistributorItemSkuInfo($filter['company_id'], $filter['item_id'], $filter['distributor_id']);
        }

        if (!$itemInfo || ($itemInfo['company_id'] != $filter['company_id'])) {
            throw new ResourceException('无效商品');
        }
        $params['special_type'] = $itemInfo['special_type'];

        return [$itemInfo, $params];
    }

    /**
     * 标准模式 + 有店铺时，itemInfo['store'] 为门店库存；总部发货时 store 为平台库存，不计入门店。
     *
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $itemInfo
     */
    private function resolveShopStockForFastBuy(array $filter, array $itemInfo): int
    {
        return self::resolveShopStockForFastBuyFromItemInfo($filter, $itemInfo);
    }

    /**
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $itemInfo
     */
    public static function resolveShopStockForFastBuyFromItemInfo(array $filter, array $itemInfo, ?string $productModel = null): int
    {
        if ((int) ($filter['distributor_id'] ?? 0) === 0) {
            return 0;
        }
        if ($productModel === null) {
            $company = (new CompanysActivationEgo())->check($filter['company_id']);
            $productModel = $company['product_model'] ?? 'platform';
        }
        if ($productModel === 'platform') {
            return 0;
        }
        if (ItemsService::isSkuHeadquartersShipment($itemInfo)) {
            return 0;
        }

        return max(0, (int) ($itemInfo['store'] ?? 0));
    }

    private function _checkAddCartItems($filter, $params = [])
    {
        [$itemInfo, $params] = $this->loadItemSkuInfoForOperatorAddCart($filter, $params);
        if ($itemInfo['store'] < $params['num']) {
            throw new ResourceException('库存不足');
        }

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
    public function getCartdataList($filter, $userId = 0, $isSubmit = false)
    {
        // 不在查询层强制 is_checked=1：列表与结算须加载同一批购物车行。
        // 库中未勾选或曾被置为未选时，列表仍可能有「有效商品」，若此处加 is_checked 会导致结算报「购物车为空」。
        // 是否计入订单由 ShopadminNormalOrderService::checkoutCartItems 内对 is_checked 的判断负责。
        $cartlist = $this->entityRepository->getLists($filter);
        if (!$cartlist && $isSubmit) {
            throw new ResourceException('购物车为空');
        } elseif (!$cartlist) {
            return ['invalid_cart' => [], 'valid_cart' => []];
        }

        $cartlist = array_column($cartlist, null, 'cart_id');
        $itemIds = array_column($cartlist, 'item_id');

        $companyId = $filter['company_id'];
        $distributorId = $filter['distributor_id'];
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

        $company = (new CompanysActivationEgo())->check($companyId);
        if ($distributorId > 0 && $company['product_model'] == 'standard') {
            $distributorItemsService = new DistributorItemsService();
            $itemList['list'] = $distributorItemsService->getDistributorSkuReplace($companyId, $distributorId, $itemList['list']);
        }
        $itemList = array_column($itemList['list'], null, 'item_id');
        if ($isSubmit && !$itemList) {
            throw new ResourceException('购物车商品已失效或不存在');
        } elseif (!$itemList) {
            return ['invalid_cart' => [], 'valid_cart' => []];
        }

        $cartService = new CartService();
        $result = $cartService->HandleValidCart($companyId, $userId, $cartlist, $itemList, 'shop_offline');

        $result['is_check_store'] = false;

        $cartTypeService = $cartService->getCartTypeService('distributor');
        if (method_exists($cartTypeService, 'formatCartList')) {
            $cartData = $cartTypeService->formatCartList($companyId, $userId, $result, $isSubmit);
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
        $cartData['valid_cart'] = $cartService->getTotalCart($cartData['valid_cart'], $cartTypeService, $distributorId, $companyId);
        if ($cartData['invalid_cart']) {
            $cartIds = array_column($cartData['invalid_cart'], 'cart_id');
            $this->entityRepository->updateBy(['cart_id' => $cartIds], ['is_checked' => 0]);
        }
        return $cartData;
    }

    /**
     * 店务立即购买：从 Redis 分桶组装与 getCartdataList 同结构的结算数据（仅 fastbuy 模式使用）。
     *
     * @param array<string, mixed> $filter company_id, operator_id, distributor_id
     *
     * @return array<string, mixed>
     */
    public function getFastBuyCartdataList(array $filter, int $userId, bool $isSubmit = false)
    {
        $redisSvc = new OperatorShopFastBuyRedisService();
        $bucket = $redisSvc->get((int) $filter['company_id'], (int) $filter['operator_id'], (int) $filter['distributor_id']);
        if (empty($bucket['item_id'])) {
            if ($isSubmit) {
                throw new ResourceException('购物车为空');
            }

            return ['invalid_cart' => [], 'valid_cart' => []];
        }

        $rowFilter = array_merge($filter, ['item_id' => (int) $bucket['item_id']]);
        $numParams = ['num' => (int) $bucket['num']];
        [$itemInfo, $numParams] = $this->loadItemSkuInfoForOperatorAddCart($rowFilter, $numParams);
        $itemsService = new ItemsService();
        $platformStock = $itemsService->resolvePlatformStoreForSkuRow($itemInfo);
        $shopStock = $this->resolveShopStockForFastBuy($rowFilter, $itemInfo);
        if ($isSubmit) {
            OperatorFastBuyStockValidator::validate($shopStock, $platformStock, (int) $numParams['num']);
        }

        $cartlist = [[
            'cart_id' => 0,
            'item_id' => (int) $bucket['item_id'],
            'num' => (int) $bucket['num'],
            'is_checked' => true,
            'company_id' => (int) $filter['company_id'],
            'operator_id' => (int) $filter['operator_id'],
            'distributor_id' => (int) $filter['distributor_id'],
            'shop_id' => (int) $filter['distributor_id'],
            'shop_type' => 'shop_offline',
            'activity_type' => 'normal',
            'items_id' => [],
        ]];

        $companyId = (int) $filter['company_id'];
        $distributorId = (int) $filter['distributor_id'];

        $itemFilter = [
            'item_id' => [(int) $bucket['item_id']],
            'company_id' => $companyId,
        ];
        $itemList = $itemsService->getSkuItemsList($itemFilter);
        if ($isSubmit && $itemList['total_count'] <= 0) {
            throw new ResourceException('商品已失效');
        }
        if ($itemList['total_count'] <= 0) {
            return ['invalid_cart' => [], 'valid_cart' => []];
        }

        $company = (new CompanysActivationEgo())->check($companyId);
        if ($distributorId > 0 && $company['product_model'] == 'standard') {
            $distributorItemsService = new DistributorItemsService();
            $itemList['list'] = $distributorItemsService->getDistributorSkuReplace($companyId, $distributorId, $itemList['list']);
        }
        $itemList = array_column($itemList['list'], null, 'item_id');
        $iid = (int) $bucket['item_id'];
        if (!isset($itemList[$iid])) {
            if ($isSubmit) {
                throw new ResourceException('购物车商品已失效或不存在');
            }

            return ['invalid_cart' => [], 'valid_cart' => []];
        }
        $itemList[$iid]['store'] = max((int) ($itemList[$iid]['store'] ?? 0), $itemsService->resolvePlatformStoreForSkuRow($itemList[$iid]));

        $cartService = new CartService();
        $result = $cartService->HandleValidCart($companyId, $userId, $cartlist, $itemList, 'shop_offline');

        $result['is_check_store'] = false;

        $cartTypeService = $cartService->getCartTypeService('distributor');
        $cartData = $result;
        if (method_exists($cartTypeService, 'formatCartList')) {
            $cartData = $cartTypeService->formatCartList($companyId, $userId, $result, $isSubmit);
        }
        if (!$cartData['is_check_store']) {
            foreach ($cartData['valid_cart'] as $row) {
                if ($row['store'] < $row['num'] && $isSubmit) {
                    throw new ResourceException(($row['item_name'] ?? '商品') . '库存不足');
                }
                if (isset($row['children']) && is_array($row['children'])) {
                    foreach ($row['children'] as $rowchild) {
                        if ($rowchild['store'] < $rowchild['num'] && $isSubmit) {
                            throw new ResourceException(($rowchild['item_name'] ?? '商品') . '库存不足');
                        }
                    }
                }
            }
            $cartData['is_check_store'] = true;
        }
        $cartData['valid_cart'] = $cartService->getCartItemUserGradePrice($cartData['valid_cart'], $companyId, $userId);
        $cartData['valid_cart'] = $cartService->getTotalCart($cartData['valid_cart'], $cartTypeService, $distributorId, $companyId);
        if ($cartData['invalid_cart']) {
            $cartIds = array_values(array_filter(array_column($cartData['invalid_cart'], 'cart_id')));
            if ($cartIds) {
                $this->entityRepository->updateBy(['cart_id' => $cartIds], ['is_checked' => 0]);
            }
        }

        return $cartData;
    }

    /**
     * create 前二次校验：Redis 立即购买桶与云仓库存。
     *
     * @param array<string, mixed> $filter company_id, operator_id, distributor_id
     */
    public function revalidateFastBuyPlatformStock(array $filter): void
    {
        $redisSvc = new OperatorShopFastBuyRedisService();
        $bucket = $redisSvc->get((int) $filter['company_id'], (int) $filter['operator_id'], (int) $filter['distributor_id']);
        if (empty($bucket['item_id'])) {
            throw new ResourceException('立即购买已失效，请重新加购');
        }
        $rowFilter = array_merge($filter, ['item_id' => (int) $bucket['item_id']]);
        $params = ['num' => (int) $bucket['num']];
        [$itemInfo, $params] = $this->loadItemSkuInfoForOperatorAddCart($rowFilter, $params);
        DistributorService::assertShopadminFastBuyAllowed(
            (int) $filter['company_id'],
            (int) $filter['distributor_id'],
            $itemInfo
        );
        $itemsService = new ItemsService();
        $platformStock = $itemsService->resolvePlatformStoreForSkuRow($itemInfo);
        $shopStock = $this->resolveShopStockForFastBuy($rowFilter, $itemInfo);
        OperatorFastBuyStockValidator::validate($shopStock, $platformStock, (int) $params['num']);
    }
}
