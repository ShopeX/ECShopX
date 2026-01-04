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

use EmployeePurchaseBundle\Entities\ActivityItems;
use GoodsBundle\Services\ItemsCategoryService;

class ActivityItemsService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(ActivityItems::class);
    }

    public function getItemsListActityPrice($itemList, $activityId, $companyId)
    {
        // TODO: optimize this method
        $itemIds = array_column($itemList['list'], 'item_id');
        if (!$itemIds) {
            return $itemList;
        }

        $filter = [
            'item_id' => $itemIds,
            'activity_id' => $activityId,
            'company_id' => $companyId,
        ];
        $list = $this->entityRepository->getLists($filter, 'item_id,activity_price,activity_store');
        $list = array_column($list, null, 'item_id');
        foreach ($itemList['list'] as $key => $row) {
            if (!isset($list[$row['item_id']])) {
                continue;
            }
            $itemList['list'][$key]['activity_price'] = intval($list[$row['item_id']]['activity_price']);
            $itemList['list'][$key]['activity_store'] = intval($list[$row['item_id']]['activity_store']);
        }

        return $itemList;
    }

    public function storeActivityItemsCategory($companyId, $activityId)
    {
        // TODO: optimize this method
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('distinct category_id')
            ->from('employee_purchase_activity_items', 'i')
            ->leftJoin('i', 'goods_items_rel_cats', 'c', 'i.item_id = c.item_id')
            ->andWhere($qb->expr()->eq('i.company_id', $companyId))
            ->andWhere($qb->expr()->eq('i.activity_id', $activityId))
            ->andWhere($qb->expr()->isNotNull('c.category_id'));
        $categoryIds = $qb->execute()->fetchAll();
        if (!$categoryIds) {
            return [];
        }

        $filter['company_id'] = $companyId;
        $filter['category_id'] = array_column($categoryIds, 'category_id');
        $itemsCategoryService = new ItemsCategoryService();
        $itemsCategory = $itemsCategoryService->lists($filter, ['sort' => 'DESC', 'created' => 'ASC'], 1000, 1);
        array_walk($itemsCategory['list'], function ($row) use ($filter) {
            $filter['category_id'] = array_merge($filter['category_id'], explode(',', $row['path']));
        });
        $filter['category_id'] = array_unique($filter['category_id']);
        $result = $itemsCategoryService->getItemsCategory($filter);

        $key = $this->getRedisKey($companyId, $activityId);
        app('redis')->set($key, json_encode($result));
        return $result;
    }

    public function fetchActivityItemsCategory($companyId, $activityId)
    {
        $key = $this->getRedisKey($companyId, $activityId);
        $result = app('redis')->get($key);
        if ($result) {
            return json_decode($result, true);
        }
        return [];
    }

    public function getRedisKey($companyId, $activityId)
    {
        return 'employee_purchase_category:'.$companyId.'_'.$activityId;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
