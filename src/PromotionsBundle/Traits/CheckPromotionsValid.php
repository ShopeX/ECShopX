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
use PromotionsBundle\Entities\LimitItemPromotions;
use PromotionsBundle\Entities\PromotionGroupsActivity;
use PromotionsBundle\Entities\SeckillRelGoods;
use PromotionsBundle\Entities\MarketingActivityItems;
use PromotionsBundle\Entities\PackagePromotions;
use PromotionsBundle\Entities\PackageItemPromotions;
use PromotionsBundle\Entities\BargainPromotions;
use PromotionsBundle\Services\LimitService;
use PromotionsBundle\Services\PromotionGroupsActivityService;
use PromotionsBundle\Services\PromotionGroupsTeamService;
use PromotionsBundle\Services\PromotionSeckillActivityService;
use PromotionsBundle\Services\SeckillActivityItemStoreService;
use PromotionsBundle\Services\MarketingActivityService;
use PromotionsBundle\Services\MemberPriceService;

trait CheckPromotionsValid
{
    /**
     * 获取指定商品当前活动信息
     *
     * 用户购买的当前商品的时候，先查询当前商品是否在参加活动
     * 如果在参加活动，那么将参加活动sku信息返回，并且返回活动信息
     *
     * @param inteter $companyId 企业ID
     * @param inteter $itemId 商品详情也的商品ID，也是默认商品ID
     * @param bool  $isItemsAll 如果商品为多规格商品是否需要查询所有的SKU信息
     */
    public function getCurrentActivityByItemId($companyId, $itemId, $distributorId = null, $isItemsAll = true)
    {
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getInfo(['item_id' => $itemId, 'company_id' => $companyId]);
        if (!$itemInfo) {
            return [];
        }

        if (($itemInfo['nospec'] === false || $itemInfo['nospec'] === 'false' || $itemInfo['nospec'] === 0 || $itemInfo['nospec'] === '0') && $itemInfo['default_item_id'] && $isItemsAll) {
            $itemsList = $itemsService->list(['default_item_id' => $itemInfo['default_item_id'], 'company_id' => $companyId], null, -1);
            if ($itemsList['total_count'] >= 0) {
                $itemId = array_column($itemsList['list'], 'item_id');
            }
        }

        // 获取商品是否在秒杀中
        $now = time();
        $promotionSeckillActivityService = new PromotionSeckillActivityService();
        $filter = [
            'company_id' => $companyId,
            'item_id' => $itemId,
            'activity_release_time|lte' => $now,
            'activity_end_time|gte' => $now,
            'disabled' => 0,
        ];
        $seckillList = $promotionSeckillActivityService->entityRelRepository->lists($filter, 1, -1);
        $activityData = [];
        if ($seckillList['total_count'] > 0) {
            // 商品参加活动类型为秒杀
            $activityItemStoreService = new SeckillActivityItemStoreService();
            $totalStore = null;
            foreach ($seckillList['list'] as $row) {
                $seckillId = $row['seckill_id'];
                $activityData['list'][$row['item_id']]['seckill_id'] = $seckillId;
                $activityData['list'][$row['item_id']]['item_id'] = $row['item_id'];
                $activityData['list'][$row['item_id']]['limit_num'] = $row['limit_num'];
                $activityData['list'][$row['item_id']]['sales_store'] = $row['sales_store'];
                $activityData['list'][$row['item_id']]['price'] = $itemInfo['price'];
                $activityData['list'][$row['item_id']]['activity_price'] = $row['activity_price'];
                if ($row['seckill_type'] == 'normal') {
                    $activityData['list'][$row['item_id']]['store'] = $activityItemStoreService->getItemStore($row['seckill_id'], $row['company_id'], $row['item_id']);
                    $totalStore += $activityData['list'][$row['item_id']]['store'];
                }
                app('log')->debug('seckillList Item:' . var_export($row, 1));
                app('log')->debug('totalStore:' . $totalStore);
            }

            $activityData['info'] = $promotionSeckillActivityService->getInfoById($seckillId);
            if ($itemInfo['distributor_id'] == $activityData['info']['source_id']) {
                if ($activityData['info']['distributor_id'] && $distributorId && !in_array($distributorId, $activityData['info']['distributor_id'])) {
                    $activityData = [];
                } else {
                    if ($activityData['info']['seckill_type'] == 'normal') {
                        $activityData['activity_type'] = 'seckill';
                    } else {
                        $activityData['activity_type'] = 'limited_time_sale';
                    }

                    if ($totalStore !== null) {
                        // 当前活动商品的总库存
                        $activityData['info']['item_total_store'] = $totalStore;
                    }
                    return $activityData;
                }
            }
        }

        //获取参与团购商品列表
        //团购目前为单SKU参加一个团购活动
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $filter = [
            'company_id' => $companyId,
            'view' => 2, //进行中
            'disabled' => false, //获取有效的活动
            'goods_id' => $itemId
        ];
        $groupList = $promotionGroupsActivityService->lists($filter);
        if ($groupList['total_count'] > 0) {
            $activityData['activity_type'] = 'group';
            $totalStore = 0;
            foreach ($groupList['list'] as $row) {
                $activityData['list'][$row['goods_id']]['item_id'] = $row['goods_id'];
                $activityData['list'][$row['goods_id']]['limit_num'] = $row['limit_buy_num'];
                $activityData['list'][$row['goods_id']]['price'] = $itemInfo['price'];
                $activityData['list'][$row['goods_id']]['activity_price'] = $row['act_price'];
                $activityData['list'][$row['goods_id']]['store'] = $row['store'];
                $totalStore += $activityData['list'][$row['goods_id']]['store'];
            }
            // 活动信息
            $activityData['info'] = $groupList['list'][0];
            // 当前活动商品的总库存
            $activityData['info']['item_total_store'] = $totalStore;

            // 商品详情中是否显示凑团
            if ($activityData['info']['rig_up'] == true) {
                $promotionGroupsTeamService = new PromotionGroupsTeamService();
                $filter = [
                    'p.act_id' => $activityData['info']['groups_activity_id'],
                    'p.company_id' => $companyId,
                    'p.team_status' => 1,
                    'p.disabled' => false,
                ];
                $activityData['groups_list'] = $promotionGroupsTeamService->getGroupsTeamByItems($filter, 1, 4);
            }
            return $activityData;
        }

        //获取商品限购
        $limitService = new  LimitService();
        $filter = [
            'company_id' => $companyId,
            'item_id' => $itemId,
            'distributor_id' => [0, $distributorId],
            'start_time|lt' => time(),
            'end_time|gt' => time(),
        ];
        //$limitItemInfo = $limitService->getLimitItemInfo($filter);
        $limitItemInfo = $limitService->getLimitItemsByItemIds($itemId, $companyId, $filter);
        if ($limitItemInfo) {
            $limit_id = 0;
            foreach ($limitItemInfo as $v) {
                $limit_id = $v['limit_id'];
            }

            $globalLimitInfo = [];
            if (isset($limitItemInfo['special'])) {
                $globalLimitInfo = $limitItemInfo['special'];
                unset($limitItemInfo['special']);
            }

            $activityData['activity_type'] = 'limited_buy';
            $limitInfo = $limitService->getLimitInfo($companyId, $limit_id);

            if ($itemInfo['distributor_id'] == $limitInfo['source_id']) {
                $limitInfo['rule'] = json_decode($limitInfo['rule'], 1);
                if (is_array($itemId)) {
                    $itemTreeLists = $itemId;
                } else {
                    $itemTreeLists = [$itemId];
                }

                if ($limitItemInfo) {
                    foreach ($limitItemInfo as $item_id => $v) {
                        $activityData['list'][$item_id]['item_id'] = $item_id;
                        $activityData['list'][$item_id]['limit_num'] = $v['limit_num'];
                        $activityData['list'][$item_id]['price'] = 9999;
                        $activityData['list'][$item_id]['sales_store'] = 0;
                        $limitInfo['rule']['limit'] = $v['limit_num'];
                        if ($v['distributor_id'] > 0) {
                            $limitInfo['rule']['day'] = 0;
                        }
                        if ($limitInfo['rule']['day'] == 0) {
                            $limitInfo['describe'] = '该商品活动期间只能购买'  . $v['limit_num'] . '件';
                        } else {
                            $limitInfo['describe'] = '该商品活动期间内' . $limitInfo['rule']['day'] . '天只能购买'  . $v['limit_num'] . '件';
                        }
                    }
                } else {
                    foreach ($itemTreeLists as $item_id) {
                        $activityData['list'][$item_id]['item_id'] = $item_id;
                        $activityData['list'][$item_id]['limit_num'] = $globalLimitInfo['limit_num'];
                        $activityData['list'][$item_id]['price'] = 9999;
                        $activityData['list'][$item_id]['sales_store'] = 0;
                        $limitInfo['rule']['limit'] = $globalLimitInfo['limit_num'];
                        if ($limitInfo['rule']['day'] == 0) {
                            $limitInfo['describe'] = '该商品活动期间只能购买'  . $globalLimitInfo['limit_num'] . '件';
                        } else {
                            $limitInfo['describe'] = '该商品活动期间内' . $limitInfo['rule']['day'] . '天只能购买'  . $globalLimitInfo['limit_num'] . '件';
                        }
                    }
                }
                $activityData['info'] = $limitInfo;

                return $activityData;
            }
        }

        // 商品是否参加会员优先购活动
        // $marketingActivityItemsRepository = app('registry')->getManager('default')->getRepository(MarketingActivityItems::class);
        // $filter = [
        //     'company_id' => $companyId,
        //     'item_id' => $itemId,
        //     'marketing_type' => ['member_preference'],
        //     'start_time|lt' => time(),
        //     'end_time|gt' => time(),
        // ];
        // $relLists = $marketingActivityItemsRepository->lists($filter);
        return [];
    }

    /**
     * 检查指定商品当前会员优先购活动，是否符合条件
     *
     * 用户购买的当前商品的时候，先查询当前商品是否在参加活动
     * 如果在参加活动，那么将参加活动sku信息返回，并且返回活动信息
     *
     * @param inteter $companyId 企业ID
     * @param inteter $itemId 商品详情也的商品ID，也是默认商品ID
     * @param bool  $isItemsAll 如果商品为多规格商品是否需要查询所有的SKU信息
     */
    public function checkCurrentMemberpreferenceByItemId($companyId, $userId, $itemId, $distributorId = null, $isItemsAll = true, &$msg)
    {
        $itemsService = new ItemsService();
        $itemInfo = $itemsService->getInfo(['item_id' => $itemId, 'company_id' => $companyId]);
        if (!$itemInfo) {
            $msg = '活动商品出错';
            return [];
        }

        if (($itemInfo['nospec'] === false || $itemInfo['nospec'] === 'false' || $itemInfo['nospec'] === 0 || $itemInfo['nospec'] === '0') && $itemInfo['default_item_id'] && $isItemsAll) {
            $itemsList = $itemsService->list(['default_item_id' => $itemInfo['default_item_id'], 'company_id' => $companyId], null, -1);
            if ($itemsList['total_count'] >= 0) {
                $itemId = array_column($itemsList['list'], 'item_id');
            }
        }
        $marketingActivityService = new MarketingActivityService();
        $memberpreferenceActivity = $marketingActivityService->getValiMemberpreferenceByItemId($companyId, $itemId, $userId, '', '', $msg);
        return $memberpreferenceActivity;
    }

    /**
     * 检查商品是否在指定时间内
     */
    public function checkActivityValid($companyId, $itemIds, $beginTime = null, $endTime = null, $activityId = null)
    {
        //验证秒杀商品指定时段是否有活动
        $this->checkSeckillActivity($companyId, $itemIds, $beginTime, $endTime, $activityId);

        //验证团购商品指定时段是否有活动
        $this->checkGroupActivity($companyId, $itemIds, $beginTime, $endTime, $activityId);

        //验证限购商品指定时段是否有活动
        $this->checkLimitActivity($companyId, $itemIds, $beginTime, $endTime, $activityId);

        return true;
    }

    public function checkSeckillActivity($companyId, $itemIds, $beginTime = null, $endTime = null, $activityId = null)
    {
        $filter['company_id'] = $companyId;
        if ($activityId) {
            $filter['seckill_id|neq'] = $activityId;
        }
        $filter['activity_end_time|gte'] = time();
        $filter['disabled'] = 0;
        $filter['item_id'] = $itemIds;

        $beginTime = $beginTime > time() ? $beginTime : time();
        $endTime = $endTime ? $endTime : time();

        $seckillRelGoodsRepository = app('registry')->getManager('default')->getRepository(SeckillRelGoods::class);
        //新增的活动开始时间如果包含在以后活动中，需要判断商品是否存在
        $filter['activity_release_time|lte'] = $beginTime;
        $filter['activity_end_time|gt'] = $beginTime;
        $relLists = $seckillRelGoodsRepository->lists($filter);
        if ($relLists['list']) {
            app('log')->info('在相同时段内，同一个商品只能参加一个活动，seckill,line:'.__LINE__);
            throw new ResourceException('在相同时段内，同一个商品只能参加一个活动');
        }
        unset($filter['activity_release_time|lte'], $filter['activity_end_time|gt']);

        //新增的活动结束时间如果包含在以后活动中，需要判断商品是否存在
        $filter['activity_release_time|lt'] = $endTime;
        $filter['activity_end_time|gte'] = $endTime;
        $relLists = $seckillRelGoodsRepository->lists($filter);
        if ($relLists['list']) {
            app('log')->info('在相同时段内，同一个商品只能参加一个活动，seckill,line:'.__LINE__);
            throw new ResourceException('在相同时段内，同一个商品只能参加一个活动');
        }
        unset($filter['activity_release_time|lt'], $filter['activity_end_time|gte']);

        //新增的时间 包含原有时间的活动
        $filter['activity_end_time|gte'] = time();
        $filter['activity_release_time|gte'] = $beginTime;
        $filter['activity_end_time|lte'] = $endTime;
        $relLists = $seckillRelGoodsRepository->lists($filter);
        if ($relLists['list']) {
            app('log')->info('在相同时段内，同一个商品只能参加一个活动，seckill,line:'.__LINE__);
            throw new ResourceException('在相同时段内，同一个商品只能参加一个活动');
        }
        unset($filter['activity_release_time|gte'], $filter['activity_end_time|lte']);
        return true;
    }

    public function checkGroupActivity($companyId, $itemIds, $beginTime = null, $endTime = null, $activityId = null)
    {
        $beginTime = $beginTime > time() ? $beginTime : time();
        $endTime = $endTime ? $endTime : time();
        $promotionGroupsActivityRepository = app('registry')->getManager('default')->getRepository(PromotionGroupsActivity::class);
        $info = $promotionGroupsActivityRepository->getIsHave($itemIds, $beginTime, $endTime, $activityId);
        if ($info) {
            app('log')->info('在相同时段内，同一个商品只能参加一个活动，group');
            throw new ResourceException('在相同时段内，同一个商品只能参加一个活动');
        }
        return true;
    }

    public function checkLimitActivity($companyId, $itemIds, $beginTime = null, $endTime = null, $activityId = null)
    {
        $beginTime = $beginTime > time() ? $beginTime : time();
        $endTime = $endTime ? $endTime : time();
        $limitItemPromotionsRepository = app('registry')->getManager('default')->getRepository(LimitItemPromotions::class);
        $info = $limitItemPromotionsRepository->getIsHave($itemIds, $beginTime, $endTime, $activityId);
        if ($info) {
            app('log')->info('在相同时段内，同一个商品只能参加一个活动，limit');
            throw new ResourceException('在相同时段内，同一个商品只能参加一个活动');
        }
        return true;
    }

    public function checkBargainActivity($companyId, $itemIds, $beginTime = null, $endTime = null, $activityId = null)
    {
        $beginTime = $beginTime > time() ? $beginTime : time();
        $endTime = $endTime ? $endTime : time();
        $bargainPromotionsRepository = app('registry')->getManager('default')->getRepository(BargainPromotions::class);
        $info = $bargainPromotionsRepository->getIsHave($itemIds, $beginTime, $endTime, $activityId);
        if ($info) {
            app('log')->info('在相同时段内，同一个商品只能参加一个活动，bargain');
            throw new ResourceException('在相同时段内，同一个商品只能参加一个活动');
        }
        return true;
    }

    /**
     * 检查满减、满折、满赠、任选优惠、加价购、会员优先购活动
     * @param  string $companyId  企业ID
     * @param  array $itemIds    商品id
     * @param  string $beginTime  开始时间(时间戳)
     * @param  string $endTime    结束时间(时间戳)
     * @param  string $activityId 活动id
     * @param  array $marketingType 营销类型: full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购,member_preference:会员优先购；
     * @return bool
     */
    public function checkMarketingActivity($companyId, $itemIds, $beginTime = null, $endTime = null, $activityId = null, $marketingType = null)
    {
        $beginTime = $beginTime > time() ? $beginTime : time();
        $endTime = $endTime ? $endTime : time();
        $marketingActivityItemsRepository = app('registry')->getManager('default')->getRepository(MarketingActivityItems::class);
        $info = $marketingActivityItemsRepository->getIsHave($itemIds, $beginTime, $endTime, $activityId, $marketingType);
        if ($info) {
            app('log')->info('在相同时段内，同一个商品只能参加一个活动，bargain');
            throw new ResourceException('在相同时段内，同一个商品只能参加一个活动');
        }
        return true;
    }

    //获取参与活动中的货品ID
    public function getActivityItems($companyId = 0)
    {
        //获取参与团购商品列表
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $filter = [
            'view' => 2, //进行中
        ];
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }
        $groupList = $promotionGroupsActivityService->lists($filter);

        $groupItem = [];
        if ($groupList) {
            foreach ((array)$groupList['list'] as $val) {
                if (!$val) {
                    continue;
                }
                $groupItem[] = $val['goods_id'];
            }
        }

        //获取参与秒杀商品列表
        $now = time();
        $promotionSeckillActivityService = new PromotionSeckillActivityService();
        $filter = [
            'item_type' => 'normal',
            'activity_start_time|lte' => $now,
            'activity_end_time|gte' => $now,
            'disabled' => 0,
        ];
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }
        $seckillList = $promotionSeckillActivityService->entityRelRepository->lists($filter);

        $seckillItem = [];
        if ($seckillList) {
            foreach ((array)$seckillList['list'] as $val) {
                if (!$val) {
                    continue;
                }
                $seckillItem[] = $val['item_id'];
            }
        }

        // 不更新库存的货品ID
        $activityItems = array_unique(array_filter(array_merge($groupItem, $seckillItem)));

        return $activityItems;
    }

    /**
     * 检查商品是否有未结束的营销活动
     */
    public function checkNotFinishedActivityValid($companyId, $itemIds, $goodsIds)
    {

        //验证秒杀商品是否有未结束的营销活动
        $this->checkNotFinishedSeckillActivity($companyId, $itemIds);

        //验证团购商品是否有未结束的营销活动
        $this->checkNotFinishedGroupActivity($companyId, $goodsIds);

        //验证限购商品是否有未结束的营销活动
        $this->checkNotFinishedLimitActivity($companyId, $itemIds);

        //验证满减、满赠、满折、加价购、会员优先购是否有未结束的营销活动
        $this->checkNotFinishedFullActivity($companyId, $itemIds);

        //验证组合商品是否有未结束的营销活动
        $this->checkNotFinishedPackageActivity($companyId, $itemIds, $goodsIds);

        //验证微信助力是否有未结束的营销活动
        $this->checkNotFinishedBargainActivity($companyId, $itemIds, $goodsIds);

        return true;
    }

    /**
     * 基础查询条件
     * @param $companyId
     * @param $itemIds
     * @return array
     */
    public function getNotFinishedBaseFilter($companyId, $itemIds)
    {
        $filter = [
            'company_id' => $companyId,
            'item_id' => $itemIds,
        ];
        return $filter;
    }

    /**
     * 验证秒杀商品是否有未结束的营销活动
     * @param $companyId
     * @param $itemIds
     * @return bool
     */
    public function checkNotFinishedSeckillActivity($companyId, $itemIds)
    {
        $filter = $this->getNotFinishedBaseFilter($companyId, $itemIds);
        $filter['activity_end_time|gte'] = time();

        $seckillRelGoodsRepository = app('registry')->getManager('default')->getRepository(SeckillRelGoods::class);
        $relLists = $seckillRelGoodsRepository->lists($filter);
        if ($relLists['list']) {
            app('log')->info('无法被设置为赠品seckill');
            throw new ResourceException('无法被设置为赠品');
        }

        return true;
    }

    /**
     * 验证团购商品是否有未结束的营销活动
     * @param $companyId
     * @param $goodsIds
     * @return bool
     */
    public function checkNotFinishedGroupActivity($companyId, $goodsIds)
    {
        $endTime = time();
        $promotionGroupsActivityRepository = app('registry')->getManager('default')->getRepository(PromotionGroupsActivity::class);
        $info = $promotionGroupsActivityRepository->getNotFinished($goodsIds, $endTime);
        if ($info) {
            app('log')->info('无法被设置为赠品groups');
            throw new ResourceException('无法被设置为赠品');
        }
        return true;
    }

    /**
     * 验证限购商品是否有未结束的营销活动
     * @param $companyId
     * @param $itemIds
     * @return bool
     */
    public function checkNotFinishedLimitActivity($companyId, $itemIds)
    {
        $endTime = time();
        $limitItemPromotionsRepository = app('registry')->getManager('default')->getRepository(LimitItemPromotions::class);
        $info = $limitItemPromotionsRepository->getNotFinished($itemIds, $endTime);
        if ($info) {
            app('log')->info('无法被设置为赠品limit');
            throw new ResourceException('无法被设置为赠品');
        }
        return true;
    }

    /**
     * 验证满减、满赠、满折、加价购、会员优先购是否有未结束的营销活动
     * @param $companyId
     * @param $itemIds
     * @return bool
     */
    public function checkNotFinishedFullActivity($companyId, $itemIds)
    {
        $filter = $this->getNotFinishedBaseFilter($companyId, $itemIds);
        $filter['end_time|gte'] = time();

        $marketingActivityItemsRepository = app('registry')->getManager('default')->getRepository(MarketingActivityItems::class);
        $relLists = $marketingActivityItemsRepository->lists($filter);
        if ($relLists['list']) {
            app('log')->info('无法被设置为赠品full');
            throw new ResourceException('无法被设置为赠品');
        }

        return true;
    }

    /**
     * 验证组合商品是否有未结束的营销活动
     * @param $companyId
     * @param $itemIds
     * @param $goodsIds
     * @return bool
     */
    public function checkNotFinishedPackageActivity($companyId, $itemIds, $goodsIds)
    {
        $filter = [
            'company_id' => $companyId,
            'goods_id' => $goodsIds,
            'end_time|gte' => time(),
        ];

        $packagePromotionsRepository = app('registry')->getManager('default')->getRepository(PackagePromotions::class);
        $relLists = $packagePromotionsRepository->lists($filter);
        if ($relLists['list']) {
            app('log')->info('无法被设置为赠品package_main');
            throw new ResourceException('无法被设置为赠品');
        }
        $filter = [
            'company_id' => $companyId,
            'item_id' => $itemIds,
            'end_time|gte' => time(),
        ];
        $packageItemPromotionsRepository = app('registry')->getManager('default')->getRepository(PackageItemPromotions::class);
        $relLists = $packageItemPromotionsRepository->lists($filter);
        if ($relLists['list']) {
            app('log')->info('无法被设置为赠品package_item');
            throw new ResourceException('无法被设置为赠品');
        }

        return true;
    }

    /**
     * 验证微信动力是否有未结束的营销活动
     * @param $companyId
     * @param $itemIds
     * @return bool
     */
    public function checkNotFinishedBargainActivity($companyId, $itemIds)
    {
        $filter = $this->getNotFinishedBaseFilter($companyId, $itemIds);
        $filter['end_time|gte'] = time();
        $bargainPromotionsRepository = app('registry')->getManager('default')->getRepository(BargainPromotions::class);
        $relLists = $bargainPromotionsRepository->getList($filter);
        if ($relLists['total_count'] > 0) {
            app('log')->info('无法被设置为赠品bargain');
            throw new ResourceException('无法被设置为赠品');
        }
        return true;
    }

    /**
     * 检查商品价格是否大于活动价格
     * @param  string $companyId 企业Id
     * @param  array $goodsIds  goodsid
     * @param  array $itemPrices     itemid和销售价的数组
     * @return bool
     */
    public function checkItemPrice($companyId, $goodsIds, $itemPrices)
    {
        // 检查团购商品未结束的活动，活动价格是否大于商品的销售价
        if (!$this->checkGroupActivityItemPrice($companyId, $goodsIds, $itemPrices, $msg)) {
            throw new ResourceException($msg);
        }

        // 检查会员价是否大于销售价
        if (!$this->checkMemberPrice($companyId, $itemPrices, $msg)) {
            throw new ResourceException($msg);
        }

        return true;
    }

    /**
     * 检查团购商品未结束的活动，活动价格是否大于商品的销售价
     * @param string $companyId 企业id
     * @param array $goodsIds goodsId
     * @param array $itemPrices itemid和销售价的数组
     * @param string $msg
     * @return bool
     */
    public function checkGroupActivityItemPrice($companyId, $goodsIds, $itemPrices, &$msg)
    {
        $endTime = time();
        $promotionGroupsActivityRepository = app('registry')->getManager('default')->getRepository(PromotionGroupsActivity::class);
        $list = $promotionGroupsActivityRepository->getNotFinished($goodsIds, $endTime);
        if (!$list) {
            return true;
        }
        foreach ($list as $activity) {
            if ($activity['act_price'] > $itemPrices[$activity['goods_id']]) {
                $msg = '拼团名称【'.$activity['act_name'].'】的活动价格大于商品价格，请检查后再提交！';
                return false;
            }
        }
        return true;
    }

    /**
     * 检查会员价是否大于销售价
     * @param string $companyId 企业id
     * @param array $itemPrices itemid和销售价的数组
     * @param string $msg
     * @return bool
     */
    public function checkMemberPrice($companyId, $itemPrices, &$msg)
    {
        $memberPriceService = new MemberPriceService();
        $itemIds = array_keys($itemPrices);
        $list = $memberPriceService->lists(['company_id' => $companyId, 'item_id' => $itemIds], 1, count($itemIds));
        foreach ($list['list'] as $row) {
            $gradePrice = json_decode($row['mprice'], true);
            foreach (($gradePrice['grade'] ?? []) as $mprice) {
                if ($mprice > $itemPrices[$row['item_id']]) {
                    $msg = '商品会员价大于销售价，请检查后再提交！';
                    return false;
                }
            }

            foreach (($gradePrice['vipGrade'] ?? []) as $mprice) {
                if ($mprice > $itemPrices[$row['item_id']]) {
                    $msg = '商品会员价大于销售价，请检查后再提交！';
                    return false;
                }
            }
        }

        return true;
    }
}
