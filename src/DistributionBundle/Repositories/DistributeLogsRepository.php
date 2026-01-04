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

namespace DistributionBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use DistributionBundle\Entities\DistributeLogs;

use Dingo\Api\Exception\ResourceException;

class DistributeLogsRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new DistributeLogs();
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
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException(trans('DistributionBundle/Repositories.no_update_data_found'));
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

        $entityList = $this->matching($criteria);
        if (!$entityList) {
            throw new ResourceException(trans('DistributionBundle/Repositories.no_update_data_found'));
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
            throw new \Exception(trans('DistributionBundle/Repositories.delete_data_not_exist'));
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
        $entityList = $this->matching($criteria);
        if (!$entityList) {
            throw new \Exception(trans('DistributionBundle/Repositories.delete_data_not_exist'));
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
    public function lists($filter, $orderBy = ["create_time" => "DESC"], $pageSize = 100, $page = 1)
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
        if (isset($data["distributor_id"]) && $data["distributor_id"]) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        if (isset($data["order_id"]) && $data["order_id"]) {
            $entity->setOrderId($data["order_id"]);
        }
        if (isset($data["item_id"]) && $data["item_id"]) {
            $entity->setItemId($data["item_id"]);
        }
        //当前字段非必填
        if (isset($data["mobile"]) && $data["mobile"]) {
            $entity->setMobile($data["mobile"]);
        }
        //当前字段非必填
        if (isset($data["item_name"]) && $data["item_name"]) {
            $entity->setItemName($data["item_name"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        //当前字段非必填
        if (isset($data["user_id"]) && $data["user_id"]) {
            $entity->setUserId($data["user_id"]);
        }
        //当前字段非必填
        if (isset($data["pic"]) && $data["pic"]) {
            $entity->setPic($data["pic"]);
        }
        if (isset($data["num"]) && $data["num"]) {
            $entity->setNum($data["num"]);
        }
        if (isset($data["rebate"]) && $data["rebate"]) {
            $entity->setRebate($data["rebate"]);
        }
        if (isset($data["distributor_mobile"]) && $data["distributor_mobile"]) {
            $entity->setDistributorMobile($data["distributor_mobile"]);
        }
        if (isset($data["total_rebate"]) && $data["total_rebate"]) {
            $entity->setTotalRebate($data["total_rebate"]);
        }
        if (isset($data["is_close"]) && $data["is_close"]) {
            $entity->setIsClose($data["is_close"]);
        }
        if (isset($data["plan_close_time"]) && $data["plan_close_time"]) {
            $entity->setPlanCloseTime($data["plan_close_time"]);
        }
        if (isset($data["create_time"]) && $data["create_time"]) {
            $entity->setCreateTime($data["create_time"]);
        }
        if (isset($data["update_time"]) && $data["update_time"]) {
            $entity->setUpdateTime($data["update_time"]);
        }

        if (isset($data["shop_id"])) {
            $entity->setShopId($data["shop_id"]);
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
            'distributor_id' => $entity->getDistributorId(),
            'order_id' => $entity->getOrderId(),
            'item_id' => $entity->getItemId(),
            'mobile' => $entity->getMobile(),
            'item_name' => $entity->getItemName(),
            'company_id' => $entity->getCompanyId(),
            'distributor_mobile' => $entity->getDistributorMobile(),
            'user_id' => $entity->getUserId(),
            'pic' => $entity->getPic(),
            'num' => $entity->getNum(),
            'rebate' => $entity->getRebate(),
            'total_rebate' => $entity->getTotalRebate(),
            'is_close' => $entity->getIsClose(),
            'plan_close_time' => $entity->getPlanCloseTime(),
            'plan_close_date' => date(trans('DistributionBundle/Repositories.date_format_year'), $entity->getPlanCloseTime()) . (date('H', $entity->getPlanCloseTime()) + 1) . trans('DistributionBundle/Repositories.hour_suffix'),
            'shop_id' => $entity->getShopId(),
            'create_time' => $entity->getCreateTime(),
            'update_time' => $entity->getUpdateTime(),
        ];
    }
}
