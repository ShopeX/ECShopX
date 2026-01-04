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
use DistributionBundle\Services\DistributorService;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Services\MemberService;
use OrdersBundle\Entities\Cart;
use OrdersBundle\Interfaces\CartInterface;
use OrdersBundle\Traits\SeckillStoreTicket;
use PromotionsBundle\Services\LimitService;
use PromotionsBundle\Services\MarketingActivityService;
use PromotionsBundle\Services\PackageService;
use PromotionsBundle\Traits\CheckPromotionsValid;
use DistributionBundle\Services\DistributorItemsService;
use OrdersBundle\Services\CartService;
use CompanysBundle\Ego\CompanysActivationEgo;
use CompanysBundle\Services\SettingService;

class DistributorCartObject implements CartInterface
{
    use CheckPromotionsValid;
    use SeckillStoreTicket;

    public $shopType = 'distributor';

    public $invalidCart = [];

    /**
     * 检查购买商品
     */
    public function checkItemParams($params)
    {
        // 检查是否是有效的会员优先购
        $memberpreference = $this->checkCurrentMemberpreferenceByItemId($params['company_id'], $params['user_id'], $params['item_id'], $params['shop_id'], false, $msg);
        if (!$memberpreference) {
            throw new ResourceException($msg);
        }
        if (($params['activity_type'] ?? '') == 'package') { // 判断组合商品
            $this->checkItemPackage($params);
            //return $params;
        }
        $activityData = $this->getCurrentActivityByItemId($params['company_id'], $params['item_id'], $params['shop_id'], false);
        if (!$activityData || !in_array(($activityData['activity_type'] ?? ''), ['limited_time_sale', 'limited_buy'])) {
            return $params;
        }

        if (($activityData['activity_type'] ?? '') == 'limited_time_sale') {
            $buyData = $this->getUserBuysData($activityData['info']['seckill_id'], $params['company_id'], $params['user_id'], $params['item_id']);
            $userBuyStore = $buyData['userBuyStore'] ?: 0;
            $itemData = $activityData['list'][$params['item_id']];
            if ($itemData['limit_num'] > 0 && ($itemData['limit_num'] - $userBuyStore) < $params['num']) {
                throw new ResourceException('超出限购数量');
            }
            $params['price'] = $itemData['price'];
            return $params;
        }

        //商品限购
        if (($activityData['activity_type'] ?? '') == 'limited_buy') {
            $memberService = new MemberService();
            $isHaveVip = $memberService->isHaveVip($params['user_id'], $params['company_id'], $activityData['info']['valid_grade']);

            if (!$isHaveVip) {
                return $params;
            }

            $filterPerson = [
                'company_id' => $params['company_id'],
                'user_id' => $params['user_id'],
                'item_id' => $params['item_id'],
            ];
            $limit_type = $activityData['info']['limit_type'] ?? 'global';
            if ($limit_type == 'shop') {
                $filterPerson['distributor_id'] = $params['shop_id'];
            }
            $filterPerson['start_time|lt'] = time();
            $filterPerson['end_time|gt'] = time();
            $limitService = new LimitService();
            $limitItemInfo = $limitService->getLimitPersonInfo($filterPerson);
            $limitNumber = $limitItemInfo['number'] ?? 0;
            $num = $params['num'] + $limitNumber;
            if ($num > $activityData['info']['rule']['limit']) {
                throw new ResourceException('超出限购数量');
            }
            return $params;
        }
        return $params;
    }

    /**
     * 检查购买组合
     * @param $params
     * @return mixed
     */
    public function checkItemPackage($params)
    {
        if (($params['activity_type'] ?? '') != 'package') {
            return $params;
        }
        $packageService = new PackageService();
        $packageData = $packageService->getPackageInfo($params['company_id'], $params['activity_id']);
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getItemsDetail($params['item_id']);
        if ((time() > $packageData['end_time'] || $packageData['start_time'] > time()) || $itemInfo['goods_id'] != $packageData['goods_id']) {
            throw new ResourceException('组合商品不存在');
        }
        $itemsId = $packageData['package_items'];

        if (isset($params['items_id']) && array_diff($params['items_id'], $itemsId)) {
            throw new ResourceException('组合商品信息错误');
        }

        $memberService = new MemberService();
        $isVip = $memberService->isHaveVip($params['user_id'], $params['company_id'], $packageData['valid_grade']);

        if (!$isVip) {
            throw new ResourceException('需要规定会员才允许购买');
        }
        return $params;
    }

    public function getShopData($companyId, $shopIds)
    {
        $result = [];
        $filter = ['company_id' => $companyId, 'distributor_id' => $shopIds];
        $distributorService = new DistributorService();
        $distributorList = $distributorService->lists($filter);
        if ($distributorList['list']) {
            foreach ($distributorList['list'] as $list) {
                $result[$list['distributor_id']] = [
                    'shop_id' => $list['distributor_id'],
                    'shop_name' => $list['name'],
                    'address' => $list['address'],
                    'hour' => $list['hour'],
                    'mobile' => $list['mobile'],
                    'lat' => $list['lat'],
                    'lng' => $list['lng'],
                    'is_ziti' => ($list['is_ziti'] ?? false) ? true : false,
                    'is_delivery' => ($list['is_delivery'] ?? true) ? true : false,
                ];
            }
        }
        return $result;
    }

    public function formatCartList($companyId, $userId, $cartData, $isCheckout = false, $userDevice = 'miniprogram')
    {
        if ($userDevice != 'pc') {
            $limitedTimeSalelimit = [];
            foreach ($cartData['valid_cart'] as $key => $cartRow) {
                // 组合商品不需要处理
                if (($cartRow['activity_type'] ?? '') == 'package') {
                    continue;
                }
                // 目前在有效商品中进行处理，后续对有时间，放到循环外面处理
                $activityData = $this->getCurrentActivityByItemId($cartRow['company_id'], $cartRow['item_id'], $cartRow['shop_id'], false);
                // 如果不是限时优惠，那么则不需要进行处理
                if (!$activityData || !in_array(($activityData['activity_type'] ?? ''), ['limited_time_sale', 'limited_buy'])) {
                    continue;
                }

                if (($activityData['activity_type'] ?? '') == 'limited_buy' && ($cartRow['shop_type'] ?? '') != 'shop_offline') {
                    $memberService = new MemberService();
                    $isHaveVip = $memberService->isHaveVip($cartRow['user_id'], $cartRow['company_id'], $activityData['info']['valid_grade']);

                    if (!$isHaveVip) {
                        continue;
                    }

                    $rule = $activityData['info']['rule'];

                    $filterPerson = [
                        'company_id' => $cartRow['company_id'],
                        'user_id' => $cartRow['user_id'],
                        'item_id' => $cartRow['item_id'],
                    ];
                    $filterPerson['start_time|lt'] = time();
                    $filterPerson['end_time|gt'] = time();
                    $limitService = new LimitService();
                    $limitItemInfo = $limitService->getLimitPersonInfo($filterPerson);
                    $limitNumber = $limitItemInfo['number'] ?? 0;
                    $num = $cartRow['num'] + $limitNumber;
                    if ($num > $rule['limit']) {
                        throw new ResourceException($cartRow['item_name'] . '超出限购数量');
                    }

                    $cartData['valid_cart'][$key]['limitedBuy'] = [
                        'activity_id' => $activityData['info']['limit_id'],
                        'marketing_type' => 'limited_buy',
                        'marketing_name' => $activityData['info']['limit_name'],
                        'limit_total' => $rule['limit'],
                        'limit_buy' => $rule['limit'] - $limitNumber,
                        'buy' => $limitNumber,
                        'rule' => $activityData['info']['rule'],
                        'promotion_tag' => '限购商品',
                    ];
                    continue;
                }

                if (($activityData['activity_type'] ?? '') == 'limited_time_sale') {
                    $itemData = $activityData['list'][$cartRow['item_id']];
                    $buyData = $this->getUserBuysData($activityData['info']['seckill_id'], $cartRow['company_id'], $cartRow['user_id'], $cartRow['item_id']);
                    $userBuyStore = $buyData['userBuyStore'] ?: 0;
                    $itemData = $activityData['list'][$cartRow['item_id']];
                    if ($itemData['limit_num'] > 0 && ($itemData['limit_num'] - $userBuyStore) < $cartRow['num'] && $isCheckout) {
                        throw new ResourceException($cartRow['item_name'] . '超出限购数量');
                    }
                    $cartData['valid_cart'][$key]['price'] = $itemData['price'];
                    $cartData['valid_cart'][$key]['activity_price'] = $itemData['activity_price'];
                    $cartData['valid_cart'][$key]['total_fee'] = bcmul($itemData['activity_price'], $cartRow['num']);
                    $cartData['valid_cart'][$key]['is_last_price'] = true;//是否为最后固定的价格了，如果是最后固定的价格，那么则不需要在进行优惠计算了
                    $cartData['valid_cart'][$key]['limitedTimeSaleAct'] = [
                        'activity_id' => $activityData['info']['seckill_id'],
                        'marketing_type' => 'limited_time_sale',
                        'marketing_name' => $activityData['info']['activity_name'],
                        'limit_total_money' => $activityData['info']['limit_total_money'],
                        'limit_money' => $activityData['info']['limit_money'],
                        'validity_period' => $activityData['info']['validity_period'],
                        'is_free_shipping' => $activityData['info']['is_free_shipping'],
                        'third_params' => $activityData['info']['otherext'],
                        'promotion_tag' => '限时优惠',
                    ];

                    // 判断用户限额
                    if ($isCheckout && ($activityData['info']['limit_total_money'] || $activityData['info']['limit_money'])) {
                        $seckillId = $activityData['info']['seckill_id'];
                        if (isset($limitedTimeSalelimit[$activityData['info']['seckill_id']])) {
                            $limitedTimeSalelimit[$seckillId]['total_fee'] += $cartData['valid_cart'][$key]['total_fee'];
                        } else {
                            $limitedTimeSalelimit[$seckillId]['total_fee'] = $cartData['valid_cart'][$key]['total_fee'];
                            // 当前活动用户限额
                            $limitedTimeSalelimit[$seckillId]['limit_total_money'] = $activityData['info']['limit_total_money'];
                            // 活动单笔限额
                            $limitedTimeSalelimit[$seckillId]['limit_money'] = $activityData['info']['limit_money'];
                            // 当前活动用户总共购买金额
                            $limitedTimeSalelimit[$seckillId]['user_buy_total_price'] = $buyData['userBuyTotalPrcie'] ?: 0;
                        }
                    }
                    continue;
                }
            }

            if ($limitedTimeSalelimit) {
                foreach ($limitedTimeSalelimit as $row) {
                    if ($row['limit_money'] > 0 && $row['total_fee'] < $row['limit_money']) {
                        throw new ResourceException('活动单笔最少购买' . ($row['limit_money'] / 100) . '元');
                    }

                    // 如果活动有总限额
                    if ($row['limit_total_money'] > 0 && $row['total_fee'] > ($row['limit_total_money'] - $row['user_buy_total_price'])) {
                        throw new ResourceException('活动总限额' . ($row['limit_total_money'] / 100) . '元');
                    }
                }
            }
        }

        //获取满减，满折，满赠优惠活动
        $cartData['valid_cart'] = $this->getPromotions($companyId, $userId, $cartData['valid_cart'], $userDevice);

        return $cartData;
    }

    //获取满减 满折优惠活动
    public function getPromotions($companyId, $userId, $cartData, $userDevice = 'miniprogram')
    {
        if (!$cartData) {
            return $cartData;
        }
        // if (!$userId) {
        //     return $cartData;
        // }

        $itemIds = array_column($cartData, 'item_id');
        $marketingService = new MarketingActivityService();
        $activityData = $marketingService->getValidMarketingActivity($companyId, $itemIds, $userId);
        if (!$activityData) {
            return $cartData;
        }
        foreach ($activityData as $activity) {
            $activityId = $activity['marketing_id'];
            $relItems = $activity['items'] ?? [];
            unset($activity['items']);
            if (isset($activity['usedCount']) && $activity['join_limit'] > 0 && $activity['usedCount'] > 0 && $activity['usedCount'] >= $activity['join_limit']) {
                continue;
            }
            foreach ($cartData as $key => $cart) {
                // 组合商品不需要处理
                if (($cart['activity_type'] ?? '') == 'package') {
                    continue;
                }
                
                //是t push否为最后固定的价格了，如果是最后固定的价格，那么则不需要在进行优惠计算了
                ////限时优惠会把is_last_price设置成true，导致赠品不执行
                if ($cart['is_last_price']) {
                    continue;
                }
                if ($cart['shop_id'] && $activity['shop_ids'] && !in_array($cart['shop_id'], $activity['shop_ids'])) {
                    continue;
                }

                // 只有平台版才校验活动是哪个店铺添加的
                $company = (new CompanysActivationEgo())->check($companyId);
                if ($company['product_model'] == 'platform') {
                    //店铺和平台只能使用各自的活动
                    $cartDistributorId = $cart['shop_id'] ?? 0;
                    $activitySourceId = $activity['source_id'] ?? 0;
                    if ($cartDistributorId != $activitySourceId) {
                        continue;
                    }
                }

                if ($activity['use_bound'] == 0 && !$relItems) {
                    if ($activity['marketing_type'] == 'full_gift') {
                        $cartData[$key]['full_gift_id'][] = $activityId;
                    } elseif ($activity['marketing_type'] == 'plus_price_buy') {
                        if ($userDevice != 'pc') {
                            $cartData[$key]['plus_buy_id'][] = $activityId;
                        }
                    } else {
                        $cartData[$key]['promotions'][] = $activity;  //所有适用于该商品的促销
                        $cartData[$key]['activity_id'] = $activityId;
                    }
                } elseif ($activity['use_bound'] > 0 && ($relItems[$cart['item_id']] ?? [])) {
                    if ($activity['marketing_type'] == 'full_gift') {
                        $cartData[$key]['full_gift_id'][] = $activityId;
                    } elseif ($activity['marketing_type'] == 'plus_price_buy') {
                        if ($userDevice != 'pc') {
                            $cartData[$key]['plus_buy_id'][] = $activityId;
                        }
                    } else {
                        $cartData[$key]['promotions'][] = $activity;  //所有适用于该商品的促销
                        $cartData[$key]['activity_id'] = $activityId;
                    }
                }
            }
        }

        return $cartData;
    }

    private function __getLimitTimeSaleDiscountDesc($cartinfo)
    {
        $cartinfo['discount_fee'] = $cartinfo['discount_fee'] ?? 0;          //优惠金额
        $cartinfo['total_fee'] = ($cartinfo['total_fee'] ?? null) ?: bcmul($cartinfo['price'], $cartinfo['num']); //总支付金额
        $cartinfo['activity_info'] = $cartinfo['activity_info'] ?? [];   //优惠内容
        if (($cartinfo['limitedTimeSaleAct'] ?? null) && ($cartinfo['activity_price'] ?? 0)) {
            $discountFeeL = bcmul($cartinfo['price'], $cartinfo['num']) - bcmul($cartinfo['activity_price'], $cartinfo['num']);
            $cartinfo['discount_fee'] += $discountFeeL ;
            $cartinfo['total_fee'] = bcmul($cartinfo['activity_price'], $cartinfo['num']);
            $activityInfo = [
                'type' => 'limited_time_sale',
                'id' => $cartinfo['limitedTimeSaleAct']['activity_id'],
                'rule' => $cartinfo['limitedTimeSaleAct']['promotion_tag'],
                'info' => $cartinfo['limitedTimeSaleAct']['marketing_name'],
                'discount_fee' => $discountFeeL,
            ];
            array_push($cartinfo['activity_info'], $activityInfo);
        }
        return $cartinfo;
    }

    public function getTotalCart($cartData)
    {
        $usedPromotions = [];
        $promotionDatas = [];
        $usedFullGift = [];
        $usedPlusBuy = [];
        foreach ($cartData as $k => $value) {
            //限时特惠订单优惠金额计算和记录
            $cartData[$k] = $this->__getLimitTimeSaleDiscountDesc($value);

            //购物车中所有有效的促销活动
            if ($value['promotions'] ?? []) {
                foreach ($value['promotions'] as $v) {
                    $promotionDatas[$v['marketing_id']] = $v;
                }
            }
            //购物车选中的促销活动
            if ($value['activity_id'] ?? 0 && !$value['is_last_price']) {
                if ($promotionDatas[$value['activity_id']] ?? []) {
                    $usedPromotions[$value['activity_id']]['cart_ids'][] = $value['cart_id'];
                }
            }

            // 购物车商品的满赠品
            //限时优惠会把is_last_price设置成true，导致赠品不执行
            if (isset($value['full_gift_id']) && $value['full_gift_id'] && !$value['is_last_price']) {
                foreach ($value['full_gift_id'] as $full_gift_id) {
                    $usedFullGift[$full_gift_id]['cart_ids'][] = $value['cart_id'];
                }
            }

            // 购物车商品的加价购商品
            if (isset($value['plus_buy_id']) && $value['plus_buy_id'] && !$value['is_last_price']) {
                foreach ($value['plus_buy_id'] as $plus_buy_id) {
                    $usedPlusBuy[$plus_buy_id]['cart_ids'][] = $value['cart_id'];
                }
            }

            $userId = $value['user_id'];
            $companyId = $value['company_id'];
            if (isset($value['activity_type']) && 'package' == $value['activity_type'] && isset($value['packages']) && is_array($value['packages'])) {
                foreach ($value['packages'] as $packagesValue) {
                    $cartData[$k]['total_fee'] += bcmul($packagesValue['price'], $value['num']);
                }
            }
        }
        $usedActivity = [];
        $usedActivityIds = [];
        foreach ($usedPromotions as $activityId => $validCartIds) {
            if ($promotionDatas[$activityId] ?? []) {
                $marketingType = $promotionDatas[$activityId]['marketing_type'];
                $marketName = $promotionDatas[$activityId]['marketing_name'];
                $usedPromotions[$activityId]['activity_name'] = $marketName;
                $usedPromotions[$activityId]['activity_id'] = $activityId;
                $usedPromotions[$activityId]['activity_tag'] = $promotionDatas[$activityId]['promotion_tag'];
                $usedPromotions[$activityId]['condition_rules'] = $promotionDatas[$activityId]['condition_rules'];
                $usedPromotions[$activityId]['activity_type'] = $marketingType;

                switch ($marketingType) {
                case 'full_minus':
                case 'full_discount':
                    $discountPrice = $this->applyFullDiscount($cartData, $companyId, $userId, $validCartIds['cart_ids'], $promotionDatas[$activityId]);
                    if ($discountPrice) {
                        $usedActivity[] = ['activity_id' => $activityId, 'activity_name' => $marketName];
                        $usedActivityIds[] = $activityId;
                    }
                    $usedPromotions[$activityId]['discount_fee'] = $discountPrice;
                    break;
                }
            }
        }

        if (isset($usedFullGift) && $usedFullGift) {
            foreach ($usedFullGift as $key => $value) {
                $detail['marketing_id'] = $key;
                $giftData = $this->applyFullGift($cartData, $companyId, $userId, $value['cart_ids'], $detail);

                if ($giftData['activity_id'] ?? 0 && $giftData['gifts']) {
                    $usedGiftActivity[] = $giftData;
                }
            }
        }

        if (isset($usedPlusBuy) && $usedPlusBuy) {
            foreach ($usedPlusBuy as $key => $value) {
                $detail['marketing_id'] = $key;
                $plusBuyData = $this->applyPlusPriceBuy($cartData, $companyId, $userId, $value['cart_ids'], $detail);

                if (($plusBuyData['activity_id'] ?? 0) && ($plusBuyData['plus_buy_items'] ?? [])) {
                    $usedPlusBuyActivity[] = $plusBuyData;
                }
            }
        }

        $TotalDiscountFee = $itemNum = $itemTotalFee = $cartNum = 0;
        foreach ($cartData as $k => $cart) {
            if (isset($cart['is_checked']) && $cart['is_checked']) {
                $itemTotalFee += bcmul($cart['price'], $cart['num']);
                $itemNum += $cart['num'];
                $cartNum += 1;
                if (isset($cart['activity_type']) && 'package' == $cart['activity_type'] && isset($cart['packages']) && is_array($cart['packages'])) {
                    foreach ($cart['packages'] as $packagesCart) {
                        $itemTotalFee += bcmul($packagesCart['price'], $cart['num']);
                    }
                }
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
        $result['gift_activity'] = $usedGiftActivity ?? [];
        $result['plus_buy_activity'] = $usedPlusBuyActivity ?? [];
        return $result;
    }

    private function applyFullDiscount(&$cartData, $companyId, $userId, $validCartIds, $activityDetail)
    {
        $cartParams['total_price'] = 0;
        $cartParams['total_num'] = 0;
        foreach ($validCartIds as $cartId) {
            if ($cartData[$cartId]['is_checked']) {
                $cartParams['total_price'] += $cartData[$cartId]['total_fee'];
                $cartParams['total_num'] += $cartData[$cartId]['num'];
            }
        }
        $marketingId = $activityDetail['marketing_id'];
        $marketingService = new MarketingActivityService();
        $discount = $marketingService->applyActivityRules($companyId, $marketingId, $userId, $cartParams);
        $discountFee = $discount['discount_fee'] ?? 0;

        if ($discountFee > 0) {
            $discountDesc = $discount['discount_desc'] ?? null;
            //优惠分摊
            $newCartData = [];
            foreach ($validCartIds as $cartId) {
                if ($cartData[$cartId]['is_checked']) {
                    $newCartData[] = $cartId;
                }
            }
            $allDiscountFeeArr = [];
            foreach ($newCartData as $key => $cartId) {
                $percent = round(bcdiv($cartData[$cartId]['total_fee'], $cartParams['total_price'], 5), 4);
                if ($allDiscountFeeArr && $key == count($newCartData) - 1) {
                    $allDiscountFeeArr[$key] = $discountFee - array_sum($allDiscountFeeArr);
                } else {
                    $allDiscountFeeArr[$key] = (count($newCartData) == 1) ? $discountFee : round(bcmul($discountFee, $percent, 2));
                }
                $cartData[$cartId]['discount_fee'] = ($cartData[$cartId]['discount_fee'] ?? 0) + $allDiscountFeeArr[$key];
                $cartData[$cartId]['total_fee'] = $cartData[$cartId]['total_fee'] - $allDiscountFeeArr[$key];
                $cartData[$cartId]['activity_type'] = $activityDetail['marketing_type'];
                $cartData[$cartId]['activity_id'] = $activityDetail['marketing_id'];
                $discountDesc['discount_fee'] = $allDiscountFeeArr[$key];
                array_push($cartData[$cartId]['activity_info'], $discountDesc);
            }
        }
        return $discountFee;
    }

    private function applyFullGift(&$cartData, $companyId, $userId, $validCartIds, $activityDetail)
    {
        $cartParams['total_price'] = 0;
        $cartParams['total_num'] = 0;
        $usedItemsIds = [];
        foreach ($validCartIds as $cartId) {
            if ($cartData[$cartId]['is_checked']) {
                $cartParams['total_price'] += $cartData[$cartId]['total_fee'];
                $cartParams['total_num'] += $cartData[$cartId]['num'];
                $usedItemsIds[] = $cartData[$cartId]['item_id'];
            }
        }
        $marketingId = $activityDetail['marketing_id'];
        $marketingService = new MarketingActivityService();
        $giftdata = $marketingService->applyActivityRules($companyId, $marketingId, $userId, $cartParams);
        if ($giftdata['activity_id'] ?? 0) {
            $giftdata['activity_item_ids'] = $usedItemsIds;
        }

        $key = 'giftSetting:'. $companyId;
        $setting = app('redis')->connection('companys')->get($key);
        $setting = json_decode($setting, 1);
        // 加开关不影响原来的流程
        if ($setting['minus_shop_gift_store'] ?? false) {
            // 门店赠品
            $cartRow = current($cartData);
            $shopId = $cartRow['shop_id'] ?? 0;
            $shopType = $cartRow['shop_type'] ?? '';
            $giftdata['gifts'] = $giftdata['gifts'] ?? [];
            if ($shopId && $shopType != 'community' && count($giftdata['gifts']) > 0) {
                $distributorItemsService = new DistributorItemsService();
                $giftdata['gifts'] = $distributorItemsService->getDistributorSkuReplace($companyId, $shopId, $giftdata['gifts']);
            }
        }

        return $giftdata;
    }

    //加价购
    private function applyPlusPriceBuy(&$cartData, $companyId, $userId, $validCartIds, $activityDetail)
    {
        $cartParams['total_price'] = 0;
        $cartParams['total_num'] = 0;
        $usedItemsIds = [];
        foreach ($validCartIds as $cartId) {
            if ($cartData[$cartId]['is_checked']) {
                $cartParams['total_price'] += $cartData[$cartId]['total_fee'];
                $cartParams['total_num'] += $cartData[$cartId]['num'];
                $usedItemsIds[] = $cartData[$cartId]['item_id'];
            }
        }
        $marketingId = $activityDetail['marketing_id'];
        $marketingService = new MarketingActivityService();
        $plusdata = $marketingService->applyActivityRules($companyId, $marketingId, $userId, $cartParams);

        if ($plusdata['activity_id'] ?? 0) {
            $pluscart = $plusdata['plus_buy_items'];
            $cartService = new CartService();
            $checked_id = $cartService->getPlusBuyCart($companyId, $userId, $marketingId);
            if ($checked_id && isset($pluscart[$checked_id])) {
                $checkedpluscart = $pluscart[$checked_id];
                $plusdata['plus_item'] = $checkedpluscart;

                foreach ($validCartIds as $cartId) {
                    $cartData[$cartId]['marketing_type'] = 'plus_price_buy';
                }
            }

            $plusdata['activity_item_ids'] = $usedItemsIds;
        }
        return $plusdata;
    }

    public function updateCartItemPromotion($filter, $activityId, $activityType = 'goods_promotion')
    {
        $filter['shop_type'] = $this->shopType;
        $entityRepository = app('registry')->getManager('default')->getRepository(Cart::class);
        $cartdata = $entityRepository->getInfo($filter);
        if (!$cartdata) {
            throw new ResourceException('购物车数据有误');
        }

        $marketingService = new MarketingActivityService();
        $marketingService->checkUserApplyActivityRules($filter['company_id'], $activityId, $filter['user_id'], $cartdata['item_id'], $cartdata['shop_id']);
        return $entityRepository->updateBy($filter, ['activity_id' => $activityId, 'activity_type' => $activityType]);
    }

    public function getInvalidCart()
    {
        return $this->invalidCart;
    }

    /**
     * 加入购物车时，检查当前商品的活动数据。限购数、限额。限时秒杀、限时特惠。
     * @param  array  $cartInfo   当前加购商品
     * @param  boolean $isCheckout 是否校验
     * @param  string  $userDevice 来源
     */
    public function __checkCartInfoPromotion($cartInfo, $isCheckout = false, $userDevice = 'miniprogram')
    {
        if ($userDevice == 'pc' || ($cartInfo['activity_type'] ?? '') == 'package') {
            return $cartInfo;
        }
        // $limitedTimeSalelimit = [];

        // 目前在有效商品中进行处理，后续对有时间，放到循环外面处理
        $activityData = $this->getCurrentActivityByItemId($cartInfo['company_id'], $cartInfo['item_id'], $cartInfo['shop_id'], false);
        // 如果不是限时优惠，那么则不需要进行处理
        $activity_type = $activityData['activity_type'] ?? '';
        if (!$activityData && !in_array($activity_type, ['limited_time_sale', 'limited_buy'])) {
            return $cartInfo;
        }

        //检查商品限购
        if ($activity_type == 'limited_buy') {
            $memberService = new MemberService();
            $isHaveVip = $memberService->isHaveVip($cartInfo['user_id'], $cartInfo['company_id'], $activityData['info']['valid_grade']);

            if (!$isHaveVip) {
                return $cartInfo;
            }

            $rule = $activityData['info']['rule'];

            $filterPerson = [
                'company_id' => $cartInfo['company_id'],
                'user_id' => $cartInfo['user_id'],
                'item_id' => $cartInfo['item_id'],
            ];
            if ($activityData['info']['limit_type'] == 'shop') {
                $filterPerson['distributor_id'] = $cartInfo['shop_id'];//店铺限购
            }
            $filterPerson['start_time|lt'] = time();
            $filterPerson['end_time|gt'] = time();
            $limitService = new LimitService();
            $limitItemInfo = $limitService->getLimitPersonInfo($filterPerson);
            $limitNumber = $limitItemInfo['number'] ?? 0;
            $num = $cartInfo['num'] + $limitNumber;
            if ($num > $rule['limit']) {
                throw new ResourceException($cartInfo['item_name'] . '超出限购数量');
            }
        }
        if ($activity_type == 'limited_time_sale') {
            $itemData = $activityData['list'][$cartInfo['item_id']];
            $buyData = $this->getUserBuysData($activityData['info']['seckill_id'], $cartInfo['company_id'], $cartInfo['user_id'], $cartInfo['item_id']);
            $userBuyStore = $buyData['userBuyStore'] ?: 0;
            $itemData = $activityData['list'][$cartInfo['item_id']];
            if ($itemData['limit_num'] > 0 && ($itemData['limit_num'] - $userBuyStore) < $cartInfo['num'] && $isCheckout) {
                throw new ResourceException($cartInfo['item_name'] . '超出限购数量');
            }
            $cartInfo['price'] = $itemData['price'];
            $cartInfo['activity_price'] = $itemData['activity_price'];
            $cartInfo['total_fee'] = bcmul($itemData['activity_price'], $cartInfo['num']);
            if (!$isCheckout || (!$activityData['info']['limit_total_money'] && !$activityData['info']['limit_money'])) {
                return $cartInfo;
            }

            // 判断用户限额
            $limit_money = $activityData['info']['limit_money'];
            $total_fee = $cartInfo['total_fee'];
            $limit_total_money = $activityData['info']['limit_total_money'];
            $user_buy_total_price = $buyData['userBuyTotalPrcie'] ?: 0;

            if ($limit_money > 0 && $total_fee < $limit_money) {
                throw new ResourceException('活动单笔最少购买' . ($limit_money / 100) . '元');
            }

            // 如果活动有总限额
            if ($limit_total_money > 0 && $total_fee > ($limit_total_money - $user_buy_total_price)) {
                throw new ResourceException('活动总限额' . ($limit_total_money / 100) . '元');
            }
        }

        return $cartInfo;
    }
}
