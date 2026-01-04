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

namespace MembersBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use MembersBundle\Entities\ShopRelMember;

class ShopRelMemberRepository extends EntityRepository
{
    public $table = 'members_rel_shop';

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new ShopRelMember();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
    * 根据条件获取单条数据
    *
    * @param $filter 更新的条件
    */
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = ["created" => "DESC"])
    {
        $lists = [];
        $totalCount = $this->count($filter);
        if ($totalCount) {
            $criteria = $this->_filter($filter);
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }
        return $lists;
    }

    /**
    * 统计数量
    */
    public function count($filter)
    {
        $criteria = $this->_filter($filter);
        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);

        return intval($total);
    }

    private function _filter($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }
        return $criteria;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["user_id"])) {
            $entity->setUserId($data["user_id"]);
        }
        if (isset($data["shop_id"])) {
            $entity->setShopId($data["shop_id"]);
        }
        if (isset($data["shop_type"]) && $data["shop_type"]) {
            $entity->setShopType($data["shop_type"]);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'company_id' => $entity->getCompanyId(),
            'user_id' => $entity->getUserId(),
            'shop_id' => $entity->getShopId(),
            'shop_type' => $entity->getShopType(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }

    //根据标签id集合获取会员id，取交集
    public function getUserIdBy($filter, $page, $pageSize)
    {
        $shopIds = $filter['shop_id'] ?? 0;
        if (!$shopIds) {
            return [];
        }
        $userIds = $filter['user_id'] ?? [];
        $companyId = $filter['company_id'] ?? [];

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $shopIds = (array)$shopIds;
        array_walk($shopIds, function (&$colVal) use ($criteria) {
            $colVal = $criteria->expr()->literal($colVal);
        });
        $criteria->select('user_id')
            ->from($this->table)
            ->where($criteria->expr()->in('shop_id', $shopIds));
        if ($companyId) {
            $criteria->where($criteria->expr()->eq('company_id', $companyId));
        }
        if ($userIds) {
            $criteria->where($criteria->expr()->in('user_id', $userIds));
        }
        $criteria->groupBy('user_id')
            ->having('count(user_id) ='.count($shopIds));
        if ($pageSize > 0) {
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
        }
        $list = $criteria->execute()->fetchAll();
        if (!$list) {
            return [];
        }
        $userIds = array_column($list, 'user_id');
        return $userIds;
    }
}
