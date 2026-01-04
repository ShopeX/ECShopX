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

namespace KaquanBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use KaquanBundle\Entities\VipGradeOrder;

use Dingo\Api\Exception\ResourceException;

class VipGradeOrderRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new VipGradeOrder();
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
            throw new ResourceException(trans('KaquanBundle.no_update_data_found'));
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
            throw new ResourceException(trans('KaquanBundle.no_update_data_found'));
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
        // 0x53686f704578
        $entity = $this->find($id);
        if (!$entity) {
            throw new \Exception(trans('KaquanBundle.delete_data_not_exist'));
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
        // 0x53686f704578
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new \Exception(trans('KaquanBundle.delete_data_not_exist'));
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
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
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
        if (isset($data["order_id"]) && $data["order_id"]) {
            $entity->setOrderId($data["order_id"]);
        }
        if (isset($data["vip_grade_id"]) && $data["vip_grade_id"]) {
            $entity->setVipGradeId($data["vip_grade_id"]);
        }
        if (isset($data["lv_type"]) && $data["lv_type"]) {
            $entity->setLvType($data["lv_type"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        //当前字段非必填
        if (isset($data["user_id"]) && $data["user_id"]) {
            $entity->setUserId($data["user_id"]);
        }
        //当前字段非必填
        if (isset($data["mobile"]) && $data["mobile"]) {
            $entity->setMobile($data["mobile"]);
        }
        //当前字段非必填
        if (isset($data["title"]) && $data["title"]) {
            $entity->setTitle($data["title"]);
        }
        if (isset($data["price"])) {
            $entity->setPrice($data["price"]);
        }
        if (isset($data["card_type"]) && $data["card_type"]) {
            $entity->setCardType($data["card_type"]);
        }
        if (isset($data["discount"])) {
            $entity->setDiscount($data["discount"]);
        }
        if (isset($data["shop_id"]) && $data["shop_id"]) {
            $entity->setShopId($data["shop_id"]);
        }
        if (isset($data["distributor_id"]) && $data["distributor_id"]) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        //当前字段非必填
        if (isset($data["source_id"]) && $data["source_id"]) {
            $entity->setSourceId($data["source_id"]);
        }

        if (isset($data['source_type']) && $data['source_type']) {
            $entity->setSourceType($data['source_type']);
        }
        //当前字段非必填
        if (isset($data["monitor_id"]) && $data["monitor_id"]) {
            $entity->setMonitorId($data["monitor_id"]);
        }
        //当前字段非必填
        if (isset($data["order_status"]) && $data["order_status"]) {
            $entity->setOrderStatus($data["order_status"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data['fee_type']) && $data['fee_type']) {
            $entity->setFeeType($data['fee_type']);
        }
        if (isset($data['fee_rate']) && $data['fee_rate']) {
            $entity->setFeeRate($data['fee_rate']);
        }
        if (isset($data['fee_symbol']) && $data['fee_symbol']) {
            $entity->setFeeSymbol($data['fee_symbol']);
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
            'order_id' => $entity->getOrderId(),
            'vip_grade_id' => $entity->getVipGradeId(),
            'lv_type' => $entity->getLvType(),
            'company_id' => $entity->getCompanyId(),
            'user_id' => $entity->getUserId(),
            'mobile' => $entity->getMobile(),
            'title' => $entity->getTitle(),
            'price' => $entity->getPrice(),
            'card_type' => $entity->getCardType(),
            'discount' => $entity->getDiscount(),
            'shop_id' => $entity->getShopId(),
            'distributor_id' => $entity->getDistributorId(),
            'source_id' => $entity->getSourceId(),
            'source_type' => $entity->getSourceType(),
            'monitor_id' => $entity->getMonitorId(),
            'order_status' => $entity->getOrderStatus(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'fee_type' => $entity->getFeeType(),
            'fee_rate' => $entity->getFeeRate(),
            'fee_symbol' => $entity->getFeeSymbol(),
        ];
    }
}
