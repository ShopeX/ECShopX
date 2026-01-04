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

namespace OpenapiBundle\Traits\DiscountCard;

use Carbon\Carbon;

use KaquanBundle\Entities\RelItems;
use GoodsBundle\Entities\Items;
use KaquanBundle\Entities\UserDiscount;

trait DiscountCardTrait
{
    protected function handleDataToList(array &$list)
    {
        foreach ($list as &$item) {
            $rel_data = [];
            // 适用商品，true:全部商品；false:部分商品；category:指定商品管理分类；tag:指定商品标签；brand:指定商品品牌
            // 适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用
            switch ($item['use_bound']) {
                case '1':
                    $use_all_items = 'false';
                    $rel_data_ids = $this->__relItemsIds($item);
                    $rel_data = $this->__relItems($rel_data_ids);
                    break;
                case '2':
                    $use_all_items = 'category';
                    // $rel_data_ids = $this->__relItemsIds($item);
                    $rel_data = $this->__relCategory($item['apply_scope']);
                    break;
                case '3':
                    $use_all_items = 'tag';
                    // $rel_data_ids = $this->__relItemsIds($item);
                    $rel_data = $this->__relTag($item['apply_scope']);
                    break;
                case '4':
                    $use_all_items = 'brand';
                    // $rel_data_ids = $this->__relItemsIds($item);
                    $rel_data = $this->__relBrand($item['apply_scope']);
                    break;
                default:
                    $use_all_items = 'true';
                    break;
            }
            $item = [
                //卡券ID
                "card_id" => (int)($item["card_id"] ?? 0),
                //卡券类型,discount:折扣券;cash:满减券;
                "card_type" => (string)($item["card_type"] ?? 0),
                //卡券名称
                "title" => (string)($item["title"] ?? ""),
                //卡券使用优惠说明
                "description" => (string)($item["description"] ?? 0),
                //折扣券打折额度（百分比)
                "discount" => (string)($item["discount"] ?? 0),
                // 减免券，减免金额（元）
                "reduce_cost" => bcdiv($item['reduce_cost'] ?? 0, 100),
                //有效期的类型,DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后
                "date_type" => (string)($item["date_type"] ?? ""),
                //有效期-开始时间。date_type=DATE_TYPE_FIX_TIME_RANGE时为时间戳；date_type=DATE_TYPE_FIX_TERM时为固定天数；
                "begin_time" => (string)($item["begin_date"] ?? ""),
                // 有效期-结束时间。date_type=DATE_TYPE_FIX_TIME_RANGE时为时间戳；date_type=DATE_TYPE_FIX_TERM时为统一过期时间，未设置统一过期时间时为0；
                "end_time" => (string)($item["end_date"] ?? ""),
                // 有效期的有效天数，固定期限类型的为null
                "fixed_term" => (string)($item["fixed_term"] ?? ""),
                // 优惠券起用金额（单位:元）
                "least_cost" => bcdiv($item['least_cost'] ?? 0, 100),
                // 优惠券最高消费限额（单位:元）。card_type=discount时有值
                "most_cost" => bcdiv($item['most_cost'] ?? 0, 100),
                // 适用商品，true:全部商品；false:部分商品；category:指定商品管理分类；tag:指定商品标签；brand:指定商品品牌
                "use_all_items" => $use_all_items,
                // 适用商品数据
                'rel_data' => $rel_data,
                'left_quantity' => max($item['quantity'] - $item['get_num'], 0),
            ];
        }
    }

    private function __relItemsIds($item)
    {
        $relItemsRepository = app('registry')->getManager('default')->getRepository(RelItems::class);
        $rel_data_lists = $relItemsRepository->lists([
            'card_id' => $item['card_id'],
            'item_type' => 'normal',
        ]);
        $rel_data_ids = array_column($rel_data_lists, 'item_id');
        if (empty($rel_data_ids)) {
            return [];
        }
        return $rel_data_ids;
    }
    private function __relItems($rel_data_ids)
    {
        if (empty($rel_data_ids)) {
            return [];
        }
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $itemsLists = $itemsRepository->getItemsLists(['item_id' => $rel_data_ids], 'item_id, item_name');
        return array_column($itemsLists, 'item_name');
    }

    private function __relCategory($apply_scope)
    {
        if (empty($apply_scope)) {
            return [];
        }
        $data = explode(',', $apply_scope);
        asort($data);
        $data = implode($data, '/');
        return [$data];
    }

    private function __relTag($apply_scope)
    {
        if (empty($apply_scope)) {
            return [];
        }
        $data = explode(',', $apply_scope);
        return $data;
    }

    private function __relBrand($apply_scope)
    {
        if (empty($apply_scope)) {
            return [];
        }
        $data = explode(',', $apply_scope);
        return $data;
    }

    protected function handleDataToResult(array &$result)
    {
        $userDiscountRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
        $data = $userDiscountRepository->getUserCardList([
            'code' => $result['code'],
        ]);
        $data = $data[0];
        $result['begin_date'] = $data['begin_date'];
        $result['end_date'] = $data['end_date'];
        $result['status'] = $this->__status($data);
        unset($result['total_lastget_num'], $result['lastget_num']);
    }

    private function __status($item)
    {
        switch ($item['status']) {
            case '4':
            case '10':
            case '1':
                $status = '1';
                if ($item['end_date'] < time()) {
                    $status = '5';
                }
                break;
            case '6':
                $status = '5';
                break;
            default:
                $status = $item['status'];
                break;
        }
        $allStatus = [
            '1' => 'unused',
            '2' => 'redeemed',
            '5' => 'expired',
        ];
        return $allStatus[$status] ?? '';
    }

    protected function handleUserDataToList(array &$list)
    {
        foreach ($list as &$item) {
            $item = [
                //卡券ID
                "card_id" => (int)($item["card_id"] ?? 0),
                //卡券code
                "code" => (string)($item["code"] ?? 0),
                "plat_account" => (string)($item["user_id"] ?? 0),
                "card_type" => (string)($item["card_type"] ?? 0),
                "begin_date" => (string)($item["begin_date"] ?? ""),
                "end_date" => (string)($item["end_date"] ?? 0),
                "status" => $this->__status($item),
            ];
        }
    }

}
