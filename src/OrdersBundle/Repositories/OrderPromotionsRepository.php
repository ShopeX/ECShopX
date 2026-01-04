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

namespace OrdersBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use OrdersBundle\Entities\OrderPromotions;

use Dingo\Api\Exception\ResourceException;

class OrderPromotionsRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        // Ref: 1996368445
        $entity = new OrderPromotions();
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
        // Ref: 1996368445
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
        if (isset($data["id"]) && $data["id"]) {
            $entity->setId($data["id"]);
        }
        if (isset($data["moid"]) && $data["moid"]) {
            $entity->setMoid($data["moid"]);
        }
        if (isset($data["coid"]) && $data["coid"]) {
            $entity->setCoid($data["coid"]);
        }
        //当前字段非必填
        if (isset($data["user_id"]) && $data["user_id"]) {
            $entity->setUserId($data["user_id"]);
        }
        if (isset($data["order_type"]) && $data["order_type"]) {
            $entity->setOrderType($data["order_type"]);
        }
        if (isset($data["item_id"]) && $data["item_id"]) {
            $entity->setItemId($data["item_id"]);
        }
        //当前字段非必填
        if (isset($data["item_name"]) && $data["item_name"]) {
            $entity->setItemName($data["item_name"]);
        }
        //当前字段非必填
        if (isset($data["item_type"]) && $data["item_type"]) {
            $entity->setItemType($data["item_type"]);
        }
        if (isset($data["activity_id"]) && $data["activity_id"]) {
            $entity->setActivityId($data["activity_id"]);
        }
        //当前字段非必填
        if (isset($data["activity_name"]) && $data["activity_name"]) {
            $entity->setActivityName($data["activity_name"]);
        }
        if (isset($data["activity_type"]) && $data["activity_type"]) {
            $entity->setActivityType($data["activity_type"]);
        }
        //当前字段非必填
        if (isset($data["activity_tag"]) && $data["activity_tag"]) {
            $entity->setActivityTag($data["activity_tag"]);
        }
        //当前字段非必填
        if (isset($data["activity_desc"]) && $data["activity_desc"]) {
            $entity->setActivityDesc(json_encode($data["activity_desc"]));
        }
        //当前字段非必填
        if (isset($data["shop_id"]) && $data["shop_id"]) {
            $entity->setShopId($data["shop_id"]);
        }
        //当前字段非必填
        if (isset($data["shop_type"]) && $data["shop_type"]) {
            $entity->setShopType($data["shop_type"]);
        }
        //当前字段非必填
        if (isset($data["status"]) && $data["status"]) {
            $entity->setStatus($data["status"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
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
            'moid' => $entity->getMoid(),
            'coid' => $entity->getCoid(),
            'user_id' => $entity->getUserId(),
            'order_type' => $entity->getOrderType(),
            'item_id' => $entity->getItemId(),
            'item_name' => $entity->getItemName(),
            'item_type' => $entity->getItemType(),
            'activity_id' => $entity->getActivityId(),
            'activity_name' => $entity->getActivityName(),
            'activity_type' => $entity->getActivityType(),
            'activity_tag' => $entity->getActivityTag(),
            'activity_desc' => json_decode($entity->getActivityDesc(), true),
            'shop_id' => $entity->getShopId(),
            'shop_type' => $entity->getShopType(),
            'status' => $entity->getStatus(),
            'company_id' => $entity->getCompanyId(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }
}
