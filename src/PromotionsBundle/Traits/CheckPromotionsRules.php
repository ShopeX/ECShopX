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

namespace PromotionsBundle\Traits;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsTagsService;
use PromotionsBundle\Entities\PackageMainItemPromotions;
use PromotionsBundle\Entities\PackageItemPromotions;
use PromotionsBundle\Services\LimitService;
use PromotionsBundle\Services\PackageService;
use PromotionsBundle\Services\PromotionGroupsActivityService;
use PromotionsBundle\Services\PromotionSeckillActivityService;
use PromotionsBundle\Services\MarketingActivityService;
use PromotionsBundle\Services\BargainPromotionsService;

trait CheckPromotionsRules
{
    //组合商品促销
    public function checkActivityValidByPackage($packageData = [], $packageId = 0)
    {
        $items = array_column($packageData['items'], 'item_id');
        $mainItems = array_column($packageData['main_items'], 'item_id');
        $params = [
            'start_time' => $packageData['start_time'],
            'end_time' => $packageData['end_time'],
            'use_bound' => 1,
            'company_id' => $packageData['company_id'],
            'shop_ids' => [],
            'package_id' => $packageId,
            'main_item_ids' => $mainItems,
            'item_ids' => $items,
            'seckill_type' => 'normal',//只排除限时秒杀
        ];

        $this->checkGroup($params);//检查拼团
        $this->checkSecKill($params);//检查限时秒杀
        $this->checkBargain($params);//检查微信助力砍价
        $this->checkPackage($params);//检查
        return true;
    }

    //检查商品限购
    public function checkActivityValidByLimit($limitData = [], $itemIds = [], $limitId = 0)
    {
        $params = [
            'start_time' => $limitData['start_time'],
            'end_time' => $limitData['end_time'],
            'use_bound' => $limitData['use_bound'],
            'company_id' => $limitData['company_id'],
            'item_category' => $limitData['item_category'] ?? [],
            'tag_ids' => $limitData['tag_ids'] ?? [],
            'brand_ids' => $limitData['brand_ids'] ?? [],
            'shop_ids' => [],
            'limit_id' => $limitId,
            'item_ids' => $itemIds,
            'seckill_type' => 'normal',//只排除限时秒杀
        ];

        $this->checkGroup($params);//检查拼团
        $this->checkSecKill($params);//检查限时秒杀
        $this->checkBargain($params);//检查微信助力砍价
        $this->checkLimit($params);//检查商品限购
        return true;
    }

    //检查微信助力砍价
    public function checkActivityValidByBargain($companyId = 0, $itemId = 0, $beginTime = 0, $endTime = 0, $bargainId = 0)
    {
        $params = [
            'start_time' => $beginTime,
            'end_time' => $endTime,
            'use_bound' => 1,
            'company_id' => $companyId,
            'shop_ids' => [],
            'bargain_id' => $bargainId,
            'item_ids' => $itemId,
            'source_id' => 0,//微信助力砍价只有平台版本
        ];

        $this->checkMarketing($params);//满减，满折，加价购
        $this->checkGroup($params);//检查拼团
        $this->checkSecKill($params);//检查限时秒杀
        $this->checkBargain($params);//检查微信助力砍价
        $this->checkLimit($params);//检查商品限购
        $this->checkPackage($params);//检查组合商品促销
        return true;
    }

    //检查限时秒杀，限时特惠
    //seckill_type : normal 正常的秒杀活动， limited_time_sale 限时特惠
    public function checkActivityValidBySecKill($companyId = 0, $itemIds = [], $beginTime = 0, $endTime = 0, $secKillId = 0, $activityData = [])
    {
        $params = [
            'start_time' => $beginTime,
            'end_time' => $endTime,
            'use_bound' => 1,
            'company_id' => $companyId,
            'shop_ids' => [],
            'seckill_id' => $secKillId,
            'item_ids' => $itemIds,
            'source_id' => $activityData['source_id'] ?? 0,
        ];

        if ($activityData['distributor_id']) {
            $params['shop_ids'] = explode(',', $activityData['distributor_id']);
        }

        // 限时秒杀和限时特惠都需要检查满减、满折、满赠等活动
        if ($activityData['seckill_type'] == 'normal') {
            $this->checkMarketing($params);//满减，满折，加价购，满赠，会员优先购
            $this->checkLimit($params);//检查商品限购
            $this->checkPackage($params);//检查组合商品促销
        } elseif ($activityData['seckill_type'] == 'limited_time_sale') {
            // 限时特惠也需要检查满减、满折、满赠等活动，但不检查商品限购和组合商品促销
            $this->checkMarketing($params);//满减，满折，加价购，满赠，会员优先购
        }

        $this->checkGroup($params);//检查拼团
        $this->checkSecKill($params);//检查限时秒杀
        $this->checkBargain($params);//检查微信助力砍价
        return true;
    }

    //检查拼团
    public function checkActivityValidByGroup($companyId = 0, $goodsId = 0, $beginTime = 0, $endTime = 0, $groupId = 0)
    {
        $params = [
            'start_time' => $beginTime,
            'end_time' => $endTime,
            'use_bound' => 1,
            'company_id' => $companyId,
            'shop_ids' => [],
            'groups_activity_id' => $groupId,
            'source_id' => 0,//拼团只有平台可用
        ];

        //将 $goodsId 转换成 itemId
        $itemsService = new ItemsService();
        $params['item_ids'] = $itemsService->getItemIds(['goods_id' => $goodsId]);

        $this->checkMarketing($params);//满减，满折，加价购
        $this->checkGroup($params);//检查拼团
        $this->checkSecKill($params);//检查限时秒杀
        $this->checkBargain($params);//检查微信助力砍价
        $this->checkLimit($params);//检查商品限购
        $this->checkPackage($params);//检查组合商品促销
        return true;
    }

    //满减，满折，加价购活动校验
    //full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购
    public function checkActivityValidByMarketing($params)
    {
        // $params['start_time'] = strtotime($params['start_time']);
        // $params['end_time'] = strtotime($params['end_time']);
        $params['shop_ids'] = $params['shop_ids'] ?? [];
        // 满减、满折、满赠等活动需要同时检查限时秒杀和限时特惠
        $params['seckill_type'] = ['normal', 'limited_time_sale'];

        $marketingType = [$params['marketing_type']];//活动都要排除自身
        switch ($params['marketing_type']) {
            case 'full_minus':
                $marketingType[] = 'full_discount';
                break;

            case 'full_discount':
                $marketingType[] = 'full_minus';
                break;

            case 'member_preference':
                // 会员优先购只和限时秒杀冲突，不和限时特惠冲突
                $params['seckill_type'] = 'normal';
                break;
        }

        $this->checkMarketing($params, $marketingType);//满减，满折，加价购，
        $this->checkGroup($params);//检查拼团
        $this->checkSecKill($params);//检查限时秒杀和限时特惠
        $this->checkBargain($params);//检查微信助力砍价
        return true;
    }

    /**
     * 检查活动冲突
     * @param array $params
     * @param array $marketingType 满减 full_minus，满折 full_discount，加价购 plus_price_buy
     * @return bool
     */
    public function checkMarketing($params = [], $marketingType = [])
    {
        $service = new MarketingActivityService();
        $marketingIds = [];
        $filter = [
            'start_time|lte' => $params['end_time'],
            'end_time|gte' => max($params['start_time'], time()),//不判断过期活动
            'company_id' => $params['company_id'],
        ];

        if ($marketingType) {
            $filter['marketing_type'] = $marketingType;
        }

        //适用所有商品
        $rs = $service->lists(array_merge($filter, ['use_bound' => 0]));
        if ($rs['list']) {
            $marketingIds = array_column($rs['list'], 'marketing_id');
        }

        if ($params['use_bound'] == 0) {
            $marketingIdData = $service->getMarketingIdsByCompanyId($params['company_id']);
            if (!empty($marketingIdData)) {
                $temp = array_column($marketingIdData, 'marketing_id');
                $temp = array_unique($temp);
                $marketingIds = array_merge($marketingIds, $temp);
            }
        }

        //指定商品
        if ($params['use_bound'] == 1) {
            $itemIds = $params['item_ids'];
            $rs = $service->getMarketingIdByItems($itemIds, $params['company_id']);
            if ($rs) {
                $marketingIds = array_merge($marketingIds, array_keys($rs));
            }
        }

        //指定分类
        if ($params['use_bound'] == 2) {
            $itemCategory = $params['item_category'];
            $rs = $service->getMarketingIdByItemCategory($itemCategory, $params);
            if ($rs) {
                $marketingIds = array_merge($marketingIds, $rs);
            }
        }

        //指定标签
        if ($params['use_bound'] == 3) {
            $tagIds = $params['tag_ids'];
            $rs = $service->getMarketingIdByItemTags($tagIds, $params);
            if ($rs) {
                $marketingIds = array_merge($marketingIds, $rs);
            }
        }

        //指定品牌
        if ($params['use_bound'] == 4) {
            $brandIds = $params['brand_ids'];
            $rs = $service->getMarketingIdByItemBrand($brandIds, $params);
            if ($rs) {
                $marketingIds = array_merge($marketingIds, $rs);
            }
        }

        if (!$marketingIds) {
            return false;
        }//没有找到冲突
        $filter['marketing_id'] = $marketingIds;
        $rs = $service->lists($filter);
        foreach ($rs['list'] as $v) {
            $errorMessage = "相同时间内不能重复{$v['promotion_tag']}活动:{$v['marketing_name']}";
            if (isset($params['marketing_id']) && $v['marketing_id'] == $params['marketing_id']) {
                continue;
            }
            //参与活动的为平台
            if ($v['source_id'] == $params['source_id']) {
                throw new ResourceException($errorMessage);
            }
            //参与活动的店铺是否有交集
            if (array_intersect($v['shop_ids'], $params['shop_ids'])) {
                throw new ResourceException($errorMessage);
            }
        }
        return true;
    }

    public function checkGroup($params = [])
    {
        $filter = [
            'begin_time|lte' => $params['end_time'],
            'end_time|gte' => max($params['start_time'], time()),//排除过期活动
            'company_id' => $params['company_id'],
            'disabled' => false,
        ];
        //排除活动自身
        if (isset($params['groups_activity_id']) && $params['groups_activity_id']) {
            $filter['groups_activity_id|neq'] = $params['groups_activity_id'];
        }
        $service = new PromotionGroupsActivityService();
        $rs = $service->lists($filter);
        if (!$rs['list']) {
            return true;//不存在有效的团购活动，直接返回
        }

        $goodsIds = array_column($rs['list'], 'goods_id');
        $itemsService = new ItemsService();
        $errorMessage = '相同时间内已经存在团购活动';

        //全部商品
        if ($params['use_bound'] == 0) {
            //店铺端不支持团购，所以这里只有平台活动才会冲突
            $shop_ids = $params['shop_ids'] ?? [];
            if (!$shop_ids or implode(',', $shop_ids) == '0') {
                throw new ResourceException($errorMessage);
            }
        }

        //指定商品
        if ($params['use_bound'] == 1) {
            $itemIds = $params['item_ids'];
            $rs = $itemsService->count(['item_id' => $itemIds, 'goods_id' => $goodsIds]);
            if ($rs > 0) {
                throw new ResourceException($errorMessage);
            }
        }

        //指定分类
        if ($params['use_bound'] == 2) {
            $itemCategory = $params['item_category'];
            $rs = $itemsService->count(['item_category' => $itemCategory, 'goods_id' => $goodsIds]);
            if ($rs > 0) {
                throw new ResourceException($errorMessage);
            }
        }

        //指定标签
        if ($params['use_bound'] == 3) {
            $tagIds = $params['tag_ids'];
            //根据 $goodsIds 查询标签
            $itemIds = $itemsService->getItemIds(['goods_id' => $goodsIds]);
            $itemTagService = new ItemsTagsService();
            $rs = $itemTagService->getTagIdsByItem($itemIds, 1, -1);
            if (array_intersect($tagIds, $rs)) {
                throw new ResourceException($errorMessage);
            }
        }

        //指定品牌
        if ($params['use_bound'] == 4) {
            $brandIds = $params['brand_ids'];
            $rs = $itemsService->count(['brand_id' => $brandIds, 'goods_id' => $goodsIds]);
            if ($rs > 0) {
                throw new ResourceException($errorMessage);
            }
        }

        return true;//不存在冲突
    }

    public function checkSecKill($params = [])
    {
        $seckillTypeConf = [
            'normal' => '限时秒杀',
            'limited_time_sale' => '限时特惠',
        ];
        $errorMessage = '相同时间内已经存在秒杀活动';
        $filter = [
            'activity_start_time|lte' => $params['end_time'],
            'activity_end_time|gte' => max($params['start_time'], time()),//排除过期活动
            'company_id' => $params['company_id'],
            'disabled' => 0,
        ];
        //排除活动自身
        if (isset($params['seckill_id']) && $params['seckill_id']) {
            $filter['seckill_id|neq'] = $params['seckill_id'];
        }

        //seckill_type : normal 正常的秒杀活动， limited_time_sale 限时特惠
        if (isset($params['seckill_type']) && $params['seckill_type']) {
            $filter['seckill_type'] = $params['seckill_type'];
        }
        $service = new PromotionSeckillActivityService();
        $rs = $service->lists($filter);
        if (!$rs['list']) {
            return true;//不存在有效的秒杀活动，直接返回
        }

        //全部商品
        if ($params['use_bound'] == 0) {
            $errorMessage = '相同时间内已经存在';
            foreach ($rs['list'] as $v) {
                if ($v['source_id'] == $params['source_id'] || in_array($params['source_id'], $v['distributor_id'])) {
                    $errorMessage .= $seckillTypeConf[$v['seckill_type']] ?? '';
                    $errorMessage .= ':'.$v['activity_name'];
                    throw new ResourceException($errorMessage);
                }
            }
        }

        //$secKillIds = array_column($rs['list'], 'seckill_id');
        $secKillIds = [];
        $secKillName = [];
        foreach ($rs['list'] as $v) {
            $secKillIds[$v['seckill_type']][] = $v['seckill_id'];
            $secKillName[$v['seckill_type']][] = $v['activity_name'] ?? '';
        }

        foreach ($seckillTypeConf as $seckillType => $typeName) {
            if (isset($secKillIds[$seckillType]) && $secKillIds[$seckillType]) {
                $errorMessage = '相同时间内已经存在'.$typeName;
                $errorMessage .= '：'.implode(',', $secKillName[$seckillType]);
                $rs = $service->getSeckillItemList(['seckill_id' => $secKillIds[$seckillType], 'company_id' => $params['company_id']]);
                $secKillItemIds = array_column($rs['list'], 'item_id');
                $this->checkValidByItemIds($secKillItemIds, $params, $errorMessage);
            }
        }

        return true;//不存在冲突
    }

    public function checkBargain($params = [])
    {
        $errorMessage = '相同时间内已经存在助力砍价活动';
        $filter = [
            'begin_time|lte' => $params['end_time'],
            'end_time|gte' => max($params['start_time'], time()),//排除过期活动
            'company_id' => $params['company_id'],
        ];
        //排除活动自身
        if (isset($params['bargain_id']) && $params['bargain_id']) {
            $filter['bargain_id|neq'] = $params['bargain_id'];
        }
        $service = new BargainPromotionsService();
        $rs = $service->getList($filter);
        if (!$rs['list']) {
            return true;//不存在有效的助力活动，直接返回
        }

        //全部商品
        if ($params['use_bound'] == 0) {
            //店铺端不支持助力，所以这里只有平台活动才会冲突
            $shop_ids = $params['shop_ids'] ?? [];
            if (!$shop_ids or implode(',', $shop_ids) == '0') {
                throw new ResourceException($errorMessage);
            }
        }

        $bargainItemIds = array_column($rs['list'], 'item_id');
        $this->checkValidByItemIds($bargainItemIds, $params, $errorMessage);

        return true;//不存在冲突
    }

    public function checkValidByItemIds($checkItemIds = [], $params, $errorMessage = '活动存在冲突')
    {
        if (!$checkItemIds) {
            return false;
        }

        $itemsService = new ItemsService();

        //指定商品
        if ($params['use_bound'] == 1) {
            $itemIds = $params['item_ids'];
            if (!is_array($itemIds)) {
                $itemIds = [$itemIds];
            }
            if (array_intersect($itemIds, $checkItemIds)) {
                throw new ResourceException($errorMessage);
            }
        }

        //指定分类
        if ($params['use_bound'] == 2) {
            $itemCategory = $params['item_category'];
            $rs = $itemsService->count(['item_category' => $itemCategory, 'item_id' => $checkItemIds]);
            if ($rs > 0) {
                throw new ResourceException($errorMessage);
            }
        }

        //指定标签
        if ($params['use_bound'] == 3) {
            $tagIds = $params['tag_ids'];
            $itemTagService = new ItemsTagsService();
            $rs = $itemTagService->getTagIdsByItem($checkItemIds, 1, -1);
            if (array_intersect($tagIds, $rs)) {
                throw new ResourceException($errorMessage);
            }
        }

        //指定品牌
        if ($params['use_bound'] == 4) {
            $brandIds = $params['brand_ids'];
            $rs = $itemsService->count(['brand_id' => $brandIds, 'item_id' => $checkItemIds]);
            if ($rs > 0) {
                throw new ResourceException($errorMessage);
            }
        }
    }

    public function checkLimit($params = [])
    {
        $errorMessage = '相同时间内已经存在商品限购';
        $filter = [
            'start_time|lte' => $params['end_time'],
            'end_time|gte' => max($params['start_time'], time()),//排除过期活动
            'company_id' => $params['company_id'],
        ];
        //排除活动自身
        if (isset($params['limit_id']) && $params['limit_id']) {
            $filter['limit_id|neq'] = $params['limit_id'];
        }
        $service = new LimitService();
        $rs = $service->lists($filter);
        if (!$rs['list']) {
            return true;//不存在有效的活动，直接返回
        }
        $limitIds = [];

        //指定商品
        if ($params['use_bound'] == 1) {
            $itemIds = $params['item_ids'];
            $rs = $service->getLimitIdByItems($itemIds, $rs['list'], $params['company_id']);
            if ($rs) {
                $limitIds = array_merge($limitIds, $rs);
            }
        }

        //指定分类
        if ($params['use_bound'] == 2) {
            $itemCategory = $params['item_category'];
            $rs = $service->getLimitIdByItemCategory($itemCategory, $params);
            if ($rs) {
                $limitIds = array_merge($limitIds, $rs);
            }
        }

        //指定标签
        if ($params['use_bound'] == 3) {
            $tagIds = $params['tag_ids'];
            $rs = $service->getLimitIdByItemTags($tagIds, $params);
            if ($rs) {
                $limitIds = array_merge($limitIds, $rs);
            }
        }

        //指定品牌
        if ($params['use_bound'] == 4) {
            $brandIds = $params['brand_ids'];
            $rs = $service->getLimitIdByItemBrand($brandIds, $params);
            if ($rs) {
                $limitIds = array_merge($limitIds, $rs);
            }
        }

        if (!$limitIds) {
            return false;
        }//没有找到冲突
        $filter['limit_id'] = $limitIds;
        $rs = $service->lists($filter);
        foreach ($rs['list'] as $v) {
            if (isset($params['limit_id']) && $v['limit_id'] == $params['limit_id']) {
                continue;
            }
            $errorMessage = "相同时间内不能重复商品限购活动:{$v['limit_name']}";
            throw new ResourceException($errorMessage);
        }

        return true;//不存在冲突
    }

    public function checkPackage($params = [])
    {
        $errorMessage = '相同时间内已经存在组合商品促销';
        $filter = [
            'start_time|lte' => $params['end_time'],
            'end_time|gte' => max($params['start_time'], time()),//排除过期活动
            'company_id' => $params['company_id'],
        ];
        //排除活动自身
        if (isset($params['package_id']) && $params['package_id']) {
            $filter['package_id|neq'] = $params['package_id'];
        }
        $service = new PackageService();
        $rs = $service->lists($filter);
        if (!$rs['list']) {
            return true;//不存在有效的活动，直接返回
        }

        $packageIds = array_column($rs['list'], 'package_id');
        $packageMainItemRepository = app('registry')->getManager('default')->getRepository(PackageMainItemPromotions::class);
        $packageItemRepository = app('registry')->getManager('default')->getRepository(PackageItemPromotions::class);
        if (isset($params['package_id'])) {
            $filter = ['main_item_id' => $params['main_item_ids'], 'package_id' => $packageIds, 'company_id' => $params['company_id']];
            $rs = $packageMainItemRepository->getLists($filter, 'package_id');
            if ($rs) {
                $filter = ['item_id' => $params['item_ids'], 'package_id' => array_column($rs, 'package_id'), 'company_id' => $params['company_id']];
                $rs = $packageItemRepository->count($filter);
                if ($rs > 0) {
                    throw new ResourceException($errorMessage);
                }
            }

            $filter = ['main_item_id' => $params['item_ids'], 'package_id' => $packageIds, 'company_id' => $params['company_id']];
            $rs = $packageMainItemRepository->getLists($filter, 'package_id');
            if ($rs) {
                $filter = ['item_id' => $params['main_item_ids'], 'package_id' => array_column($rs, 'package_id'), 'company_id' => $params['company_id']];
                $rs = $packageItemRepository->count($filter);
                if ($rs > 0) {
                    throw new ResourceException($errorMessage);
                }
            }
        } else {
            //检查主商品
            $filter = ['main_item_id' => $params['item_ids'], 'package_id' => $packageIds, 'company_id' => $params['company_id']];
            $rs = $packageMainItemRepository->count($filter);
            if ($rs > 0) {
                throw new ResourceException($errorMessage);
            }

            //检查组合商品
            $filter = ['item_id' => $params['item_ids'], 'package_id' => $packageIds, 'company_id' => $params['company_id']];
            $rs = $packageItemRepository->count($filter);
            if ($rs > 0) {
                throw new ResourceException($errorMessage);
            }
        }

        return true;//不存在冲突
    }
}
