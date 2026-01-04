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

namespace ReservationBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

class ResourceLevelRepository extends EntityRepository
{
    public $table = "reservation_resource_level";
    public $relServiceTable = "reservation_level_rel_service";

    public function create($postParams, $serviceIds)
    {
        // fe10e2f6 module
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $resourceLevel = [
                'company_id' => $postParams['companyId'],
                'shop_id' => $postParams['shopId'],
                'shop_name' => $postParams['shopName'],
                'name' => $postParams['name'],
                'description' => $postParams['description'],
                'status' => $postParams['status'],
                'image_url' => $postParams['image_url'],
                'created' => time(),
            ];
            $conn->insert($this->table, $resourceLevel);
            $resourceLevelId = $conn->lastInsertId();
            if ($serviceIds) {
                foreach ($serviceIds as $serviceId) {
                    $relService = [
                        'company_id' => intval($postParams['companyId']),
                        'material_id' => intval($serviceId),
                        'resource_level_id' => intval($resourceLevelId),
                        'shop_id' => intval($postParams['shopId']),
                        'created' => time(),
                    ];
                    $conn->insert($this->relServiceTable, $relService);
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function update($filter, $postParams, $serviceIds)
    {
        $conn = app('registry')->getConnection('default');
        $resourceLevels = $conn->fetchAssoc('select * from '.$this->table.' where resource_level_id=? and company_id=?', [$filter['resource_level_id'],$filter['company_id']]);
        if (!$resourceLevels) {
            return false;
        }

        $conn->beginTransaction();
        try {
            $resourceLevel = [
                'company_id' => $postParams['companyId'],
                'name' => $postParams['name'],
                'description' => $postParams['description'],
                'status' => $postParams['status'],
                'image_url' => $postParams['image_url'],
                'updated' => time(),
            ];
            $conn->update($this->table, $resourceLevel, $filter);
            $relFiler = [
                'company_id' => $filter['company_id'],
                'resource_level_id' => $filter['resource_level_id']
            ];
            $conn->delete($this->relServiceTable, $relFiler);
            if ($serviceIds) {
                foreach ($serviceIds as $serviceId) {
                    $relService = [
                        'company_id' => $postParams['companyId'],
                        'material_id' => $serviceId,
                        'resource_level_id' => $filter['resource_level_id'],
                        'shop_id' => intval($postParams['shopId']),
                        'created' => time(),
                    ];
                    $conn->insert($this->relServiceTable, $relService);
                }
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function remove($filter)
    {
        $conn = app('registry')->getConnection('default');
        $resourceLevel = $conn->fetchAssoc('select * from '.$this->table.' where resource_level_id=? and company_id=?', [$filter['resource_level_id'],$filter['company_id']]);
        if (!$resourceLevel) {
            return false;
        }

        $conn->beginTransaction();
        try {
            $conn->delete($this->table, $filter);
            $relFiler = [
                'company_id' => $filter['company_id'],
                'resource_level_id' => $filter['resource_level_id']
            ];
            $conn->delete($this->relServiceTable, $relFiler);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function get($filter)
    {
        $resourceLevel = $this->findOneBy($filter);
        return normalize($resourceLevel);
    }

    /**
     * 获取资源位列表
     *
     * @param filter
     * @param pageSize 查询个数
     * @param page 查询页数
     * @param order by
     */
    public function getList($filter, $pageSize = 100, $page = 1, $orderBy = ['resource_level_id' => 'DESC'])
    {
        $data = [];
        $criteria = $this->__filter($filter);
        $criteria = $criteria->orderBy($orderBy)
            ->setFirstResult($pageSize * ($page - 1))
            ->setMaxResults($pageSize);
        $listDatas = $this->matching($criteria);
        foreach ($listDatas as $value) {
            $data[] = normalize($value);
        }
        return $data;
    }

    public function getCount($filter)
    {
        $criteria = $this->__filter($filter);
        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        return intval($total);
    }

    private function __filter($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            if (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }
        return $criteria;
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $conn = app('registry')->getConnection('default');
        $resourceLevels = $conn->fetchAssoc('select * from '.$this->table.' where resource_level_id=? and company_id=?', [$filter['resource_level_id'],$filter['company_id']]);
        if (!$resourceLevels) {
            return [];
        }
        $conn->update($this->table, $data, $filter);
        $resourceLevel = $this->findOneBy($filter);
        return normalize($resourceLevel);
    }
}
