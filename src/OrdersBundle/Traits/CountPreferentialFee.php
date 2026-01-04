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

namespace OrdersBundle\Traits;

use KaquanBundle\Services\DiscountCardService;
use MembersBundle\Services\MemberService;
use KaquanBundle\Services\MemberCardService;
use KaquanBundle\Services\UserDiscountService;
use KaquanBundle\Services\VipGradeOrderService;
use Dingo\Api\Exception\ResourceException;
use PointBundle\Exception\PointResourceException;
use PointBundle\Services\PointMemberRuleService;
use KaquanBundle\Services\KaquanService;
use GoodsBundle\Services\ItemsTagsService;
use GoodsBundle\Services\ItemsService;

trait CountPreferentialFee
{
    public $discountInfo;

    /**
     * 获取会员等级折扣
     */
    public function getMemberDeduction($userId, $companyId, $orderData)
    {
        //获取付费会员卡信息
        $vipGradeService = new VipGradeOrderService();
        $privileges = $vipGradeService->userVipGradeGet($companyId, $userId);
        if (isset($privileges['is_vip']) && !$privileges['is_vip']) {
            $privileges = [];
        }
        if (!$privileges) {
            $filter = [
                'user_id' => $userId,
                'company_id' => $companyId,
            ];
            $memberService = new MemberService();
            $memberInfo = $memberService->getMemberInfo($filter);
            if ($memberInfo) {
                $memberCardService = new MemberCardService();
                $gradeInfo = $memberCardService->getGradeByGradeId($memberInfo['grade_id']);
                $privileges = $gradeInfo['privileges'];
                $privileges['grade_name'] = $gradeInfo['grade_name'];
            }
        }

        if ($privileges) {
            $member_discount = 0;
            $orderData['member_discount'] = 0;
            $orderData['member_discount_desc'] = [];

            $total_fee = array_column($orderData['items'], 'total_fee');
            array_multisort($total_fee, SORT_ASC, $orderData['items']);

            foreach ($orderData['items'] as $key => &$item) {
                //if (($item['order_item_type'] ?? '') && $item['order_item_type'] == 'gift') {
                //    continue;
                //}
                if ($privileges['discount']) {
                    $memberDiscount = bcmul($item['total_fee'], bcdiv($privileges['discount'], 100, 2));
                    $item['discount_fee'] = $item['discount_fee'] + $memberDiscount;
                    $item['total_fee'] = $item['total_fee'] - $memberDiscount;
                    $discountDesc = [
                        'type' => 'member_discount:'.($privileges['vip_type'] ?? $memberInfo['grade_id']),
                        'discount_fee' => $memberDiscount,
                        'rule' => '会员折扣'.$privileges['discount']."%",
                    ];
                    $item['discount_info'] = $item['discount_info'] ?? [];
                    array_push($item['discount_info'], $discountDesc);
                    $member_discount += $memberDiscount;
                }
            }
            $discountDesc = [
                'grade_name' => $privileges['grade_name'].(isset($privileges['vip_type']) ? "--".$privileges['vip_type'] : ""),
                'discount_fee' => $member_discount,
                'id' => ($privileges['vip_type'] ?? $memberInfo['grade_id']),
                'type' => 'member_discount',
                'info' => '会员卡折扣优惠',
                'rule' => '会员卡'.$privileges['discount'].'%优惠',
            ];
            $orderData['total_fee'] = $orderData['total_fee'] - $member_discount;
            $orderData['discount_fee'] = $orderData['discount_fee'] + $member_discount;
            $orderData['member_discount'] = $member_discount;
            $orderData['discount_info'] = $orderData['discount_info'] ?? [];
            array_push($orderData['discount_info'], $discountDesc);
        }

        return $orderData;
    }

    /**
     * 获取会员优惠券折扣
     */
    public function getCouponDiscount($userId, $code, $companyId, $orderData)
    {
        $userDiscountService = new UserDiscountService();
        $filter = [
            'user_id' => $userId,
            'code' => $code,
            'company_id' => $companyId,
            'status' => 1
        ];
        $userDiscount = $userDiscountService->getUserCardInfo($filter);

        if (isset($userDiscount['detail'])) {
            $userDiscount = $userDiscount['detail'];
            if (!$userDiscount) {
                throw new ResourceException('优惠券数据有误');
            }
            $itemTotalFee = 0;
            $couponValid = false;
            if (($userDiscount['rel_item_ids'] ?? '') && is_array($userDiscount['rel_item_ids'])) {
                foreach ($orderData['items'] as $k => $item) {
                    if (($item['order_item_type'] ?? '') && $item['order_item_type'] == 'gift') {
                        continue;
                    }
                    $itemId = $item['default_item_id'] ?? $item['item_id'];
                    if (in_array($itemId, $userDiscount['rel_item_ids'])) {
                        $orderData['items'][$k]['coupon_valid'] = true;
                        $itemTotalFee += $item['total_fee'];
                        $couponValid = true;
                    }
                }
                if (!$couponValid) {
                    throw new ResourceException('优惠券不适用与该商品');
                }
            }

            $totalFee = $orderData['total_fee'] - $orderData['freight_fee'];
            if ($itemTotalFee > 0 && intval($userDiscount['least_cost']) > $itemTotalFee) {
                throw new ResourceException('优惠券不满足使用条件');
            } elseif ($totalFee > 0 && intval($userDiscount['least_cost']) > $totalFee) {
                throw new ResourceException('优惠券不满足使用条件');
            }
            if ($userDiscount['card_type'] == 'cash') {
                $couponResult = $this->getCashCouponDiscountFee($orderData, $userDiscount, $itemTotalFee, $couponValid);
            } elseif ($userDiscount['card_type'] == 'discount') {
                $couponResult = $this->getDiscountCouponDiscountFee($orderData, $userDiscount, $couponValid);
            }
            $orderData['discount_fee'] = $orderData['discount_fee'] + $couponResult['discount_fee'];
            $orderData['coupon_discount'] = $couponResult['discount_fee'];
            $orderData['total_fee'] = $orderData['total_fee'] - $couponResult['discount_fee'];
            $orderData['discount_info'] = $orderData['discount_info'] ?? [];
            array_push($orderData['discount_info'], $couponResult['discount_info']);
        }
        return $orderData;
    }

    //获取代金券优惠金额
    private function getCashCouponDiscountFee(&$orderData, $userDiscount, $itemTotalFee = 0, $couponValid = false)
    {
        $couponCashDiscount = [];
        if ($couponValid) {
            if ($userDiscount['reduce_cost'] >= $itemTotalFee) {
                throw new ResourceException('优惠券不适用该订单');
            }
            foreach ($orderData['items'] as  $key => $item) {
                if (($item['order_item_type'] ?? '') && $item['order_item_type'] == 'gift') {
                    continue;
                }
                if ($item['total_fee'] < 100) {
                    continue;
                }
                $newOrderData[] = $item;
            }
            $lastOrderItemData = [];
            foreach ($newOrderData as $key => $orderItem) {
                if ($orderItem['coupon_valid'] ?? false) {
                    $percent = round(bcdiv($orderItem['total_fee'], $itemTotalFee, 5), 4);
                    if ($couponCashDiscount && $key == count($newOrderData) - 1) {
                        $couponCashDiscount[$key] = $userDiscount['reduce_cost'] - array_sum($couponCashDiscount);
                    } else {
                        $couponCashDiscount[$key] = (count($newOrderData) == 1) ? $userDiscount['reduce_cost'] : round(bcmul($userDiscount['reduce_cost'], $percent, 2));
                    }
                    $orderItem['discount_fee'] += $couponCashDiscount[$key];
                    $orderItem['total_fee'] -= $couponCashDiscount[$key];
                }
                $discountDesc = [
                    'id' => $userDiscount['card_id'],
                    'type' => 'coupon_discount',
                    'discount_fee' => $couponCashDiscount[$key],
                    'rule' => '代金券抵扣'.($couponCashDiscount[$key] / 100)."元",
                ];
                $orderItem['discount_info'] = $orderItem['discount_info'] ?? [];
                array_push($orderItem['discount_info'], $discountDesc);
                $lastOrderItemData[$orderItem['item_id']] = $orderItem;
            }

            foreach ($orderData['items'] as $k => $order) {
                if (($lastOrderItemData[$order['item_id']] ?? []) && (!($order['order_item_type'] ?? '') || $order['order_item_type'] == 'normal')) {
                    $orderData['items'][$k] = $lastOrderItemData[$order['item_id']];
                }
            }
        } else {
            $orderTotalFee = $orderData['total_fee'] - $orderData['freight_fee'];
            if ($userDiscount['reduce_cost'] >= $orderTotalFee) {
                throw new ResourceException('优惠券不适用该订单');
            }
            foreach ($orderData['items'] as  $key => $item) {
                if (($item['order_item_type'] ?? '') && $item['order_item_type'] == 'gift') {
                    continue;
                }
                if ($item['total_fee'] < 100) {
                    continue;
                }
                $newOrderData[] = $item;
            }
            $lastOrderItemData = [];
            foreach ($newOrderData as $key => $orderItem) {
                $percent = round(bcdiv($orderItem['total_fee'], $orderTotalFee, 5), 4);
                if ($couponCashDiscount && $key == count($newOrderData) - 1) {
                    $couponCashDiscount[$key] = $userDiscount['reduce_cost'] - array_sum($couponCashDiscount);
                } else {
                    $couponCashDiscount[$key] = (count($newOrderData) == 1) ? $userDiscount['reduce_cost'] : round(bcmul($userDiscount['reduce_cost'], $percent, 2));
                }
                $orderItem['discount_fee'] += $couponCashDiscount[$key];
                $orderItem['total_fee'] -= $couponCashDiscount[$key];
                $discountDesc = [
                    'id' => $userDiscount['card_id'],
                    'type' => 'coupon_discount',
                    'discount_fee' => $couponCashDiscount[$key],
                    'rule' => '代金券抵扣'.($couponCashDiscount[$key] / 100)."元",
                ];
                $orderItem['discount_info'] = $orderItem['discount_info'] ?? [];
                array_push($orderItem['discount_info'], $discountDesc);
                $lastOrderItemData[$orderItem['item_id']] = $orderItem;
            }
            foreach ($orderData['items'] as $k => $order) {
                if (($lastOrderItemData[$order['item_id']] ?? []) && (!($order['order_item_type'] ?? '') || $order['order_item_type'] == 'normal')) {
                    $orderData['items'][$k] = $lastOrderItemData[$order['item_id']];
                }
            }
        }
        $discountDesc = [
            'id' => $userDiscount['card_id'],
            'type' => 'coupon_discount',
            'discount_fee' => $userDiscount['reduce_cost'],
            'coupon_code' => $userDiscount['code'],
            'info' => '代金券优惠',
            'rule' => '代金券满'.$userDiscount['least_cost'] / 100 .'元减'.$userDiscount['reduce_cost'] / 100 .'元',
        ];
        $result['discount_fee'] = $userDiscount['reduce_cost'];
        $result['discount_info'] = $discountDesc;
        return $result;
    }

    //获取折扣券优惠金额
    private function getDiscountCouponDiscountFee(&$orderData, $userDiscount, $couponValid = false)
    {
        $discount = $userDiscount['discount'] / 100;
        $couponDiscount = 0;
        foreach ($orderData['items'] as &$item) {
            if (($item['order_item_type'] ?? '') && $item['order_item_type'] == 'gift') {
                continue;
            }
            if ($item['total_fee'] < 100) {
                continue;
            }
            if ($couponValid) {
                if (isset($item['coupon_valid']) && $item['coupon_valid']) {
                    $payFee = $item['total_fee'];
                    $curCouponDiscount = bcmul($payFee, $discount) ;
                    $item['total_fee'] = $payFee - $curCouponDiscount;
                    $item['discount_fee'] += $curCouponDiscount;
                    $couponDiscount += $curCouponDiscount;
                }
            } else {
                $payFee = $item['total_fee'];
                $curCouponDiscount = bcmul($payFee, $discount) ;
                $item['total_fee'] = $payFee - $curCouponDiscount;
                $item['discount_fee'] += $curCouponDiscount;
                $couponDiscount += $curCouponDiscount;
            }
            $discountDesc = [
                'id' => $userDiscount['card_id'],
                'type' => 'coupon_discount',
                'discount_fee' => $couponDiscount,
                'rule' => '代金券满'.$userDiscount['least_cost'] / 100 .'元减'.$userDiscount['reduce_cost'] / 100 .'元',
            ];
            $item['discount_info'] = $item['discount_info'] ?? [];
            array_push($item['discount_info'], $discountDesc);
        }
        $discountDesc = [
            'id' => $userDiscount['card_id'],
            'type' => 'coupon_discount',
            'coupon_code' => $userDiscount['code'],
            'discount_fee' => $couponDiscount,
            'info' => '折扣券优惠',
            'rule' => '折扣券满'.$userDiscount['least_cost'] / 100 .'元减'.$userDiscount['discount'].'%折扣',
        ];
        $result['discount_fee'] = $couponDiscount;
        $result['discount_info'] = $discountDesc;
        return $result;
    }

    public function lockingUserCoupon($userId, $code, $orderId)
    {
        $userDiscountService = new UserDiscountService();
        $userDiscount = $userDiscountService->freezeUserCard($code, $orderId, $userId);
        return $userDiscount;
    }

    /**
     * 获取会员优惠券折扣
     */
    public function getCouponDeduction($userId, $code, $companyId, $orderData)
    {
        $orderItemIds = [];
        $defaultItemIds = [];
        foreach ($orderData['items'] as $item) {
            if ($item['order_item_type'] == 'gift') {
                continue;//计算可用优惠券的时候，排除赠品
            }
            $orderItemIds[] = $item['item_id'];
            $defaultItemIds[$item['item_id']] = $item['default_item_id'];
        }

        $userDiscountService = new UserDiscountService();
        $filter = [
            'default_item_ids' => $defaultItemIds,
            'order_item_ids' => $orderItemIds,
            'user_id' => $userId,
            'code' => $code,
            'company_id' => $companyId,
            'status' => 1
        ];
        $userDiscount = $userDiscountService->getUserCardInfo($filter);

        if (!isset($userDiscount['detail'])) {
            return $orderData;
        }

        $userDiscount = $userDiscount['detail'];
        if (!$userDiscount) {
            throw new ResourceException('优惠券数据有误');
        }
        $orderTotalItemFee = 0;
        $totalFee = 0;
        if (is_array($userDiscount['rel_distributor_ids'])) {
            if (!in_array($orderData['distributor_id'], $userDiscount['rel_distributor_ids'])) {
                throw new ResourceException('优惠券只能在规定店铺使用');
            }
        }
        if (is_array($userDiscount['rel_item_ids'])) {
            $couponValid = false;
            foreach ($orderData['items'] as $k => $item) {
                $itemFilter = [
                    'company_id' => $companyId,
                    'item_id' => $item['item_id'],
                ];
                $itemFilter = $this->__getItemInfo($itemFilter);
                $orderData['items'][$k]['coupon_valid'] = in_array($item['item_id'], $orderItemIds);
                switch ($userDiscount['use_bound']) {
                    case 1:
                        if (!in_array($item['item_id'], $userDiscount['rel_item_ids'])) {
                            $orderData['items'][$k]['coupon_valid'] = false;
                        }
                        break;
                    case 2:
                        if (!in_array($itemFilter['item_main_cat_id'], $userDiscount['rel_item_ids'])) {
                            $orderData['items'][$k]['coupon_valid'] = false;
                        }
                        break;
                    case 3:
                        if (empty(array_intersect($itemFilter['tag_id'], $userDiscount['rel_item_ids']))) {
                            $orderData['items'][$k]['coupon_valid'] = false;
                        }
                        break;
                    case 4:
                        if (!in_array($itemFilter['brand_id'], $userDiscount['rel_item_ids'])) {
                            $orderData['items'][$k]['coupon_valid'] = false;
                        }
                        break;
                    case 5:
                        if (in_array($item['item_id'], $userDiscount['rel_item_ids'])) {
                            $orderData['items'][$k]['coupon_valid'] = false;
                        }
                        break;
                }
                if (!$orderData['items'][$k]['coupon_valid']) {
                    continue;
                }
                $orderTotalItemFee += $item['item_fee'];
                $totalFee += $item['total_fee'];
                $couponValid = true;
            }
            if (!$couponValid) {
                throw new ResourceException('优惠券不适用于该商品');
            }
        } else {
            foreach ($orderData['items'] as $k => $item) {
                $orderData['items'][$k]['coupon_valid'] = in_array($item['item_id'], $orderItemIds);
                $orderTotalItemFee += $item['item_fee'];
                $totalFee += $item['total_fee'];
            }
        }

        if ($totalFee > 0 && intval($userDiscount['least_cost']) > $totalFee) {
            throw new ResourceException('优惠券不满足使用金额条件');
        }

        // 判断是否有跨境税费-如果有，折扣不包含跨境税费
        if (isset($orderData['total_tax'])) {
            $totalFee = $totalFee - $orderData['total_tax'];
        }
        //计算优惠券优惠金额
        $totalDiscountFee = $this->computeKaquanDiscountFee($userDiscount, $totalFee);

        //订单中商品分摊有会员优惠金
        $orderData = $this->apportionDiscountFee($orderData, $totalDiscountFee, $totalFee);

        $orderData['coupon_discount'] = $totalDiscountFee;
        $orderData['discount_fee'] += $totalDiscountFee;
        $orderData['total_fee'] -= $totalDiscountFee;

        $orderData['discount_info'] = $orderData['discount_info'] ?? [];
        array_push($orderData['discount_info'], $this->discountInfo);
        $orderData['coupon_info'] = $this->discountInfo;
        return $orderData;
    }

    //计算优惠券优惠金额
    private function computeKaquanDiscountFee($userDiscount, $totalFee)
    {
        $this->discountInfo = [
            'id' => $userDiscount['card_id'],
            'coupon_code' => $userDiscount['code'],
            'info' => $userDiscount['title'],
            'dm_card_code' => $userDiscount['dm_card_code'] ?? '',
        ];
        if ($userDiscount['card_type'] == 'cash') {
            if ($userDiscount['reduce_cost'] >= $totalFee) {
                throw new ResourceException('优惠券不适用该订单');
            }
            $totalDiscountFee = $userDiscount['reduce_cost'];
            $this->discountInfo['type'] = 'cash_discount';
            $this->discountInfo['rule'] = '代金券满'.($userDiscount['least_cost'] / 100) .'元减'.($totalDiscountFee / 100)."元";
        } elseif ($userDiscount['card_type'] == 'discount') {
            $discount = bcdiv($userDiscount['discount'], 100, 2);
            // 如果超过最高限额则直接用最高限额计算优惠金额
            if (($userDiscount['most_cost'] > 0) && ($totalFee > $userDiscount['most_cost'])) {
                $totalFee = $userDiscount['most_cost'];
            }
            $totalDiscountFee = bcmul($totalFee, $discount) ;
            if ($totalDiscountFee >= $totalFee) {
                throw new ResourceException('优惠券不适用该订单');
            }
            $this->discountInfo['type'] = 'coupon_discount';
            $this->discountInfo['rule'] = '折扣券满'.$userDiscount['least_cost'] / 100 .'元减'.$userDiscount['discount'].'%优惠';
        }
        $this->discountInfo['discount_fee'] = $totalDiscountFee;
        return $totalDiscountFee;
    }

    //订单中商品分摊有会员优惠金
    private function apportionDiscountFee($orderData, $discountFee, $orderTotalFee)
    {
        $orderItems = []; //$orderData['items'];
        //获取需要使用优惠券的商品
        foreach ($orderData['items'] as $keyid => $value) {
            if ($value['coupon_valid']) {
                $orderItems[] = $value;
                unset($orderData['items'][$keyid]);
            }
        }
        $orderItemDiscountFee = [];
        $orderItemCount = count($orderItems);
        $discountInfo = $this->discountInfo;
        $total_fee = array_column($orderItems, 'total_fee');
        array_multisort($total_fee, SORT_ASC, $orderItems);
        $discountFeePlus = 0;
        foreach ($orderItems as $key => $value) {
            $percent = round(bcdiv($value['total_fee'], $orderTotalFee, 6), 4);
            if ($key == $orderItemCount - 1) {
                $discount_fee = bcsub($discountFee, $discountFeePlus);
            } else {
                $discount_fee = ($orderItemCount == 1) ? $discountFee : round(bcmul($discountFee, $percent, 2));
                $discountFeePlus += $discount_fee;
            }
            $discountInfo['discount_fee'] = $discount_fee;
            $value['discount_fee'] += $discount_fee;
            $value['total_fee'] -= $discount_fee;
            $value['coupon_discount'] = $discount_fee;

            if (isset($value['discount_info'])) {
                array_push($value['discount_info'], $discountInfo);
            } else {
                $value['discount_info'][] = $discountInfo;
            }
            $orderData['items'][] = $value;
        }
        $orderData['items'] = array_values($orderData['items']);
        return $orderData;
    }

    /**
     * 获取会员折扣最大的优惠券
     */
    public function getOptimalCoupon($userId, $companyId, $orderData)
    {
        $orderItemIds = [];
        $defaultItemIds = [];
        $orderTotalItemFee = 0;
        foreach ($orderData['items'] as $item) {
            $orderTotalItemFee += $item['total_fee'];

            if ($item['order_item_type'] == 'gift') {
                continue;
            }//赠品不计算优惠券
            $orderItemIds[] = $item['item_id'];
            $defaultItemIds[$item['item_id']] = $item['default_item_id'] ?? $item['item_id'];
        }

        $filter['company_id'] = $companyId;
        $filter['user_id'] = $userId;
        $filter['card_type'] = ['discount', 'cash'];
        $filter['use_platform'] = 'mall';
        $filter['least_cost|lte'] = $orderTotalItemFee;
        $filter['distributor_id'] = $orderData['distributor_id'];
        $filter['status'] = [1,4];
        $filter['begin_date|lte'] = time();
        $filter['end_date|gt'] = time();
        $filter['item_id'] = array_column($orderData['items'], 'item_id');

        //获取当前购物车商品可用的优惠券列表 smallCat
        $userDiscountService = new UserDiscountService();
        $cardLists = $userDiscountService->getNewUserCardList($filter);

        //处理用户当前拥有的 所有卡券 的商品使用范围
        //todo 如果用户的卡券很多，这里会有性能问题
        $cardRelItems = [];
        if ($cardLists['total_count'] > 0) {
            $cardIds = array_unique(array_column($cardLists['list'], 'card_id'));
            foreach ($cardIds as $cardId) {
                $discountCardService = new KaquanService(new DiscountCardService());
                $cardInfo = $discountCardService->getCardInfoById($companyId, $cardId, $orderItemIds, $defaultItemIds);
                if ($cardInfo) {
                    $cardRelItems[$cardId] = array_filter(explode(',', $cardInfo['rel_item_ids']));
                }
            }
        }

        $maxDiscount = 0;
        $optimalCode = '';
        if ($cardLists['total_count'] > 0) {
            foreach ($cardLists['list'] as $coupon) {
                $discount = 0;
                $totalFee = $orderTotalItemFee;//默认为全部商品的金额

                if (isset($cardRelItems[$coupon['card_id']])) {
                    $coupon['rel_item_ids'] = $cardRelItems[$coupon['card_id']];
                }

                if (is_array($coupon['rel_item_ids'])) {
                    $totalFee = 0;
                    foreach ($orderData['items'] as $item) {
                        //如果是全部商品可用， rel_item_ids 为空数组
                        if (!$coupon['rel_item_ids']) {
                            $totalFee += $item['total_fee'];
                        } elseif (in_array($item['item_id'], $coupon['rel_item_ids'])) {
                            $totalFee += $item['total_fee'];
                        }
                    }
                }

                // 判断是否有跨境税费-如果有，折扣不包含跨境税费
                if (isset($orderData['total_tax'])) {
                    $totalFee = $totalFee - $orderData['total_tax'];
                }

                //是否满足优惠券的门槛金额
                if ($totalFee == 0 || $totalFee < $coupon['least_cost']) {
                    continue;
                }

                if ($coupon['card_type'] == 'cash') {
                    $discount = $coupon['reduce_cost'];
                } elseif ($coupon['card_type'] == 'discount') {
                    $discount = $coupon['discount'] * $totalFee / 100;
                }

                if ($discount < $totalFee && $maxDiscount < $discount) {
                    $maxDiscount = $discount;
                    $optimalCode = $coupon['code'];
                }
            }
        }

        return $optimalCode;
    }

    /**
     * 积分抵扣
     * @param  string $companyId   企业ID
     * @param  array $orderData   订单数据
     * @param  int $memberPoint 会员积分
     * @param  int $upvaluation 升值倍数
     * @param  int $uppoints 当日升值积分数
     * @return array  抵扣数据
     */
    public function getPointDeduction($companyId, $orderData, $memberPoint, $upvaluation = false, $uppoints = 0, $isTry = false)
    {
        // 检查会员积分是否足够
        if ($memberPoint < $orderData['point_use']) {
            throw new PointResourceException("{point}不足");
        }
        // 获取积分规则并检查积分抵扣限制
        $pointService = new PointMemberRuleService($companyId);
        $moneyOutLimit = $pointService->moneyOutLimit($orderData['point_use'], $orderData['total_fee']);
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':moneyOutLimit:' . json_encode($moneyOutLimit));
        if (!$moneyOutLimit) {
            app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':moneyOutLimit:' . json_encode($moneyOutLimit));

            throw new PointResourceException("超出本单可用{point}抵扣");
        }

        // 订单商品根据价格重新排序，从高到低
        for ($i = 0; $i < count($orderData['items']); $i++) {
            for ($j = 0; $j < count($orderData['items']); $j++) {
                if (($orderData['items'][$i]['price'] * $orderData['items'][$i]['num']) < ($orderData['items'][$j]['price'] * $orderData['items'][$j]['num'])) {
                    $tmp = $orderData['items'][$i];
                    $orderData['items'][$i] = $orderData['items'][$j];
                    $orderData['items'][$j] = $tmp;
                }
            }
        }

        // 初始化变量
        $tmpPoint = 0; // 已使用的积分
        $tmpPointFee = 0; // 已抵扣的金额
        $totalSub = 0; // 总抵扣金额
        $totalPointSub = 0; // 总抵扣积分
        $sharePoints = 0; // 分摊的积分
        $can_use_point = false; // 是否可以使用积分
        $last_money_for_integral = $orderData['total_fee']; // 剩余可抵扣商品金额
        $last_money_for_integral = $orderData['total_fee'] - $orderData['freight_fee']; // 剩余可抵扣金额，去除运费
        $total_point_use = $orderData['point_use']; // 要使用的积分

        $total_fee_by_point_fee = $pointService->pointToMoney($orderData['point_use']);
        if($total_fee_by_point_fee > $orderData['total_fee']){
            $total_fee_by_point_fee = $orderData['total_fee'];
        }

        if($total_fee_by_point_fee >= $last_money_for_integral){
            $item_fee_by_point_fee = $last_money_for_integral;
        }else{
            $item_fee_by_point_fee = $total_fee_by_point_fee;
        }
        $item_point_use = 0; // 要使用的积分
        $item_point_use = $pointService->moneyToPoint($companyId, $item_fee_by_point_fee);
        if($item_point_use >= $total_point_use){
            $item_point_use = $total_point_use;
        }

        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':item_point_use:' . json_encode($item_point_use));
        $isIntegerPoint = false;
        $freightSharePoints = 0; // 运费分摊的积分

        // 获取积分抵扣运费的配置
        $pointRule = $pointService->getPointRule($companyId);
        $canDeductFreight = isset($pointRule['can_deduct_freight']) ? $pointRule['can_deduct_freight'] : false;
        if ($canDeductFreight) { // 抵扣运费 
            $point_freight_use = $total_point_use - $item_point_use;
            $point_freight_fee = $total_fee_by_point_fee - $item_fee_by_point_fee;
            $orderData['freight_point'] = $point_freight_use;
            $orderData['freight_point_fee'] = $point_freight_fee;
            $orderData['freight_fee'] = $orderData['freight_fee'] - $point_freight_fee;

            // // $point_freight_use = $pointService->moneyToPoint($companyId, $orderData['freight_fee']);
            // if($total_point_use >= $point_freight_use){
            //     $point_freight_use = $point_freight_use;
            //     $point_freight_fee = $orderData['freight_fee'];
            //     $item_fee_by_point_fee = $total_fee_by_point_fee - $point_freight_fee;
            //     $item_point_use = $total_point_use - $point_freight_use;
            // }else{
            //     $point_freight_use = $total_point_use;
            //     $point_freight_fee = $pointService->pointToMoney($point_freight_use);
            //     $item_fee_by_point_fee = 0;
            //     $item_point_use = 0;
            // }
        }
        // 按商品金额比例分摊积分和金额到每个商品（改进版本，确保完全分摊）
        $totalItemFee = 0; // 商品总金额（不含运费）
        foreach ($orderData['items'] as $item) {
            $totalItemFee += $item['total_fee'];
        }
        
        $remainingPoints = $item_point_use; // 剩余待分摊积分
        $remainingFee = $item_fee_by_point_fee; // 剩余待分摊金额
        
        // 第一轮：按比例分摊
        foreach ($orderData['items'] as $k => &$itemInfo) {
            // 计算当前商品应分摊的比例
            $itemProportion = $totalItemFee > 0 ? bcdiv($itemInfo['total_fee'], $totalItemFee, 6) : 0;
            
            if ($k === count($orderData['items']) - 1) {
                // 最后一个商品，使用剩余的所有积分和金额
                $itemInfo['share_points'] = $remainingPoints;
                $itemInfo['point_fee'] = $remainingFee;
                // 更新剩余积分和金额为0
                $remainingPoints = 0;
                $remainingFee = 0;
            } else {
                // 按比例分摊积分和金额
                $sharePoints = intval(ceil(bcmul($item_point_use, $itemProportion, 6))); // 使用ceil向上取整
                $shareFee = intval(ceil(bcmul($item_fee_by_point_fee, $itemProportion, 6))); // 使用ceil向上取整
                
                // 确保分摊的积分不超过剩余积分
                if ($sharePoints > $remainingPoints) {
                    $sharePoints = $remainingPoints;
                }
                
                // 确保分摊的金额不超过剩余金额
                if ($shareFee > $remainingFee) {
                    $shareFee = $remainingFee;
                }
                
                // 确保分摊的金额不超过商品金额
                if ($shareFee > $itemInfo['total_fee']) {
                    $shareFee = $itemInfo['total_fee'];
                }
                
                $itemInfo['share_points'] = $sharePoints;
                $itemInfo['point_fee'] = $shareFee;
                
                // 更新剩余积分和金额
                $remainingPoints -= $sharePoints;
                $remainingFee -= $shareFee; // 使用整数减法
            }
        }
        
        // // 第二轮：简单循环分配剩余积分和金额
        // if ($remainingPoints > 0 || $remainingFee > 0) {
        //     // 找出还有剩余金额的商品
        //     $availableItems = [];
        //     foreach ($orderData['items'] as $k => $item) {
        //         $remainingItemFee = $item['total_fee'] - $item['point_fee'];
        //         if ($remainingItemFee > 0) {
        //             $availableItems[] = $k;
        //         }
        //     }
            
        //     if (count($availableItems) > 0) {
        //         // 简单循环分配剩余积分
        //         $itemIndex = 0;
        //         while ($remainingPoints > 0 && count($availableItems) > 0) {
        //             $k = $availableItems[$itemIndex % count($availableItems)];
        //             $orderData['items'][$k]['share_points'] += 1;
        //             $remainingPoints -= 1;
        //             $itemIndex++;
        //         }
                
        //         // 简单循环分配剩余金额
        //         $itemIndex = 0;
        //         while ($remainingFee > 0 && count($availableItems) > 0) {
        //             $k = $availableItems[$itemIndex % count($availableItems)];
        //             $addFee = min($remainingFee, 1); // 最小金额单位：1分
        //             $orderData['items'][$k]['point_fee'] += $addFee;
        //             $remainingFee -= $addFee;
        //             $itemIndex++;
        //         }
        //     }
        // }
        
        // 更新商品积分字段和剩余金额
        foreach ($orderData['items'] as $k => &$itemInfo) {
            // 设置商品积分字段
            $itemInfo['point'] = $itemInfo['share_points'];
            
            // 更新商品金额（减去积分抵扣的金额）
            if ($itemInfo['total_fee'] > $itemInfo['point_fee']) {
                $itemInfo['total_fee'] = $itemInfo['total_fee'] - $itemInfo['point_fee'];
            } else {
                $itemInfo['point_fee'] = $itemInfo['total_fee'];
                $itemInfo['total_fee'] = 0;
            }
            
            // 记录日志
            app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':item_' . $k . '_share_points:' . json_encode($itemInfo['share_points']));
            app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':item_' . $k . '_point_fee:' . json_encode($itemInfo['point_fee']));
            app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':item_' . $k . '_remaining_total_fee:' . json_encode($itemInfo['total_fee']));
        }

        // 计算积分抵扣的金额
        $point_use_to_money = $pointService->pointToMoney($orderData['point_use']);
        $_point_use = $pointService->moneyToPoint($companyId, $point_use_to_money);
        if ($_point_use == $orderData['point_use']) {
            $isIntegerPoint = true;
        }

        // // 先处理商品积分抵扣
        // $itemMaxPoints = 0;
        // foreach ($orderData['items'] as $k => &$itemInfo) {
        //     app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':item_point_use:' . json_encode($item_point_use));
        //     if ($item_point_use <= 0) {
        //         continue;
        //     }
        //     if ($itemInfo !== end($orderData['items'])) {
        //         // 计算商品分摊的积分
        //         $total_fee = $itemInfo['total_fee'];
        //         $proportion = $orderData['total_fee'] != 0 ? bcdiv($total_fee, $last_money_for_integral, 5) : 0;
        //         $sharePoints = floor(bcmul($item_point_use, $proportion, 5)); // round
        //         if ($sharePoints > $item_point_use - $tmpPoint) {
        //             $sharePoints = $item_point_use - $tmpPoint;
        //         }
        //         $pointFee = $pointService->pointToMoney($sharePoints);
        //         if ($pointFee > $total_fee) { // 积分抵扣的金额大于商品金额，则取商品金额
        //             $pointFee = $total_fee;
        //         }
        //         $_sharePoints = $pointService->moneyToPoint($companyId, $pointFee);
        //         $pointFee = $pointService->pointToMoney($_sharePoints);
        //         $sharePoints = $_sharePoints;
        //         if ($sharePoints <= 0) {
        //             $itemInfo['share_points'] = 0;
        //             $itemInfo['point_fee'] = 0;
        //             continue;
        //         }
        //         $itemInfo['share_points'] = $sharePoints;
        //         $itemInfo['point'] = $sharePoints;
        //         $itemMaxPoints = $pointService->moneyToPoint($companyId, $itemInfo['total_fee']);
        //         if ($itemInfo['total_fee'] > $pointFee) {
        //             if ($orderData['pay_type'] == 'point') {
        //                 $pointFee = $itemInfo['total_fee'];
        //                 $itemInfo['total_fee'] = 0;
        //             } else {
        //                 $itemInfo['total_fee'] -= $pointFee;
        //             }
        //         } else {
        //             if ($orderData['pay_type'] == 'point') {
        //                 $pointFee = $itemInfo['total_fee'];
        //             }
        //             $itemInfo['total_fee'] = 0;
        //         }
        //         $itemInfo['point_fee'] = $pointFee;
        //         $tmpPoint += $sharePoints;
        //     } else {
        //         // 处理最后一个商品，使用剩余积分
        //         $sharePoints = $item_point_use - $tmpPoint;
        //         if ($sharePoints < 0) {
        //             $sharePoints = 0;
        //         }
        //         $pointFee = $pointService->pointToMoney($sharePoints);
        //         // 如果积分抵扣金额大于商品总价，则取商品总价
        //         if ($pointFee > $itemInfo['total_fee']) {
        //             $pointFee = $itemInfo['total_fee'];
        //         }
        //         // $_sharePoints = $pointService->moneyToPoint($companyId, $pointFee);
        //         // $sharePoints = $_sharePoints;
                
        //         // $pointFee = $pointService->pointToMoney($_sharePoints,$itemInfo['total_fee']);
        //         if ($itemInfo['total_fee'] > $pointFee) {
        //             $itemInfo['total_fee'] -= $pointFee;
        //         } else {
        //             $pointFee = $itemInfo['total_fee'];
        //             $itemInfo['total_fee'] = 0;
        //         }
        //         $itemInfo['point_fee'] = $pointFee;
        //         $itemInfo['share_points'] = $sharePoints;
        //         $itemInfo['point'] = $sharePoints;
        //         $tmpPoint += $sharePoints;
        //     }
        //     $totalSub += $pointFee;
        //     if (!$can_use_point && $sharePoints > 0) {
        //         $can_use_point = true;
        //     }
        // }
        // unset($itemInfo);
        // $maxPointZiti = $tmpPoint;

        // app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':超出本单可用{point}抵扣', [':point-diff:' => $item_point_use - $tmpPoint]);
        // app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':超出本单可用{point}抵扣', [':tmpPoint:' => $tmpPoint]);
        // app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':超出本单可用{point}抵扣', [':item_point_use' => $item_point_use]);
        // app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':超出本单可用{point}抵扣', [':canDeductFreight:' => $canDeductFreight]);
        // app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':超出本单可用{point}抵扣', [':isTry:' => $isTry]);
        // app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':超出本单可用{point}抵扣', [':item_point_use > $tmpPoint:' => $item_point_use . $tmpPoint]);

        // // 如果没开启积分可抵扣运费，但是使用的积分没有分摊完，抛错
        // if (!$isTry && !$canDeductFreight && $item_point_use > $tmpPoint && ($item_point_use - $tmpPoint  > 1) ) {
        //     app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':moneyOutLimit:' . json_encode($tmpPoint));

        //     app('log')->info('超出本单可用{point}抵扣', [':point-diff:' => $item_point_use - $tmpPoint]);
        //     app('log')->info('超出本单可用{point}抵扣', [':tmpPoint:' => $tmpPoint]);
        //     app('log')->info('超出本单可用{point}抵扣', [':item_point_use' => $item_point_use]);
        //     throw new PointResourceException("超出本单可用{point}抵扣");
        // }

//         // 如果配置允许抵扣运费，且还有剩余积分，则抵扣运费
//         if ($canDeductFreight && $orderData['freight_fee'] > 0 && $item_point_use > $tmpPoint) {
//             $remainingPoints = $item_point_use - $tmpPoint;
//             $maxFreightPoints = $pointService->moneyToPoint($companyId, $orderData['freight_fee']);
//             $freightSharePoints = min($remainingPoints, $maxFreightPoints);
//             $pointFee = $pointService->pointToMoney($freightSharePoints);

//             if ($orderData['freight_fee'] > $pointFee) {
//                 if ($orderData['pay_type'] == 'point') {
//                     $pointFee = $orderData['freight_fee'];
//                 }
//             } else {
//                 $pointFee = $orderData['freight_fee'];
//             }

//             $totalSub += $pointFee;
//             $orderData['freight_point'] = $freightSharePoints;
//             $orderData['freight_point_fee'] = $pointFee;
// //            $last_money_for_integral -= $pointFee;
//             $tmpPoint += $freightSharePoints;
//         }

        // 检查是否有商品可以使用积分
        // if (!$isTry && $can_use_point == false) {
        //     throw new PointResourceException("商品不能使用{point}进行抵扣");
        // }

        // // 确保总抵扣金额不超过订单总金额
        // if ($totalSub > $orderData['total_fee']) {
        //     $totalSub = $orderData['total_fee'];
        // }
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':before_point_deduction_total_fee:' . json_encode($orderData['total_fee']));
        $orderData['before_point_deduction_total_fee'] = $orderData['total_fee'];
        // $orderData['total_fee'] -= $totalSub; //point_fee
        $orderData['total_fee'] = $orderData['total_fee'] - $total_fee_by_point_fee ;
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':after_point_deduction_total_fee:' . json_encode($orderData['total_fee']));
        $orderData['point_fee'] = $total_fee_by_point_fee;
        $orderData['point_fee_item'] = $item_fee_by_point_fee;//$total_fee_by_point_fee;
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':total_fee_by_point_fee:' . json_encode($total_fee_by_point_fee));
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':point_fee:' . json_encode($orderData['point_fee']));
        $orderData['real_use_point'] = $total_point_use;
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':total_point_use:' . json_encode($total_point_use));
        $orderData['real_use_point_ziti'] = $item_point_use;
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':total_fee_by_point_fee:' . json_encode($total_fee_by_point_fee));
        return $orderData;
    }

    /**
     * 获取订单最大可抵扣积分
     * @param  $companyId        企业ID
     * @param  $orderData        订单数据
     * @param  $memberPoint      会员积分
     * @param  $pointupvaluation 升值活动数据
     * @return                   [description]
     */
    public function getUpTotalMaxPointDeduction($companyId, $orderData, $memberPoint, $pointupvaluation)
    {
        //订单商品根据价格重新排序
        for ($i = 0; $i < count($orderData['items']); $i++) {
            for ($j = 0; $j < count($orderData['items']); $j++) {
                if (($orderData['items'][$i]['price'] * $orderData['items'][$i]['num']) < ($orderData['items'][$j]['price'] * $orderData['items'][$j]['num'])) {
                    $tmp = $orderData['items'][$i];
                    $orderData['items'][$i] = $orderData['items'][$j];
                    $orderData['items'][$j] = $tmp;
                }
            }
        }

        $upvaluation = $pointupvaluation['upvaluation'];
        $pointService = new PointMemberRuleService($companyId);
        // 当前用户积分最大可用升值积分
        $user_uppoint = bcmul($memberPoint, ($upvaluation - 1));

        $max_base_point = $pointService->orderMaxMoneyToPoint($companyId, $orderData['total_fee']);//订单最大可抵扣基础积分
        $order_max_upoint = intval(bcmul(bcdiv($max_base_point, $upvaluation, 5), ($upvaluation - 1)));// 本单最大可用升值积分
        $order_max_point = bcdiv($order_max_upoint, ($upvaluation - 1));// 订单最大积分
        $order_max_point = min($order_max_point, $memberPoint);

        $order_max_upoint = bcmul($order_max_point, ($upvaluation - 1));// 订单最大升值积分
        $user_max_uppoint = min($user_uppoint, $order_max_upoint);// 会员最大升值积分
        $diff_max = bcsub(bcmul($pointupvaluation['max_up_point'], ($upvaluation - 1)), $pointupvaluation['uppoints']);
        // 全部使用翻倍积分
        if ($user_max_uppoint <= $diff_max) {
            $max_canuse_uppoint = min($user_max_uppoint, $diff_max);// 用户最大可用升值积分
            $result = $this->__allMaxUpPointDeduction($companyId, $orderData, $max_base_point, $max_canuse_uppoint, $order_max_point, $upvaluation);
        } else {
            $user_max_uppoint = min($diff_max, $user_max_uppoint);
            $max_canuse_uppoint = min($user_max_uppoint, $diff_max);// 用户最大可用升值积分
            // 反推最大基础积分
            $max_canuse_point_base = bcdiv($max_canuse_uppoint, ($upvaluation - 1));
            $max_canuse_uppoint = bcmul($max_canuse_point_base, ($upvaluation - 1));
            $order_max_point = bcsub($max_base_point, $max_canuse_uppoint);// 订单最大可用积分
            $order_max_point = min($order_max_point, $memberPoint);
            // 部分使用翻倍积分
            $result = $this->__maxSomeUpPointDeduction($companyId, $orderData, $max_base_point, $max_canuse_uppoint, $order_max_point, $upvaluation);
        }
        return $result;
    }

    /**
     * 最大可抵扣积分，全部升值
     * @param  $companyId          企业ID
     * @param  $orderData          订单数据
     * @param  $max_base_point     订单最大基础积分
     * @param  $max_canuse_uppoint 订单最大可用升值积分
     * @param  $order_max_point    订单最大积分
     * @param  $upvaluation        升值倍数
     * @return
     */
    public function __allMaxUpPointDeduction($companyId, $orderData, $max_base_point, $max_canuse_uppoint, $order_max_point, $upvaluation)
    {
        $pointService = new PointMemberRuleService($companyId);
        $item_max_canuse_uppoint = $max_canuse_uppoint;
        $item_total_fee = $orderData['total_fee'];
        $totalSub = 0;
        $useLimit = 0;
        $result = ['limit_point' => $order_max_point, 'max_point' => $useLimit, 'max_money' => $totalSub, 'max_uppoint' => $max_canuse_uppoint];
        // 如果有运费，优先抵扣运费
        $pointRule = $pointService->getPointRule($companyId);
        $canDeductFreight = isset($pointRule['can_deduct_freight']) ? $pointRule['can_deduct_freight'] : false;
        if ($canDeductFreight && $orderData['freight_fee'] > 0) {
            $freight_result = $this->__freightUpPointDeduction($companyId, $orderData['freight_fee'], $upvaluation, $order_max_point);
            $totalSub += $freight_result['freight_point_fee'];
            $useLimit += $freight_result['freight_canuse_point'];

            // 商品剩余可用升值积分
            $item_max_canuse_uppoint = bcsub($max_canuse_uppoint, $freight_result['freight_uppoints']);
            $item_total_fee = bcsub($orderData['total_fee'], $freight_result['freight_point_fee']);
        }
        if ($item_max_canuse_uppoint <= 0) {
            $result['max_point'] = $useLimit;
            $result['max_money'] = $totalSub;
            return $result;
        }
        $item_tmp_uppoint = 0;
        $share_points = 0;
        foreach ($orderData['items'] as $k => &$itemInfo) {
            if ($itemInfo !== end($orderData['items'])) {
                $proportion = $itemInfo['total_fee'] != 0 ? bcdiv($itemInfo['total_fee'], $item_total_fee, 5) : 0;
                $share_uppoints = round(bcmul($item_max_canuse_uppoint, $proportion, 5));// 分摊的升值积分
                $_sharePoints = bcsub($item_max_canuse_uppoint, $item_tmp_uppoint);
                if ($share_uppoints > $_sharePoints) {
                    $share_uppoints = $_sharePoints;
                }
            } else {
                //数组末尾
                $share_uppoints = bcsub($item_max_canuse_uppoint, $item_tmp_uppoint);// 分摊的升值积分
                if ($share_uppoints < 0) {
                    $share_uppoints = 0;
                }
            }
            $share_points = bcdiv($share_uppoints, ($upvaluation - 1));// 分摊积分
            $share_uppoints = bcmul($share_points, ($upvaluation - 1));// 分摊的升值积分
            $share_base_point = bcmul($share_points, $upvaluation);// 分摊的基础积分
            $point_fee = $pointService->pointToMoney($share_base_point);

            $itemInfo['point_fee'] = $point_fee;
            $itemInfo['share_points'] = $share_points;
            $itemInfo['point'] = $share_points;
            $itemInfo['share_uppoints'] = $share_uppoints;

            $item_tmp_uppoint += $share_uppoints;
            $totalSub += $point_fee;
            $useLimit += $share_points;
        }
        $result['max_point'] = $useLimit;
        $result['max_money'] = $totalSub;
        return $result;
    }

    /**
     * 最大可抵扣积分，部分升值
     * @param  $companyId          企业ID
     * @param  $orderData          订单数据
     * @param  $max_base_point     订单最大基础积分
     * @param  $max_canuse_uppoint 订单最大可用升值积分
     * @param  $order_max_point    订单最大可用积分
     * @param  $upvaluation        升值倍数
     * @return
     */
    public function __maxSomeUpPointDeduction($companyId, $orderData, $max_base_point, $max_canuse_uppoint, $order_max_point, $upvaluation)
    {
        $pointService = new PointMemberRuleService($companyId);
        $item_max_canuse_uppoint = $max_canuse_uppoint;
        $item_total_fee = $orderData['total_fee'];
        $item_total_base_point = $max_base_point;// 商品总的基础积分
        $totalSub = $useLimit = $tmp_uppoint = 0;
        $_order_max_point = bcsub($max_base_point, $max_canuse_uppoint);
        $result = ['limit_point' => $order_max_point, 'max_point' => $useLimit, 'max_money' => $totalSub, 'max_uppoint' => $max_canuse_uppoint];
        // 如果有运费，优先抵扣运费
        $pointRule = $pointService->getPointRule($companyId);
        $canDeductFreight = isset($pointRule['can_deduct_freight']) ? $pointRule['can_deduct_freight'] : false;
        if ($canDeductFreight && $orderData['freight_fee'] > 0) {
            // 运费抵扣数据
            $freight_result = $this->__freightSomeUpPointDeduction($companyId, $orderData['freight_fee'], $upvaluation, $max_canuse_uppoint, $max_base_point, $order_max_point);
            $totalSub += $freight_result['freight_point_fee'];
            $useLimit += $freight_result['freight_canuse_point'];

            // 商品剩余可用升值积分
            $item_max_canuse_uppoint = bcsub($max_canuse_uppoint, $freight_result['freight_uppoints']);
            $item_total_fee = bcsub($orderData['total_fee'], $freight_result['freight_point_fee']);
            $item_total_base_point = bcsub($max_base_point, $freight_result['freight_total_base_point']);
            if ($item_max_canuse_uppoint <= 0) {
                $max_canuse_uppoint = $freight_result['freight_uppoints'];
            }
            $tmp_uppoint += $freight_result['freight_uppoints'];
        }
        if ($item_total_base_point <= 0) {
            $result['max_point'] = $useLimit;
            $result['max_money'] = $totalSub;
            $result['max_uppoint'] = $max_canuse_uppoint;
        }
        $tmp_base_point = 0;
        $share_points = 0;
        foreach ($orderData['items'] as $k => &$itemInfo) {
            if ($itemInfo !== end($orderData['items'])) {
                // 商品最大基础积分 * （A商品金额/商品总金额） * 订单实付积分/订单最大基础积分 = 实付积分
                $proportion = $itemInfo['total_fee'] != 0 ? bcdiv($itemInfo['total_fee'], $item_total_fee, 5) : 0;
                $order_point_proportion = bcdiv($order_max_point, $max_base_point, 5);
                $share_points = bcmul(bcmul($item_total_base_point, $proportion), $order_point_proportion);// 分摊的实付积分 向下取整
                $share_base_point = bcmul($item_total_base_point, $proportion);// 分摊的基础积分
                $share_uppoints = bcsub($share_base_point, $share_points);// 分摊的升值积分
                $_share_uppoints = bcsub($max_canuse_uppoint, $tmp_uppoint);
                if ($share_uppoints > $_share_uppoints) {
                    $share_uppoints = $_share_uppoints;
                }
            } else {
                //数组末尾
                $share_base_point = bcsub($item_total_base_point, $tmp_base_point);// 分摊的基础积分
                $order_point_proportion = bcdiv($order_max_point, $max_base_point, 5);
                // $share_points = bcmul($share_base_point, $order_point_proportion);// 实付积分
                $share_points = $order_max_point - $useLimit;
                $share_uppoints = bcsub($max_canuse_uppoint, $tmp_uppoint);// 分摊的升值积分
                if ($share_uppoints < 0) {
                    $share_uppoints = 0;
                }
            }
            $share_point_up = bcdiv($share_uppoints, ($upvaluation - 1));// 分摊积分中的升值积分
            $share_point_base = bcsub($share_points, $share_point_up);// 分摊积分中的基础积分
            $share_uppoints = bcmul($share_point_up, ($upvaluation - 1));// 重新计算分摊的升值积分
            $share_base_point = bcadd(bcmul($share_point_up, $upvaluation), $share_point_base);// 转换为基础积分
            $point_fee = $pointService->pointToMoney($share_base_point);

            $itemInfo['point_fee'] = $point_fee;
            $itemInfo['share_points'] = $share_points;
            $itemInfo['point'] = $share_points;
            $itemInfo['share_uppoints'] = $share_uppoints;
            $tmp_base_point += $share_base_point;
            $totalSub += $point_fee;
            $useLimit += $share_points;
            $tmp_uppoint += $share_uppoints;
        }
        $result['max_point'] = min($result['limit_point'], $useLimit);
        $result['max_money'] = $totalSub;
        $result['max_uppoint'] = $tmp_uppoint;
        return $result;
    }

    /**
     * 积分升值，抵扣
     * @param  [type] $companyId        企业Id
     * @param  [type] $orderData        订单数据
     * @param  [type] $memberPoint      会员积分
     * @param  [type] $pointupvaluation 升值活动数据
     * @return [type]                   订单数据
     */
    public function getUpTotalUsePointDeduction($companyId, $orderData, $memberPoint, $pointupvaluation)
    {
        if ($memberPoint < $orderData['point_use']) {
            throw new PointResourceException("{point}不足");
        }
        //订单商品根据价格重新排序
        for ($i = 0; $i < count($orderData['items']); $i++) {
            for ($j = 0; $j < count($orderData['items']); $j++) {
                if (($orderData['items'][$i]['price'] * $orderData['items'][$i]['num']) < ($orderData['items'][$j]['price'] * $orderData['items'][$j]['num'])) {
                    $tmp = $orderData['items'][$i];
                    $orderData['items'][$i] = $orderData['items'][$j];
                    $orderData['items'][$j] = $tmp;
                }
            }
        }
        $upvaluation = $pointupvaluation['upvaluation'];
        $pointService = new PointMemberRuleService($companyId);
        // 当前用户积分最大可用升值积分
        $user_uppoint = bcmul($orderData['point_use'], ($upvaluation - 1));
        $diff_max = bcsub(bcmul($pointupvaluation['max_up_point'], ($upvaluation - 1)), $pointupvaluation['uppoints']);

        $max_base_point = $pointService->orderMaxMoneyToPoint($companyId, $orderData['total_fee']);//订单最大可抵扣基础积分
        $order_max_upoint = intval(bcmul(bcdiv($max_base_point, $upvaluation, 5), ($upvaluation - 1)));// 本单最大可用升值积分
        $order_max_point = bcdiv($order_max_upoint, ($upvaluation - 1));
        $order_max_point = min($order_max_point, $memberPoint, $orderData['point_use']);
        $order_max_upoint = bcmul($order_max_point, ($upvaluation - 1));

        $user_max_uppoint = min($user_uppoint, $order_max_upoint);
        $diff_max = bcsub(bcmul($pointupvaluation['max_up_point'], ($upvaluation - 1)), $pointupvaluation['uppoints']);
        $max_canuse_uppoint = min($user_max_uppoint, $diff_max);
        // 全部使用翻倍积分
        if ($user_max_uppoint <= $diff_max) {
            $orderData = $this->__allUseUpPointDeduction($companyId, $orderData, $max_base_point, $max_canuse_uppoint, $order_max_point, $upvaluation);
        } else {
            $user_max_uppoint = min($diff_max, $user_max_uppoint);
            $max_canuse_uppoint = min($user_max_uppoint, $diff_max);// 用户最大可用升值积分
            // 反推最大基础积分
            $max_canuse_point_base = bcdiv($max_canuse_uppoint, ($upvaluation - 1));
            $max_canuse_uppoint = bcmul($max_canuse_point_base, ($upvaluation - 1));
            $order_max_point = bcsub($max_base_point, $max_canuse_uppoint);// 订单最大可用积分
            $order_max_point = min($order_max_point, $memberPoint, $orderData['point_use']);
            $orderData = $this->__useSomeUpPointDeduction($companyId, $orderData, $max_base_point, $max_canuse_uppoint, $order_max_point, $upvaluation);
        }
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':point_use:' . json_encode( $orderData['point_use']));
        app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':real_use_point:' . json_encode( $orderData['real_use_point']));
        if ($orderData['real_use_point'] > $orderData['point_use']) {
            app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':real_use_point:' . json_encode( $orderData['real_use_point']));
            app('log')->info(__FUNCTION__ . ':' . __LINE__ . ':point_use:' . json_encode( $orderData['point_use']));
            throw new PointResourceException("超出本单可用{point}抵扣");
        }
        return $orderData;
    }

    /**
     * 积分使用抵扣，全部升值
     * @param  $companyId          企业ID
     * @param  $orderData          订单数据
     * @param  $max_base_point     订单最大基础积分
     * @param  $max_canuse_uppoint 订单最大可用升值积分
     * @param  $order_max_point    订单最大积分
     * @param  $upvaluation        升值倍数
     * @return                     订单数据
     */
    public function __allUseUpPointDeduction($companyId, $orderData, $max_base_point, $max_canuse_uppoint, $order_max_point, $upvaluation)
    {
        $pointService = new PointMemberRuleService($companyId);
        $item_max_canuse_uppoint = $max_canuse_uppoint;
        $item_total_fee = $orderData['total_fee'];
        $freightSharePoints = $freightShareUppoints = 0;
        $tmp_points = $tmp_uppoint = $tmp_share_point_up = $totalSub = 0;
        $freight_result = [
            'freight_uppoints' => 0,
            'freight_point_fee' => 0,
            'freight_canuse_point' => 0,
            'freight_canuse_point_up' => 0,
        ];
        // 如果有运费，优先抵扣运费
        $pointRule = $pointService->getPointRule($companyId);
        $canDeductFreight = isset($pointRule['can_deduct_freight']) ? $pointRule['can_deduct_freight'] : false;
        if ($canDeductFreight && $orderData['freight_fee'] > 0) {
            // 运费抵扣数据
            $freight_result = $this->__freightUpPointDeduction($companyId, $orderData['freight_fee'], $upvaluation, $order_max_point);
            $freightSharePoints = $freight_result['freight_canuse_point'];
            $tmp_points += $freightSharePoints;
            $tmp_uppoint += $freight_result['freight_uppoints'];
            $tmp_share_point_up += $freight_result['freight_canuse_point_up'];
            $totalSub += $freight_result['freight_point_fee'];

            // 商品剩余可用升值积分
            $item_max_canuse_uppoint = bcsub($max_canuse_uppoint, $freight_result['freight_uppoints']);
            $item_total_fee = bcsub($orderData['total_fee'], $freight_result['freight_point_fee']);

            $orderData['freight_point'] = $freightSharePoints;
            $orderData['freight_point_fee'] = $freight_result['freight_point_fee'];
            $orderData['freight_uppoints'] = $freight_result['freight_uppoints'];
        }
        if ($item_max_canuse_uppoint <= 0) {
            $orderData['before_point_deduction_total_fee'] = $orderData['total_fee'];
            $orderData['total_fee'] -= $totalSub;
            $orderData['point_fee'] = $totalSub;
            $orderData['real_use_point'] = $freight_result['freight_canuse_point'];
            $orderData['uppoint_use'] = $tmp_uppoint;
            $orderData['point_up_use'] = $tmp_share_point_up;
            return $orderData;
        }
        $share_points = 0;
        foreach ($orderData['items'] as $k => &$itemInfo) {
            if ($itemInfo !== end($orderData['items'])) {
                $proportion = $itemInfo['total_fee'] != 0 ? bcdiv($itemInfo['total_fee'], $item_total_fee, 5) : 0;
                $share_uppoints = round(bcmul($item_max_canuse_uppoint, $proportion, 5));
                $_share_uppoints = bcsub($max_canuse_uppoint, $tmp_uppoint);
                if ($share_uppoints > $_share_uppoints) {
                    $share_uppoints = $_share_uppoints;
                }
            } else {
                //数组末尾
                $share_uppoints = bcsub($max_canuse_uppoint, $tmp_uppoint);// 分摊的升值积分
                if ($share_uppoints < 0) {
                    $share_uppoints = 0;
                }
            }
            $share_points = bcdiv($share_uppoints, ($upvaluation - 1));// 分摊积分
            $share_uppoints = bcmul($share_points, ($upvaluation - 1));// 分摊的升值积分
            $share_base_point = bcmul($share_points, $upvaluation);// 分摊的基础积分
            $point_fee = $pointService->pointToMoney($share_base_point);
            $itemInfo['total_fee'] -= $point_fee;
            $itemInfo['total_fee'] < 0 and $itemInfo['total_fee'] = 0;

            $itemInfo['point_fee'] = $point_fee;
            $itemInfo['share_points'] = $share_points;
            $itemInfo['point'] = $share_points;
            $itemInfo['share_uppoints'] = $share_uppoints;

            $tmp_uppoint += $share_uppoints;
            $totalSub += $point_fee;
            $tmp_points += $share_points;
            $tmp_share_point_up += $share_points;
        }
        $orderData['before_point_deduction_total_fee'] = $orderData['total_fee'];
        $orderData['total_fee'] -= $totalSub;
        $orderData['point_fee'] = $totalSub;
        $orderData['real_use_point'] = $tmp_points;
        $orderData['uppoint_use'] = $tmp_uppoint;// 商家补贴的积分数
        $orderData['point_up_use'] = $tmp_share_point_up;// 使用的升值积分数
        return $orderData;
    }

    /**
     * 积分使用抵扣，部分升值
     * @param  $companyId          企业ID
     * @param  $orderData          订单数据
     * @param  $max_base_point     订单最大基础积分
     * @param  $max_canuse_uppoint 订单最大可用升值积分
     * @param  $order_max_point    订单最大积分
     * @param  $upvaluation        升值倍数
     * @return                     订单数据
     */
    public function __useSomeUpPointDeduction($companyId, $orderData, $max_base_point, $max_canuse_uppoint, $order_max_point, $upvaluation)
    {
        $pointService = new PointMemberRuleService($companyId);
        $item_max_canuse_uppoint = $max_canuse_uppoint;
        $item_total_fee = $orderData['total_fee'];
        $item_total_base_point = $max_base_point;// 商品总的基础积分
        $item_max_point = $order_max_point;// 商品总的最大可用积分
        $_order_max_point = bcsub($max_base_point, $max_canuse_uppoint);
        $tmp_points = 0;
        $tmp_uppoint = 0;
        $tmp_share_point_up = 0;// 商家补贴升值积分
        $totalSub = 0;
        // 如果有运费，优先抵扣运费
        $pointRule = $pointService->getPointRule($companyId);
        $canDeductFreight = isset($pointRule['can_deduct_freight']) ? $pointRule['can_deduct_freight'] : false;
        if ($canDeductFreight && $orderData['freight_fee'] > 0) {
            // 运费抵扣数据
            $freight_result = $this->__freightSomeUpPointDeduction($companyId, $orderData['freight_fee'], $upvaluation, $max_canuse_uppoint, $max_base_point, $order_max_point);
            $totalSub += $freight_result['freight_point_fee'];
            // 商品剩余可用升值积分
            $item_max_canuse_uppoint = bcsub($max_canuse_uppoint, $freight_result['freight_uppoints']);
            $item_total_fee = bcsub($orderData['total_fee'], $freight_result['freight_point_fee']);
            $item_total_base_point = bcsub($max_base_point, $freight_result['freight_total_base_point']);
            if ($item_max_canuse_uppoint <= 0) {
                $max_canuse_uppoint = $freight_result['freight_uppoints'];
            }
            $item_max_point = bcsub($order_max_point, $freight_result['freight_canuse_point']);
            $tmp_points = $freight_result['freight_canuse_point'];
            $tmp_uppoint += $freight_result['freight_uppoints'];
            $tmp_share_point_up += $freight_result['freight_canuse_point_up'];

            $orderData['freight_point'] = $freight_result['freight_canuse_point'];
            $orderData['freight_point_fee'] = $freight_result['freight_point_fee'];
            $orderData['freight_uppoints'] = $freight_result['freight_uppoints'];
        }
        if ($item_total_base_point <= 0 || $item_max_point <= 0) {
            $orderData['before_point_deduction_total_fee'] = $orderData['total_fee'];
            $orderData['total_fee'] -= $totalSub;
            $orderData['point_fee'] = $totalSub;
            $orderData['real_use_point'] = $freight_result['freight_canuse_point'];
            $orderData['uppoint_use'] = $tmp_uppoint;// 商家补贴的升值积分
            $orderData['point_up_use'] = $tmp_share_point_up;// 使用的升值积分
            return $orderData;
        }
        $tmp_base_point = 0;
        $share_points = 0;
        foreach ($orderData['items'] as $k => &$itemInfo) {
            if ($itemInfo !== end($orderData['items'])) {
                // 商品最大基础积分 * （A商品金额/商品总金额） * 订单实付积分/订单最大基础积分 = 实付积分
                $proportion = $itemInfo['total_fee'] != 0 ? bcdiv($itemInfo['total_fee'], $item_total_fee, 5) : 0;
                $order_point_proportion = bcdiv($order_max_point, $max_base_point, 5);
                $share_points = bcmul(bcmul($item_total_base_point, $proportion), $order_point_proportion);// 分摊的实付积分 向下取整
                $share_base_point = bcmul($item_total_base_point, $proportion);// 分摊的基础积分
                $share_uppoints = bcsub($share_base_point, $share_points);// 分摊的升值积分
                $_share_uppoints = bcsub($max_canuse_uppoint, $tmp_uppoint);
                if ($share_uppoints > $_share_uppoints) {
                    $share_uppoints = $_share_uppoints;
                }
            } else {
                //数组末尾
                $share_base_point = bcsub($item_total_base_point, $tmp_base_point);// 分摊的基础积分
                $order_point_proportion = bcdiv($order_max_point, $max_base_point, 5);
                // $share_points = bcmul($share_base_point, $order_point_proportion);// 实付积分
                $share_points = bcsub($order_max_point, $tmp_points);// 实付积分
                $share_uppoints = bcsub($max_canuse_uppoint, $tmp_uppoint);// 分摊的升值积分

                if ($share_uppoints < 0) {
                    $share_uppoints = 0;
                }
            }
            $share_point_up = bcdiv($share_uppoints, ($upvaluation - 1));// 分摊积分中的升值积分
            $share_point_base = bcsub($share_points, $share_point_up);// 分摊积分中的基础积分
            $share_uppoints = bcmul($share_point_up, ($upvaluation - 1));// 重新计算分摊的升值积分
            $share_base_point = bcadd(bcmul($share_point_up, $upvaluation), $share_point_base);// 转换为基础积分
            $point_fee = $pointService->pointToMoney($share_base_point);
            $itemInfo['total_fee'] -= $point_fee;
            $itemInfo['total_fee'] < 0 and $itemInfo['total_fee'] = 0;
            if ('point' == $orderData['pay_type']) {
                $itemInfo['total_fee'] = 0;
            }
            $itemInfo['point_fee'] = $point_fee;
            $itemInfo['share_points'] = $share_points;
            $itemInfo['point'] = $share_points;
            $itemInfo['share_uppoints'] = $share_uppoints;
            $itemInfo['share_point_up'] = $share_point_up;
            $tmp_base_point += $share_base_point;
            $totalSub += $point_fee;
            $tmp_points += $share_points;
            $tmp_uppoint += $share_uppoints;
            $tmp_share_point_up += $share_point_up;
        }
        $orderData['before_point_deduction_total_fee'] = $orderData['total_fee'];
        $orderData['total_fee'] -= $totalSub;
        $orderData['point_fee'] = $totalSub;
        $orderData['real_use_point'] = $tmp_points;
        $orderData['uppoint_use'] = $tmp_uppoint;// 商家补贴的升值积分
        $orderData['point_up_use'] = $tmp_share_point_up;// 使用的升值积分
        return $orderData;
    }

    /**
     * 运费全部升值抵扣
     * @param  [type] $companyId   [description]
     * @param  [type] $freight_fee [description]
     * @param  [type] $upvaluation [description]
     * @return [type]              [description]
     */
    public function __freightUpPointDeduction($companyId, $freight_fee, $upvaluation, $order_max_point)
    {
        $pointService = new PointMemberRuleService($companyId);
        $freight_base_point = $pointService->moneyToPoint($companyId, $freight_fee);// 最大基础积分
        $freight_uppoints = bcmul(bcdiv($freight_base_point, $upvaluation, 5), ($upvaluation - 1));// 最大升值积分
        $freight_canuse_point = bcdiv($freight_uppoints, ($upvaluation - 1));// 最大可用积分
        $freight_canuse_point = min($freight_canuse_point, $order_max_point);// 可用积分
        $freight_uppoints = bcmul($freight_canuse_point, ($upvaluation - 1));// 可用升值积分
        $freight_base_point = bcmul($freight_canuse_point, $upvaluation);// 转换为基础积分
        $freight_point_fee = $pointService->pointToMoney($freight_base_point);// 转换为金额
        $result = [
            'freight_uppoints' => $freight_uppoints,
            'freight_point_fee' => $freight_point_fee,
            'freight_canuse_point' => $freight_canuse_point,
            'freight_canuse_point_up' => $freight_canuse_point,
        ];
        return $result;
    }

    /**
     * 运费 部分升值抵扣
     * @param   $companyId [description]
     * @param   $freight_fee        运费金额（单位分）
     * @param   $upvaluation        升值倍数
     * @param   $max_canuse_uppoint 订单最大可用升值积分
     * @param   $max_base_point     订单最大可用基础积分
     * @param   $order_max_point    订单最大可用积分
     * @return                      [description]
     */
    public function __freightSomeUpPointDeduction($companyId, $freight_fee, $upvaluation, $max_canuse_uppoint, $max_base_point, $order_max_point)
    {
        $pointService = new PointMemberRuleService($companyId);
        $freight_base_point = $pointService->moneyToPoint($companyId, $freight_fee);// 运费基础积分
        // 整单抵扣金额小于运费抵扣时
        if ($freight_base_point > $max_base_point) {
            $freight_base_point = $max_base_point;
            $freight_canuse_point = bcsub($freight_base_point, $max_canuse_uppoint);
        } else {
            $freight_canuse_point = bcmul($freight_base_point, bcdiv($order_max_point, $max_base_point, 5));// 实付积分
        }
        if ($freight_canuse_point <= 0 && $freight_base_point > $max_base_point) {
            $freight_canuse_point = $order_max_point;
            $freight_uppoints = $max_canuse_uppoint;
        } else {
            $freight_uppoints = bcsub($freight_base_point, $freight_canuse_point);// 升值积分
        }
        if ($freight_canuse_point == 0) {
            $result = [
                'freight_uppoints' => 0,
                'freight_point_fee' => 0,
                'freight_canuse_point' => 0,
                'freight_canuse_point_up' => 0,
                'freight_total_base_point' => 0,
            ];
            return $result;
        }
        $freight_canuse_point_up = bcdiv($freight_uppoints, ($upvaluation - 1));// 可用的升值积分
        $freight_canuse_point_base = bcsub($freight_canuse_point, $freight_canuse_point_up);// 可用的基础积分
        $freight_uppoints = bcmul($freight_canuse_point_up, ($upvaluation - 1));// 重新计算的升值积分
        $freight_total_base_point = bcadd(bcmul($freight_canuse_point_up, $upvaluation), $freight_canuse_point_base);// 转换为基础积分
        $freight_point_fee = $pointService->pointToMoney($freight_total_base_point);// 转换为金额
        $result = [
            'freight_uppoints' => $freight_uppoints,
            'freight_point_fee' => $freight_point_fee,
            'freight_canuse_point' => $freight_canuse_point,
            'freight_canuse_point_up' => $freight_canuse_point_up,
            'freight_total_base_point' => $freight_total_base_point,
        ];
        return $result;
    }

    private function __getItemInfo($itemFilter)
    {
        $itemsService = new ItemsService();
        $item = $itemsService->getItem($itemFilter);

        $itemFilter['default_item_id'] = $item['default_item_id'];
        $itemFilter['item_main_cat_id'] = $item['item_main_cat_id'];
        $itemFilter['brand_id'] = $item['brand_id'];

        //查询商品的标签
        $itemFilter['tag_id'] = [];
        $tagFilter = [
            'item_id' => $itemFilter['default_item_id'],//商品标签只关联到主商品
            'company_id' => $itemFilter['company_id'],
        ];
        $itemsTagsService = new ItemsTagsService();
        $tagList = $itemsTagsService->getListTags($tagFilter, 1, -1, null, false);
        $itemFilter['tag_id'] = array_column($tagList['list'], 'tag_id');
        return $itemFilter;
    }
}
