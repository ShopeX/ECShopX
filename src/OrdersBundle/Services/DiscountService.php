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

use KaquanBundle\Services\UserDiscountService;
use MembersBundle\Services\MemberService;
use KaquanBundle\Services\MemberCardService;
use KaquanBundle\Services\VipGradeOrderService;

/**
 * 计算交易优惠信息
 *
 */
class DiscountService
{
    /**
     * 计算优惠信息
     */
    public function discount($data)
    {
        // 如果存在会员卡
        if (isset($data['member_card_code']) && $data['member_card_code']) {
            $return = $this->memberCardDiscount($data['company_id'], $data['user_id'], $data['member_card_code'], $data['total_fee']);
        } else {
            $return['pay_fee'] = $data['total_fee'];
            $return['discount_fee'] = 0;
        }

        // 优惠券优惠
        if (isset($data['coupon_code']) && $data['coupon_code']) {
            $return = $this->couponCardDiscount($data['user_id'], $data['coupon_code'], $data['shop_id'], $return);
        }

        return $return;
    }

    /**
     * 会员卡优惠
     *
     * @param int $user 用户ID
     * @param string $memberCardCode 会员卡号
     * @param string $totalFee 支付总金额
     */
    private function memberCardDiscount($companyId, $userId, $memberCardCode, $totalFee)
    {
        $return['pay_fee'] = $totalFee;
        $return['discount_fee'] = 0;

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
                'user_card_code' => $memberCardCode
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


        if ($privileges && ($privileges['discount'] ?? 0)) {
            // 获取折扣优惠金额
            $discountFee = bcmul($return['pay_fee'], bcdiv($privileges['discount'], 100, 2));

            // 获取折扣优惠金额
            $money = $return['pay_fee'] - $discountFee;

            $return['pay_fee'] = $money;
            $return['discount_fee'] += $discountFee;
            $return['discount_info'][] = [
                'member_card_code' => $memberCardCode,
                'discount_fee' => $discountFee,
                'info' => '会员卡折扣优惠',
                'rule' => '会员卡'.$privileges['discount'].'%优惠',
            ];
        }
        return $return;
    }

    /**
     * 优惠券优惠
     *
     * @param int $userId
     * @param string $couponCode
     * @param string $shopid 门店id
     * @param array $return 会员卡优惠后返回的数组
     */
    private function couponCardDiscount($userId, $couponCode, $shopid, $return)
    {
        //优惠卡券优惠
        $discountCardService = new UserDiscountService();
        $cardInfo = $discountCardService->getUserCardInfo(['code' => $couponCode, 'user_id' => $userId, 'status' => 1])['detail'];
        //如果存在优惠券， 并且适用于全部门店或者在指定门店可用
        $locationIdList = isset($cardInfo['rel_shops_ids']) ? $cardInfo['rel_shops_ids'] : null;
        if ($cardInfo && ($locationIdList == 'all' || empty($locationIdList) || in_array($shopid, $locationIdList))) {
            if ($cardInfo['card_type'] == 'discount') {
                // 获取折扣优惠金额
                $discountFee = bcmul($return['pay_fee'], bcdiv($cardInfo['discount'], 100, 2));
                // 获取折后金额
                $money = $return['pay_fee'] - $discountFee;
                $return['pay_fee'] = $money;
                $return['discount_fee'] += $discountFee;
                $return['discount_info'][] = [
                    'coupon_code' => $couponCode,
                    'discount_fee' => $discountFee,
                    'card_id' => $cardInfo['card_id'],
                    'info' => '优惠券折扣优惠',
                    'rule' => $cardInfo['discount'].'%优惠',
                ];
            } elseif ($cardInfo['card_type'] == 'cash') {
                if ($return['pay_fee'] >= $cardInfo['least_cost']) {
                    $return['pay_fee'] = ($return['pay_fee'] - $cardInfo['reduce_cost']);
                    $return['discount_fee'] += $cardInfo['reduce_cost'];
                    $return['discount_info'][] = [
                        'coupon_code' => $couponCode,
                        'discount_fee' => $cardInfo['reduce_cost'],
                        'card_id' => $cardInfo['card_id'],
                        'info' => '代金券优惠',
                        'rule' => '代金券满'.$cardInfo['least_cost'] / 100 .'元减'.$cardInfo['reduce_cost'] / 100 .'元',
                    ];
                }
            }
        }

        return $return;
    }
}
