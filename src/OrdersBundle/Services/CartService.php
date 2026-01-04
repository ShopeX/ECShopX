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

use CrossBorderBundle\Entities\CrossBorderSet;
use CrossBorderBundle\Entities\OriginCountry;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\DistributorItems;
use DistributionBundle\Services\DistributorItemsService;
use GoodsBundle\Entities\ItemsCategory;
use GoodsBundle\Services\ItemsService;
use PointsmallBundle\Services\ItemsService as PointsmallItemsService;
use MembersBundle\Services\MemberService;
use OrdersBundle\Entities\Cart;
use SalespersonBundle\Entities\SalespersonCart;
use CompanysBundle\Entities\OperatorCart;
use EmployeePurchaseBundle\Entities\Cart as EmployeePurchaseCart;
use OrdersBundle\Services\Cart\DistributorCartObject;
use OrdersBundle\Services\Cart\PointsmallCartObject;
use PromotionsBundle\Services\LimitService;
use PromotionsBundle\Services\MemberPriceService;
use PromotionsBundle\Services\PackageService;
use PromotionsBundle\Entities\MarketingGiftItems;
use DistributionBundle\Entities\Distributor;
use PromotionsBundle\Traits\CheckPromotionsValid;
use MerchantBundle\Services\MerchantService;
use CompanysBundle\Ego\CompanysActivationEgo;

class CartService
{
    use CheckPromotionsValid;
    /** @var entityRepository */
    public $entityRepository;

    public $userVipBuyGuide = '';   //付费会员购买引导语

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Cart::class);
    }

    /**
     * 加入购物车 加入购物车的数据
     */
    public function addCart($data)
    {
        // 验证购物车传入参数
        $this->__validatorAddCartParams($data);
        app('log')->info(':dkxd:'.__FUNCTION__.__LINE__.':111:data :' . json_encode($data) ) ;

        // 加入购物车类型， 立即购物 or 加入购物车
        $cartType = (isset($data['cart_type']) && $data['cart_type'] == 'fastbuy') ? 'fastbuy' : 'cart';
        if (isset($data['item_id'])) {
            $cartInfo = [];
            if ($cartType == 'cart') {
                // 根据加入购物车参数获取已有的购物车数据
                $cartInfo = $this->__getCartDataInfoByAddParams($data);
            }
            app('log')->info(':dkxd:'.__FUNCTION__.__LINE__.':111111:cartInfo :' . json_encode($cartInfo) ) ;

            // 特殊处理，如果前端传入购买商品数量小于0，则表示为需要删除改购物车信息
            if ($cartInfo && $data['num'] <= 0) {
                $result = $this->entityRepository->deleteBy(['cart_id' => $cartInfo['cart_id']]);
                return ['status' => $result];
            }

            // 格式化加入购物车数据，返回存储到数据库的购物车数据结构
            $cartTypeService = $this->getCartTypeService($data['shop_type']);
            if (method_exists($cartTypeService, 'formatAddCartData')) {
                $cartInfo = $cartTypeService->formatAddCartData($data, $cartInfo);
            } else {
                $cartInfo = $this->formatAddCartData($data, $cartInfo);
            }
            // 离线购物车不需要保存购物车数据
            if (!$data['user_id']) {
                return true;
            }
            // 进行活动校验，暂时只对限时特惠、限时秒杀进行处理。校验限购数、限额
            if (method_exists($cartTypeService, '__checkCartInfoPromotion')) {
                $cartInfo = $cartTypeService->__checkCartInfoPromotion($cartInfo, true);
            }

            if ($cartType == 'fastbuy') {
                $cartInfo['is_checked'] = true;
                $result = $this->setFastBuyCart($cartInfo['company_id'], $cartInfo['user_id'], $cartInfo);
            } else {
                app('log')->info(':dkxd:'.__FUNCTION__.__LINE__.':222:cartInfo :' . json_encode($cartInfo) ) ;

                // 如果有id，则表示更新
                if ($cartInfo['cart_id']) {
                    app('log')->info(':dkxd:'.__FUNCTION__.__LINE__.':222update:cartInfo :' . json_encode($cartInfo) ) ;

                    $result = $this->entityRepository->updateOneBy(['cart_id' => $cartInfo['cart_id']], $cartInfo);

                } else {
                    app('log')->info(':dkxd:'.__FUNCTION__.__LINE__.':222create:cartInfo :' . json_encode($cartInfo) ) ;

                    $result = $this->entityRepository->create($cartInfo);
                }
            }

            return $result;
        }

        $result = [];
        if ($cartType == 'cart' && isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
            foreach ($items as $item) {
                $data['item_id'] = $item['item_id'];
                $data['num'] = $item['num'];

                // 根据加入购物车参数获取已有的购物车数据
                $cartInfo = $this->__getCartDataInfoByAddParams($data);

                // 特殊处理，如果前端传入购买商品数量小于0，则表示为需要删除改购物车信息
                if ($cartInfo && $data['num'] <= 0) {
                    $this->entityRepository->deleteBy(['cart_id' => $cartInfo['cart_id']]);
                    continue;
                }

                // 格式化加入购物车数据，返回存储到数据库的购物车数据结构
                $cartInfo = $this->formatAddCartData($data, $cartInfo);

                // 离线购物车不需要保存购物车数据
                if (!$data['user_id']) {
                    continue;
                }

                // 如果有id，则表示更新
                if ($cartInfo['cart_id']) {
                    $result[] = $this->entityRepository->updateOneBy(['cart_id' => $cartInfo['cart_id']], $cartInfo);
                } else {
                    $result[] = $this->entityRepository->create($cartInfo);
                }
            }

            if (!$data['user_id']) {
                return true;
            }

            return $result;
        }
    }

    /**
     * 验证加入购物车传入参数
     */
    private function __validatorAddCartParams($params)
    {
        $rules = [
            'user_id' => ['required', '提交的用户信息有误'],
            'item_id' => ['required_without:items', '提交的商品数据错误'],
            'num' => ['required_without:items|integer', '提交的商品数据错误'],
            'shop_type' => ['required', '提交购物车数据有误'],
            'items' => ['required_without:item_id|array', '提交的商品数据错误']
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if (isset($params['items'])) {
            $itemsService = new ItemsService();
            foreach ($params['items'] as $item) {
                $rules = [
                    'item_id' => ['required', '提交的商品数据错误'],
                    'num' => ['required|integer', '提交的商品数据错误'],
                ];
                $errorMessage = validator_params($item, $rules);
                if ($errorMessage) {
                    throw new ResourceException($errorMessage);
                }

                // 起订量验证
                $itemInfo = $itemsService->getItem(['company_id' =>$item['company_id'] ,'item_id' => $item['item_id']]);
                if (isset($itemInfo['start_num']) && $itemInfo['start_num'] > 0 && $itemInfo['start_num'] > $item['num']) {
                    throw new ResourceException(trans('OrdersBundle/Order.item_minimum_purchase_not_met'));
                }
            }
        }

        if (isset($params['item_id'])) {
            $itemsService = new ItemsService();
            // 起订量验证
            $itemInfo = $itemsService->getItem(['company_id' =>$params['company_id'] ,'item_id' => $params['item_id']]);
            if (isset($itemInfo['start_num']) && $itemInfo['start_num'] > 0 && $itemInfo['start_num'] > $params['num']) {
                throw new ResourceException("数量不能少于起订量");
            }
        }

        if (isset($params['activity_type']) && $params['activity_type'] == 'package' && (!isset($params['items_id']) || !$params['items_id'])) {
            throw new ResourceException(trans('OrdersBundle/Order.please_select_package_items'));
        }

        $cartType = $params['cart_type'] ?? 'cart';
        if ($params['shop_type'] == 'drug' && $cartType == 'fastbuy') {
            throw new ResourceException(trans('OrdersBundle/Order.item_cannot_be_purchased'));
        }

        //判断店铺是否失效
        if (($params['shop_id'] ?? 0) && $params['shop_type'] == 'distributor') {
            $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
            $distributorInfo = $distributorRepository->getInfoById($params['shop_id']);
            if (!$distributorInfo || $distributorInfo['is_valid'] != 'true') {
                throw new ResourceException(trans('OrdersBundle/Order.shop_invalid'));
            }
        }
        return $params;
    }

    /**
     * 根据加入购物车的参数，查询购物车数据
     * 用于判断加入的购物车为更新还是新增
     */
    public function __getCartDataInfoByAddParams($params)
    {
        app('log')->info(':dkxd:'.__FUNCTION__.__LINE__.':111:cartInfo :' . json_encode($params) ) ;

        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'item_id' => $params['item_id'],
            'shop_type' => $params['shop_type'],
            'shop_id' => $params['shop_id'] ?? 0,
        ];
        if(isset($params['promoter_user_id']) &&  $params['promoter_user_id']){
            $filter['promoter_user_id'] = $params['promoter_user_id'];
        }
        app('log')->info(':dkxd:'.__FUNCTION__.__LINE__.':22222:filter :' . json_encode($filter) ) ;

        if (($params['activity_id'] ?? 0) && ($params['activity_type'] ?? '')) {
            $filter['activity_type'] = $params['activity_type'];
            $filter['activity_id'] = $params['activity_id'];
        } else {
            $filter['activity_type'] = 'normal';
            $filter['activity_id'] = null;
        }

        if (isset($params['cart_id']) && $params['cart_id']) {
            $filter['cart_id'] = $params['cart_id'];
        }

        if (isset($params['wxapp_appid']) && $params['wxapp_appid']) {
            $filter['wxa_appid'] = $params['wxapp_appid'];
        }
        if (($params['activity_type'] ?? '') == 'package') {
            if (isset($params['items_id']) && $params['items_id']) {
                $filter['items_id'] = implode(',', $params['items_id']);
            } else {
                $filter['items_id'] = null;
            }
        }

        // 获取购物车数据
        $cartInfo = $this->entityRepository->getInfo($filter);

        return $cartInfo;
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
        $params['activity_type'] = $params['activity_type'] ?? '';

        // 判断商品是否参加活动
        if ('package' == $params['activity_type']) { // 组合商品全部都要测试库存
            $itemsId = $params['items_id'] ?? [];
            $itemsId = array_merge($itemsId, [$params['item_id']]); // 组合商品最后一个数组数值是主商品id,为方便下面取值
        } else {
            $itemsId = [$params['item_id']];
        }
        $itemInfo = [];

        $itemService = new ItemsService();
        $distributorItemsService = new DistributorItemsService();
        foreach ($itemsId as $itemId) {
            if ($params['shop_id'] && ($params['shop_type'] ?? '') != 'community') {
                $itemInfo = $distributorItemsService->getValidDistributorItemSkuInfo($params['company_id'], $itemId, $params['shop_id']);
            } else {
                $itemInfo = $itemService->getItemsSkuDetail($itemId);
            }
            if (!$itemInfo || ($itemInfo['company_id'] != $params['company_id'])) {
                throw new ResourceException(trans('OrdersBundle/Order.item_not_exist'));
            }
            if (($itemInfo['approve_status'] != 'onsale' && $itemInfo['approve_status'] != 'offline_sale')) {
                throw new ResourceException(trans('OrdersBundle/Order.item_not_on_sale'));
            }

            if ($itemInfo && $itemInfo['special_type'] != 'drug' && ($params['shop_type'] ?? '') == 'drug') {
                throw new ResourceException(trans('OrdersBundle/Order.item_cannot_be_purchased_with_normal_items'));
            }

            // 检查购物车数量是否超出药品设置的最大单次可购买数量
            if ($itemInfo['medicine_data'] ?? []) {
                if ($itemInfo['medicine_data']['max_num'] > 0 && $params['num'] > $itemInfo['medicine_data']['max_num']) {
                    throw new ResourceException(trans('OrdersBundle/Order.item_purchase_limit_exceeded'));
                }
            }

            $activityInfo = $itemService->getCurrentActivityByItemId($params['company_id'], $itemId);

            // 组合商品提前判断库存
            if ('package' == $params['activity_type']) {
                $params['is_check_store'] = true;
                if ($itemInfo['store'] < $params['num']) {
                    throw new ResourceException(trans('OrdersBundle/Order.item_stock_insufficient'));
                }
            } elseif (isset($activityInfo['activity_type']) && 'seckill' == $activityInfo['activity_type']) {
                // 检查是否是秒杀商品
                $params['is_check_store'] = true;
            } else {
                $params['is_check_store'] = false;
            }
        }

        $params['item_name'] = $itemInfo['item_name'];
        $params['pics'] = (is_array($itemInfo['pics']) && $itemInfo['pics']) ? reset($itemInfo['pics']) : '';
        $params['price'] = $itemInfo['price'];

        //验证对应的商品
        // 目前只有社区活动商品进行了验证
        $cartTypeService = $this->getCartTypeService($params['shop_type']);
        if (method_exists($cartTypeService, 'checkItemParams')) {
            $params = $cartTypeService->checkItemParams($params);
        }
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
            'wxa_appid' => $params['wxa_appid'] ?? '',
            'is_plus_buy' => $params['is_plus_buy'] ?? false,
            'isAccumulate' => false,
            'promoter_user_id'=>$params['promoter_user_id'] ?? null,
        ];

        if($data['promoter_user_id']){
            $data['source_type'] = 'salesperson';
        }
        return $data;
    }

    /**
     * 获取购物车不同类型
     */
    public function getCartTypeService($shopType)
    {
        $shopType = strtolower($shopType);
        switch ($shopType) {
            case 'distributor':
            case 'drug': //药品清单
                $cartTypeService = new DistributorCartObject();
                break;
            case 'pointsmall':
                $cartTypeService = new PointsmallCartObject();
                break;
            default:
                throw new ResourceException(trans('OrdersBundle/Order.cart_items_cannot_mix_types'));
        }

        return $cartTypeService;
    }

    public function setFastBuyCart($companyId, $userId, $params)
    {
        $key = "fastbuy:" . sha1($companyId . $userId);
        if ($params) {
            $params['cart_id'] = 0;
        }
        app('redis')->setex($key, 600, json_encode($params));
        return $params;
    }

    /**
     * 获取购物车列表，处理满减 满折等
     */
    public function getCartList($companyId, $userId, $shopId = 0, $cartType = 'cart', $shopType = 'distributor', $isCheckout = false, $iscrossborder = false, $isShopScreen = false, $userDevice = 'miniprogram', $items = [], $inputData = [])
    {
        if ($cartType == 'offline') {
            $cartData = [];
            $offlineCartId = 0;
            $itemService = new ItemsService();
            foreach ($items as $item) {
                $itemInfo = $itemService->getItemsSkuDetail($item['item_id']);
                if (!$itemInfo) {
                    continue;
                }
                $item['cart_id'] = --$offlineCartId;
                $item['company_id'] = $companyId;
                $item['shop_id'] = $shopId;
                $item['is_checked'] = ($item['is_checked'] ?? 'true') == 'false' ? false : true;
                $item['item_name'] = $itemInfo['item_name'];
                $item['pics'] = $itemInfo['pics'];
                $item['price'] = $itemInfo['price'];
                $item['start_num'] = $itemInfo['start_num'] ?? 0;
                $item['is_medicine'] = $itemInfo['is_medicine'] ?? 0;
                if ($item['is_medicine'] == 1) {
                    $item['is_prescription'] = $itemInfo['medicine_data']['is_prescription'] ?? 0;
                }
                $cartData[] = $item;
            }
        } else {
            $cartData = $this->__getCartBasicData($companyId, $userId, $shopId, $cartType, $shopType, $isCheckout, ($inputData ?? []) );
        }
        if ($isCheckout && !$cartData) {
            throw new ResourceException(trans('OrdersBundle/Order.cart_selected_items_empty'));
        }
        if (!$cartData) {
            return ['invalid_cart' => [], 'valid_cart' => []];
        }

        $cartData = $this->__getValidCartList($companyId, $userId, $cartData, $shopId, $shopType, $isCheckout, $iscrossborder, $isShopScreen, $userDevice);
        if (!$cartData || (isset($cartData['valid_cart']) && empty($cartData['valid_cart']))) {
            if ($isCheckout) {
                throw new ResourceException(trans('OrdersBundle/Order.cart_items_not_from_same_shop'));
            }
            return ['invalid_cart' => $cartData['invalid_cart'] ?? [], 'valid_cart' => []];
        }

        if ($cartData['invalid_cart'] && $isCheckout) {
            throw new ResourceException(trans('OrdersBundle/Order.cart_items_not_from_same_activity'));
        }

        // 是否已经检查了库存，默认还没有判断
        $cartData['is_check_store'] = false;
        //检测是否有活动商品，是否是有效的活动商品 处理满减 满折
        $cartTypeService = $this->getCartTypeService($shopType);
        if (method_exists($cartTypeService, 'formatCartList')) {
            $cartData = $cartTypeService->formatCartList($companyId, $userId, $cartData, $isCheckout, $userDevice);
        }
        if (!$cartData['is_check_store']) {
            foreach ($cartData['valid_cart'] as $row) {
                if ($row['store'] < $row['num'] && $isCheckout) {
                    throw new ResourceException($row['item_name'] . trans('OrdersBundle/Order.item_stock_insufficient'));
                }
                // 组合商品子商品库存计算
                if (isset($row['children']) && is_array($row['children'])) {
                    foreach ($row['children'] as $rowchild) {
                        if ($rowchild['store'] < $rowchild['num'] && $isCheckout) {
                            throw new ResourceException($rowchild['item_name'] . trans('OrdersBundle/Order.item_stock_insufficient'));
                        }
                    }
                }
            }
            $cartData['is_check_store'] = true;
        }

        //处理会员价
        $cartData['valid_cart'] = $this->getCartItemUserGradePrice($cartData['valid_cart'], $companyId, $userId);
        //处理购物车价格计算
        $cartData['valid_cart'] = $this->getTotalCart($cartData['valid_cart'], $cartTypeService, $shopId, $companyId);
        if ($cartData['invalid_cart']) {
            $cartIds = array_column($cartData['invalid_cart'], 'cart_id');
            $this->entityRepository->updateBy(['cart_id' => $cartIds], ['is_checked' => false]);
        }

        foreach ($cartData['valid_cart'] as $key => $val) {
            foreach ($val['list'] as $k => $v) {
                // 跨境商品
                if (($v['type'] ?? 0) == 1) {
                    $cartData['valid_cart'][$key]['list'][$k]['cross_border_taxation'] = bcdiv(bcmul($v['total_fee'], $v['tax_rate'], 0), 100, 0);
                }
            }
        }

        return $cartData;
    }

    /**
     * 获取购物车基础数据
     */
    private function __getCartBasicData($companyId, $userId, $shopId = 0, $cartType = 'cart', $shopType = 'distributor', $isCheckout,$inputData = [] )
    {
        $cartList = [];
        if ($cartType == 'cart') {
            $filter = [
                'company_id' => $companyId,
                'user_id' => $userId,
                'shop_type' => $shopType,
                'shop_id' => $shopId
            ];
            // 如果是订单结算，那么只取已经选择的购物车数据
            if ($isCheckout) {
                $filter['is_checked'] = true;
            }
            if(isset($inputData['promoter_user_id']) && $inputData['promoter_user_id'] ){
                $filter['promoter_user_id'] = $inputData['promoter_user_id'];
            }
            $cartList = $this->entityRepository->lists($filter)['list'];
        } elseif ($cartType == 'fastbuy') {
            $fastBuyCart = $this->getFastBuyCart($companyId, $userId);
            $cartList = $fastBuyCart ? [$fastBuyCart] : [];
        }
        return $cartList;
    }

    public function getFastBuyCart($companyId, $userId)
    {
        $key = "fastbuy:" . sha1($companyId . $userId);
        $cartList = app('redis')->get($key);
        if ($cartList) {
            return json_decode($cartList, true);
        }

        return [];
    }

    /**
     * 将购物车数据分为有效的数据和无效的数据 function
     * 判断基础商品数据是否有效，基础库中商品下架或者不存在则表示失效了
     *
     * @return array
     */
    private function __getValidCartList($companyId, $userId, $cartList, $shopId, $shopType, $isCheckout, $iscrossborder, $isShopScreen = false, $userDevice = 'miniprogram')
    {
        $itemIds = array_column($cartList, 'item_id');
        foreach ($cartList as $v) {
            $itemIds = array_merge($itemIds, $v['items_id'] ?? []);
        }
        $itemIds = array_unique(array_filter($itemIds));

        //检查商品是否有效
        $filter = [
            'company_id' => $companyId,
            'item_id' => $itemIds,
        ];
        if ('pointsmall' == $shopType) {
            $itemService = new PointsmallItemsService();
        } else {
            $itemService = new ItemsService();
        }

        $itemList = $itemService->getSkuItemsList($filter);
        if ($itemList['total_count'] <= 0) {
            return ['valid_cart' => [], 'invalid_cart' => $cartList];
        }

        if ($shopId && $shopType != 'community' && $itemList['total_count'] > 0) {
            $distributorItemsService = new DistributorItemsService();
            $itemList['list'] = $distributorItemsService->getDistributorSkuReplace($companyId, $shopId, $itemList['list']);
        }

        $itemList = array_column($itemList['list'], null, 'item_id');
        $data = $this->HandleValidCart($companyId, $userId, $cartList, $itemList, 'normal', $iscrossborder, $isShopScreen, $shopType, $userDevice);

        return $data;
    }


    // 获取产地国信息
    private function getorigincountry($company_id)
    {
        $filter['company_id'] = $company_id;
        // 查询内容
        $find = [
            'origincountry_id',
            'origincountry_name',
            'origincountry_img_url',
        ];
        $origincountry = app('registry')->getManager('default')->getRepository(OriginCountry::class)->lists($filter, $find);
        return $origincountry['list'];
    }

    /**
     * 处理失效商品
     * @param $companyId
     * @param $userId
     * @param $cartList
     * @param $itemList
     * @param $cartType
     * @return mixed
     */
    public function HandleValidCart($companyId, $userId, $cartList, $itemList, $cartType = 'normal', $iscrossborder = false, $isShopScreen = false, $shopType = 'distributor', $userDevice = 'miniprogram')
    {
        // 跨境
        if ($iscrossborder == 1) {
            // 产地国信息
            $origincountry = $this->getorigincountry($companyId);
            $origincountry_data = array_column($origincountry, null, 'origincountry_id');
            $origincountry_idall = array_column($origincountry, 'origincountry_id');

            // 全局税率
            $crossborder_set_info = app('registry')->getManager('default')->getRepository(CrossBorderSet::class)->getInfo(['company_id' => $companyId]);
            if (empty($crossborder_set_info)) {
                $default_tax_rate = 0;
            } else {
                $default_tax_rate = $crossborder_set_info['tax_rate'];
            }
        }

        //获取购物车商品相关的有效的店铺集合
        $shopIds = array_unique(array_column($cartList, 'shop_id'));
        $validShopIds = [];
        // if ($cartType != 'employee_purchase' && $shopType == 'distributor') {
        if ($shopType == 'distributor') {
            $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
            $validShopList = $distributorRepository->getLists(['distributor_id' => $shopIds, 'is_valid' => 'true'], 'distributor_id');
            $validShopIds = array_column($validShopList, 'distributor_id');
            $merchantIds = array_column($validShopList, 'merchant_id');
            // 获取可用的店铺id,去检查店铺关联的商户，是否是开启状态
            $merchantService = new MerchantService();
            $validShopIds = $merchantService->getVaildDistributorByMid($companyId, $merchantIds, $validShopIds);
        }

        $validCart = []; //购物车有效的商品
        $invalidCart = []; //购物车失效商品
        $packageService = new PackageService();
        foreach ($cartList as $k => $cartdata) {
            $itemId = $cartdata['item_id'];
            $memberpreference = true;
            $cartdata['shop_type'] = $cartdata['shop_type'] ?? '';
            if ($cartType != 'employee_purchase' && ($cartdata['shop_type'] != 'pointsmall' && $cartdata['shop_type'] != 'shop_offline')) {
                $memberpreference = $this->checkCurrentMemberpreferenceByItemId($companyId, $userId, $itemId, $cartdata['shop_id'], false, $msg);
            }
            if (!$memberpreference) {
                $invalidCart[] = $cartdata;
                continue;
            }

            if (!($itemList[$itemId] ?? null)) {
                $invalidCart[] = $cartdata;
                continue;
            }
            if ($cartdata['shop_id'] ?? null) {
                if (!in_array($cartdata['shop_id'], $validShopIds)) {
                    $invalidCart[] = $cartdata;
                    continue;
                }
            }
            $itemPic = ($itemList[$itemId]['spec_image_url'] ?? null) ? $itemList[$itemId]['spec_image_url'] : ($itemList[$itemId]['pics'] ?? null);
            if ($cartType != 'employee_purchase' &&'package' == ($cartdata['activity_type'] ?? '') && ($cartdata['items_id'] ?? null)) {
                $packageInfo = $packageService->getPackageInfo($companyId, $cartdata['activity_id']);
                if (!$packageInfo) {
                    $invalidCart[] = $cartdata;
                    continue;
                }
                // 判断组合商品是否有效
                $result = $this->packageItem($companyId, $userId, $packageInfo, $cartdata, $itemList);
                if (!$result) {
                    $invalidCart[] = $cartdata;
                    continue;
                }

                $itemId = $cartdata['item_id'];
                $store = $itemList[$itemId]['store'];
                if ($cartdata['cart_id'] && (intval($cartdata['num']) > intval($store))) {
                    $this->entityRepository->updateOneBy(['cart_id' => $cartdata['cart_id']], ['num' => $store]);
                    $cartdata['num'] = $store;
                }
                if (intval($cartdata['num']) <= 0) {
                    $invalidCart[] = $cartdata;
                    continue;
                }
                // 获取组合商品已选择sku的商品活动价
                $main_items_price = array_column($packageInfo['main_items'], 'item_price', 'item_id');
                // $cartdata['price'] = $packageInfo['main_item_price'];
                $cartdata['price'] = $main_items_price[$cartdata['item_id']];
                $cartdata['discount_fee'] = 0;
                $cartdata['total_fee'] = $main_items_price[$cartdata['item_id']] * $cartdata['num'];
                // $cartdata['total_fee'] = $packageInfo['main_item_price'] * $cartdata['num'];
                $cartdata['store'] = $itemList[$itemId]['store'];
                $cartdata['market_price'] = $itemList[$itemId]['market_price'];
                $cartdata['brief'] = $itemList[$itemId]['brief'];
                $cartdata['item_type'] = $itemList[$itemId]['item_type'];
                $cartdata['approve_status'] = $itemList[$itemId]['approve_status'];
                $cartdata['item_name'] = $itemList[$itemId]['itemName'];
                $cartdata['pics'] = $itemList[$itemId]['pics'] ? reset($itemList[$itemId]['pics']) : '';
                $cartdata['item_spec_desc'] = $itemList[$itemId]['item_spec_desc'] ?? '';
                $cartdata['parent_id'] = 0;
                $cartdata['is_last_price'] = true;
                foreach ($cartdata['items_id'] as $v) {
                    $children = [];
                    $children['parent_id'] = $cartdata['cart_id'];
                    if ($userDevice != 'pc') {
                        $children['price'] = $packageInfo['new_price'][$v];
                    }
                    $children['discount_fee'] = 0;
                    $children['num'] = $cartdata['num'];
                    $children['total_fee'] = $packageInfo['new_price'][$v] * $cartdata['num'];
                    $children['store'] = $itemList[$v]['store'];
                    $children['market_price'] = $itemList[$v]['market_price'];
                    $children['brief'] = $itemList[$v]['brief'];
                    $children['item_type'] = $itemList[$v]['item_type'];
                    $children['approve_status'] = $itemList[$v]['approve_status'];
                    $children['item_name'] = $itemList[$v]['itemName'];
                    $children['item_id'] = $itemList[$v]['itemId'];
                    $children['pics'] = $itemList[$v]['pics'] ? reset($itemList[$v]['pics']) : '';
                    $children['item_spec_desc'] = $itemList[$v]['item_spec_desc'] ?? '';
                    $children['is_last_price'] = true;
                    $cartdata['packages'][] = $children;
                }
                // 跨境参数
                $cartdata['type'] = $itemList[$itemId]['type'] ?? '0';
                $cartdata['crossborder_tax_rate'] = $itemList[$itemId]['crossborder_tax_rate'] ?? '0';
                $cartdata['taxstrategy_id'] = $itemList[$itemId]['taxstrategy_id'] ?? '0';
                $cartdata['taxation_num'] = $itemList[$itemId]['taxation_num'] ?? '0';
                $cartdata['origincountry_id'] = $itemList[$itemId]['origincountry_id'] ?? '';

                // 药品数据
                $cartdata['is_medicine'] = $itemList[$itemId]['is_medicine'] ?? 0;
                if ($cartdata['is_medicine'] == 1) {
                    $cartdata['is_prescription'] = $itemList[$itemId]['medicine_data']['is_prescription'] ?? 0;
                }
                // 起订量
                $cartdata['start_num'] = $itemList[$itemId]['start_num'] ?? 0;

                $validCart[] = $cartdata;
            } else {
                if ($shopType != 'pointsmall') {
                    $result = $this->singleItem($companyId, $userId, $cartdata, $itemList);
                    if (!$result) {
                        $invalidCart[] = $cartdata;
                        continue;
                    }
                }
                // $is_market = $itemList[$itemId]['is_market'] ?? 1;
                // if($is_market == 0){
                //     $invalidCart[] = $cartdata;
                //     continue;
                // }

                $itemId = $cartdata['item_id'];
                $store = $itemList[$itemId]['store'];
                $logisticsStore = 0;
                if ($isShopScreen) {
                    // 只有门店大屏才拆单
                    $logisticsStore = $itemList[$itemId]['logistics_store'] ?? 0;
                }
                if (intval($cartdata['num']) > intval($store)) {
                    if ($cartType == 'salesperson') {
                        if (($cartdata['cart_id'] ?? 0) > 0) {
                            $salespersonCartRepository = app('registry')->getManager('default')->getRepository(SalespersonCart::class);
                            $salespersonCartRepository->updateOneBy(['cart_id' => $cartdata['cart_id']], ['num' => $store]);
                        }
                        $cartdata['num'] = $store;
                    } elseif ($cartType == 'shop_offline') {
                        if (($cartdata['cart_id'] ?? 0) > 0) {
                            $operatorCartRepository = app('registry')->getManager('default')->getRepository(OperatorCart::class);
                            $operatorCartRepository->updateOneBy(['cart_id' => $cartdata['cart_id']], ['num' => $store]);
                        }
                        $cartdata['num'] = $store;
                    } elseif ($cartType == 'employee_purchase') {
                        if (($cartdata['cart_id'] ?? 0) > 0) {
                            $employeePurchaseCartRepository = app('registry')->getManager('default')->getRepository(EmployeePurchaseCart::class);
                            $employeePurchaseCartRepository->updateOneBy(['cart_id' => $cartdata['cart_id']], ['num' => $store]);
                        }
                        $cartdata['num'] = $store;
                    } else {
                        $logisticsNum = intval($cartdata['num']) - intval($store);
                        if ($logisticsNum > $logisticsStore) {
                            $logisticsNum = $logisticsStore;
                            if (($cartdata['cart_id'] ?? 0) > 0) {
                                $this->entityRepository->updateOneBy(['cart_id' => $cartdata['cart_id']], ['num' => $store + $logisticsStore]);
                            }
                        }

                        if ($logisticsNum > 0) {
                            $cartdata['logistics_num'] = $logisticsNum;
                        }
                        $cartdata['num'] = $store;
                    }
                }
                if ($cartType == 'employee_purchase') {
                    $cartdata['sale_price'] = $itemList[$itemId]['sale_price'];
                }

                $store += $logisticsStore;

                if (intval($cartdata['num']) + intval($cartdata['logistics_num'] ?? 0) <= 0) {
                    if ($store <= 0) {
                        $invalidCart[] = $cartdata;
                        continue;
                    } else {
                        $cartdata['num'] = 1;
                        if (($cartdata['cart_id'] ?? 0) > 0) {
                            $this->entityRepository->updateOneBy(['cart_id' => $cartdata['cart_id']], ['num' => 1]);
                        }
                    }
                }

                // 判断是否为跨境商品
                if ($iscrossborder == 1) {
                    if ($itemList[$itemId]['type'] != 1) {
                        continue;
                    }
                } else {
                    if ($itemList[$itemId]['type'] == 1) {
                        continue;
                    }
                }

                $cartdata['is_last_price'] = false;
                $cartdata['price'] = $itemList[$itemId]['price'];
                $cartdata['discount_fee'] = 0;
                $cartdata['total_fee'] = $itemList[$itemId]['price'] * $cartdata['num'];
                $cartdata['store'] = $store;
                $cartdata['market_price'] = $itemList[$itemId]['market_price'];
                $cartdata['brief'] = $itemList[$itemId]['brief'];
                $cartdata['item_type'] = $itemList[$itemId]['item_type'];
                $cartdata['approve_status'] = $itemList[$itemId]['approve_status'];
                $cartdata['item_name'] = $itemList[$itemId]['itemName'];
                $cartdata['pics'] = (is_array($itemList[$itemId]['pics']) && $itemList[$itemId]['pics']) ? reset($itemList[$itemId]['pics']) : '';
                $cartdata['item_spec_desc'] = $itemList[$itemId]['item_spec_desc'] ?? '';
                $cartdata['parent_id'] = 0;
                $cartdata['goods_id'] = $itemList[$itemId]['goods_id'] ?? $itemId;
                $cartdata['user_id'] = $userId;
                $cartdata['item_category'] = $itemList[$itemId]['item_category'];

                // 跨境参数
                $cartdata['type'] = $itemList[$itemId]['type'] ?? '0';
                $cartdata['crossborder_tax_rate'] = $itemList[$itemId]['crossborder_tax_rate'] ?? '0';
                $cartdata['taxstrategy_id'] = $itemList[$itemId]['taxstrategy_id'] ?? '0';
                $cartdata['taxation_num'] = $itemList[$itemId]['taxation_num'] ?? '0';
                $cartdata['origincountry_id'] = $itemList[$itemId]['origincountry_id'] ?? '';

                // 药品数据
                $cartdata['is_medicine'] = $itemList[$itemId]['is_medicine'] ?? 0;
                if ($cartdata['is_medicine'] == 1) {
                    $cartdata['is_prescription'] = $itemList[$itemId]['medicine_data']['is_prescription'] ?? 0;
                }

                // 起订量
                $cartdata['start_num'] = $itemList[$itemId]['start_num'] ?? 0;

                // 产地国信息- 是跨境商品-产地国id不为空，产地国信息存在
                if ($cartdata['type'] == 1 and !empty($cartdata['origincountry_id']) and in_array($cartdata['origincountry_id'], $origincountry_idall)) {
                    $cartdata['origincountry_name'] = $origincountry_data[$cartdata['origincountry_id']]['origincountry_name'];
                    $cartdata['origincountry_img_url'] = $origincountry_data[$cartdata['origincountry_id']]['origincountry_img_url'];
                } else {
                    $cartdata['origincountry_name'] = '';
                    $cartdata['origincountry_img_url'] = '';
                }


                // 跨境商品
                if ($cartdata['type'] == 1) {
                    // 判断商品是否有税率
                    if (!empty($cartdata['crossborder_tax_rate']) and $cartdata['crossborder_tax_rate'] > 0) {
                        $cartdata['tax_rate'] = $cartdata['crossborder_tax_rate'];
                    } else {
                        // 判断主类目
                        $filter['company_id'] = $companyId;
                        $filter['category_id'] = $cartdata['item_category'];
                        $item_category_tax_rate = app('registry')->getManager('default')->getRepository(ItemsCategory::class)->getInfo($filter)['crossborder_tax_rate'];
                        if (!empty($item_category_tax_rate) and $item_category_tax_rate > 0) {
                            $cartdata['tax_rate'] = $item_category_tax_rate;
                        } else {
                            // 使用全局税率
                            $cartdata['tax_rate'] = $default_tax_rate ? $default_tax_rate : 0;
                        }
                    }
                }

                if ($cartdata['num'] > 0) {
                    $validCart[] = $cartdata;
                }

                if (($cartdata['logistics_num'] ?? 0) > 0) {
                    $cartdata['cart_id'] = $cartdata['cart_id'].'_';
                    $cartdata['num'] = $cartdata['logistics_num'];
                    $cartdata['is_logistics'] = true;
                    $cartdata['is_total_store'] = true;
                    $cartdata['total_fee'] = $itemList[$itemId]['price'] * $cartdata['num'];
                    $validCart[] = $cartdata;
                }
            }
        }
        $data['valid_cart'] = $validCart;
        $data['invalid_cart'] = $invalidCart;
        return $data;
    }

    /**
     * 购物车商品组合判断是否失效
     * @param $packageInfo
     * @param $cartdata
     * @param $itemList
     * @return bool
     */
    public function packageItem($companyId, $userId, $packageInfo, $cartdata, $itemList)
    {
        if ($packageInfo['start_time'] > time() || $packageInfo['end_time'] < time()) {
            return false;
        }

        $memberService = new MemberService();
        $isHaveVip = $memberService->isHaveVip($userId, $companyId, $packageInfo['valid_grade']);

        if (!$isHaveVip) {
            return false;
        }

        if (!$this->singleItem($companyId, $userId, $cartdata, $itemList)) {
            return false;
        }

        foreach ($cartdata['items_id'] as $v) {
            if (!isset($itemList[$v])) {
                return false;
            }

            if ($itemList[$v]['approve_status'] != 'onsale' && $itemList[$v]['approve_status'] != 'offline_sale') {
                return false;
            }
            // 处方单预约清单，只能购买处方药
            if (($cartdata['shop_type'] ?? '') == 'drug' && $itemList[$v]['special_type'] != 'drug') {
                return false;
            }
            // 处方药，只能用于药品清单
            if (($cartdata['shop_type'] ?? '') != 'drug' && $itemList[$v]['special_type'] == 'drug') {
                return false;
            }

            if (($cartdata['shop_type'] ?? '') != 'shop_offline') {
                return $this->limitBuy($companyId, $userId, $v, $cartdata['num']);
            }
        }

        return true;
    }

    /**
     * 购物车单个判断是否失效
     * @param $cartdata
     * @param $itemList
     * @return bool
     */
    public function singleItem($companyId, $userId, $cartdata, $itemList)
    {
        $itemId = $cartdata['item_id'];

        if (!isset($itemList[$itemId])) {
            return false;
        }

        if ($itemList[$itemId]['approve_status'] != 'onsale' && $itemList[$itemId]['approve_status'] != 'offline_sale') {
            return false;
        }
        // 处方单预约清单，只能购买处方药
        if (($cartdata['shop_type'] ?? '') == 'drug' && $itemList[$itemId]['special_type'] != 'drug') {
            return false;
        }
        // 处方药，只能用于药品清单
        if (($cartdata['shop_type'] ?? '') != 'drug' && $itemList[$itemId]['special_type'] == 'drug') {
            return false;
        }
        // }
        // 赠品类型的商品
        if ($itemList[$itemId]['is_gift'] == true) {
            return false;
        }

        if (($cartdata['shop_type'] ?? '') != 'shop_offline' && ($cartdata['shop_type'] ?? '') != 'employee_purchase') {
            return $this->limitBuy($companyId, $userId, $itemId, $cartdata['num']);
        }

        return true;
    }

    /**
     * 判断商品限购
     * @param $companyId
     * @param $userId
     * @param $itemId
     * @param $number
     * @return bool
     */
    public function limitBuy($companyId, $userId, $itemId, $number)
    {
        $limitService = new  LimitService();
        $filter = [
            'company_id' => $companyId,
            'item_id' => $itemId,
            'start_time|lt' => time(),
            'end_time|gt' => time(),
        ];
        $limitItemInfo = $limitService->getLimitItemInfoNew($filter);
        if (!$limitItemInfo) {
            return true;
        }

        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getInfo(['item_id' => $itemId, 'company_id' => $companyId]);

        $activityData['activity_type'] = 'limited_buy';
        $limitInfo = $limitService->getLimitInfo($companyId, $limitItemInfo['limit_id']);

        if ($itemInfo['distributor_id'] != $limitInfo['source_id']) {
            return true;
        }

        $memberService = new MemberService();
        $isHaveVip = $memberService->isHaveVip($userId, $companyId, $limitInfo['valid_grade']);

        if (!$isHaveVip) {
            return true;
        }

        $rule = json_decode($limitInfo['rule'], 1);
        //$limitNum = $rule['limit'];
        $limitNum = $limitItemInfo['limit_num'];

        $filterPerson = [
            'company_id' => $companyId,
            'user_id' => $userId,
            'item_id' => $itemId,
        ];
        $filterPerson['start_time|lt'] = time();
        $filterPerson['end_time|gt'] = time();
        $limitService = new LimitService();
        $limitItemInfo = $limitService->getLimitPersonInfo($filterPerson);
        $limitNumber = $limitItemInfo['number'] ?? 0;
        $num = $number + $limitNumber;
        return $num > $limitNum ? false : true;
    }

    // 处理会员价
    public function getCartItemUserGradePrice($validCart, $companyId, $userId)
    {
        if (!$userId) {
            return $validCart;
        }
        foreach ($validCart as $cartdata) {
            // 组合商品不需要处理
            if (($cartdata['activity_type'] ?? '') == 'package') {
                continue;
            }
            if (!$cartdata['is_last_price']) {
                $itemIds[] = $cartdata['item_id'];
            }
        }
        if (!($itemIds ?? [])) {
            return $validCart;
        }
        //获取购物车需要计算会员价的商品的会员价
        $memberPriceService = new MemberPriceService();
        $filter = ['company_id' => $companyId, 'item_id' => $itemIds];
        $priceList = $memberPriceService->lists($filter);
        $priceData = array_column($priceList['list'], 'mprice', 'item_id');
        //获取会员当前的等级
        $memberService = new MemberService();
        $userGradeData = $memberService->getValidUserGradeUniqueByUserId($userId, $companyId);
        if (!$userGradeData) {
            return $validCart;
        }
        $discount = $userGradeData['discount'];            //会员折扣参数
        $gradeId = $userGradeData['id'];                   //会员等级id
        $gradeName = $userGradeData['name'];                   //会员等级名称
        $lvType = $userGradeData['lv_type'] ?? 'normal';   //会员等级类型 vip、svip、normal
        $userVipData = $userGradeData['userVipData'] ?? [];  //平台默认开启的付费会员卡 详细信息
        $gradeType = [
            'vip' => 'vipGrade',
            'svip' => 'vipGrade',
            'normal' => 'grade',
        ];

        if (!$priceData && (0 == $discount || $discount == 100)) {
            return $validCart;
        }
        foreach ($validCart as $key => $cartdata) {
            // 组合商品不需要处理
            if (($cartdata['activity_type'] ?? '') == 'package') {
                continue;
            }
            if (isset($cartdata['limitedTimeSaleAct']) && $cartdata['limitedTimeSaleAct']) {
                continue;
            }

            $validCart[$key]['price'] = $cartdata['price'];
            $validCart[$key]['discount_desc'] = '';
            $validCart[$key]['grade_name'] = $gradeName;

            $issetMemberPrice = false;
            if (isset($priceData[$cartdata['item_id']])) {
                $mprice = json_decode($priceData[$cartdata['item_id']], true);
                $grade = $gradeType[$lvType];
                if (isset($mprice[$grade][$gradeId]) && intval($mprice[$grade][$gradeId]) > 0) {
                    $issetMemberPrice = true;
                    $validCart[$key]['discount_fee'] = bcsub((int)$cartdata['price'], (int)$mprice[$grade][$gradeId]) * $cartdata['num'];
                    $validCart[$key]['member_price'] = (int)$mprice[$grade][$gradeId];
                    $validCart[$key]['member_discount'] = $validCart[$key]['discount_fee'];     // 会员折扣金额
                    $validCart[$key]['grade_name'] = $gradeName;
                    $validCart[$key]['activity_info'] = [[
                        'id' => 0,
                        'type' => 'member_price',
                        'info' => '会员价',
                        'rule' => '会员价直减' . bcdiv($validCart[$key]['discount_fee'], 100, 2),
                        'discount_fee' => $validCart[$key]['discount_fee'],
                    ]];
                }
            }

            if (!$issetMemberPrice && $discount > 0 && $discount < 100) {
                $memberDiscount = bcmul($cartdata['price'], bcdiv($discount, 100, 2));
                $validCart[$key]['discount_fee'] = $memberDiscount * $cartdata['num'];
                $validCart[$key]['member_price'] = bcsub($cartdata['price'], $memberDiscount);
                $validCart[$key]['member_discount'] = $validCart[$key]['discount_fee'];     // 会员折扣金额
                $validCart[$key]['grade_name'] = $gradeName;
                $validCart[$key]['activity_info'] = [[
                    'id' => 0,
                    'type' => 'member_price',
                    'info' => '会员价',
                    'rule' => '会员折扣优惠'.($discount / 10),
                    'discount_fee' => $validCart[$key]['discount_fee'],
                ]];
            }

            if (!$userVipData) {
                $validCart[$key]['discount_desc'] = ($gradeType == 'normal') ? '' : ($validCart[$key]['discount_fee'] ? ($gradeName . '为您节省' . ($validCart[$key]['discount_fee'] / 100) . '元') : '');
            } else {
                $this->userVipBuyGuide = $userVipData['guide_title'];
                $vipGradeId = $userVipData['vip_grade_id'];
                $type = $gradeType[$userVipData['lv_type']];
                if (isset($mprice[$type][$vipGradeId]) && intval($mprice[$type][$vipGradeId]) > 0) {
                    $memberDiscount = $validCart[$key]['price'] - intval($mprice[$type][$vipGradeId]);
                } else {
                    $price = $validCart[$key]['price']; //计算付费会员将有的优惠时，使用普通会员的会员价进行计算
                    $vipdiscount = $userVipData['discount'];
                    $memberDiscount = $validCart[$key]['price'] - ($price - bcmul($price, bcdiv($vipdiscount, 100, 2)));
                }
                $validCart[$key]['discount_desc'] = $memberDiscount ? ("加入" . $userVipData['grade_name'] . '立省' . ($memberDiscount * $cartdata['num'] / 100) . '元') : '';
            }
            //商品总价需要减去会员优惠
            $validCart[$key]['total_fee'] -= $validCart[$key]['discount_fee'];
        }
        return $validCart;
    }

    public function getTotalCart($validCart, $cartTypeService, $shopId, $companyId)
    {
        $shopData = [];
        if (method_exists($cartTypeService, 'getShopData')) {
            $shopData = $cartTypeService->getShopData($companyId, $shopId);
        }
        if (isset($shopData[$shopId])) {
            $data['shop_name'] = $shopData[$shopId]['shop_name'];
            $data['address'] = $shopData[$shopId]['address'];
            if (isset($shopData[$shopId]['mobile'])) {
                $data['mobile'] = $shopData[$shopId]['mobile'];
            }

            if (isset($shopData[$shopId]['lat'])) {
                $data['lat'] = $shopData[$shopId]['lat'];
            }

            if (isset($shopData[$shopId]['lng'])) {
                $data['lng'] = $shopData[$shopId]['lng'];
            }

            if (isset($shopData[$shopId]['hour'])) {
                $data['hour'] = $shopData[$shopId]['hour'];
            }
            $data['is_ziti'] = $shopData[$shopId]['is_ziti'] ?? false;
            $data['is_delivery'] = $shopData[$shopId]['is_delivery'] ?? true;
        } else {
            $data['is_ziti'] = false;
            $data['is_delivery'] = true;
        }
        $data['shop_id'] = $shopId;
        $cartDataValidCart = [];
        foreach ($validCart as $key => $row) {
            $cartDataValidCart[$row['cart_id']] = $row;
        }
        $validCart = $cartDataValidCart;

        // 如果不是最终价格，并且有特有的
        if (method_exists($cartTypeService, 'getTotalCart')) {
            /** @var \OrdersBundle\Services\Cart\DistributorCartObject $cartTypeService  */
            $totalCart = $cartTypeService->getTotalCart($validCart);
            $data['cart_total_price'] = $totalCart['item_fee'] ?? 0; //计算商品促销之前的购物车总价
            $data['item_fee'] = $totalCart['item_fee'] ?? 0; //计算商品促销之前的购物车总价
            $data['cart_total_num'] = $totalCart['cart_total_num'] ?? 0;
            $data['cart_total_count'] = $totalCart['cart_total_count'] ?? 0;
            $data['discount_fee'] = $totalCart['discount_fee'] ?? 0;//购物车商品促销总优惠金额
            $data['total_fee'] = $totalCart['total_fee'] ?? 0; //购物车减去优惠金额的总金额
            $data['member_discount'] = 0;
            foreach ($totalCart['cart_list'] as $cartId => $cart) {
                $data['list'][] = $cart;
                // 判断会员折扣
                if (isset($cart['member_discount']) && $cart['member_discount']) {
                    $data['member_discount'] += $cart['member_discount'];
                }
            }
            $data['used_activity'] = $totalCart['used_activity'];
            $data['used_activity_ids'] = $totalCart['used_activity_ids'];
            $data['activity_grouping'] = [];
            foreach ($totalCart['activity_grouping'] as $activityId => $usedActivity) {
                $usedActivity['cart_ids'] = array_column(array_unique($usedActivity['cart_ids']), null);
                $data['activity_grouping'][] = $usedActivity;
            }
            $data['vipgrade_guide_title']['guide_title_desc'] = $this->userVipBuyGuide;

            $data['gift_activity'] = $totalCart['gift_activity'];
            $data['plus_buy_activity'] = $totalCart['plus_buy_activity'];
        } else {
            $cartTotalPrice = 0;
            $cartTotalNum = 0;
            $cartTotalCount = 0;
            foreach ($validCart as $cart) {
                if (isset($cart['is_checked']) && $cart['is_checked']) {
                    $cartTotalPrice += ($cart['price'] * $cart['num']);
                    $cartTotalNum += $cart['num'];
                    $cartTotalCount += 1;
                }
            }
            $data['item_fee'] = $cartTotalPrice;
            $data['cart_total_price'] = $cartTotalPrice;
            $data['cart_total_num'] = $cartTotalNum;
            $data['cart_total_count'] = $cartTotalCount;
            foreach ($validCart as $cartId => $cart) {
                $data['list'][] = $cart;
            }
            $data['vipgrade_guide_title']['guide_title_desc'] = $this->userVipBuyGuide;
        }

        $result[] = $data;
        return $result;
    }

    //$item_id 加价购商品ID
    public function checkPlusItem($companyId, $userId, $marketing_id, $item_id)
    {
        $filter['company_id'] = $companyId;
        $filter['marketing_id'] = $marketing_id;
        $filter['item_id'] = $item_id;
        if ($item_id) {
            $entityGiftRelRepository = app('registry')->getManager('default')->getRepository(MarketingGiftItems::class);
            $itemInfo = $entityGiftRelRepository->getInfo($filter);
            if (!$itemInfo) {
                $item_id = 0;
            }
        }
        $this->setPlusBuyCart($companyId, $userId, $marketing_id, $item_id);
        return true;
    }

    public function setPlusBuyCart($companyId, $userId, $marketing_id, $item_id)
    {
        $key = "plusbuy:" . sha1($companyId .'userId'.$userId . 'marketingId' .$marketing_id);
        app('redis')->set($key, $item_id);
    }

    public function getPlusBuyCart($companyId, $userId, $marketing_id)
    {
        $key = "plusbuy:" . sha1($companyId . 'userId' .$userId . 'marketingId' .$marketing_id);
        $item_id = app('redis')->get($key);

        return $item_id ?? 0;
    }

    /**
     * 删除加价购的商品
     * @param int $companyId 公司id
     * @param int $userId 用户id
     * @param int $marketing_id 营销id
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deletePlusBuyCart($companyId, $userId, $marketing_id): bool
    {
        $key = "plusbuy:" . sha1($companyId .'userId'.$userId . 'marketingId' .$marketing_id);
        app('redis')->del($key);
        return true;
    }

    /**
     * 重置换购商品
     * @param int $companyId 公司id
     * @param int $userId 用户id
     * @param array $cartList 购物车信息
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function resetPlusBuyCart($companyId, $userId, array $cartList): void
    {
        if (empty($cartList["valid_cart"]) || !is_array($cartList["valid_cart"])) {
            return;
        }
        // 清空换购列表
        foreach ($cartList["valid_cart"] as $item) {
            if (empty($item["plus_buy_activity"]) || !is_array($item["plus_buy_activity"])) {
                continue;
            }
            foreach ($item["plus_buy_activity"] as $activity) {
                if (empty($activity["activity_id"])) {
                    continue;
                }
                // 清空换购列表
                $this->deletePlusBuyCart($companyId, $userId, $activity["activity_id"]);
            }
        }
    }

    /**
     * 套装商品库存
     */
    private function packageItemsStore($itemInfo, $params)
    {
        $store = 0;
        $itemService = new ItemsService();
        if ($params['shop_id'] == 0) {
            $item_bn_arr = explode('+', $itemInfo['item_bn']);
            $filter = [
                'company_id' => $itemInfo['company_id'],
                'item_bn' => $item_bn_arr
            ];
            $package_items_info = $itemService->list($filter, ['store' => 'asc'], 1);
            if (!empty($package_items_info['list'])) {
                $store = $package_items_info['list']['0']['store'];

                return $store;
            }
        }
        //判断是否门店库存
        $distributor_items_repository = app('registry')->getManager('default')->getRepository(DistributorItems::class);
        $filter = [
            'company_id' => $itemInfo['company_id'],
            'item_id' => $itemInfo['item_id'],
            'distributor_id' => $params['shop_id'],
            'is_total_store' => 1
        ];
        $distributor_items_info = $distributor_items_repository->getInfo($filter);
        //非门店库存
        if (empty($distributor_items_info)) {
            $item_bn_arr = explode('+', $itemInfo['item_bn']);
            $filter = [
                'company_id' => $itemInfo['company_id'],
                'item_bn' => $item_bn_arr
            ];
            $package_items_info = $itemService->list($filter, ['store' => 'asc'], 100);
            $package_items_ids = array_column($package_items_info['list'], 'item_id');
            $distributor_items_store_info = $distributor_items_repository->lists(['item_id' => $package_items_ids, 'distributor_id' => $params['shop_id']], ['store' => 'asc'], 1);
            if (empty($distributor_items_store_info['list'])) {
                $store = 0;
            } else {
                $store = $distributor_items_store_info['list']['0']['store'];
            }
        } else {
            $item_bn_arr = explode('+', $itemInfo['item_bn']);
            $filter = [
                'company_id' => $itemInfo['company_id'],
                'item_bn' => $item_bn_arr
            ];
            $package_items_info = $itemService->list($filter, ['store' => 'asc'], 1);
            if (!empty($package_items_info['list'])) {
                $store = $package_items_info['list']['0']['store'];
            }
        }

        return $store;
    }

    /**
     * 获取购物车列表
     */
    public function getCartListNostores($companyId, $userId, $shopId = 0, $cartType = 'cart', $shopType = 'distributor', $isCheckout = false, $iscrossborder = false, $isShopScreen = false, $items = [])
    {
        if ($cartType == 'offline') {
            $cartData = [];
            $offlineCartId = 0;
            foreach ($items as $item) {
                $item['cart_id'] = --$offlineCartId;
                $item['company_id'] = $companyId;
                $item['shop_id'] = $shopId;
                $item['is_checked'] = ($item['is_checked'] ?? 'true') == 'false' ? false : true;
                $cartData[] = $item;
            }
        } else {
            $cartData = $this->__getCartBasicData($companyId, $userId, $shopId, $cartType, $shopType, $isCheckout);
        }
        if ($isCheckout && !$cartData) {
            return false;
            // throw new ResourceException('请在购物车选择需要购买的商品');
        }

        if (!$cartData) {
            return ['invalid_cart' => [], 'valid_cart' => []];
        }
        $cartData = $this->__getValidCartListNostores($companyId, $userId, $cartData, $shopId, $shopType, $isCheckout, $iscrossborder, $isShopScreen);
        if (!$cartData || !$cartData['valid_cart']) {
            if ($isCheckout) {
                return false;
                // throw new ResourceException('购物车商品已失效，请重新结算');
            }
            return ['invalid_cart' => $cartData['invalid_cart'], 'valid_cart' => []];
        }

        if ($cartData['invalid_cart'] && $isCheckout) {
            return false;
            // throw new ResourceException('部分购物车商品已失效，请重新结算');
        }

        // 是否已经检查了库存，默认还没有判断
        $cartData['is_check_store'] = false;
        //检测是否有活动商品，是否是有效的活动商品
        $cartTypeService = $this->getCartTypeService($shopType);
        if (method_exists($cartTypeService, 'formatCartList')) {
            $cartData = $cartTypeService->formatCartList($companyId, $userId, $cartData, $isCheckout);
        }
        if (!$cartData['is_check_store']) {
            foreach ($cartData['valid_cart'] as $row) {
                if ($row['store'] < $row['num'] && $isCheckout) {
                    return false;
                    // throw new ResourceException($row['item_name'] . '库存不足');
                }
                // 组合商品子商品库存计算
                if (isset($row['children']) && is_array($row['children'])) {
                    foreach ($row['children'] as $rowchild) {
                        if ($rowchild['store'] < $rowchild['num'] && $isCheckout) {
                            return false;
                            // throw new ResourceException($rowchild['item_name'] . '库存不足');
                        }
                    }
                }
            }
            $cartData['is_check_store'] = true;
        }

        //处理会员价
        $cartData['valid_cart'] = $this->getCartItemUserGradePrice($cartData['valid_cart'], $companyId, $userId);
        //处理购物车价格计算
        $cartData['valid_cart'] = $this->getTotalCart($cartData['valid_cart'], $cartTypeService, $shopId, $companyId);
        if ($cartData['invalid_cart']) {
            $cartIds = array_column($cartData['invalid_cart'], 'cart_id');
            $this->entityRepository->updateBy(['cart_id' => $cartIds], ['is_checked' => false]);
        }

        foreach ($cartData['valid_cart'] as $key => $val) {
            foreach ($val['list'] as $k => $v) {
                // 跨境商品
                if (($v['type'] ?? 0) == 1) {
                    $cartData['valid_cart'][$key]['list'][$k]['cross_border_taxation'] = bcdiv(bcmul($v['total_fee'], $v['tax_rate'], 0), 100, 0);
                }
            }
        }

        return $cartData;
    }

    /**
     * 无门店，将购物车数据分为有效的数据和无效的数据 function
     * 判断基础商品数据是否有效，基础库中商品下架或者不存在则表示失效了
     *
     * @return array
     */
    private function __getValidCartListNostores($companyId, $userId, $cartList, $shopId, $shopType, $isCheckout, $iscrossborder, $isShopScreen = false)
    {
        $itemIds = array_column($cartList, 'item_id');
        foreach ($cartList as $v) {
            $itemIds = array_merge($itemIds, $v['items_id'] ?? []);
        }
        $itemIds = array_unique(array_filter($itemIds));

        //检查商品是否有效
        $filter = [
            'company_id' => $companyId,
            'item_id' => $itemIds,
        ];
        $itemService = new ItemsService();
        $itemList = $itemService->getSkuItemsList($filter);
        if ($itemList['total_count'] <= 0) {
            return false;
        }

        if ($shopId && $shopType != 'community' && $itemList['total_count'] > 0) {
            $distributorItemsService = new DistributorItemsService();
            $itemList['list'] = $distributorItemsService->getDistributorSkuReplace($companyId, $shopId, $itemList['list']);
        }

        $itemList = array_column($itemList['list'], null, 'item_id');
        $data = $this->HandleValidCartNostores($companyId, $userId, $cartList, $itemList, 'normal', $iscrossborder, $isShopScreen);

        return $data;
    }

    /**
     * 无门店，处理失效商品
     * @param $companyId
     * @param $userId
     * @param $cartList
     * @param $itemList
     * @param $cartType
     * @return mixed
     */
    public function HandleValidCartNostores($companyId, $userId, $cartList, $itemList, $cartType = 'normal', $iscrossborder = false, $isShopScreen = false)
    {
        // 跨境
        if ($iscrossborder == 1) {
            // 产地国信息
            $origincountry = $this->getorigincountry($companyId);
            $origincountry_data = array_column($origincountry, null, 'origincountry_id');
            $origincountry_idall = array_column($origincountry, 'origincountry_id');

            // 全局税率
            $crossborder_set_info = app('registry')->getManager('default')->getRepository(CrossBorderSet::class)->getInfo(['company_id' => $companyId]);
            if (empty($crossborder_set_info)) {
                $default_tax_rate = 0;
            } else {
                $default_tax_rate = $crossborder_set_info['tax_rate'];
            }
        }

        $validCart = []; //购物车有效的商品
        $invalidCart = []; //购物车失效商品
        $packageService = new PackageService();
        foreach ($cartList as $k => $cartdata) {
            $itemId = $cartdata['item_id'];
            if (!($itemList[$itemId] ?? null)) {
                $invalidCart[] = $cartdata;
                continue;
            }
            $itemPic = ($itemList[$itemId]['spec_image_url'] ?? null) ? $itemList[$itemId]['spec_image_url'] : ($itemList[$itemId]['pics'] ?? null);
            if ('package' == ($cartdata['activity_type'] ?? '') && ($cartdata['items_id'] ?? null)) {
                $packageInfo = $packageService->getPackageInfo($companyId, $cartdata['activity_id']);
                if (!$packageInfo) {
                    $invalidCart[] = $cartdata;
                    continue;
                }
                // 判断组合商品是否有效
                $result = $this->packageItem($companyId, $userId, $packageInfo, $cartdata, $itemList);
                if (!$result) {
                    $invalidCart[] = $cartdata;
                    continue;
                }

                $itemId = $cartdata['item_id'];
                $store = $itemList[$itemId]['store'];
                if ($cartdata['cart_id'] && (intval($cartdata['num']) > intval($store))) {
                    // $this->entityRepository->updateOneBy(['cart_id' => $cartdata['cart_id']], ['num' => $store]);
                    // $cartdata['num'] = $store;
                    $invalidCart[] = $cartdata;
                    continue;
                }
                if (intval($cartdata['num']) <= 0) {
                    $invalidCart[] = $cartdata;
                    continue;
                }
                // 获取组合商品已选择sku的商品活动价
                $main_items_price = array_column($packageInfo['main_items'], 'item_price', 'item_id');
                // $cartdata['price'] = $packageInfo['main_item_price'];
                $cartdata['price'] = $main_items_price[$cartdata['item_id']];
                $cartdata['discount_fee'] = 0;
                $cartdata['total_fee'] = $main_items_price[$cartdata['item_id']] * $cartdata['num'];
                // $cartdata['total_fee'] = $packageInfo['main_item_price'] * $cartdata['num'];
                $cartdata['store'] = $itemList[$itemId]['store'];
                $cartdata['market_price'] = $itemList[$itemId]['market_price'];
                $cartdata['brief'] = $itemList[$itemId]['brief'];
                $cartdata['item_type'] = $itemList[$itemId]['item_type'];
                $cartdata['approve_status'] = $itemList[$itemId]['approve_status'];
                $cartdata['item_name'] = $itemList[$itemId]['itemName'];
                $cartdata['pics'] = $itemList[$itemId]['pics'] ? reset($itemList[$itemId]['pics']) : '';
                $cartdata['item_spec_desc'] = $itemList[$itemId]['item_spec_desc'] ?? '';
                $cartdata['parent_id'] = 0;
                $cartdata['is_last_price'] = true;
                foreach ($cartdata['items_id'] as $v) {
                    $children = $cartdata;
                    $children['parent_id'] = $cartdata['cart_id'];
                    $children['price'] = $packageInfo['new_price'][$v];
                    $children['discount_fee'] = 0;
                    $children['total_fee'] = $packageInfo['new_price'][$v] * $cartdata['num'];
                    $children['store'] = $itemList[$v]['store'];
                    $children['market_price'] = $itemList[$v]['market_price'];
                    $children['brief'] = $itemList[$v]['brief'];
                    $children['item_type'] = $itemList[$v]['item_type'];
                    $children['approve_status'] = $itemList[$v]['approve_status'];
                    $children['item_name'] = $itemList[$v]['itemName'];
                    $children['item_id'] = $itemList[$v]['itemId'];
                    $children['pics'] = $itemList[$v]['pics'] ? reset($itemList[$v]['pics']) : '';
                    $children['item_spec_desc'] = $itemList[$v]['item_spec_desc'] ?? '';
                    $children['is_last_price'] = true;
                    $cartdata['packages'][] = $children;
                }
                $validCart[] = $cartdata;
            } else {
                $result = $this->singleItem($companyId, $userId, $cartdata, $itemList);
                if (!$result) {
                    $invalidCart[] = $cartdata;
                    continue;
                }

                $itemId = $cartdata['item_id'];
                $store = $itemList[$itemId]['store'];
                $logisticsStore = 0;
                if ($isShopScreen) {
                    // 只有门店大屏才拆单
                    $logisticsStore = $itemList[$itemId]['logistics_store'] ?? 0;
                }
                if (intval($cartdata['num']) > intval($store)) {
                    if ($cartType == 'salesperson') {
                        if (($cartdata['cart_id'] ?? 0) > 0) {
                            $salespersonCartRepository = app('registry')->getManager('default')->getRepository(SalespersonCart::class);
                            $salespersonCartRepository->updateOneBy(['cart_id' => $cartdata['cart_id']], ['num' => $store]);
                        }
                        $cartdata['num'] = $store;
                    } elseif ($cartType == 'shop_offline') {
                        if (($cartdata['cart_id'] ?? 0) > 0) {
                            $operatorCartRepository = app('registry')->getManager('default')->getRepository(OperatorCart::class);
                            $operatorCartRepository->updateOneBy(['cart_id' => $cartdata['cart_id']], ['num' => $store]);
                        }
                        $cartdata['num'] = $store;
                    } else {
                        $logisticsNum = intval($cartdata['num']) - intval($store);
                        if ($logisticsNum > $logisticsStore) {
                            $logisticsNum = $logisticsStore;
                            if (($cartdata['cart_id'] ?? 0) > 0) {
                                $this->entityRepository->updateOneBy(['cart_id' => $cartdata['cart_id']], ['num' => $store + $logisticsStore]);
                            }
                        }

                        if ($logisticsNum > 0) {
                            $cartdata['logistics_num'] = $logisticsNum;
                        }

                        // $cartdata['num'] = $store;
                    }
                }

                $store += $logisticsStore;

                if (intval($cartdata['num']) + intval($cartdata['logistics_num'] ?? 0) <= 0) {
                    $invalidCart[] = $cartdata;
                    continue;
                }

                // 判断是否为跨境商品
                if ($iscrossborder == 1) {
                    if ($itemList[$itemId]['type'] != 1) {
                        continue;
                    }
                } else {
                    if ($itemList[$itemId]['type'] == 1) {
                        continue;
                    }
                }

                $cartdata['is_last_price'] = false;
                $cartdata['price'] = $itemList[$itemId]['price'];
                $cartdata['discount_fee'] = 0;
                $cartdata['total_fee'] = $itemList[$itemId]['price'] * $cartdata['num'];
                $cartdata['store'] = $store;
                $cartdata['market_price'] = $itemList[$itemId]['market_price'];
                $cartdata['brief'] = $itemList[$itemId]['brief'];
                $cartdata['item_type'] = $itemList[$itemId]['item_type'];
                $cartdata['approve_status'] = $itemList[$itemId]['approve_status'];
                $cartdata['item_name'] = $itemList[$itemId]['itemName'];
                $cartdata['pics'] = $itemList[$itemId]['pics'] ? reset($itemList[$itemId]['pics']) : '';
                $cartdata['item_spec_desc'] = $itemList[$itemId]['item_spec_desc'] ?? '';
                $cartdata['parent_id'] = 0;
                $cartdata['goods_id'] = $itemList[$itemId]['goods_id'] ?? $itemId;
                $cartdata['user_id'] = $userId;
                $cartdata['item_category'] = $itemList[$itemId]['item_category'];

                // 跨境参数
                $cartdata['type'] = $itemList[$itemId]['type'] ?? '0';
                $cartdata['crossborder_tax_rate'] = $itemList[$itemId]['crossborder_tax_rate'] ?? '0';
                $cartdata['taxstrategy_id'] = $itemList[$itemId]['taxstrategy_id'] ?? '0';
                $cartdata['taxation_num'] = $itemList[$itemId]['taxation_num'] ?? '0';
                $cartdata['origincountry_id'] = $itemList[$itemId]['origincountry_id'] ?? '';


                // 产地国信息- 是跨境商品-产地国id不为空，产地国信息存在
                if ($cartdata['type'] == 1 and !empty($cartdata['origincountry_id']) and in_array($cartdata['origincountry_id'], $origincountry_idall)) {
                    $cartdata['origincountry_name'] = $origincountry_data[$cartdata['origincountry_id']]['origincountry_name'];
                    $cartdata['origincountry_img_url'] = $origincountry_data[$cartdata['origincountry_id']]['origincountry_img_url'];
                } else {
                    $cartdata['origincountry_name'] = '';
                    $cartdata['origincountry_img_url'] = '';
                }


                // 跨境商品
                if ($cartdata['type'] == 1) {
                    // 判断商品是否有税率
                    if (!empty($cartdata['crossborder_tax_rate']) and $cartdata['crossborder_tax_rate'] > 0) {
                        $cartdata['tax_rate'] = $cartdata['crossborder_tax_rate'];
                    } else {
                        // 判断主类目
                        $filter['company_id'] = $companyId;
                        $filter['category_id'] = $cartdata['item_category'];
                        $item_category_tax_rate = app('registry')->getManager('default')->getRepository(ItemsCategory::class)->getInfo($filter)['crossborder_tax_rate'];
                        if (!empty($item_category_tax_rate) and $item_category_tax_rate > 0) {
                            $cartdata['tax_rate'] = $item_category_tax_rate;
                        } else {
                            // 使用全局税率
                            $cartdata['tax_rate'] = $default_tax_rate ? $default_tax_rate : 0;
                        }
                    }
                }

                if ($cartdata['num'] > 0) {
                    $validCart[] = $cartdata;
                }

                if (($cartdata['logistics_num'] ?? 0) > 0) {
                    $cartdata['cart_id'] = $cartdata['cart_id'] . '_';
                    $cartdata['num'] = $cartdata['logistics_num'];
                    $cartdata['is_logistics'] = true;
                    $cartdata['is_total_store'] = true;
                    $cartdata['total_fee'] = $itemList[$itemId]['price'] * $cartdata['num'];
                    $validCart[] = $cartdata;
                }
            }
        }
        $data['valid_cart'] = $validCart;
        $data['invalid_cart'] = $invalidCart;
        return $data;
    }

    /**
     * 获取购物车商品数量
     */
    public function countCart($filter, $cartType = 'cart', $iscrossborder, $isShopScreen)
    {
        $cartList = $this->entityRepository->lists($filter);
        if ($cartList['total_count']) {
            $shopIds = array_unique(array_column($cartList['list'], 'shop_id'));
            $cartIds = [];
            foreach ($shopIds as $shopId) {
                $cartData = $this->__getCartBasicData($filter['company_id'], $filter['user_id'], $shopId, $cartType, $filter['shop_type'], false);
                if ($cartData) {
                    $cartData = $this->__getValidCartList($filter['company_id'], $filter['user_id'], $cartData, $shopId, $filter['shop_type'], false, $iscrossborder, $isShopScreen);
                    if (!empty($cartData['invalid_cart'])) {
                        $cartIds = array_merge(array_column($cartData['invalid_cart'], 'cart_id'), $cartIds);
                    }
                }
            }
            if ($cartIds) {
                $filter['cart_id|notIn'] = $cartIds;
            }
        }

        $result = $this->entityRepository->countCart($filter);

        return $result;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
