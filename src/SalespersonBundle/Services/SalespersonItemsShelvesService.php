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

namespace SalespersonBundle\Services;

use SalespersonBundle\Entities\SalespersonItemsShelves;
use PromotionsBundle\Services\MarketingActivityService;
use PromotionsBundle\Services\PromotionSeckillActivityService;
use PromotionsBundle\Services\PromotionGroupsActivityService;
use PromotionsBundle\Services\PackageService;
use GoodsBundle\Services\ItemsService;
use SalespersonBundle\Jobs\SalespersonItemsShelvesJob;
use DistributionBundle\Services\DistributorService;

class SalespersonItemsShelvesService
{
    public $SalespersonItemsShelvesRepository;

    public function __construct()
    {
        $this->SalespersonItemsShelvesRepository = app('registry')->getManager('default')->getRepository(SalespersonItemsShelves::class);
    }

    /**
     * 活动相关店铺商品数据添加
     *
     * @param string $companyId
     * @param string $activityId
     * @param string $activityType
     * @return boolean
     */
    public function addSalespersonItemsShelves($companyId, $activityId, $activityType)
    {
        $this->delSalespersonItemsShelves($companyId, $activityId);
        $data = [];
        switch ($activityType) {
            case 'full_discount':
            case 'full_minus':
            case 'full_gift':
                $data = $this->getMarketingActivity($companyId, $activityId);
                break;
            case 'seckill':
            case 'limited_time_sale':
                $data = $this->getSeckill($companyId, $activityId);
                break;
            case 'group':
                $data = $this->getGroup($companyId, $activityId);
                break;
            case 'package':
                $data = $this->getPackage($companyId, $activityId);
                break;
        }
        if (!$data) {
            return true;
        }
        if (time() > $data['end_time']) {
            return true;
        }

        foreach ($data['distributor_id'] as $distributorId) {
            foreach ($data['item_id'] as $itemId) {
                $params = [
                    'company_id' => $companyId,
                    'activity_id' => $activityId,
                    'activity_type' => $activityType,
                    'distributor_id' => $distributorId,
                    'item_id' => $itemId,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                ];
                $this->SalespersonItemsShelvesRepository->create($params);
            }
        }
        return true;
    }

    /**
     * 获取活动数据
     *
     * @param string $companyId
     * @param string $activityId
     * @param string $activityType
     * @return array
     */
    public function getItemsShelves($companyId, $activityId, $activityType)
    {
        $data = [];
        switch ($activityType) {
            case 'full_discount':
            case 'full_minus':
            case 'full_gift':
                $data = $this->getMarketingActivity($companyId, $activityId);
                break;
            case 'normal':
            case 'limited_time_sale':
                $data = $this->getSeckill($companyId, $activityId);
                break;
            case 'single_group':
                $data = $this->getGroup($companyId, $activityId);
                break;
            case 'package':
                $data = $this->getPackage($companyId, $activityId);
                break;
        }

        return $data;
    }

    /**
     * 活动相关店铺商品数据删除
     *
     * @param string $companyId
     * @param string $activityId
     * @param string $activityType
     * @return boolean
     */
    public function delSalespersonItemsShelves($companyId, $activityId)
    {
        $filter = [
            'company_id' => $companyId,
            'activity_id' => $activityId,
        ];
        return $this->SalespersonItemsShelvesRepository->deleteBy($filter);
    }

    /**
     * 获取满减,满折,满赠商品活动数据
     *
     * @param string $companyId
     * @param string $activityId
     * @return array
     */
    public function getMarketingActivity($companyId, $activityId)
    {
        $filter = [
            'company_id' => $companyId,
            'marketing_id' => $activityId,
        ];
        $marketingActivityService = new MarketingActivityService();
        $list = $marketingActivityService->getMarketingLists($filter);
        if (!$list['list']) {
            return [];
        }
        $data = $list['list'][0];
        $result['distributor_id'] = $data['shop_ids'] ? array_filter($data['shop_ids']) : [0];
        $result['item_id'] = array_column($data['items'], 'item_id');
        $result['start_time'] = $data['start_time'];
        $result['end_time'] = $data['end_time'];
        $result['activity_name'] = $data['marketing_name'];
        return $result;
    }

    /**
     * 获取秒杀,限时特惠商品活动数据
     *
     * @param string $companyId
     * @param string $activityId
     * @return array
     */
    public function getSeckill($companyId, $activityId)
    {
        $filter = [
            'company_id' => $companyId,
            'seckill_id' => $activityId,
        ];
        $promotionSeckillActivityService = new PromotionSeckillActivityService();
        $data = $promotionSeckillActivityService->getSeckillInfo($filter);
        if (!$data) {
            return [];
        }
        $result['distributor_id'] = $data['distributor_id'] ?: [0];
        $result['item_id'] = array_column($data['items'], 'item_id');
        $result['start_time'] = $data['activity_start_time'];
        $result['end_time'] = $data['activity_end_time'];
        $result['activity_name'] = $data['activity_name'];
        return $result;
    }

    /**
     * 获取拼团商品活动数据
     *
     * @param string $companyId
     * @param string $activityId
     * @return array
     */
    public function getGroup($companyId, $activityId)
    {
        $filter = [
            'company_id' => $companyId,
            'groups_activity_id' => $activityId,
        ];
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $data = $promotionGroupsActivityService->getInfo($filter);
        if (!($data['goods_id'] ?? 0)) {
            return [];
        }
        $result['distributor_id'][] = 0;

        $itemsService = new ItemsService();
        $itemsInfo = $itemsService->getItemsDetail($data['goods_id']);
        $result['item_id'][] = $itemsInfo['goods_id'];
        $result['start_time'] = $data['begin_time'];
        $result['end_time'] = $data['end_time'];
        $result['activity_name'] = $data['act_name'];
        return $result;
    }

    /**
     * 获取组合商品活动数据
     *
     * @param string $companyId
     * @param string $activityId
     * @return array
     */
    public function getPackage($companyId, $activityId)
    {
        $packageService = new PackageService();
        $data = $packageService->getPackageInfo($companyId, $activityId);
        if (!$data) {
            return [];
        }
        $result['distributor_id'][] = 0;
        $result['item_id'][] = $data['main_item_id'];
        $result['start_time'] = $data['start_time'];
        $result['end_time'] = $data['end_time'];
        $result['activity_name'] = $data['package_name'];
        return $result;
    }

    /**
     * 获取活动商品列表
     *
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @param null $sort
     * @return array
     */
    public function getItemList($filter, $page = 1, $pageSize = 10, $sort = null)
    {
        $orderBy = [];
        if ($sort) {
            $sort = explode('-', $sort);
            switch ($sort[0]) {
                case 'created':
                    $orderBy = ['i.created' => $sort[1]];
                    break;
                case 'price':
                    $orderBy = ['i.price' => $sort[1]];
                    break;
                case 'profit':
                    $orderBy = ['i.profit_fee' => $sort[1]];
                    break;
                case 'sales':
                    $orderBy = ['i.sales' => $sort[1]];
                    break;
            }
        }
        $result = $this->SalespersonItemsShelvesRepository->getDistributorPromotionItemList($filter, 'i.*', $page, $pageSize, $orderBy);
        return $result;
    }

    /**
     * 删除活动商品
     *
     * @return void
     */
    public function scheduleDelPromotionItem()
    {
        // 满折 满赠 满减活动
        $filter = [
            'end_time|lte' => time() + 60
        ];
        return $this->SalespersonItemsShelvesRepository->deleteBy($filter);
    }


    /**
     * 自动同步活动商品脚本
     *
     * @return void
     */
    public function scheduleSyncPromotionItem()
    {
        // 满折 满赠 满减活动
        $filter = [
            'end_time|gte' => time()
        ];
        $marketingActivityService = new MarketingActivityService();
        $list = $marketingActivityService->getMarketingLists($filter);
        foreach ($list['list'] as $v) {
            $job = (new SalespersonItemsShelvesJob($v['company_id'], $v['marketing_id'], $v['marketing_type']));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
        // 限时 特惠秒杀活动
        $filter = [
            'activity_end_time|gte' => time()
        ];
        $promotionSeckillActivityService = new PromotionSeckillActivityService();
        $list = $promotionSeckillActivityService->getLists($filter, 1, 1000000);
        foreach ($list['list'] as $v) {
            $activityType = $v['seckill_type'] == 'normal' ? 'seckill' : 'limited_time_sale';
            $job = (new SalespersonItemsShelvesJob($v['company_id'], $v['seckill_id'], $activityType));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
        // 拼团活动
        $filter = [
            'end_time|gte' => time()
        ];
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $list = $promotionGroupsActivityService->getList($filter, 1, 1000000);
        foreach ($list['list'] as $v) {
            $job = (new SalespersonItemsShelvesJob($v['company_id'], $v['groups_activity_id'], 'group'));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
        // 组合商品活动
        $filter = [
            'end_time|gte' => time()
        ];
        $packageService = new PackageService();
        $list = $packageService->getPackageFilterList($filter, 1, 1000000);
        foreach ($list['list'] as $v) {
            $job = (new SalespersonItemsShelvesJob($v['company_id'], $v['package_id'], 'package'));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
    }
}
