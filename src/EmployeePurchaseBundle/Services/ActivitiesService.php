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

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Entities\Activities;
use EmployeePurchaseBundle\Entities\ActivityItems;
use EmployeePurchaseBundle\Entities\ActivityGoods;
use EmployeePurchaseBundle\Entities\ActivityEnterprises;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsRelCatsService;
use DistributionBundle\Services\DistributorService;
use DistributionBundle\Services\DistributorItemsService;
use EmployeePurchaseBundle\Services\ActivityItemsService;

class ActivitiesService
{
    public $entityRepository;
    public $itemsEntityRepository;
    public $enterpriseEntityRepository;

    /**
     * MemberService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Activities::class);
        $this->itemsEntityRepository = app('registry')->getManager('default')->getRepository(ActivityItems::class);
        $this->goodsEntityRepository = app('registry')->getManager('default')->getRepository(ActivityGoods::class);
        $this->enterpriseEntityRepository = app('registry')->getManager('default')->getRepository(ActivityEnterprises::class);
    }

    public function create($data)
    {
        $result = $this->entityRepository->create($data);
        $enterpriseData = [];
        foreach ($data['enterprise_id'] as $enterpriseId) {
            $enterpriseData[] = [
                'activity_id' => $result['id'],
                'enterprise_id' => $enterpriseId,
                'company_id' => $result['company_id'],
            ];
        }
        $this->enterpriseEntityRepository->batchInsert($enterpriseData);
        return $result;
    }

    public function updateActivity($filter, $data)
    {
        $result = $this->entityRepository->updateOneBy($filter, $data);
        if (isset($data['enterprise_id']) && $data['enterprise_id']) {
            $this->enterpriseEntityRepository->deleteBy(['company_id' => $result['company_id'], 'activity_id' => $result['id']]);
            $enterpriseData = [];
            foreach ($data['enterprise_id'] as $enterpriseId) {
                $enterpriseData[] = [
                    'activity_id' => $result['id'],
                    'enterprise_id' => $enterpriseId,
                    'company_id' => $result['company_id'],
                ];
            }
            $this->enterpriseEntityRepository->batchInsert($enterpriseData);
        }
        return $result;
    }

    public function cancelActivity($filter)
    {
        $activity = $this->entityRepository->getInfo($filter);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['display_time'] < time()) {
            throw new ResourceException('只能取消未开始的活动');
        }

        return $this->entityRepository->updateBy($filter, ['status' => 'cancel']);
    }

    public function suspendActivity($filter)
    {
        $activity = $this->entityRepository->getInfo($filter);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['status'] != 'active') {
            throw new ResourceException('只能暂停进行中的活动');
        }

        $now = time();
        if (($activity['employee_begin_time'] > $now && $activity['relative_begin_time'] > $now) || ($activity['employee_end_time'] < $now && $activity['relative_end_time'] < $now)) {
            throw new ResourceException('只能暂停进行中的活动');
        }

        return $this->entityRepository->updateBy($filter, ['status' => 'pending']);
    }

    public function activeActivity($filter)
    {
        $activity = $this->entityRepository->getInfo($filter);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['status'] != 'pending') {
            throw new ResourceException('只能开始暂停中的活动');
        }

        return $this->entityRepository->updateBy($filter, ['status' => 'active']);
    }

    public function endActivity($filter)
    {
        $activity = $this->entityRepository->getInfo($filter);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['display_time'] > time()) {
            throw new ResourceException('只能结束已开始的活动');
        }

        return $this->entityRepository->updateBy($filter, ['status' => 'over']);
    }

    public function aheadActivity($filter)
    {
        $activity = $this->entityRepository->getInfo($filter);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['status'] != 'active') {
            throw new ResourceException('只能提前开始有效的活动');
        }

        $now = time();
        if ($activity['display_time'] > $now || $activity['employee_begin_time'] < $now) {
            throw new ResourceException('只能提前开始预热中的活动');
        }

        return $this->entityRepository->updateBy($filter, ['employee_begin_time' => $now]);
    }

    public function getActivityList($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = ['created' => 'DESC'])
    {
        if (isset($filter['enterprise_id']) && $filter['enterprise_id']) {
            $enterpriseList = $this->enterpriseEntityRepository->getLists(['company_id' => $filter['company_id'], 'enterprise_id' => $filter['enterprise_id']], 'activity_id');
            if (!$enterpriseList) {
                return ['total_count' => 0, 'list' => []];
            }
            $filter['id'] = array_unique(array_column($enterpriseList, 'activity_id'));
        }
        unset($filter['enterprise_id']);

        $result = $this->entityRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        if (!$result['total_count']) {
            return $result; 
        }
        $distributorService = new DistributorService();
        $storeIds = array_filter(array_unique(array_column($result['list'], 'distributor_id')), function ($distributorId) {
            return is_numeric($distributorId) && $distributorId >= 0;
        });
        $storeData = [];
        if ($storeIds) {
            $storeList = $distributorService->getDistributorOriginalList([
                'company_id' => $filter['company_id'],
                'distributor_id' => $storeIds,
            ], 1, $pageSize);
            $storeData = array_column($storeList['list'], null, 'distributor_id');
            // 附加总店信息
            $storeData[0] = $distributorService->getDistributorSelfSimpleInfo($filter['company_id']);
        }
        foreach ($result['list'] as $key => $row) {
            $result['list'][$key]['price_display_config'] = json_decode($row['price_display_config'], true);
            $result['list'][$key]['distributor_name'] = isset($row['distributor_id']) ? ($storeData[$row['distributor_id']]['name'] ?? '') : '';
        }

        return $result;
    }

    public function getActivityItemList($filter, $page, $pageSize, $itemSpec = false, $isDefault = false, $orderBy = ['item_id' => 'desc'])
    {
        $distributorId = $filter['distributor_id'] ?? 0;
        unset($filter['distributor_id']);
        $result = $this->goodsEntityRepository->getActivityGoodsList($filter, $page, $pageSize);
        if ($result['list']) {
            $result['list'] = $this->itemsEntityRepository->getActivityItemsList($filter['company_id'], $filter['activity_id'], array_column($result['list'], 'goods_id'), $itemSpec, $isDefault, $orderBy);
            if ($distributorId > 0) {
                // 查询店铺商品的是否为总库库存、店铺库存字段
                $itemIds = array_column($result['list'], 'item_id');
                $distributorItemsService = new DistributorItemsService();
                $distributorItemsList = $distributorItemsService->getDistributorRelItemList([
                    'company_id' => $filter['company_id'],
                    'item_id' => $itemIds,
                    'distributor_id' => $distributorId,
                ], $pageSize, 1, ['item_id' => 'desc'], false);
                $distributorItemsList = array_column($distributorItemsList['list'], null, 'item_id');
            }
            $itemsService = new ItemsService();
            foreach ($result['list'] as &$row) {
                if ($row['nospec'] === false || $row['nospec'] === 'false' || $row['nospec'] === 0 || $row['nospec'] === '0') {
                    $row['total_item_spec'] = $itemsService->count(['company_id' => $row['company_id'], 'goods_id' => $row['goods_id']]);
                }
                if ($distributorId > 0) {
                    $row['store'] = $distributorItemsList[$row['item_id']]['store'] ?? 0;
                }
            }
        }
        return $result;
    }

    public function addActivityItems($params)
    {
        $activity = $this->entityRepository->getInfo(['id' => $params['activity_id']]);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        $itemsService = new ItemsService();
        $items = $itemsService->getItems($params['item_id'], $params['company_id'], 'item_id,goods_id,price,store');
        if (!$items) {
            throw new ResourceException('请选择活动商品');
        }

        $itemsData = [];
        $goodsData = [];
        foreach ($items as $item) {
            $itemsData[] = [
                'activity_id' => $activity['id'],
                'company_id' => $activity['company_id'],
                'item_id' => $item['item_id'],
                'goods_id' => $item['goods_id'],
                'activity_price' => $item['price'],
                'activity_store' => 0,
            ];

            $goodsData[$item['goods_id']] = [
                'activity_id' => $activity['id'],
                'company_id' => $activity['company_id'],
                'goods_id' => $item['goods_id'],
            ];
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->itemsEntityRepository->batchInsert($itemsData);
            $this->goodsEntityRepository->batchInsert(array_values($goodsData));
            // 更新活动关联的商品分类
            $activityItemsService = new ActivityItemsService();
            $activityItemsService->storeActivityItemsCategory($params['company_id'], $params['activity_id']);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return true;
    }

    public function addActivityItemsByCategory($params)
    {
        $itemsCategoryService = new ItemsCategoryService();
        $catIds = $itemsCategoryService->getItemsCategoryIds($params['cat_id'], $params['company_id']);

        $itemsRelCatsService = new ItemsRelCatsService();
        $itemsService = new ItemsService();
        $filter = [
            'company_id' => $params['company_id'],
            'category_id' => $catIds,
        ];

        $page = 1;
        $pageSize = 200;
        do {
            $relCatList = $itemsRelCatsService->getList($filter, 'item_id', $page, $pageSize, ['item_id' => 'ASC']);
            if (!$relCatList) {
                break;
            }
            $itemIds = array_column($relCatList, 'item_id');
            if (isset($params['distributor_id']) && $params['distributor_id'] > 0) {
                $distributorItemsService = new DistributorItemsService();
                $distributorItemsList = $distributorItemsService->getDistributorRelItemList([
                    'company_id' => $params['company_id'],
                    'item_id' => $itemIds,
                    'distributor_id' => $params['distributor_id'],
                ], $pageSize, 1, ['item_id' => 'desc'], false);
                if ($distributorItemsList['total_count'] == 0) {
                    break;
                }
                $distributorItemIds = array_column($distributorItemsList['list'], 'item_id');
                $itemIds = array_intersect((array)$itemIds, $distributorItemIds);
            }
            $list = $itemsService->getItems($itemIds, $params['company_id'], 'item_id', 'default_item_id');
            if (!$list) {
                break;
            }
            $params['item_id'] = array_column($list, 'item_id');
            $this->addActivityItems($params);
            $page++;
        } while (count($list) == $pageSize);

        return true;
    }

    public function addActivityItemsByMainCategory($params)
    {
        $itemsCategoryService = new ItemsCategoryService();
        $mainCatIds = $params['main_cat_id'];
        foreach ($params['main_cat_id'] as $mainCatId) {
            $mainCatIds = array_merge($mainCatIds, $itemsCategoryService->getMainCatChildIdsBy($mainCatId, $params['company_id']));
        }
        $filter = [
            'company_id' => $params['company_id'],
            'item_category' => $mainCatIds,
        ];
        if (isset($params['distributor_id']) && $params['distributor_id'] > 0) {
            $distributorItemsService = new DistributorItemsService();
            $distributorItemsList = $distributorItemsService->getDistributorRelItemList([
                'company_id' => $params['company_id'],
                'item_category' => $mainCatIds,
                'distributor_id' => $params['distributor_id'],
            ], -1, 1, ['item_id' => 'desc'], false);
            if ($distributorItemsList['total_count'] == 0) {
                return true;
            }
            $filter['item_id'] = array_column($distributorItemsList['list'], 'item_id');
            unset($filter['item_category']);
        }
        
        $itemsService = new ItemsService();
        $page = 1;
        $pageSize = 500;
        do {
            $list = $itemsService->getLists($filter, 'item_id', $page, $pageSize, ['item_id' => 'ASC']);
            if (!$list) {
                break;
            }
            $params['item_id'] = array_column($list, 'item_id');
            $this->addActivityItems($params);
            $page++;
        } while (count($list) == $pageSize);

        return true;
    }

    public function updateActivityItems($filter, $data)
    {
        return $this->itemsEntityRepository->updateBy($filter, $data);
    }

    public function deleteActivityItems($filter, $allSpec = false)
    {
        $item = $this->itemsEntityRepository->getInfo($filter);
        if (!$item) {
            return true;
        }

        if ($allSpec) {
            $filter['goods_id'] = $item['goods_id'];
            unset($filter['item_id']);
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->itemsEntityRepository->deleteBy($filter);
            $filter['goods_id'] = $item['goods_id'];
            unset($filter['item_id']);
            $exist = $this->itemsEntityRepository->count($filter);
            if (!$exist) {
                $this->goodsEntityRepository->deleteBy($filter);
            }
            // 更新活动关联的商品分类
            $activityItemsService = new ActivityItemsService();
            $activityItemsService->storeActivityItemsCategory($filter['company_id'], $filter['activity_id']);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
        return true;
    }

    public function getActivityItemInfo($filter)
    {
        return $this->itemsEntityRepository->getInfo($filter);
    }

    public function getActivityEnterprises($filter)
    {
        $enterpriseList = $this->enterpriseEntityRepository->getLists($filter, 'enterprise_id');
        return $enterpriseList;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
