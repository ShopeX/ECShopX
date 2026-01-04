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

namespace PromotionsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use PromotionsBundle\Entities\ExtraPointActivity;

use Dingo\Api\Exception\ResourceException;

class ExtraPointActivityRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new ExtraPointActivity();
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
        // Log: 456353686f7058
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException(trans("PromotionsBundle.no_update_data_found"));
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
        // Log: 456353686f7058
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
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

        $entityList = $this->matching($criteria);
        if (!$entityList) {
            throw new ResourceException(trans("PromotionsBundle.no_update_data_found"));
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
            throw new \Exception(trans("PromotionsBundle.delete_data_not_exist"));
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
            throw new \Exception(trans("PromotionsBundle.delete_data_not_exist"));
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

    public function count($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
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
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
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
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
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
        if (isset($data["type"]) && $data["type"]) {
            $entity->setType($data["type"]);
        }
        if (isset($data["title"]) && $data["title"]) {
            $entity->setTitle($data["title"]);
        }
        if (isset($data["trigger_condition"]) && $data["trigger_condition"]) {
            $entity->setTriggerCondition(json_encode($data["trigger_condition"]));
        }
        if (isset($data["condition_type"]) && $data["condition_type"]) {
            $entity->setConditionType(json_encode($data["condition_type"]));
        }
        if (isset($data["condition_value"]) && $data["condition_value"]) {
            $entity->setConditionValue($data["condition_value"]);
        }
        if (isset($data["valid_grade"]) && $data["valid_grade"]) {
            $entity->setValidGrade(json_encode($data["valid_grade"]));
        } else {
            $entity->setValidGrade(null);
        }

        if (isset($data["use_shop"])) {
            $entity->setUseShop($data["use_shop"]);
        }
        if (isset($data["shop_ids"]) && $data["shop_ids"]) {
            $shopIds = ','.implode(',', $data['shop_ids']).",";
            $entity->setShopIds($shopIds);
        } else {
            $entity->setShopIds(null);
        }
        if (isset($data["begin_time"]) && $data["begin_time"]) {
            $entity->setBeginTime($data["begin_time"]);
        }
        if (isset($data["end_time"]) && $data["end_time"]) {
            $entity->setEndTime($data["end_time"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        if (isset($data["activity_status"]) && $data["activity_status"]) {
            $entity->setActivityStatus($data["activity_status"]);
        }

        $entity->setUpdated(time());

        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        $data = [
            'activity_id' => $entity->getActivityId(),
            'company_id' => $entity->getCompanyId(),
            'type' => $entity->getType(),
            'title' => $entity->getTitle(),
            'trigger_condition' => json_decode($entity->getTriggerCondition(), 1),
            'condition_value' => $entity->getConditionValue(),
            'condition_type' => json_decode($entity->getConditionType(), 1),
            'valid_grade' => json_decode($entity->getValidGrade(), true) ? json_decode($entity->getValidGrade(), true) : [],
            'use_shop' => $entity->getUseShop(),
            'shop_ids' => $entity->getShopIds() ? explode(',', $entity->getShopIds()) : [],
            'begin_time' => date('Y-m-d H:i:s', $entity->getBeginTime()),
            'end_time' => date('Y-m-d H:i:s', $entity->getEndTime()),
            'created' => date('Y-m-d H:i:s', $entity->getCreated()),
            'updated' => date('Y-m-d H:i:s', $entity->getUpdated()),
        ];

        if ($entity->getActivityStatus() == 'valid') {
            $now = time();
            if ($entity->getBeginTime() > $now) {
                $data['activity_status'] = 'ready';
            } elseif ($entity->getEndTime() > $now) {
                $data['activity_status'] = 'processing';
            } else {
                $data['activity_status'] = 'end';
            }
        } else {
            $data['activity_status'] = 'end';
        }

        if ($entity->getEndTime() == '5000000000') {
            $data['is_forever'] = true;
        } else {
            $data['is_forever'] = false;
        }

        return $data;
    }
}
