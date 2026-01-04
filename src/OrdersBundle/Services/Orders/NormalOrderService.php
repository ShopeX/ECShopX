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

use GoodsBundle\Services\ItemStoreService;
use GoodsBundle\Services\ItemsService;
use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Jobs\FinishOrderJob;
use OrdersBundle\Jobs\ConsumptionOrderJob;
use OrdersBundle\Services\CartService;
use OrdersBundle\Traits\SeckillStoreTicket;
use GoodsBundle\Services\ItemsCategoryService;

use PromotionsBundle\Services\LimitService;
use PromotionsBundle\Services\MarketingActivityService;
use PromotionsBundle\Services\PackageService;

use KaquanBundle\Services\UserDiscountService;
use PointBundle\Services\PointMemberService;
use OrdersBundle\Events\OrderProcessLogEvent;
use PromotionsBundle\Services\SpecificCrowdDiscountService;
use SupplierBundle\Services\SupplierOrderService;
use ThirdPartyBundle\Events\ScheduleCancelOrdersEvent;
use OrdersBundle\Entities\OfflinePayment;
use OrdersBundle\Services\OrderInvoiceService;

class NormalOrderService extends AbstractNormalOrder
{
    use SeckillStoreTicket;

    // 订单种类
    public $orderClass = 'normal';

    // 订单类型 实体类订单 服务类订单 等其他订单
    public $orderType = 'normal';

    // 订单是否支持优惠券优惠
    public $isSupportCouponDiscount = true;

    // 订单是否需要进行门店验证
    public $isCheckShopValid = false;

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
    public $gift_activity = [];

    public $orderItemPackagePrice = [];

    //订单参与活动的详情
    public $joinActvityInfo = [];
    // 限时特惠集合
    public $validLimitedTimeSaleAct = [];
    // 限购集合
    public $limitedBuy = [];
    //订单是否支持积分抵扣
    public $isSupportPointDiscount = true;
    // 订单是否支持获取积分
    public $isSupportGetPoint = true;

    // 加价购
    public $plusBuyActivity = [];

    public $itemCart;
    public $MemberDiscount;

    public function scheduleCancelOrders()
    {
        app('log')->info('自动取消订单');
        // 取消订单，每分钟执行一次，当前只处理一分钟内的订单
        // 排除需要单独处理的支付方式：
        // - offline_pay: 由 scheduleOfflinePayCancelOrders 单独处理（需要检查 offline_payment 表的 check_status）
        // - point: 积分支付，通常用于 pointsmall 订单类型，有单独处理
        $pageSize = 20;
        $time = time() + 60;
        $filter = [
            'auto_cancel_time|lt' => $time,
            'order_status' => 'NOTPAY',
            'order_class' => ['normal', 'shopguide', 'employee_purchase'],
            'pay_type|notIn' => ['offline_pay', 'point'],
        ];
        app('log')->info('自动取消订单::filter=====>'.json_encode($filter));
        $totalCount = $this->normalOrdersRepository->count($filter);
        app('log')->info('自动取消订单::totalCount=====>'.$totalCount);
        $totalPage = ceil($totalCount / $pageSize);

        $itemStoreService = new ItemStoreService();
        $marketingService = new MarketingActivityService();
        $limitServer = new LimitService();
        $supplierOrderService = new SupplierOrderService();
        for ($i = 1; $i <= $totalPage; $i++) {
            $result = $this->normalOrdersRepository->getList($filter, 0, $pageSize);
            $orderIds = array_column($result, 'order_id');
            if ($orderIds) {
                $this->normalOrdersRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => 'CANCEL']);
                $this->orderAssociationsRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => 'CANCEL']);

                $orderItems = $this->normalOrdersItemsRepository->getList(['order_id|in' => $orderIds]);
                $limitedTimeSale = $this->orderPromotionsRepository->lists(['moid|in' => $orderIds]); //, 'activity_type'=>''
                if ($limitedTimeSale['total_count'] > 0) {
                    foreach ($limitedTimeSale['list'] as $value) {
                        if ($value['activity_type'] == 'limited_time_sale') {
                            $limitedTimeSale[$value['item_id']] = $value;
                        } elseif (in_array($value['activity_type'], ['full_discount', 'full_gift', 'full_minus'])) {
                            $marketingActivity[$value['user_id']][$value['moid']][$value['activity_id']] = $value['activity_id'];
                        }
                    }
                }
                //扣减会员参与活动次数
                foreach ($result as $order) {
                    //取消订单发票
                    $orderInvoiceService = new OrderInvoiceService();
                    app('log')->info('[scheduleCancelOrders] 取消订单发票', [
                        'company_id' => $order['company_id'],
                        'order_id' => $order['order_id'],
                        'status' => 'cancel'
                    ]);
                    $orderInvoiceService->updateInvoiceStatusCancel($order['company_id'], $order['order_id'], 'cancel');

                    //更新供应商订单状态
                    if ($supplierOrderService->repository->count(['order_id' => $order['order_id']])) {
                        $supplierOrderService->repository->updateBy(['order_id' => $order['order_id']], ['order_status' => 'CANCEL']);
                    }

                    if (isset($marketingActivity[$order['user_id']][$order['order_id']])) {
                        $activity = $marketingActivity[$order['user_id']][$order['order_id']];
                        $marketingService->lessUserJoinMarketingNum($order['company_id'], $order['user_id'], $activity);
                    }
                    //退还积分
                    (new PointMemberService())->cancelOrderReturnBackPoints($order);
                    // 升值积分，额度返回
                    if ($order['uppoint_use'] > 0) {
                        parent::minusOrderUppoints($order['company_id'], $order['user_id'], $order['uppoint_use']);
                    }

                    $orderProcessLog = [
                        'order_id' => $order['order_id'],
                        'company_id' => $order['company_id'],
                        'operator_type' => 'system',
                        'operator_id' => 0,
                        'remarks' => '订单取消',
                        'detail' => '订单单号：' . $order['order_id'] . '，取消订单退款',
                    ];
                    event(new OrderProcessLogEvent($orderProcessLog));

                    $eventData = [
                        'order_id' => $order['order_id'],
                        'company_id' => $order['company_id']
                    ];
                    event(new ScheduleCancelOrdersEvent($eventData));
                    // 内购订单返还额度
                    if ($order['order_class'] == 'employee_purchase') {
                        $employeeService = new \EmployeePurchaseBundle\Services\NormalOrderService();
                        $employeeOrder = $employeeService->getOrderInfo($order['company_id'], $order['order_id']);
                        if (! empty($employeeOrder['orderInfo'])) {
                            $employeeService->restoreItemStoreAndAggregate($employeeOrder['orderInfo']);
                        }
                    }
                }

                foreach ($orderItems['list'] as $row) {
                    if (isset($limitedTimeSale[$row['item_id']])) {
                        $promotion = $limitedTimeSale[$row['item_id']];
                        $totalFee = ($row['num'] * $row['price']);
                        $this->setUserBuysStore($promotion['activity_id'], $promotion['company_id'], $promotion['user_id'], $promotion['item_id'], -$row['num'], -$totalFee);
                    }
                    // 总部发货
                    if ($row['is_total_store']) {
                        $itemStoreService->minusItemStore($row['item_id'], -$row['num'], $row['distributor_id'], true);
                    } else {
                        $itemStoreService->minusItemStore($row['item_id'], -$row['num'], $row['distributor_id'], false);
                    }

                    // 限购商品删除
                    $params = [
                        'company_id' => $row['company_id'],
                        'user_id' => $row['user_id'],
                        'item_id' => $row['item_id'],
                        'number' => $row['num'],
                    ];
                    $limitServer->reduceLimitPerson($params);
                }
                foreach ($result as $orderData) {
                    try {
                        $discountInfo = isset($orderData['discount_info']) ? $orderData['discount_info'] : [];
                        if (!is_array($discountInfo)) {
                            $discountInfo = json_decode($orderData['discount_info'], true);
                            $orderData['discount_info'] = $discountInfo;
                        }
                        if (!$discountInfo) {
                            continue;
                        }
                        $userDiscountService = new UserDiscountService();
                        foreach ($discountInfo as $value) {
                            if ($value && isset($value['coupon_code'])) {
                                $userDiscountService->callbackUserCard($orderData['company_id'], $value['coupon_code'], $orderData['user_id']);
                            }
                            if (($value['type'] ?? '') == 'member_tag_targeted_promotion') {
                                $specificCrowdDiscountService = new SpecificCrowdDiscountService();
                                $specificCrowdDiscountService->setUserTotalDiscount($orderData['company_id'], $orderData['user_id'], $orderData, 'less');
                            }
                        }
                    } catch (\Exception $e) {
                        app('log')->debug('取消订单，优惠券恢复:'.$orderData['order_id'].'---->'.$e->getMessage(). var_export($discountInfo, 1));
                    }
                }
            }
        }
    }

    public function checkoutCartItems($params)
    {
        $cartDataService = $this->getCartTypeService('distributor');
        if (!isset($params['cart_type']) && isset($params['items']) && $params['items']) {
            $shopId = $params['distributor_id'] ?? 0;
            // 如果关闭前端店铺(无门店)，则查询总店的购物车商品
            if ($params['isNostores']) {
                $shopId = 0;
            }
            $cartlist = $cartDataService->getFastBuy($params['company_id'], $params['user_id'], $params['items'], $shopId);
        } else {
            $cartType = $params['cart_type'] ?? 'cart';
            $shopId = $params['distributor_id'] ?? 0;
            // 如果关闭前端店铺(无门店)，则查询总店的购物车商品
            if ($params['isNostores']) {
                $shopId = 0;
            }

            $cartService = new CartService();
            $cartData = $cartService->getCartList($params['company_id'], $params['user_id'], $shopId, $cartType, 'distributor', true, $params['iscrossborder'] ?? 0, $params['isShopScreen'] ?? 0, $params['user_device'], $params['items'] ?? [],$params);
            $cartlist = reset($cartData['valid_cart']);
            if (!$cartlist) {
                throw new ResourceException(trans('OrdersBundle/Order.cart_empty'));
            }
        }

        //单笔订单有效应用的活动id集合
        $this->usedActivity = $cartlist['used_activity_ids'] ?? [];
        $params['items'] = [];
        $packageService = new PackageService();
        foreach ($cartlist['list'] as $cart) {
            $cart['activity_id'] = $cart['activity_id'] ?? null;
            $cart['activity_type'] = $cart['activity_type'] ?? 'normal';
            $cart['items_id'] = $cart['items_id'] ?? [];
            if ($cart['is_checked']) {
                $params['items'][] = [   //订单中的商品数据
                    'item_id' => $cart['item_id'],
                    'num' => $cart['num'],
                    'activity_id' => $cart['activity_id'],
                    'activity_type' => $cart['activity_type'],
                    'items_id' => $cart['items_id'],
                    'is_logistics' => isset($cart['is_logistics']) && ($cart['is_logistics'] === true || $cart['is_logistics'] === 'true'),
                    'is_medicine' => $cart['is_medicine'] ?? 0,
                    'is_prescription' => $cart['is_prescription'] ?? 0,
                ];

                $userId = $cart['user_id'];
                $this->itemCart[$cart['item_id']] = $cart; //根据商品取购物车数据
                if ('package' == $cart['activity_type']) {
                    $packageInfo = $packageService->getPackageInfoPrice($cart['company_id'], $cart['activity_id']);
                    $this->orderItemPackagePrice[$cart['activity_id']][$cart['item_id']] = (int)$packageInfo['main_item_price'];
                    foreach ($packageInfo['new_price'] as $k => $v) {
                        $this->orderItemPackagePrice[$cart['activity_id']][$k] = (int)$v;
                    }
                } else {
                    $this->orderItemPrcie[$cart['item_id']] = (int)$cart['price'];
                }
                if (isset($cart['activity_info']) && $cart['activity_info']) {   //所有有效的活动
                    $this->joinActvityInfo = array_merge($this->joinActvityInfo, $cart['activity_info']);
                }
                if ($cart['promotions'] ?? []) {
                    foreach ($cart['promotions'] as $promotion) {
                        $this->validPromotion[$promotion['marketing_id']] = $promotion; //有效的促销活动列表
                    }
                }
                if (isset($cart['limitedTimeSaleAct'])) {
                    $this->validLimitedTimeSaleAct[$cart['item_id']] = $cart['limitedTimeSaleAct'];
                }
                if (isset($cart['limitedBuy'])) {
                    $this->limitedBuy[$cart['item_id']] = $cart['limitedBuy'];
                }
            }
        }
        $userId = $params['user_id'];
        $this->TotalFee[$userId] = $cartlist['total_fee'];
        $this->TotalDiscountFee[$userId] = $cartlist['discount_fee'] ?? 0;
        $this->MemberDiscount[$userId] = $cartlist['member_discount'] ?? 0;
        $this->gift_activity = $cartlist['gift_activity'] ?? [];
        if (isset($params['cart_type']) && $params['cart_type']=='fastbuy') {
            $this->plusBuyActivity=[];
        }else{
            $this->plusBuyActivity = $cartlist['plus_buy_activity'] ?? [];
        }

        if (isset($cartData['order_third_params'])) {
            $params['order_third_params'] = $cartData['order_third_params'] ?? '';
        }
        return $params;
    }

    /**
     * 获取商品的原始销售价格
     */
    public function getOrderItemOriginalPrice($itemId, $activityId, $activityType)
    {
        // This module is part of ShopEx EcShopX system
        if ('package' == $activityType) {
            return $this->orderItemPackagePrice[$activityId][$itemId];
        } else {
            return $this->orderItemPrcie[$itemId];
        }
    }

    /**
     * 获取商品的最终销售价格
     */
    public function getOrderItemPrice($itemId, $activityId, $activityType)
    {
        if ('package' == $activityType) {
            return $this->orderItemPackagePrice[$activityId][$itemId];
        } else {
            return $this->orderItemPrcie[$itemId];
        }
    }

    public function getOrderItemPromotion($orderData, $isShopScreen = false)
    {
        // This module is part of ShopEx EcShopX system
        $userId = $orderData['user_id'];
        $companyId = $orderData['company_id'];
        if (!($orderData['items'] ?? [])) {
            throw new ResourceException(trans('OrdersBundle/Order.item_data_error'));
        }
        $giftItemArr = [];
        foreach ($orderData['items'] as $key => $orderitem) {
            $itemDiscountInfo = $orderitem['discount_info'] ?? [];

            if ($orderitem['activity_type'] == 'package') {
                $orderData['items'][$key]['order_item_type'] = $orderitem['activity_type'];
                $orderData['items'][$key]['act_id'] = $orderitem['activity_id'];
                $itemId = $orderitem['item_id'];
                $cart = $this->itemCart[$itemId] ?? [];
                if (!$cart) {
                    $cart = $orderitem;
                    // 找到主组合商品信息
                    $mainItem = array_first($orderData['items'], function ($item) use ($orderitem) {
                        return $item['activity_type'] == 'package' && $item['activity_id'] == $orderitem['activity_id'];
                    });
                    $mainCart = $this->itemCart[$mainItem['item_id']];
                    $cart['activity_info'] = $mainCart['activity_info'];
                }
                continue;
            } else {
                $itemId = $orderitem['item_id'];
                $cart = $this->itemCart[$itemId] ?? [];
                if (!$cart) {
                    throw new ResourceException(trans('OrdersBundle/Order.cart_items_changed'));
                }
            }
            $itemDiscountInfo = array_merge($itemDiscountInfo, $cart['activity_info']);
            $orderData['items'][$key]['total_fee'] -= (int)($cart['discount_fee'] ?? 0);
            $orderData['items'][$key]['discount_fee'] = (int)($cart['discount_fee'] ?? 0);
            $orderData['items'][$key]['discount_info'] = $itemDiscountInfo;
            if (isset($cart['activity_price'])) {
                $orderData['items'][$key]['activity_price'] = (int)($cart['activity_price']);    // 活动价
            }
            if (isset($cart['member_price'])) {
                $orderData['items'][$key]['member_price'] = (int)($cart['member_price']);    // 会员价
            }
            if (isset($cart['member_discount'])) {
                $orderData['items'][$key]['member_discount'] = (int)($cart['member_discount']);    // 会员折扣价格
            }

            $activityId = $cart['activity_id'] ?? 0;
            if (in_array($activityId, $this->usedActivity)) {
                $activity = $this->validPromotion[$activityId] ?? [];
                if ($activity) {
                    $activity['activity_id'] = $activityId;
                    $orderData = $this->__preItemsPromotion($orderData, $orderitem, $userId, $itemId, $activity, $cart);
                }
                //if ($activity['marketing_type'] == 'full_gift' && ($activity['gifts'] ?? [])) {
                //    $giftItemArr[$activityId] = $activity['gifts'];
                //}
            }

            if ($this->validLimitedTimeSaleAct[$itemId] ?? []) {
                $activity = $this->validLimitedTimeSaleAct[$itemId];
                $orderData = $this->__preItemsPromotion($orderData, $orderitem, $userId, $itemId, $activity, $cart);
            }

            if ($this->limitedBuy[$itemId] ?? []) {
                $activity = $this->limitedBuy[$itemId];
                $orderData = $this->__preItemsPromotion($orderData, $orderitem, $userId, $itemId, $activity, $cart);
            }
        }

        $orderData['discount_fee'] = $this->TotalDiscountFee[$userId];
        $orderData['goods_discount'] = $this->TotalDiscountFee[$userId];
        $orderData['member_discount'] = $this->MemberDiscount[$userId];                 // 会员折扣总价
        //重新整理订单中的所有优惠信息
        $disInfo = [];
        if (($this->joinActvityInfo ?? null)) {
            $nds = [];
            foreach ($this->joinActvityInfo as $desc) {
                $key = ($desc['type'] ?? '').($desc['id'] ?? 0);
                $disInfo[$key] = $desc;
                if (in_array(($desc['type'] ?? ''), ['member_price', 'limited_time_sale', 'full_minus', 'full_discount'])) {
                    if (isset($nds[$key])) {
                        $nds[$key] += $desc['discount_fee'];
                    } else {
                        $nds[$key] = $desc['discount_fee'];
                    }
                }
            }
            foreach ($disInfo as $k => $value) {
                if (isset($nds[$k])) {
                    $disInfo[$k]['discount_fee'] = $nds[$k];
                }
            }
        }
        $orderData['discount_info'] = array_merge($orderData['discount_info'], $disInfo);
        $orderData['total_fee'] = $this->TotalFee[$userId];

        if ($this->gift_activity && is_array($this->gift_activity)) {
            // 获取营销活动的服务
            $marketingActivityService = new MarketingActivityService();
            foreach ($this->gift_activity as $activityData) {
                if ($activityData['activity_id'] && $activityData['gifts']) {
                    // 获取当前用户参与活动的次数
                    $currentUserJoinLimit = $marketingActivityService->getMarketingJoinNumByUser($companyId, $userId, $activityData['activity_id']);
                    // 获取该活动每个用户最多参数的次数
                    $maxLimit = (int)($activityData["discount_desc"]["max_limit"] ?? 0);
                    // 有参与资格的才可以添加赠品
                    if ($currentUserJoinLimit < $maxLimit) {
                        $orderData = $this->handleGiftItems($activityData, $orderData, $isShopScreen);
                    }
                }
            }
        }

        if ($this->plusBuyActivity) {
            foreach ($this->plusBuyActivity as $activityData) {
                if ($activityData['activity_id'] && ($activityData['plus_item'] ?? [])) {
                    $orderData = $this->handlePlusBuyItems($activityData, $orderData);
                }
            }
        }

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
            'activity_rule' => $activity['rule'] ?? [],
        ];
        return $orderData;
    }

    //赠品加入订单处理
    //商品实付金额 = 优惠券折扣 x (商品原价 - 商品折扣 - (商品折后金额 x 赠品金额合计)/(商品折后金额 + 赠品金额合计))
    //赠品实付金额 = 优惠券折扣 x (赠品金额 - (赠品金额 x 赠品金额合计) / (商品折后金额 + 赠品金额合计))
    private function handleGiftItems($activityData, $orderData, $isShopScreen = false)
    {
        $activityItems = [];
        $totalDiscountFee = 0;
        // 遍历赠品信息
        foreach ($activityData['gifts'] as $itemInfo) {
            if (isset($itemInfo['item_main_cat_id']) && $itemInfo['item_main_cat_id']) {
                $itemsCategoryService = new ItemsCategoryService();
                $item_category_main = $itemsCategoryService->getCategoryPathById($itemInfo['item_main_cat_id'], $itemInfo['company_id'], true);
            } else {
                $item_category_main = [];
            }

            if ($itemInfo['gift_num'] > $itemInfo['store']) {
                $logisticsNum = $itemInfo['gift_num'] - $itemInfo['store'];
                $logisticsStore = 0;
                if ($isShopScreen) {
                    $logisticsStore = $itemInfo['logistics_store'] ?? 0;
                }
                if ($logisticsNum > $logisticsStore) {
                    $logisticsNum = $logisticsStore;
                }

                if ($logisticsNum > 0) {
                    $itemInfo['logistics_num'] = $logisticsNum;
                }

                $key = 'giftSetting:'. $itemInfo['company_id'];
                $setting = app('redis')->connection('companys')->get($key);
                $setting = json_decode($setting, 1);
                // 加开关不影响原来的流程
                if ($setting['check_gift_store'] ?? false) {
                    $itemInfo['gift_num'] = $itemInfo['store'];
                } else {
                    $itemInfo['logistics_num'] = 0;
                }
            }

            $giftItems = [
                'order_id' => $orderData['order_id'],
                'item_id' => $itemInfo['item_id'],
                'goods_id' => $itemInfo['goods_id'],
                'item_bn' => $itemInfo['itemBn'],
                'goods_bn' => $itemInfo['goods_bn'] ?? '',
                'supplier_id' => $itemInfo['supplier_id'] ?? 0,
                'company_id' => $orderData['company_id'],
                'user_id' => $orderData['user_id'],
                'item_name' => $itemInfo['itemName'],
                'templates_id' => $itemInfo['templates_id'] ?: 0,
                'pic' => isset($itemInfo['pics'][0]) ? $itemInfo['pics'][0] : '',
                'num' => $itemInfo['gift_num'], // 购买数量
                'price' => $itemInfo['price'], // 单价
                'activity_price' => 0,
                'discount_fee' => $itemInfo['price'] * $itemInfo['gift_num'], // 优惠总金额
                'discount_info' => [],
                'item_fee' => $itemInfo['price'] * $itemInfo['gift_num'], // 商品总金额
                'cost_fee' => 0, // 商品总金额
                'item_unit' => $itemInfo['item_unit'],
                'total_fee' => 0, //总支付金额
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
                'is_gift' => (isset($itemInfo['is_gift']) && $itemInfo['is_gift'] == 'true') ? true : false,
                'item_spec_desc' => $itemInfo['item_spec_desc'] ?? '',
                'volume' => $itemInfo['volume'] * $itemInfo['gift_num'],
                'weight' => $itemInfo['gift_num'] * $itemInfo['weight'],
                'item_category_main' => $item_category_main,
                'is_profit' => $itemInfo['is_profit'] ?? false,
                'cost_price' => $itemInfo['cost_price'] ?? 0,
                'market_price' => $itemInfo['market_price'] ?? 0,
                'activity_id' => $activityData['activity_id'],
                'supplier_id' => $itemInfo['supplier_id'] ?? 0,
            ];

            if ($giftItems['num'] > 0) {
                $totalDiscountFee += $giftItems['item_fee'];
                array_push($activityItems, $giftItems);
                $orderData['totalItemNum'] += $giftItems['num'];
            }

            if (($itemInfo['logistics_num'] ?? 0) > 0) {
                $giftItems['is_logistics'] = true;
                $giftItems['is_total_store'] = true;
                $giftItems['num'] = $itemInfo['logistics_num'];
                $giftItems['discount_fee'] = $itemInfo['price'] * $itemInfo['logistics_num'];
                $giftItems['item_fee'] = $itemInfo['price'] * $itemInfo['logistics_num'];
                $giftItems['volume'] = $itemInfo['volume'] * $itemInfo['logistics_num'];
                $giftItems['weight'] = $itemInfo['weight'] * $itemInfo['logistics_num'];

                $totalDiscountFee += $giftItems['item_fee'];
                array_push($activityItems, $giftItems);
                $orderData['totalItemNum'] += $giftItems['num'];
            }

            // 将赠品加入商品促销中
            $orderData = $this->__preItemsPromotion($orderData, $giftItems, $orderData['user_id'], $giftItems["item_id"], [
                "activity_id" => $activityData['activity_id'],
                "marketing_type" => $activityData['discount_desc']["type"],
                "marketing_name" => $activityData['discount_desc']["info"],
                "promotion_tag" => [],
                "rule" => $activityData['discount_desc']["rule"],
            ], [
                "activity_info" => $activityData,
            ]);

            unset($giftItems);
        }
        //满赠 优惠的金额 就是赠品的销售价*赠品数量
        $orderData['discount_fee'] += $totalDiscountFee;
        $orderData['item_fee'] += $totalDiscountFee;
        $discountInfo = $activityData['discount_desc'] ?? [];
        $discountInfo['discount_fee'] = $totalDiscountFee;
        array_push($orderData['discount_info'], $discountInfo);

        $orderData['items'] = array_merge($orderData['items'], $activityItems);
        $orderData['items'] = array_values($orderData['items']);

        return $orderData;
    }

    //加价购商品加入订单处理
    private function handlePlusBuyItems($activityData, $orderData)
    {
        $activityItems = [];
        if ($activityData['activity_item_ids'] ?? []) {
            foreach ($orderData['items'] as $k => $items) {
                if (in_array($items['item_id'], $activityData['activity_item_ids'])) {
                    $activityItems[] = $items;
                    unset($orderData['items'][$k]);
                }
            }
        }

        $itemInfo = $activityData['plus_item'];
        if (isset($itemInfo['item_main_cat_id']) && $itemInfo['item_main_cat_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $item_category_main = $itemsCategoryService->getCategoryPathById($itemInfo['item_main_cat_id'], $itemInfo['company_id'], true);
        } else {
            $item_category_main = [];
        }

        $plusItem = [
            'order_id' => $orderData['order_id'],
            'item_id' => $itemInfo['item_id'],
            'goods_id' => $itemInfo['goods_id'],
            'item_bn' => $itemInfo['itemBn'],
            'goods_bn' => $itemInfo['goods_bn'] ?? '',
            'company_id' => $orderData['company_id'],
            'user_id' => $orderData['user_id'],
            'item_name' => $itemInfo['itemName'],
            'templates_id' => $itemInfo['templates_id'] ?: 0,
            'pic' => isset($itemInfo['pics'][0]) ? $itemInfo['pics'][0] : '',
            'num' => $itemInfo['gift_num'], // 购买数量
            'price' => $itemInfo['price'], // 单价
            'activity_price' => $itemInfo['plus_price'],
            'discount_fee' => $itemInfo['price'] - $itemInfo['plus_price'], // 单价
            'discount_info' => [],
            'item_fee' => $itemInfo['price'] * $itemInfo['gift_num'], // 商品总金额
            'cost_fee' => 0, // 商品总金额
            'item_unit' => $itemInfo['item_unit'],
            'total_fee' => $itemInfo['plus_price'] * $itemInfo['gift_num'], // 商品总金额
            'rebate' => 0, // 单个商品店奖金金额
            'total_rebate' => 0, // 商品总店铺奖金金额
            'distributor_id' => $orderData['distributor_id'] ?? 0,
            'mobile' => $orderData['mobile'] ?? '',
            'is_total_store' => $itemInfo['is_total_store'] ?? true,
            'shop_id' => $orderData['shop_id'] ?? 0,
            'fee_rate' => $orderData['fee_rate'] ?? '',
            'fee_type' => $orderData['fee_type'] ?? '',
            'fee_symbol' => $orderData['fee_symbol'] ?? '',
            'order_item_type' => 'plus_buy',
            'is_gift' => (isset($itemInfo['is_gift']) && $itemInfo['is_gift'] == 'true') ? true : false,
            'item_spec_desc' => $itemInfo['item_spec_desc'] ?? '',
            'volume' => 0,
            'activity_id' => $activityData['activity_id'],
            'item_category_main' => $item_category_main,
            'cost_price' => $itemInfo['cost_price'] ?? 0,
            'market_price' => $itemInfo['market_price'] ?? 0,
            'supplier_id' => $itemInfo['supplier_id'] ?? 0,
        ];

        // 将加价购加入商品促销中
        $orderData = $this->__preItemsPromotion($orderData, $plusItem, $orderData['user_id'], $plusItem["item_id"], [
            "activity_id"    => $activityData['activity_id'],
            "marketing_type" => $activityData['discount_desc']["type"],
            "marketing_name" => $activityData['discount_desc']["info"],
            "promotion_tag"  => [],
            "rule"           => $activityData['discount_desc']["rule"],
        ], [
            "activity_info" => $activityData,
        ]);

        $orderData['discount_fee'] += $plusItem['discount_fee'];
        $orderData['item_fee'] += $plusItem['item_fee'];
        $orderData['total_fee'] += $plusItem['total_fee'];
        $orderData['totalItemNum'] += $plusItem['num'];
        $discountInfo = $activityData['discount_desc'] ?? [];
        $discountInfo['discount_fee'] = $plusItem['discount_fee'];
        array_push($orderData['discount_info'], $discountInfo);

        array_push($activityItems, $plusItem);
        foreach ($activityItems as $key => $item) {
            $discountInfo['discount_fee'] = 0;
            if ($item['item_id'] == $plusItem['item_id']) {
                $discountInfo['discount_fee'] = $plusItem['discount_fee'];
            }
            $activityItems[$key]['discount_info'][] = $discountInfo;
        }

        $orderData['items'] = array_merge($orderData['items'], $activityItems);
        $orderData['items'] = array_values($orderData['items']);
        return $orderData;
    }

    /**
     * 获取常购清单
     *
     * @param int $companyId
     * @param int $userId
     * @return void
     */
    public function getFrequentItemList($companyId, $userId)
    {
        $key = "user:frequent:item:c:" . $companyId . ":u:" . $userId;
        $result = json_decode(app('redis')->connection('default')->get($key), true);
        if (!$result) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb = $qb->select('onoi.item_id,count(*) count')
            ->from('orders_normal_orders', 'ono')
            ->leftjoin('ono', 'orders_normal_orders_items', 'onoi', 'ono.order_id = onoi.order_id')
            ->andWhere($qb->expr()->eq('ono.company_id', $qb->expr()->literal($companyId)))
            ->andWhere($qb->expr()->eq('ono.user_id', $qb->expr()->literal($userId)))
            ->andWhere($qb->expr()->eq('onoi.company_id', $qb->expr()->literal($companyId)))
            ->andWhere($qb->expr()->eq('onoi.user_id', $qb->expr()->literal($userId)))
            ->andWhere($qb->expr()->eq('ono.pay_status', $qb->expr()->literal('PAYED')))
            ->groupBy('onoi.item_id')
            ->orderBy('count', 'desc');
            $result = $qb->execute()->fetchAll();
            app('redis')->connection('default')->set($key, json_encode($result));
            app('redis')->connection('default')->expire($key, 3600);
        }
        $itemList = [];
        if ($result) {
            $itemIds = array_column($result, 'item_id');
            $itemCount = array_column($result, null, 'item_id');
            $itemService = new ItemsService();
            $itemListTemp = $itemService->getItemsList(['item_id|in' => $itemIds], 1, 5);
            $itemList = $itemListTemp['list'] ?? [];
            foreach ($itemList as &$v) {
                $v['buy_num'] = $itemCount[$v['item_id']]['count'] ?? 0;
            }
        }

        return $itemList;
    }


    /**
     * 按照时间段获取常购清单
     *
     * @param int $companyId
     * @param int $userId
     * @param int $timeRange 0:一年内 1:半年内 2:三个月内
     * @return void
     */
    public function getFrequentItemListByTime($companyId, $userId, $timeRange = '0', $cols = '*')
    {
        $now = time();
        if ($timeRange == '0') {
            $time = strtotime("-1 year");
        } elseif ($timeRange == '1') {
            $time = strtotime("-6 month");
        } elseif ($timeRange == '2') {
            $time = strtotime("-3 month");
        } else {
            throw new ResourceException(trans('OrdersBundle/Order.time_period_invalid'));
        }
        $key = "user:frequent:item:c:" . $companyId . ":u:" . $userId . ":p:".$timeRange;
        $result = json_decode(app('redis')->connection('default')->get($key), true);
        if (!$result) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb = $qb->select('onoi.item_id, count(*) count,sum(num) sum')
            ->from('orders_normal_orders', 'ono')
            ->leftjoin('ono', 'orders_normal_orders_items', 'onoi', 'ono.order_id = onoi.order_id')
            ->andWhere($qb->expr()->eq('ono.company_id', $qb->expr()->literal($companyId)))
            ->andWhere($qb->expr()->eq('ono.user_id', $qb->expr()->literal($userId)))
            ->andWhere($qb->expr()->eq('onoi.company_id', $qb->expr()->literal($companyId)))
            ->andWhere($qb->expr()->eq('onoi.user_id', $qb->expr()->literal($userId)))
            ->andWhere($qb->expr()->eq('ono.pay_status', $qb->expr()->literal('PAYED')))
            ->andWhere($qb->expr()->gte('ono.create_time', $qb->expr()->literal($time)))
            ->groupBy('onoi.item_id')
            ->orderBy('count', 'desc');
            $result = $qb->execute()->fetchAll();
            app('redis')->connection('default')->set($key, json_encode($result));
            app('redis')->connection('default')->expire($key, 3600);
        }
        $itemList = [];
        if ($result) {
            $itemIds = array_column($result, 'item_id');
            $itemCount = array_column($result, null, 'item_id');
            $itemService = new ItemsService();
            $itemListTemp = $itemService->getItemsList(['item_id|in' => $itemIds]);
            $itemList = $itemListTemp['list'] ?? [];
            foreach ($itemList as &$v) {
                $v['buy_num'] = $itemCount[$v['item_id']]['count'] ?? 0;
                $v['sales_num'] = $itemCount[$v['item_id']]['sum'] ?? 0;
            }
        }

        return $itemList;
    }

    /**
     * 获取常购商品类目
     *
     * @param int $companyId
     * @param int $userId
     * @return void
     */
    public function getFrequentCategoryList($companyId, $userId)
    {
        $key = "user:frequent:category:c:" . $companyId . ":u:" . $userId;
        $result = json_decode(app('redis')->connection('default')->get($key), true);
        if (!$result) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb = $qb->select('i.item_category,count(*) count')
                ->from('orders_normal_orders', 'ono')
                ->leftjoin('ono', 'orders_normal_orders_items', 'onoi', 'ono.order_id = onoi.order_id')
                ->leftjoin('onoi', 'items', 'i', 'onoi.item_id = i.item_id')
                ->andWhere($qb->expr()->eq('ono.company_id', $qb->expr()->literal($companyId)))
                ->andWhere($qb->expr()->eq('ono.user_id', $qb->expr()->literal($userId)))
                ->andWhere($qb->expr()->eq('onoi.company_id', $qb->expr()->literal($companyId)))
                ->andWhere($qb->expr()->eq('onoi.user_id', $qb->expr()->literal($userId)))
                ->andWhere($qb->expr()->eq('ono.pay_status', $qb->expr()->literal('PAYED')))
                ->groupBy('i.item_category')
                ->orderBy('count', 'desc');
            $result = $qb->execute()->fetchAll();
            app('redis')->connection('default')->set($key, json_encode($result));
            app('redis')->connection('default')->expire($key, 3600);
        }
        $itemCategoryList = [];
        if ($result) {
            $itemCategoryIds = array_column($result, 'item_category');
            $itemCategoryCount = array_column($result, null, 'item_category');
            $ItemsCategoryService = new ItemsCategoryService();
            $itemCategoryListTemp = $ItemsCategoryService->lists(['category_id' => $itemCategoryIds], ["created" => "DESC"], 5, 1);
            $itemCategoryList = $itemCategoryListTemp['list'] ?? [];
            foreach ($itemCategoryList as &$v) {
                $v['buy_num'] = $itemCategoryCount[$v['category_id']]['count'] ?? 0;
            }
        }

        return $itemCategoryList;
    }

    /**
     * 无门店， 检查购物车数据
     * @param $params
     * @return mixed
     */
    public function checkoutCartItemsNostores($params)
    {
        $cartDataService = $this->getCartTypeService('distributor');
        if (!isset($params['cart_type']) && isset($params['items']) && $params['items']) {
            $shopId = $params['cart_distributor_id'] ?? 0;
            $cartlist = $cartDataService->getFastBuy($params['company_id'], $params['user_id'], $params['items'], $shopId);
        } else {
            $cartType = $params['cart_type'] ?? 'cart';
            $shopId = $params['cart_distributor_id'] ?? 0;

            $cartService = new CartService();
            $cartData = $cartService->getCartListNostores($params['company_id'], $params['user_id'], $shopId, $cartType, 'distributor', true, $params['iscrossborder'] ?? 0, $params['isShopScreen'] ?? 0, $params['items'] ?? []);

            if (!$cartData) {
                return false;
            }
            $cartlist = reset($cartData['valid_cart']);
            if (!$cartlist) {
                return false;
            }
        }
        //单笔订单有效应用的活动id集合
        $this->usedActivity = $cartlist['used_activity_ids'] ?? [];
        $params['items'] = [];
        $packageService = new PackageService();
        foreach ($cartlist['list'] as $cart) {
            $cart['activity_id'] = $cart['activity_id'] ?? null;
            $cart['activity_type'] = $cart['activity_type'] ?? 'normal';
            $cart['items_id'] = $cart['items_id'] ?? [];
            if ($cart['is_checked']) {
                $params['items'][] = [   //订单中的商品数据
                    'item_id' => $cart['item_id'],
                    'num' => $cart['num'],
                    'activity_id' => $cart['activity_id'],
                    'activity_type' => $cart['activity_type'],
                    'items_id' => $cart['items_id'],
                    'is_logistics' => isset($cart['is_logistics']) && ($cart['is_logistics'] === true || $cart['is_logistics'] === 'true'),
                ];

                $userId = $cart['user_id'];
                $this->itemCart[$cart['item_id']] = $cart; //根据商品取购物车数据
                if ('package' == $cart['activity_type']) {
                    $packageInfo = $packageService->getPackageInfoPrice($cart['company_id'], $cart['activity_id']);
                    $this->orderItemPackagePrice[$cart['activity_id']][$cart['item_id']] = (int)$packageInfo['main_item_price'];
                    foreach ($packageInfo['new_price'] as $k => $v) {
                        $this->orderItemPackagePrice[$cart['activity_id']][$k] = (int)$v;
                    }
                } else {
                    $this->orderItemPrcie[$cart['item_id']] = (int)$cart['price'];
                }
                if (isset($cart['activity_info']) && $cart['activity_info']) {   //所有有效的活动
                    $this->joinActvityInfo = array_merge($this->joinActvityInfo, $cart['activity_info']);
                }
                if ($cart['promotions'] ?? []) {
                    foreach ($cart['promotions'] as $promotion) {
                        $this->validPromotion[$promotion['marketing_id']] = $promotion; //有效的促销活动列表
                    }
                }
                if (isset($cart['limitedTimeSaleAct'])) {
                    $this->validLimitedTimeSaleAct[$cart['item_id']] = $cart['limitedTimeSaleAct'];
                }
                if (isset($cart['limitedBuy'])) {
                    $this->limitedBuy[$cart['item_id']] = $cart['limitedBuy'];
                }
            }
        }
        $userId = $params['user_id'];
        $this->TotalFee[$userId] = $cartlist['total_fee'];
        $this->TotalDiscountFee[$userId] = $cartlist['discount_fee'] ?? 0;
        $this->MemberDiscount[$userId] = $cartlist['member_discount'] ?? 0;
        $this->gift_activity = $cartlist['gift_activity'] ?? [];
        $this->plusBuyActivity = $cartlist['plus_buy_activity'] ?? [];

        if (isset($cartData['order_third_params'])) {
            $params['order_third_params'] = $cartData['order_third_params'] ?? '';
        }
        return $params;
    }

    public function afterCreateOrder(array $orderData): void
    {
        // 获取用户id
        $userId = (int)($orderData['user_id'] ?? 0);
        // 删除加购商品
        $this->deletePlusBuyCart($userId);
    }

    /**
     * 删除购物车中的加购商品
     * @param int $userId
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deletePlusBuyCart(int $userId): void
    {
        if (!is_array($this->plusBuyActivity)) {
            return;
        }

        $cartService = new CartService();

        foreach ($this->plusBuyActivity as $activityItem) {
            if (empty($activityItem["plus_item"]) || !is_array($activityItem["plus_item"])) {
                continue;
            }
            $companyId = (int)($activityItem["plus_item"]["company_id"] ?? 0);
            $marketingId = (int)($activityItem["plus_item"]["marketing_id"] ?? 0);
            $cartService->deletePlusBuyCart($companyId, $userId, $marketingId);
        }
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

    public function scheduleFinishOrders()
    {
        $gotoJob = (new FinishOrderJob())->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        return true;
    }

    /**
     * 累加消费金额，对会员进行升级
     * @return [type] [description]
     */
    public function scheduleConsumptionOrders()
    {
        // 数云模式，不执行
        if (config('common.oem-shuyun')) {
            return true;
        }
        $gotoJob = (new ConsumptionOrderJob())->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        return true;
    }

    /**
     * 自动取消线下转账支付方式的订单
     */
    public function scheduleOfflinePayCancelOrders()
    {
        app('log')->debug('自动取消线下转账支付方式的订单');
        $filterSql = $this->buildFilter();
        $totalCount = $this->countJoinOfflinePay($filterSql);
        app('log')->debug('自动取消线下转账支付方式的订单::totalCount=====>'.$totalCount);
        if ($totalCount === 0) {
            return true;
        }
        $pageSize = 20;
        $totalPage = ceil($totalCount / $pageSize);
        $this->processOrdersInBatches($totalPage, $pageSize);
    }

    /**
     * 构建过滤条件
     */
    private function buildFilter()
    {
        $sql = "WHERE `o`.`auto_cancel_time` < ".(time() + 60). " ";
        $sql .= "AND `o`.`order_status` = 'NOTPAY' ";
        $sql .= "AND `o`.`order_class` IN ('normal', 'shopguide', 'employee_purchase') ";
        $sql .= "AND `o`.`pay_type` = 'offline_pay' ";
        $sql .= "AND (`of`.`check_status` IS NULL OR `of`.`check_status` != 0)";
        return $sql;
    }

    /**
     * 获取总数
     */
    private function countJoinOfflinePay($filterSql)
    {
        $conn = app('registry')->getConnection('default');
        $sql = "SELECT COUNT(*) FROM `orders_normal_orders` AS `o` ";
        $sql .= "LEFT JOIN `offline_payment` AS `of` ON `o`.`order_id` = `of`.`order_id` ";
        $sql .= $filterSql;
        app('log')->debug('自动取消线下转账支付方式的订单::countJoinOfflinePay=====>'.$sql);
        return $conn->executeQuery($sql)->fetchColumn();
    }

    /**
     * 获取列表
     */
    private function getListJoinOfflinePay($filterSql, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;
        $conn = app('registry')->getConnection('default');
        $sql = "SELECT o.* FROM `orders_normal_orders` AS `o` ";
        $sql .= "LEFT JOIN `offline_payment` AS `of` ON o.order_id = of.order_id ";
        $sql .= $filterSql;
        $sql .= " LIMIT {$pageSize} OFFSET {$offset}";
        app('log')->debug('自动取消线下转账支付方式的订单::getListJoinOfflinePay=====>'.$sql);
        return $conn->executeQuery($sql)->fetchAll();
    }

    /**
     * 处理订单
     */
    private function processOrdersInBatches($totalPage, $pageSize)
    {
        $itemStoreService = new ItemStoreService();
        $marketingService = new MarketingActivityService();
        $limitServer = new LimitService();
        $supplierOrderService = new SupplierOrderService();
        $filterSql = $this->buildFilter();
        for ($i = 1; $i <= $totalPage; $i++) {
            $results = $this->getListJoinOfflinePay($filterSql, 1, $pageSize);
            $this->processResults($results, $itemStoreService, $marketingService, $limitServer, $supplierOrderService);
        }
    }

    /**
     * 处理订单
     */
    private function processResults(array $results, ItemStoreService $itemStoreService,MarketingActivityService $marketingService, LimitService $limitServer, SupplierOrderService $supplierOrderService)
    {
        try {
            $orderIds = array_column($results, 'order_id');
            if ($orderIds) {
                $this->updateOrderStatus($orderIds, 'CANCEL');
                $this->processOrderItems($results, $marketingActivity, $itemStoreService, $limitServer);
                $this->processMarketingActivities($results, $marketingActivity, $marketingService);
                $this->processSupplierOrders($results, $supplierOrderService);
                $this->logOrderProcess($results);
                $this->restoreEmployeePurchase($results);
                $this->restoreDiscounts($results);
                $this->updateOfflinePayments($results);
            }
        } catch (\Exception $e) {
            // 这里处理整个过程中的任何异常
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
            ];
            app('log')->debug('自动取消线下转账支付方式的订单::processResults发生错误::' . json_encode($error));
        }
    }

    /**
     * 更新订单状态为取消。
     *
     * @param array $orderIds 订单ID数组。
     * @param string $newStatus 新的订单状态。
     */
    private function updateOrderStatus(array $orderIds, string $newStatus)
    {
        try {
            // 更新主订单表
            $this->normalOrdersRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => $newStatus]);
            // 更新订单关联表
            $this->orderAssociationsRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => $newStatus]);

            // 记录日志
            app('log')->info('自动取消线下转账支付方式的订单::订单状态更新为取消：' . json_encode($orderIds));
        } catch (\Exception $e) {
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
            ];
            app('log')->debug('自动取消线下转账支付方式的订单::updateOrderStatus发生错误::' . json_encode($error));
        }
    }

    /**
     * 处理订单项。
     *
     * @param array $results 订单结果数组。
     * @param ItemStoreService $itemStoreService 商品库存服务。
     * @param LimitService $limitServer 限购服务。
     */
    private function processOrderItems(array $results, &$marketingActivity , ItemStoreService $itemStoreService, LimitService $limitServer)
    {
        try {
            $orderIds = array_column($results, 'order_id');
            $orderItems = $this->normalOrdersItemsRepository->getList(['order_id|in' => $orderIds]);
            if ($orderItems['total_count'] == 0) {
                app('log')->debug('自动取消线下转账支付方式的订单::processOrderItems发生错误::未查询到子单数据');
                return false;
            }
            $limitedTimeSale = $this->orderPromotionsRepository->lists(['moid|in' => $orderIds]); //, 'activity_type'=>''
            $marketingActivity = [];
            if ($limitedTimeSale['total_count'] > 0) {
                foreach ($limitedTimeSale['list'] as $value) {
                    if ($value['activity_type'] == 'limited_time_sale') {
                        $limitedTimeSale[$value['item_id']] = $value;
                    } elseif (in_array($value['activity_type'], ['full_discount', 'full_gift', 'full_minus'])) {
                        $marketingActivity[$value['user_id']][$value['moid']][$value['activity_id']] = $value['activity_id'];
                    }
                }
            }
            foreach ($orderItems['list'] as $row) {
                if (isset($limitedTimeSale[$row['item_id']])) {
                    $promotion = $limitedTimeSale[$row['item_id']];
                    $totalFee = ($row['num'] * $row['price']);
                    $this->setUserBuysStore($promotion['activity_id'], $promotion['company_id'], $promotion['user_id'], $promotion['item_id'], -$row['num'], -$totalFee);
                }
                if ($row['is_total_store']) {
                    $itemStoreService->minusItemStore($row['item_id'], -$row['num'], $row['distributor_id'], true);
                } else {
                    $itemStoreService->minusItemStore($row['item_id'], -$row['num'], $row['distributor_id'], false);
                }
                $params = [
                    'company_id' => $row['company_id'],
                    'user_id' => $row['user_id'],
                    'item_id' => $row['item_id'],
                    'number' => $row['num'],
                ];
                $limitServer->reduceLimitPerson($params);
            }
        } catch (\Exception $e) {
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
            ];
            app('log')->debug('自动取消线下转账支付方式的订单::processOrderItems发生错误::' . json_encode($error));
        }
    }

    /**
     * 处理营销活动。
     *
     * @param array $results 订单结果数组。
     * @param MarketingActivityService $marketingService 营销活动服务。
     */
    private function processMarketingActivities(array $results, array $marketingActivity, MarketingActivityService $marketingService)
    {
        foreach ($results as $order) {
            try {
                if (isset($marketingActivity[$order['user_id']][$order['order_id']])) {
                    $activity = $marketingActivity[$order['user_id']][$order['order_id']];
                    $marketingService->lessUserJoinMarketingNum($order['company_id'], $order['user_id'], $activity);
                }
            } catch (\Exception $e) {
                app('log')->debug('自动取消线下转账支付方式的订单::processMarketingActivities发生错误::' . $order['order_id'] . '---->' . $e->getMessage());
            }
        }
    }

    /**
     * 处理供应商订单。
     *
     * @param array $results 订单结果数组。
     * @param SupplierOrderService $supplierOrderService 供应商订单服务。
     */
    private function processSupplierOrders(array $results, SupplierOrderService $supplierOrderService)
    {
        foreach ($results as $order) {
            try {
                if ($supplierOrderService->repository->count(['order_id' => $order['order_id']])) {
                    $supplierOrderService->repository->updateBy(['order_id' => $order['order_id']], ['order_status' => 'CANCEL']);
                }
                //退还积分
                (new PointMemberService())->cancelOrderReturnBackPoints($order);
                // 升值积分，额度返回
                if ($order['uppoint_use'] > 0) {
                    parent::minusOrderUppoints($order['company_id'], $order['user_id'], $order['uppoint_use']);
                }
            } catch (\Exception $e) {
                app('log')->debug('自动取消线下转账支付方式的订单::processSupplierOrders发生错误::' . $order['order_id'] . '---->' . $e->getMessage());
            }
        }
    }

    /**
     * 记录订单处理日志。
     *
     * @param array $results 订单结果数组。
     */
    private function logOrderProcess(array $results)
    {
        foreach ($results as $order) {
            $orderProcessLog = [
                'order_id' => $order['order_id'],
                'company_id' => $order['company_id'],
                'operator_type' => 'system',
                'operator_id' => 0,
                'remarks' => '订单取消',
                'detail' => '订单单号：' . $order['order_id'] . '，取消订单退款',
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            $eventData = [
                'order_id' => $order['order_id'],
                'company_id' => $order['company_id']
            ];
            event(new ScheduleCancelOrdersEvent($eventData));
        }
    }

    /**
     * 恢复员工购买。
     *
     * @param array $results 订单结果数组。
     */
    private function restoreEmployeePurchase(array $results)
    {
        foreach ($results as $order) {
            try {
                // 内购订单返还额度
                if ($order['order_class'] == 'employee_purchase') {
                    $employeeService = new \EmployeePurchaseBundle\Services\NormalOrderService();
                    $employeeOrder = $employeeService->getOrderInfo($order['company_id'], $order['order_id']);
                    if (! empty($employeeOrder['orderInfo'])) {
                        $employeeService->restoreItemStoreAndAggregate($employeeOrder['orderInfo']);
                    }
                }
            } catch (\Exception $e) {
                app('log')->debug('自动取消线下转账支付方式的订单::restoreEmployeePurchase发生错误::' . $order['order_id'] . '---->' . $e->getMessage());
            }
        }
    }

    /**
     * 恢复折扣。
     *
     * @param array $results 订单结果数组。
     */
    private function restoreDiscounts(array $results)
    {
        foreach ($results as $orderData) {
            try {
                $discountInfo = isset($orderData['discount_info']) ? $orderData['discount_info'] : [];
                if (!is_array($discountInfo)) {
                    $discountInfo = json_decode($orderData['discount_info'], true);
                    $orderData['discount_info'] = $discountInfo;
                }
                if (!$discountInfo) {
                    continue;
                }
                $userDiscountService = new UserDiscountService();
                foreach ($discountInfo as $value) {
                    if ($value && isset($value['coupon_code'])) {
                        $userDiscountService->callbackUserCard($orderData['company_id'], $value['coupon_code'], $orderData['user_id']);
                    }
                    if (($value['type'] ?? '') == 'member_tag_targeted_promotion') {
                        $specificCrowdDiscountService = new SpecificCrowdDiscountService();
                        $specificCrowdDiscountService->setUserTotalDiscount($orderData['company_id'], $orderData['user_id'], $orderData, 'less');
                    }
                }
            } catch (\Exception $e) {
                app('log')->debug('自动取消线下转账支付方式的订单::restoreDiscounts发生错误::' . $orderData['order_id'] . '---->' . $e->getMessage()  . ',discountInfo::' . json_encode($discountInfo));
            }
        }
    }

    /**
     * 更新线下支付状态
     */
    private function updateOfflinePayments(array $results)
    {
        $this->offlinePaymentRepository = app('registry')->getManager('default')->getRepository(OfflinePayment::class);
        foreach ($results as $order) {
            try {
                $this->offlinePaymentRepository->updateBy(['order_id' => $order['order_id']], ['check_status' => 9]);
            } catch (\Exception $e) {
                app('log')->debug('自动取消线下转账支付方式的订单::updateOfflinePayments发生错误::' . $e->getMessage());
            }
        }
    }
}
