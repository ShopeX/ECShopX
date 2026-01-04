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
use OrdersBundle\Entities\CancelOrders;

use Dingo\Api\Exception\ResourceException;

class CancelOrdersRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new CancelOrders();
        $entity = $this->setCancelOrderData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getCancelOrderData($entity);
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
            throw new ResourceException("订单待取消记录不存在");
        }

        $entity = $this->setCancelOrderData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getCancelOrderData($entity);
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
            $entityProp = $this->setCancelOrderData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getCancelOrderData($entityProp);
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
            throw new ResourceException("删除的数据不存在");
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
            throw new ResourceException("删除的数据不存在");
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

        return $this->getCancelOrderData($entity);
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

        return $this->getCancelOrderData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $criteria = $this->__preFilter($filter);
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
        $criteria = $this->__preFilter($filter);

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
                $lists[] = $this->getCancelOrderData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    private function __preFilter($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            if (is_null($value) || $value == '') {
                continue;
            }
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
    private function setCancelOrderData($entity, $data)
    {
        /** @var \OrdersBundle\Entities\CancelOrders $entity */
        if (isset($data["cancel_id"]) && $data["cancel_id"]) {
            $entity->setCancelId($data["cancel_id"]);
        }
        if (isset($data["order_id"]) && $data["order_id"]) {
            $entity->setOrderId($data["order_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["supplier_id"])) {
            $entity->setSupplierId($data["supplier_id"]);
        }
        //当前字段非必填
        if (isset($data["shop_id"]) && $data["shop_id"]) {
            $entity->setShopId($data["shop_id"]);
        }
        if (isset($data["user_id"])) {
            $entity->setUserId($data["user_id"]);
        }
        if (isset($data["distributor_id"]) && $data["distributor_id"]) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        //当前字段非必填
        if (isset($data["order_type"]) && $data["order_type"]) {
            $entity->setOrderType($data["order_type"]);
        }
        if (isset($data["total_fee"])) {
            $entity->setTotalFee($data["total_fee"]);
        }
        if (isset($data["progress"])) {
            $entity->setProgress($data["progress"]);
        }
        if (isset($data["cancel_from"]) && $data["cancel_from"]) {
            $entity->setCancelFrom($data["cancel_from"]);
        }
        //当前字段非必填
        if (isset($data["cancel_reason"]) && $data["cancel_reason"]) {
            $entity->setCancelReason($data["cancel_reason"]);
        }
        //当前字段非必填
        if (isset($data["shop_reject_reason"]) && $data["shop_reject_reason"]) {
            $entity->setShopRejectReason($data["shop_reject_reason"]);
        }
        if (isset($data["refund_status"]) && $data["refund_status"]) {
            $entity->setRefundStatus($data["refund_status"]);
        }
        if (isset($data["create_time"]) && $data["create_time"]) {
            $entity->setCreateTime($data["create_time"]);
        }
        //当前字段非必填
        if (isset($data["update_time"]) && $data["update_time"]) {
            $entity->setUpdateTime($data["update_time"]);
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
        if (isset($data["point"])) {
            $entity->setPoint($data["point"]);
        }
        if (isset($data["pay_type"])) {
            $entity->setPayType($data["pay_type"]);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getCancelOrderData($entity)
    {
        /** @var \OrdersBundle\Entities\CancelOrders $entity */
        return [
            'cancel_id' => $entity->getCancelId(),
            'order_id' => $entity->getOrderId(),
            'company_id' => $entity->getCompanyId(),
            'supplier_id' => $entity->getSupplierId(),
            'shop_id' => $entity->getShopId(),
            'user_id' => $entity->getUserId(),
            'distributor_id' => $entity->getDistributorId(),
            'order_type' => $entity->getOrderType(),
            'total_fee' => $entity->getTotalFee(),
            'progress' => $entity->getProgress(),
            'cancel_from' => $entity->getCancelFrom(),
            'cancel_reason' => $entity->getCancelReason(),
            'shop_reject_reason' => $entity->getShopRejectReason(),
            'refund_status' => $entity->getRefundStatus(),
            'create_time' => $entity->getCreateTime(),
            'update_time' => $entity->getUpdateTime(),
            'fee_type' => $entity->getFeeType(),
            'fee_rate' => $entity->getFeeRate(),
            'fee_symbol' => $entity->getFeeSymbol(),
            'point' => $entity->getPoint(),
            'pay_type' => $entity->getPayType(),
        ];
    }
}
