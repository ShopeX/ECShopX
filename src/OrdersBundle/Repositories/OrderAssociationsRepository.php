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
use OrdersBundle\Entities\OrderAssociations;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Doctrine\Common\Collections\Criteria;

class OrderAssociationsRepository extends EntityRepository
{
    public $table = 'orders_associations';

    public function create($postdata)
    {
        $associationEntity = new OrderAssociations();
        $association = $this->setAssociationData($associationEntity, $postdata);
        $em = $this->getEntityManager();
        $em->persist($association);
        $em->flush();

        $result = $this->getAssociationData($association);

        return $result;
    }

    public function batchUpdateBy($filter, $updateInfo)
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
        if ($entityList) {
            $em = $this->getEntityManager();
            foreach ($entityList as $entityProp) {
                $entityProp = $this->setAssociationData($entityProp, $updateInfo);
                $em->persist($entityProp);
            }
            $em->flush();
            $em->clear();
        }
        return true;
    }

    public function update($filter, $updateInfo)
    {
        $associationEntity = $this->findOneBy($filter);
        if (!$associationEntity) {
            throw new UpdateResourceFailedException("订单关联信息不存在");
        }

        $orderAssociation = $this->setAssociationData($associationEntity, $updateInfo);
        $em = $this->getEntityManager();
        $em->persist($orderAssociation);
        $em->flush();

        $result = $this->getAssociationData($orderAssociation);

        return $result;
    }

    public function get($filter)
    {
        $orderAssociation = $this->findOneBy($filter);
        $result = [];
        if ($orderAssociation) {
            $result = $this->getAssociationData($orderAssociation);
        }

        return $result;
    }

    public function getList($cols = '*', $filter = array(), $offset = 0, $limit = -1, $orderby = null)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();

        $qb = $qb->select($cols)
            ->from($this->table)
            ->orderBy('create_time', 'DESC');

        if ($limit > 0) {
            $qb = $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }
        $qb = $this->_filter($filter, $qb);
        return $qb->execute()->fetchAll();
    }

    private function _filter($filter, $qb)
    {
        if ($filter) {
            //实体订单 状态筛选修正
            if (isset($filter['order_status']) && $filter['order_status']) {
                if (isset($filter['order_type']) && $filter['order_type'] == "normal") {
                    switch ($filter['order_status']) {
                    case "NOTSHIP":
                        $qb->andWhere($qb->expr()->andX(
                            $qb->expr()->eq('order_status', $qb->expr()->literal('DONE'))
                        ));
                        $qb->andWhere($qb->expr()->andX(
                            $qb->expr()->eq('delivery_status', $qb->expr()->literal('PENDING'))
                        ));
                        unset($filter['order_status']);
                        break;
                    case "FINISH":
                        $qb->andWhere($qb->expr()->andX(
                            $qb->expr()->eq('order_status', $qb->expr()->literal('DONE'))
                        ));
                        $qb->andWhere($qb->expr()->andX(
                            $qb->expr()->eq('delivery_status', $qb->expr()->literal('DONE'))
                        ));
                        unset($filter['order_status']);
                        break;
                    }
                }
            }

            foreach ($filter as $key => $filterValue) {
                if ($filterValue) {
                    if (is_array($filterValue)) {
                        array_walk($filterValue, function (&$value) use ($qb) {
                            $value = $qb->expr()->literal($value);
                        });
                    } else {
                        $filterValue = $qb->expr()->literal($filterValue);
                    }
                    $list = explode('|', $key);
                    if (count($list) > 1) {
                        list($v, $k) = $list;
                        $qb->andWhere($qb->expr()->andX(
                            $qb->expr()->$k($v, $filterValue)
                        ));
                        continue;
                    } else {
                        $qb->andWhere($qb->expr()->andX(
                            $qb->expr()->eq($key, $filterValue)
                        ));
                    }
                }
            }
        }
        return $qb;
    }

    public function count($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->table);
        $qb = $this->_filter($filter, $qb);
        return $qb->execute()->fetchColumn();
    }

    private function setAssociationData($associationEntity, $data)
    {
        if (isset($data['order_id'])) {
            $associationEntity->setOrderId($data['order_id']);
        }
        if (isset($data['authorizer_appid'])) {
            $associationEntity->setAuthorizerAppid($data['authorizer_appid']);
        }
        if (isset($data['wxa_appid'])) {
            $associationEntity->setWxaAppid($data['wxa_appid']);
        }
        if (isset($data['title'])) {
            $associationEntity->setTitle($data['title']);
        }
        if (isset($data['company_id'])) {
            $associationEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['shop_id'])) {
            $associationEntity->setShopId($data['shop_id']);
        }
        if (isset($data['user_id'])) {
            $associationEntity->setUserId($data['user_id']);
        }
        if (isset($data['promoter_user_id'])) {
            $associationEntity->setPromoterUserId($data['promoter_user_id']);
        }
        if (isset($data['promoter_shop_id'])) {
            $associationEntity->setPromoterShopId($data['promoter_shop_id']);
        }
        if (isset($data['order_class'])) {
            $associationEntity->setOrderClass($data['order_class']);
        }
        if (isset($data['order_type'])) {
            $associationEntity->setOrderType($data['order_type']);
        }
        if (isset($data['order_status'])) {
            $associationEntity->setOrderStatus($data['order_status']);
        }
        if (isset($data['create_time'])) {
            $associationEntity->setCreateTime($data['create_time']);
        }
        if (isset($data['total_fee'])) {
            $associationEntity->setTotalFee($data['total_fee']);
        }
        if (isset($data['store_name'])) {
            $associationEntity->setStoreName($data['store_name']);
        }
        if (isset($data['mobile'])) {
            $associationEntity->setMobile($data['mobile']);
        }
        if (isset($data['monitor_id'])) {
            $associationEntity->setMonitorId($data['monitor_id']);
        }
        if (isset($data['source_id'])) {
            $associationEntity->setSourceId($data['source_id']);
        }
        if (isset($data['salesman_id'])) {
            $associationEntity->setSalesmanId($data['salesman_id']);
        }
        if (isset($data['is_distribution'])) {
            $associationEntity->setIsDistribution($data['is_distribution']);
        }
        if (isset($data['total_rebate'])) {
            $associationEntity->setTotalRebate($data['total_rebate']);
        }
        if (isset($data['delivery_corp'])) {
            $associationEntity->setDeliveryCorp($data['delivery_corp']);
        }
        if (isset($data['delivery_code'])) {
            $associationEntity->setDeliveryCode($data['delivery_code']);
        }
        if (isset($data['delivery_status'])) {
            $associationEntity->setDeliveryStatus($data['delivery_status']);
        }
        if (isset($data['delivery_time'])) {
            $associationEntity->setDeliveryTime($data['delivery_time']);
        }
        if (isset($data['end_time']) && $data['end_time']) {
            $associationEntity->setEndTime($data['end_time']);
        }
        if (isset($data['cancel_status']) && $data['cancel_status']) {
            $associationEntity->setCancelStatus($data['cancel_status']);
        }

        if (isset($data['member_discount'])) {
            $associationEntity->setMemberDiscount($data['member_discount']);
        }
        if (isset($data['coupon_discount'])) {
            $associationEntity->setCouponDiscount($data['coupon_discount']);
        }

        if (isset($data['coupon_discount_desc'])) {
            $couponDiscountDesc = $data['coupon_discount_desc'];
            $associationEntity->setCouponDiscountDesc(json_encode($couponDiscountDesc));
        }

        if (isset($data['member_discount_desc'])) {
            $memberDiscountDesc = $data['member_discount_desc'];
            $associationEntity->setMemberDiscountDesc(json_encode($memberDiscountDesc));
        }
        if (isset($data['fee_type']) && $data['fee_type']) {
            $associationEntity->setFeeType($data['fee_type']);
        }
        if (isset($data['fee_rate']) && $data['fee_rate']) {
            $associationEntity->setFeeRate($data['fee_rate']);
        }
        if (isset($data['fee_symbol']) && $data['fee_symbol']) {
            $associationEntity->setFeeSymbol($data['fee_symbol']);
        }
        return $associationEntity;
    }

    public function getAssociationData($associationEntity)
    {
        $result = [
            'order_id' => $associationEntity->getOrderId(),
            'authorizer_appid' => $associationEntity->getAuthorizerAppid(),
            'wxa_appid' => $associationEntity->getWxaAppid(),
            'title' => $associationEntity->getTitle(),
            'total_fee' => $associationEntity->getTotalFee(),
            'company_id' => $associationEntity->getCompanyId(),
            'shop_id' => $associationEntity->getShopId(),
            'store_name' => $associationEntity->getStoreName(),
            'user_id' => $associationEntity->getUserId(),
            'salesman_id' => $associationEntity->getSalesmanId(),
            'promoter_user_id' => $associationEntity->getPromoterUserId(),
            'promoter_shop_id' => $associationEntity->getPromoterShopId(),
            'source_id' => $associationEntity->getSourceId(),
            'monitor_id' => $associationEntity->getMonitorId(),
            'mobile' => $associationEntity->getMobile(),
            'order_class' => $associationEntity->getOrderClass(),
            'order_type' => $associationEntity->getOrderType(),
            'order_status' => $associationEntity->getOrderStatus(),
            'create_time' => $associationEntity->getCreateTime(),
            'update_time' => $associationEntity->getUpdateTime(),
            'is_distribution' => $associationEntity->getIsDistribution(),
            'total_rebate' => $associationEntity->getTotalRebate(),
            'delivery_corp' => $associationEntity->getDeliveryCorp(),
            'delivery_code' => $associationEntity->getDeliveryCode(),
            'member_discount' => $associationEntity->getMemberDiscount(),
            'coupon_discount' => $associationEntity->getCouponDiscount(),
            'coupon_discount_desc' => [],
            'member_discount_desc' => [],
            'delivery_status' => $associationEntity->getDeliveryStatus(),
            'delivery_time' => $associationEntity->getDeliveryTime(),
            'cancel_status' => $associationEntity->getCancelStatus(),
            'end_time' => $associationEntity->getEndTime(),
            'fee_type' => $associationEntity->getFeeType(),
            'fee_rate' => $associationEntity->getFeeRate(),
            'fee_symbol' => $associationEntity->getFeeSymbol(),
        ];

        if ($associationEntity->getCouponDiscountDesc()) {
            $result['coupon_discount_desc'] = json_decode($associationEntity->getCouponDiscountDesc(), true);
        }

        if ($associationEntity->getMemberDiscountDesc()) {
            $result['member_discount_desc'] = json_decode($associationEntity->getMemberDiscountDesc(), true);
        }
        return $result;
    }
}
