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

namespace YoushuBundle\Services;

use KaquanBundle\Entities\DiscountCards;
use KaquanBundle\Entities\RelItems;

class CouponService
{
    public $discountCardRepository;
    public $relItemsRepository;

    public function __construct()
    {
        // Hash: 0d723eca
        $this->discountCardRepository = app('registry')->getManager('default')->getRepository(DiscountCards::class);
        $this->relItemsRepository = app('registry')->getManager('default')->getRepository(RelItems::class);
    }

    /**
     * @return array
     * 添加/更新卡券信息
     */
    public function getData($params)
    {
        // Hash: 0d723eca
        $filter['card_id'] = $params['object_id'];
        $filter['company_id'] = $params['company_id'];
        $detail = $this->discountCardRepository->get($filter);
        $detail = $detail[0];
        if (empty($detail)) {
            return [];
        }

        //不支持 兑换券
        if ($detail['card_type'] == 'gift') {
            return [];
        }

        if ($detail['date_type'] == 'DATE_TYPE_FIX_TERM') {
            return [];
        }

        $external_coupon_id = $detail['card_id'];
        $coupon_name = $detail['title'];
        $coupon_type = $detail['card_type'] == 'cash' ? 1 : 2;
        $plan_count = $detail['quantity'];
        $start_time = (string)bcmul($detail['begin_date'], 1000);
        $end_time = (string)bcmul($detail['end_date'], 1000);
        $rule_description = $detail['description'];
        $amount_coupon = bcdiv($detail['reduce_cost'], 100, 2);
        $discount_coupon = bcdiv($detail['discount'], 100, 2);
        $amount_minimum = bcdiv($detail['least_cost'], 100, 2);
        $max_coupon_number_per_user = $detail['get_limit'];
        $is_all = true;
        $sku_ids = [];

        //线上商城使用
        if ($detail['use_platform'] == 'mall') {
            $relItem = $this->relItemsRepository->lists(['company_id' => $detail['company_id'], 'card_id' => $detail['card_id']]);
            if (!empty($relItem)) {
                $is_all = false;
                $sku_ids = array_filter(array_column($relItem, 'item_id'));
            }
        }

        $coupons[] = [
            'external_coupon_id' => $external_coupon_id,
            'coupon_name' => $coupon_name,
            'coupon_type' => 1, //1：商家券；2：微信券,
            'coupon_sub_type' => $coupon_type, //卡券子类型；1：代金券；2：打折券
            'plan_count' => (int)$plan_count,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'rule_description' => $rule_description,
            'amount_coupon' => (int)$amount_coupon,
            'discount_coupon' => (int)$discount_coupon,
            'amount_minimum' => (int)$amount_minimum,
            'release_status' => 1,
            'max_coupon_number_per_user' => (int)$max_coupon_number_per_user,
            'product' => [
                'is_all' => $is_all,
                'sku_ids' => $sku_ids
            ]
        ];

        return $coupons;
    }
}
