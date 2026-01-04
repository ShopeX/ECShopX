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
use CompanysBundle\Services\OperatorCartService;
use GoodsBundle\Services\ItemsCategoryService;
use CompanysBundle\Services\OperatorsService;

//use PromotionsBundle\Traits\GetAllItemAcitivityData;

class ShopadminNormalOrderService extends AbstractNormalOrder
{
    //use GetAllItemAcitivityData;
    // 订单种类
    public $orderClass = 'shopadmin';

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
    public $isCheckWhitelistValid = false;

    //商品使用的商品促销id集合
    public $usedActivity = [];

    //有效的商品促销列表
    public $validPromotion = [];

    public $TotalFee = [];

    public $TotalDiscountFee = [];

    public $gift_activity = [];

    //订单是否支持积分抵扣
    public $isSupportPointDiscount = true;

    // 订单是否支持获取积分
    public $isSupportGetPoint = false;

    public $isSupportMarkDown = true;

    //订单参与活动的详情
    public $joinActvityInfo = [];

    // 限时特惠集合
    public $validLimitedTimeSaleAct = [];

    // 限购集合
    public $limitedBuy = [];

    public $itemCart;
    public $MemberDiscount;

    public function checkoutCartItems($params)
    {
        $userId = $params['user_id'];
        $filter = [
            'operator_id' => $params['operator_id'],
            'distributor_id' => $params['distributor_id'],
            'company_id' => $params['company_id'],
        ];
        $operatorCartService = new OperatorCartService();
        $cartData = $operatorCartService->getCartdataList($filter, $userId, true);
        $cartlist = reset($cartData['valid_cart']);
        if (!$cartlist) {
            throw new ResourceException('购物车为空');
        }
        //单笔订单有效应用的活动id集合
        $this->usedActivity = $cartlist['used_activity_ids'] ?? [];
        $params['items'] = [];
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
                ];

                $this->itemCart[$cart['item_id']] = $cart; //根据商品取购物车数据

                if (isset($cart['activity_info']) && $cart['activity_info']) {
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

        $this->TotalFee[$userId] = $cartlist['total_fee'];
        $this->TotalDiscountFee[$userId] = $cartlist['discount_fee'] ?? 0;
        $this->MemberDiscount[$userId] = $cartlist['member_discount'] ?? 0;
        $this->gift_activity = $cartlist['gift_activity'] ?? [];

        return $params;
    }

    public function formatOrderData($orderData, $params, $isCheck)
    {
        $orderData['operator_id'] = $params['operator_id'];

        return $orderData;
    }

    public function emptyCart($params)
    {
        if (isset($params['items']) && $params['items']) {
            $filter['item_id'] = array_column($params['items'], 'item_id');
            $filter['company_id'] = $params['company_id'];
            $filter['operator_id'] = $params['operator_id'];
            $filter['distributor_id'] = $params['distributor_id'];
            $operatorCartService = new OperatorCartService();
            return $operatorCartService->deleteBy($filter);
        }
    }

    public function getOrderItemPromotion($orderData)
    {
        if (!$this->itemCart) {
            return $orderData;
        }
        $userId = $orderData['user_id'];
        if (!($orderData['items'] ?? [])) {
            throw new ResourceException('商品数据有误，请重新确认');
        }
        foreach ($orderData['items'] as $key => $orderitem) {
            $itemDiscountInfo = $orderitem['discount_info'] ?? [];

            if ($orderitem['activity_type'] == 'package') {
                $orderData['items'][$key]['order_item_type'] = $orderitem['activity_type'];
                $orderData['items'][$key]['act_id'] = $orderitem['activity_id'];
                continue;
            } else {
                $itemId = $orderitem['item_id'];
                $cart = $this->itemCart[$itemId] ?? [];
                if (!$cart) {
                    throw new ResourceException('购物车商品有变，请重新确认');
                }
            }
            $itemDiscountInfo = array_merge($itemDiscountInfo, $cart['activity_info']);
            $orderData['items'][$key]['total_fee'] -= (int)($cart['discount_fee'] ?? 0);
            $orderData['items'][$key]['discount_fee'] = (int)($cart['discount_fee'] ?? 0);
            $orderData['items'][$key]['discount_info'] = $itemDiscountInfo;

            $activityId = $cart['activity_id'] ?? 0;
            if (in_array($activityId, $this->usedActivity)) {
                $activity = $this->validPromotion[$activityId] ?? [];
                if ($activity) {
                    $activity['activity_id'] = $activityId;
                    $orderData = $this->__preItemsPromotion($orderData, $orderitem, $userId, $itemId, $activity, $cart);
                }
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
        $orderData['member_discount'] = $this->MemberDiscount[$userId]; // 会员折扣总价
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

        if ($this->gift_activity) {
            foreach ($this->gift_activity as $activityData) {
                if ($activityData['activity_id'] && $activityData['gifts']) {
                    $orderData = $this->handleGiftItems($activityData, $orderData);
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
    private function handleGiftItems($activityData, $orderData)
    {
        $activityItems = [];
        $totalItemFee = 0;
        $totalDiscountFee = 0;
        // 将活动商品从订单商品中暂时移除，并作为活动商品
        if ($activityData['activity_item_ids'] ?? []) {
            foreach ($orderData['items'] as $k => $items) {
                if (in_array($items['item_id'], $activityData['activity_item_ids'])) {
                    $activityItems[] = $items;
                    $totalItemFee += $items['item_fee'];
                    unset($orderData['items'][$k]);
                }
            }
        }
        // 遍历赠品信息
        foreach ($activityData['gifts'] as $itemInfo) {
            if (isset($itemInfo['item_main_cat_id']) && $itemInfo['item_main_cat_id']) {
                $itemsCategoryService = new ItemsCategoryService();
                $item_category_main = $itemsCategoryService->getCategoryPathById($itemInfo['item_main_cat_id'], $itemInfo['company_id'], true);
            } else {
                $item_category_main = [];
            }

            if ($itemInfo['gift_num'] > $itemInfo['store']) {
                $key = 'giftSetting:'. $itemInfo['company_id'];
                $setting = app('redis')->connection('companys')->get($key);
                $setting = json_decode($setting, 1);
                // 加开关不影响原来的流程
                if ($setting['check_gift_store'] ?? false) {
                    $itemInfo['gift_num'] = $itemInfo['store'];
                }
            }

            $giftItems = [
                'order_id' => $orderData['order_id'],
                'item_id' => $itemInfo['item_id'],
                'goods_id' => $itemInfo['goods_id'],
                'item_bn' => $itemInfo['itemBn'],
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
            ];

            if ($giftItems['num'] > 0) {
                $totalItemFee += $giftItems['item_fee'];
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
        $discountInfo = $dinfo = $activityData['discount_desc'] ?? [];
        $discountInfo['discount_fee'] = $totalDiscountFee;
        array_push($orderData['discount_info'], $discountInfo);

        //赠品分摊金额
        $activityItemsTotalFee = 0; //参与赠品优惠商品的总额
        $totalPayFee = 0; //赠品+活动商品的总额
        $totalGiftFee = 0; //赠品总金额
        $percent = 0;
        $zeroGift = [];
        if ($activityItems) {
            foreach ($activityItems as $key => $item) {
                //赠品金额为0的不参与分摊
                if ($item['item_fee'] <= 0) {
                    $zeroGift[] = $item;
                    unset($activityItems[$key]);
                    continue;
                }
                if ($item['order_item_type'] == 'gift') {
                    $totalGiftFee += $item['item_fee'];
                    $item['total_fee'] = $item['item_fee']; //赠品的付款金额以赠品价格为准
                }
                $activityItemsTotalFee += $item['total_fee']; //参与赠品优惠商品的总额
            }
            $percent = bcdiv($totalGiftFee, $activityItemsTotalFee, 5);

            //重新排序
            // $activityItems = collect($activityItems)->sortBy('total_fee')->values()->all();
        }
        // 0元赠品 discount_info数据
        if ($zeroGift) {
            foreach ($zeroGift as $key => $val) {
                if ($item['order_item_type'] == 'gift') {  //赠品优惠的是自身的金额
                    $discountInfo['info'] = '满赠优惠';
                    $discountInfo['discount_fee'] = 0;
                } else {
                    $discountInfo = $dinfo;
                    $discountInfo['discount_fee'] = 0;
                }
                if (isset($orderData['items'][$key]['discount_info'])) {
                    array_push($zeroGift[$key]['discount_info'], $discountInfo);
                } else {
                    $zeroGift[$key]['discount_info'][] = $discountInfo;
                }
            }
        }

        if ($activityItems) {
            $remainDiscountFee = $totalDiscountFee; //剩余待分摊的金额
            foreach ($activityItems as $key => $item) {
                $discountInfo = [];
                $itemTotalFee = $activityItems[$key]['total_fee'];//当前商品的结算金额
                $itemDiscountFee = $activityItems[$key]['discount_fee'];//当前商品的结算金额
                if ($item['order_item_type'] == 'gift') {  //赠品优惠的是自身的金额
                    $discountInfo['info'] = '满赠优惠';
                    $itemTotalFee = $item['item_fee']; //赠品的结算金额以赠品金额为准
                    $itemDiscountFee = 0; //赠品的折扣金额重新计算
                } else {
                    $discountInfo = $dinfo;
                }

                //最后一个商品，防止除不尽
                if ($item == end($activityItems)) {
                    //防止剩余折扣金额超过商品金额
                    if ($remainDiscountFee > $itemTotalFee) {
                        $overflowFee = $remainDiscountFee - $itemTotalFee;
                        $remainDiscountFee = $itemTotalFee;

                        //把多余的金额加到第一个满足条件商品上
                        foreach ($activityItems as $k => $v) {
                            if ($v['total_fee'] >= $overflowFee) {
                                $activityItems[$k]['discount_fee'] += $overflowFee;
                                $activityItems[$k]['total_fee'] -= $overflowFee;
                                // 把多余的金额加到商品优惠信息里面
                                $giftDiscountInfo = array_pop($activityItems[$k]['discount_info']);
                                $giftDiscountInfo['discount_fee'] += $overflowFee;
                                $activityItems[$k]['discount_info'][] = $giftDiscountInfo;
                                break;
                            }
                        }
                    }

                    $discountInfo['discount_fee'] = $remainDiscountFee;
                }else{
                    $discountInfo['discount_fee'] = floor(bcmul($itemTotalFee, $percent, 2));
                }
                //app('log')->info("赠品分摊金额 => {$item['item_name']} : {$itemTotalFee} * $totalGiftFee / $activityItemsTotalFee");
                $activityItems[$key]['discount_fee'] = $itemDiscountFee + $discountInfo['discount_fee'];
                $activityItems[$key]['total_fee'] = $itemTotalFee - $discountInfo['discount_fee'];
                $remainDiscountFee -= $discountInfo['discount_fee'];

                if (isset($orderData['items'][$key]['discount_info'])) {
                    array_push($activityItems[$key]['discount_info'], $discountInfo);
                } else {
                    $activityItems[$key]['discount_info'][] = $discountInfo;
                }
            }
        }
        $activityItems = array_merge($activityItems, $zeroGift);

        $orderData['items'] = array_merge($orderData['items'], $activityItems);
        $orderData['items'] = array_values($orderData['items']);

        return $orderData;
    }

    public function getOrderInfo($companyId, $orderId, $checkaftersales = false, $from = 'api')
    {
        $data = parent::getOrderInfo($companyId, $orderId, $checkaftersales, $from);

        // 返回操作人信息
        if ($data['orderInfo']['operator_id'] > 0) {
            $operatorsService = new OperatorsService();
            $operatorInfo = $operatorsService->operatorsRepository->getInfo(['company_id' => $companyId, 'operator_id' => $data['orderInfo']['operator_id']]);
            if ($operatorInfo) {
                $data['operatorInfo']['operator_id'] = $operatorInfo['operator_id'];
                $data['operatorInfo']['mobile'] = $operatorInfo['mobile'];
                $data['operatorInfo']['login_name'] = $operatorInfo['login_name'];
                $data['operatorInfo']['username'] = $operatorInfo['username'];
                $data['operatorInfo']['head_portrait'] = $operatorInfo['head_portrait'];
                $data['operatorInfo']['operator_type'] = $operatorInfo['operator_type'];
            }
        }

        return $data;
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
