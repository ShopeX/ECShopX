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

namespace GoodsBundle\Traits;

use PopularizeBundle\Services\PromoterGoodsService;
use GoodsBundle\Services\ItemsCategoryService;

trait ItemSearchFilter
{
    public function getFilter($input, $authinfo)
    {
        $filter = [];
        $filter['company_id'] = $authinfo['company_id'];
        if (is_array($input['item_id'] ?? null)) {
            $filter['item_id'] = (array)$input['item_id'];
        }
        if ($input['goods_id'] ?? null) {
            $filter['goods_id'] = (array)$input['goods_id'];
        }
        if ($input['promoter_shop_id'] ?? null) {
            $promoterGoodsService = new PromoterGoodsService();
            $goodsIds = $promoterGoodsService->lists(['user_id' => $input['promoter_shop_id'], 'company_id' => $filter['company_id']], 'goods_id');
            if (!$goodsIds) {
                return false;
            }
            $filter['goods_id'] = array_column($goodsIds['list'], 'goods_id');
        }

        $approveStatusArr = [];
        if (isset($input['approve_status'])) {
            $input['approve_status'] = explode(',', $input['approve_status']);
            foreach ($input['approve_status'] as $approveStatus) {
                if (in_array($approveStatus, ['onsale', 'only_show'])) {
                    $approveStatusArr[] = $approveStatus;
                }
            }
        }

        if ($approveStatusArr) {
            $filter['approve_status'] = $input['approve_status'];
        } else {
            $filter['approve_status'] = ['onsale', 'only_show'];
        }

        if (isset($input['category']) && $input['category'] && $input['category'] != 'undefined') {
            $filter['category_id'] = $input['category'];
        }

        if ($input['item_name'] ?? null) {
            $filter['item_name'] = trim($input['item_name']);
        }

        if ($input['keywords'] ?? null) {
            $filter['item_name'] = trim($input['keywords']);
        }

        if ($input['tag_id'] ?? null) {
            $filter['tag_id'] = $input['tag_id'];
        }

        if ($input['item_params'] ?? null) {
            $filter['item_params'] = $input['item_params'];
        }

        if ($input['regions_id'] ?? null) {
            $filter['regions_id'] = implode(',', $input['regions_id']);
        }

        if ($input['start_price'] ?? null) {
            $filter['price|gte'] = $input['start_price'] * 100;
        }

        if ($input['end_price'] ?? null) {
            $filter['price|lte'] = $input['end_price'] * 100;
        }

        if ($input['brand_id'] ?? null) {
            $filter['brand_id'] = $input['brand_id'];
        }

        $filter['item_type'] = $input['item_type'] ?? 'services';

        if (($input['distributor_id'] ?? 'false') !== 'false') {
            $filter['distributor_id'] = $input['distributor_id'];
            $filter['is_can_sale'] = true;
        }

        if (isset($input['is_promoter']) && $input['is_promoter']) {
            $filter['rebate'] = 1;
        }

        if (isset($input['rebate_type']) && $input['rebate_type']) {
            $filter['rebate_type'] = $input['rebate_type'];
        }

        $filter['is_default'] = true;

        if (isset($input['category_id']) && $input['category_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $ids = $itemsCategoryService->getItemIdsByCatId($input['category_id'], $filter['company_id']);
            if (!$ids) {
                return false;
            }
            if (isset($filter['item_id'])) {
                $filter['item_id'] = array_intersect($filter['item_id'], $ids);
            } else {
                $filter['item_id'] = $ids;
            }
        }
        return $filter;
    }

    public function getShopFilter($input, $authinfo, $isCanShow = true)
    {
        $filter = [];
        $filter['company_id'] = $authinfo['company_id'];
        if (is_array($input['item_id'] ?? null)) {
            $filter['item_id'] = (array)$input['item_id'];
        }
        if ($input['goods_id'] ?? null) {
            $filter['goods_id'] = (array)$input['goods_id'];
        }
        if ($input['promoter_shop_id'] ?? null) {
            $promoterGoodsService = new PromoterGoodsService();
            $goodsIds = $promoterGoodsService->lists(['user_id' => $input['promoter_shop_id'], 'company_id' => $filter['company_id']], 'goods_id');
            if (!$goodsIds) {
                return false;
            }
            $filter['goods_id'] = array_column($goodsIds['list'], 'goods_id');
        }
        if ($isCanShow) {
            $approveStatusArr = [];
            if (isset($input['approve_status'])) {
                $input['approve_status'] = explode(',', $input['approve_status']);
                foreach ($input['approve_status'] as $approveStatus) {
                    if (in_array($approveStatus, ['onsale', 'only_show'])) {
                        $approveStatusArr[] = $approveStatus;
                    }
                }
            }

            if ($approveStatusArr) {
                $filter['approve_status'] = $input['approve_status'];
            } else {
                $filter['approve_status'] = ['onsale', 'only_show'];
            }
        }

        if (isset($input['category']) && $input['category'] && $input['category'] != 'undefined') {
            $filter['category_id'] = $input['category'];
        }

        if ($input['keywords'] ?? null) {
            $filter['keywords'] = trim($input['keywords']);
        }

        if ($input['tag_id'] ?? null) {
            $filter['tag_id'] = $input['tag_id'];
        }

        if ($input['item_params'] ?? null) {
            $filter['item_params'] = $input['item_params'];
        }

        if ($input['regions_id'] ?? null) {
            $filter['regions_id'] = implode(',', $input['regions_id']);
        }

        if ($input['start_price'] ?? null) {
            $filter['price|gte'] = $input['start_price'] * 100;
        }

        if ($input['end_price'] ?? null) {
            $filter['price|lte'] = $input['end_price'] * 100;
        }

        if ($input['brand_id'] ?? null) {
            $filter['brand_id'] = $input['brand_id'];
        }

        $filter['item_type'] = $input['item_type'] ?? 'services';

        if (($input['distributor_id'] ?? 'false') !== 'false') {
            $filter['distributor_id'] = $input['distributor_id'];
            if ($isCanShow) {
                $filter['is_can_sale'] = true;
            }
        }

        if (isset($input['is_promoter']) && $input['is_promoter']) {
            $filter['rebate'] = 1;
        }

        if (isset($input['rebate_type']) && $input['rebate_type']) {
            $filter['rebate_type'] = $input['rebate_type'];
        }

        $filter['is_default'] = true;

        if (isset($input['category_id']) && $input['category_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $ids = $itemsCategoryService->getItemIdsByCatId($input['category_id'], $filter['company_id']);
            if (!$ids) {
                return false;
            }
            if (isset($filter['item_id'])) {
                $filter['item_id'] = array_intersect($filter['item_id'], $ids);
            } else {
                $filter['item_id'] = $ids;
            }
        }
        return $filter;
    }
}
