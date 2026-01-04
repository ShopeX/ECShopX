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

namespace PointBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use PointBundle\Entities\PointMemberLog;

use Dingo\Api\Exception\ResourceException;

class PointMemberLogRepository extends EntityRepository
{
    public $table = 'point_member_log';

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new PointMemberLog();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        // CRC: 2367340174
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getColumnNamesData($entityProp);
        }
        return $result;
    }

    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return true;
        }
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
        return true;
    }

    /**
     * 根据条件删除指定数据
     *
     * @param $filter 删除的条件
     */
    public function deleteBy($filter)
    {
        // CRC: 2367340174
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            return true;
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
    }

    /**
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
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

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);

        return intval($total);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = array())
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

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }
            if ($pageSize > 0) {
                $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["user_id"]) && $data["user_id"]) {
            $entity->setUserId($data["user_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["journal_type"]) && $data["journal_type"]) {
            $entity->setJournalType($data["journal_type"]);
        }
        if (isset($data["point_desc"]) && $data["point_desc"]) {
            $entity->setPointDesc($data["point_desc"]);
        }
        if (isset($data["income"])) {
            $entity->setIncome($data["income"]);
        }
        if (isset($data["outcome"])) {
            $entity->setOutcome($data["outcome"]);
        }
        //当前字段非必填
        if (isset($data["order_id"])) {
            $entity->setOrderId($data["order_id"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["operater"])) {
            $entity->setOperater($data["operater"]);
        }
        if (isset($data["operater_remark"])) {
            $entity->setOperaterRemark($data["operater_remark"]);
        }
        if (isset($data["external_id"])) {
            $entity->setExternalId($data["external_id"]);
        } else {
            $entity->setExternalId((string)$entity->getExternalId());
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
        $income = $entity->getIncome();
        $outcome = $entity->getOutcome();
        $outin_type = 0 == $income ? 'out' : 'in';
        if (0 == $income && 0 == $outcome) {
            $outin_type = 'in';
        }
        return [
            'id' => $entity->getId(),
            'user_id' => $entity->getUserId(),
            'company_id' => $entity->getCompanyId(),
            'journal_type' => $entity->getJournalType(),
            'point_desc' => $entity->getPointDesc(),
            'income' => $income,
            'outcome' => $outcome,
            'order_id' => $entity->getOrderId(),
            'outin_type' => $outin_type, // 增加减少积分 1 增加 2 减少
            'point' => 0 == $entity->getIncome() ? $entity->getOutcome() : $entity->getIncome(), // 增加减少积分 1 增加 2 减少
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'operater' => $entity->getOperater(),
            'operater_remark' => $entity->getOperaterRemark(),
            'external_id' => $entity->getExternalId(),
        ];
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                    $value = '%'.$value.'%';
                }
                $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }

    /**
    * 根据条件，获取积分的收入-支出的总和
    */
    public function sumCanUsePoint($filter)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('SUM(income)-SUM(outcome)')->from($this->table);

        if ($filter) {
            $this->_filter($filter, $criteria);
        }

        $total = $criteria->execute()->fetchColumn();
        return $total ?: 0;
    }

    /**
    * 根据条件，获取指定字段的总和
    */
    public function sumPointByField($filter, $field)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('SUM('.$field.')')->from($this->table);

        if ($filter) {
            $this->_filter($filter, $criteria);
        }

        $total = $criteria->execute()->fetchColumn();
        return $total ?: 0;
    }
}
