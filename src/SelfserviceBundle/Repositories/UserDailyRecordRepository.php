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

namespace SelfserviceBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use SelfserviceBundle\Entities\UserDailyRecord;

use Dingo\Api\Exception\ResourceException;

class UserDailyRecordRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new UserDailyRecord();
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
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException(trans("SelfserviceBundle.no_update_data_found"));
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
            throw new ResourceException(trans("SelfserviceBundle.no_update_data_found"));
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
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
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
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["user_id"]) && $data["user_id"]) {
            $entity->setUserId($data["user_id"]);
        }
        if (isset($data["record_date"]) && $data["record_date"]) {
            $entity->setRecordDate($data["record_date"]);
        }
        if (isset($data["shop_id"]) && $data["shop_id"]) {
            $entity->setShopId($data["shop_id"]);
        }
        if (isset($data["form_data"]) && $data["form_data"]) {
            $entity->setFormData(json_encode($data["form_data"]));
        }
        if (isset($data["operator_id"]) && $data["operator_id"]) {
            $entity->setOperatorId($data["operator_id"]);
        }
        if (isset($data["operator"]) && $data["operator"]) {
            $entity->setOperator($data["operator"]);
        }
        if (isset($data["temp_id"]) && $data["temp_id"]) {
            $entity->setTempId($data["temp_id"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
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
            'id' => $entity->getId(),
            'company_id' => $entity->getCompanyId(),
            'user_id' => $entity->getUserId(),
            'operator' => $entity->getOperator(),
            'operator_id' => $entity->getOperatorId(),
            'record_date' => $entity->getRecordDate(),
            'shop_id' => $entity->getShopId(),
            'temp_id' => $entity->getTempId(),
            'form_data' => json_decode($entity->getFormData(), true),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }

    //获取记录的所有日期列表
    public function getRecordDateListGroupByRecorddate($filter, $page = 1, $pageSize = -1, $groupBy = null)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('*')
                 ->from('selfservice_user_daily_record');
        $qb = $this->_filter($filter, $qb);
        $qb = $qb->groupBy('record_date');

        $count = $qb->execute()->rowCount();
        if ($count <= 0) {
            return ['list' => [], 'total_count' => 0];
        }
        $qb->orderBy('record_date', 'DESC');
        if ($pageSize > 0) {
            $qb = $qb->setFirstResult($pageSize * ($page - 1))
                     ->setMaxResults($pageSize);
        }
        $list = $qb->execute()->fetchAll();

        return ['list' => $list, 'total_count' => $count];
    }

    //获取记录的所有会员列表
    public function getUserRecordingGroupByUserId($filter, $page = 1, $pageSize = -1)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('*')
                 ->from('selfservice_user_daily_record');
        $qb = $this->_filter($filter, $qb);
        $qb = $qb->groupBy('user_id');
        $count = $qb->execute()->rowCount();
        if ($count <= 0) {
            return ['list' => [], 'total_count' => 0];
        }
        $qb = $qb->orderBy('record_date', 'DESC');
        if ($pageSize > 0) {
            $qb = $qb->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
        }
        $list = $qb->execute()->fetchAll();

        foreach ($list as &$val) {
            if (!is_array($val['form_data'])) {
                $val['form_data'] = json_decode($val['form_data'], true);
            } else {
                $val['form_data'] = [];
            }
        }
        $result['total_count'] = $count ?? 0;
        $result['list'] = $list ?? [];
        return $result;
    }

    public function getCount($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('count(id)')
                 ->from('selfservice_user_daily_record');
        $qb = $this->_filter($filter, $qb);
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

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
}
