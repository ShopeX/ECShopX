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

namespace AftersalesBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use AftersalesBundle\Entities\AftersalesRefund;

use Dingo\Api\Exception\ResourceException;

class AftersalesRefundRepository extends EntityRepository
{
    public $table = "aftersales_refund";
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $filter = [
            'company_id' => $data['company_id'],
            'refund_bn' => $data['refund_bn'],
        ];

        app('log')->debug('new_aftersales_refund => '. var_export($filter, 1));
        $aftersalesRefund = $this->findOneBy($filter);
        if ($aftersalesRefund) {
            throw new ResourceException("退款单已存在，不需要重复申请");
        }
        $entity = new AftersalesRefund();
        $entity = $this->setAftersalesRefundData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getAftersalesRefundData($entity);
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
            throw new ResourceException("售后退款单不存在");
        }

        $entity = $this->setAftersalesRefundData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getAftersalesRefundData($entity);
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
            $entityProp = $this->setAftersalesRefundData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getAftersalesRefundData($entityProp);
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
            throw new \Exception("删除的数据不存在");
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
            throw new \Exception("删除的数据不存在");
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
    }

    /**
    * 统计数量
    */
    public function count($filter)
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                if (!is_null($value) && $value != '') {
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
    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getAftersalesRefundData($entity);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getList($filter, $offset = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'])
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                if (!is_null($value) && $value != '') {
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
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        $prderList = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy);
            if ($limit > 0) {
                $criteria = $criteria->setFirstResult($offset)
                                     ->setMaxResults($limit);
            }
            $list = $this->matching($criteria);
            foreach ($list as $v) {
                $order = $this->getAftersalesRefundData($v);
                $prderList[] = $order;
            }
        }
        $res['list'] = $prderList;

        return $res;
    }

    public function sum($filter, $cols)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select($cols)
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $result = $qb->execute()->fetchColumn();
        return $result ?? 0;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setAftersalesRefundData($entity, $data)
    {
        /** @var \AftersalesBundle\Entities\AftersalesRefund $entity */
        if (isset($data['refund_bn']) && $data['refund_bn']) {
            $entity->setRefundBn($data['refund_bn']);
        }
        if (isset($data['aftersales_bn']) && $data['aftersales_bn']) {
            $entity->setAftersalesBn($data['aftersales_bn']);
        }
        if (isset($data['order_id']) && $data['order_id']) {
            $entity->setOrderId($data['order_id']);
        }
        if (isset($data['trade_id']) && $data['trade_id']) {
            $entity->setTradeId($data['trade_id']);
        }
        if (isset($data['supplier_id'])) {
            $entity->setSupplierId($data['supplier_id']);
        }
        // if(isset($data['item_id']) && $data['item_id']) {
        //     $entity->setItemId($data['item_id']);
        // }
        if (isset($data['company_id']) && $data['company_id']) {
            $entity->setCompanyId($data['company_id']);
        }
        if (isset($data['user_id'])) {
            $entity->setUserId($data['user_id']);
        }
        if (isset($data['shop_id']) && $data['shop_id']) {
            $entity->setShopId($data['shop_id']);
        }
        if (isset($data['distributor_id'])) {
            $entity->setDistributorId($data['distributor_id']);
        }
        if (isset($data['refund_type'])) {
            $entity->setRefundType($data['refund_type']);
        }
        if (isset($data['refund_channel']) && $data['refund_channel']) {
            $entity->setRefundChannel($data['refund_channel']);
        }
        if (isset($data['refund_status']) && $data['refund_status']) {
            $entity->setRefundStatus($data['refund_status']);
        }
        // if(isset($data['order_fee'])) {
        //     $entity->setOrderFee($data['order_fee']);
        // }
        if (isset($data['refund_fee'])) {
            $entity->setRefundFee($data['refund_fee']);
        }
        if (isset($data['refunded_fee'])) {
            $entity->setRefundedFee($data['refunded_fee']);
        }
        if (isset($data['refund_point'])) {
            $entity->setRefundPoint($data['refund_point']);
        }
        if (isset($data['refunded_point'])) {
            $entity->setRefundedPoint($data['refunded_point']);
        }
        if (isset($data['return_point'])) {
            $entity->setReturnPoint($data['return_point']);
        }
        if (isset($data['return_freight'])) {
            $entity->setReturnFreight($data['return_freight']);
        }
        if (isset($data['pay_type']) && $data['pay_type']) {
            $entity->setPayType($data['pay_type']);
        }
        if (isset($data['currency']) && $data['currency']) {
            $entity->setCurrency($data['currency']);
        }
        //当前字段非必填
        if (isset($data['refunds_memo']) && $data['refunds_memo']) {
            $entity->setRefundsMemo($data['refunds_memo']);
        }
        //当前字段非必填
        // if(isset($data['refund_time']) && $data['refund_time']) {
        //     $entity->setRefundTime($data['refund_time']);
        // }
        //当前字段非必填
        if (isset($data['refund_success_time']) && $data['refund_success_time']) {
            $entity->setRefundSuccessTime($data['refund_success_time']);
        }
        //当前字段非必填
        if (isset($data['refund_id']) && $data['refund_id']) {
            $entity->setRefundId($data['refund_id']);
        }
        if (isset($data['create_time']) && $data['create_time']) {
            $entity->setCreateTime($data['create_time']);
        }
        //当前字段非必填
        if (isset($data['update_time']) && $data['update_time']) {
            $entity->setUpdateTime($data['update_time']);
        }

        //当前字段非必填
        if (isset($data["cur_fee_type"]) && $data["cur_fee_type"]) {
            $entity->setCurFeeType($data["cur_fee_type"]);
        }
        //当前字段非必填
        if (isset($data["cur_fee_rate"]) && $data["cur_fee_rate"]) {
            $entity->setCurFeeRate($data["cur_fee_rate"]);
        }
        if (isset($data["cur_fee_symbol"]) && $data["cur_fee_symbol"]) {
            $entity->setCurFeeSymbol($data["cur_fee_symbol"]);
        }
        //当前字段非必填
        if (isset($data["cur_pay_fee"])) {
            $entity->setCurPayFee($data["cur_pay_fee"]);
        }
        //当前字段非必填
        if (isset($data["hf_order_id"]) && $data["hf_order_id"]) {
            $entity->setHfOrderId($data["hf_order_id"]);
        }
        if (isset($data['merchant_id'])) {
            $entity->setMerchantId($data['merchant_id']);
        }
        if (isset($data['freight'])) {
            $entity->setFreight($data['freight']);
        }
        if (isset($data['freight_type'])) {
            $entity->setFreightType($data['freight_type']);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getAftersalesRefundData($entity)
    {
        /** @var \AftersalesBundle\Entities\AftersalesRefund $entity */
        return [
            'refund_bn' => $entity->getRefundBn(),
            'aftersales_bn' => $entity->getAftersalesBn(),
            'supplier_id' => $entity->getSupplierId(),
            'order_id' => $entity->getOrderId(),
            'trade_id' => $entity->getTradeId(),
            // 'item_id'=> $entity->getItemId(),
            'company_id' => $entity->getCompanyId(),
            'user_id' => $entity->getUserId(),
            'shop_id' => $entity->getShopId(),
            'distributor_id' => $entity->getDistributorId(),
            'refund_type' => $entity->getRefundType(),
            'refund_channel' => $entity->getRefundChannel(),
            'refund_status' => $entity->getRefundStatus(),
            // 'order_fee'=> $entity->getOrderFee(),
            'refund_fee' => $entity->getRefundFee(),
            'refunded_fee' => $entity->getRefundedFee(),
            'refund_point' => $entity->getRefundPoint(),
            'refunded_point' => $entity->getRefundedPoint(),
            'return_point' => $entity->getReturnPoint(),
            'return_freight' => $entity->getReturnFreight(),
            'pay_type' => $entity->getPayType(),
            'currency' => $entity->getCurrency(),
            'refunds_memo' => $entity->getRefundsMemo(),
            // 'refund_time'=> $entity->getRefundTime(),
            'refund_success_time' => $entity->getRefundSuccessTime(),
            'refund_id' => $entity->getRefundId(),
            // 'paycost'=> $entity->getPaycost(),
            'create_time' => $entity->getCreateTime(),
            'update_time' => $entity->getUpdateTime(),
            //cur_pay_fee汇率换算之前的原金额
            'cur_pay_fee' => $entity->getCurPayFee(),
            'cur_fee_symbol' => $entity->getCurFeeSymbol(),
            'cur_fee_rate' => $entity->getCurFeeRate(),
            'cur_fee_type' => $entity->getCurFeeType(),
            'hf_order_id' => $entity->getHfOrderId(),
            'merchant_id' => $entity->getMerchantId(),
            'freight' => $entity->getFreight(),
            'freight_type' => $entity->getFreightType(),
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
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($v, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
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
