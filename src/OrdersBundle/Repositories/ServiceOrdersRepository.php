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
use OrdersBundle\Entities\ServiceOrders;
use Doctrine\Common\Collections\Criteria;
use Dingo\Api\Exception\UpdateResourceFailedException;

class ServiceOrdersRepository extends EntityRepository
{
    public $table = 'service_orders';

    public function create($params)
    {
        $serviceOrdersEntity = new ServiceOrders();
        $serviceOrder = $this->setServiceOrderData($serviceOrdersEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($serviceOrder);
        $em->flush();

        $result = $this->getServiceOrderData($serviceOrder);

        return $result;
    }

    public function batchUpdateBy($filter, $updateInfo)
    {
        // TS: 53686f704578
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
                $entityProp = $this->setServiceOrderData($entityProp, $updateInfo);
                $em->persist($entityProp);
            }
            $em->flush();
            $em->clear();
        }
        return true;
    }

    public function update($filter, $updateInfo)
    {
        $order = $this->findOneBy($filter);
        if (!$order) {
            throw new UpdateResourceFailedException("订单不存在");
        }
        $serviceOrder = $this->setServiceOrderData($order, $updateInfo);
        $em = $this->getEntityManager();
        $em->persist($serviceOrder);
        $em->flush();

        return true;
    }

    public function getList($filter = array(), $offset = 0, $limit = 500, $orderby = null)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();

        $qb = $qb->select('*')
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
                        if (is_array($filterValue)) {
                            $qb->andWhere($qb->expr()->andX(
                                $qb->expr()->in($key, $filterValue)
                            ));
                        } else {
                            $qb->andWhere($qb->expr()->andX(
                                $qb->expr()->eq($key, $filterValue)
                            ));
                        }
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

    public function get($companyId, $orderId)
    {
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId
        ];

        $result = $this->findOneBy($filter);
        return $result;
    }

    private function setServiceOrderData($orderEntity, $data)
    {
        if (isset($data['order_id'])) {
            $orderEntity->setOrderId($data['order_id']);
        }
        if (isset($data['title'])) {
            $orderEntity->setTitle($data['title']);
        }
        if (isset($data['company_id'])) {
            $orderEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['shop_id'])) {
            $orderEntity->setShopId($data['shop_id']);
        }
        if (isset($data['user_id'])) {
            $orderEntity->setUserId($data['user_id']);
        }
        if (isset($data['consume_type'])) {
            $orderEntity->setConsumeType($data['consume_type']);
        }
        if (isset($data['bargain_id'])) {
            $orderEntity->setBargainId($data['bargain_id']);
        }
        if (isset($data['item_id'])) {
            $orderEntity->setItemId($data['item_id']);
        }
        if (isset($data['item_num'])) {
            $orderEntity->setItemNum($data['item_num']);
        }
        if (isset($data['item_brief'])) {
            $orderEntity->setItemBrief($data['item_brief']);
        }
        if (isset($data['item_pics'])) {
            $orderEntity->setItemPics($data['item_pics']);
        }
        if (isset($data['mobile'])) {
            $orderEntity->setMobile($data['mobile']);
        }
        if (isset($data['total_fee'])) {
            $orderEntity->setTotalFee($data['total_fee']);
        }
        if (isset($data['step_paid_fee'])) {
            $orderEntity->setStepPaidFee($data['step_paid_fee']);
        }
        if (isset($data['order_class'])) {
            $orderEntity->setOrderClass($data['order_class']);
        }
        if (isset($data['order_status'])) {
            $orderEntity->setOrderStatus($data['order_status']);
        }
        if (isset($data['order_source'])) {
            $orderEntity->setOrderSource($data['order_source']);
        }
        if (isset($data['operator_desc'])) {
            $orderEntity->setOperatorDesc($data['operator_desc']);
        }
        if (isset($data['order_type'])) {
            $orderEntity->setOrderType($data['order_type']);
        }
        if (isset($data['auto_cancel_time'])) {
            $orderEntity->setAutoCancelTime($data['auto_cancel_time']);
        }
        if (isset($data['store_name'])) {
            $orderEntity->setStoreName($data['store_name']);
        }
        if (isset($data['date_type'])) {
            $orderEntity->setDateType($data['date_type']);
        }
        if (isset($data['begin_date'])) {
            $orderEntity->setBeginDate($data['begin_date']);
        }
        if (isset($data['end_date'])) {
            $orderEntity->setEndDate($data['end_date']);
        }
        if (isset($data['fixed_term'])) {
            $orderEntity->setFixedTerm($data['fixed_term']);
        }
        if (isset($data['source_id'])) {
            $orderEntity->setSourceId($data['source_id']);
        }
        if (isset($data['monitor_id'])) {
            $orderEntity->setMonitorId($data['monitor_id']);
        }
        if (isset($data['salesman_id'])) {
            $orderEntity->setSalesmanId($data['salesman_id']);
        }
        if (isset($data['item_fee'])) {
            $orderEntity->setItemFee($data['item_fee']);
        }
        if (isset($data['cost_fee'])) {
            $orderEntity->setCostFee($data['cost_fee']);
        }
        if (isset($data['member_discount'])) {
            $orderEntity->setMemberDiscount($data['member_discount']);
        }
        if (isset($data['coupon_discount'])) {
            $orderEntity->setCouponDiscount($data['coupon_discount']);
        }

        if (isset($data['coupon_discount_desc'])) {
            $couponDiscountDesc = $data['coupon_discount_desc'];
            $orderEntity->setCouponDiscountDesc(json_encode($couponDiscountDesc));
        }

        if (isset($data['member_discount_desc'])) {
            $memberDiscountDesc = $data['member_discount_desc'];
            $orderEntity->setMemberDiscountDesc(json_encode($memberDiscountDesc));
        }

        if (isset($data['fee_type']) && $data['fee_type']) {
            $orderEntity->setFeeType($data['fee_type']);
        }
        if (isset($data['fee_rate']) && $data['fee_rate']) {
            $orderEntity->setFeeRate($data['fee_rate']);
        }
        if (isset($data['fee_symbol']) && $data['fee_symbol']) {
            $orderEntity->setFeeSymbol($data['fee_symbol']);
        }

        return $orderEntity;
    }

    public function getServiceOrderData($orderEntity)
    {
        $result = [
            'order_id' => $orderEntity->getOrderId(),
            'title' => $orderEntity->getTitle(),
            'company_id' => $orderEntity->getCompanyId(),
            'shop_id' => $orderEntity->getShopId(),
            'store_name' => $orderEntity->getStoreName(),
            'user_id' => $orderEntity->getUserId(),
            'bargain_id' => $orderEntity->getBargainId(),
            'consume_type' => $orderEntity->getConsumeType(),
            'item_id' => $orderEntity->getItemId(),
            'source_id' => $orderEntity->getSourceId(),
            'monitor_id' => $orderEntity->getMonitorId(),
            'salesman_id' => $orderEntity->getSalesmanId(),
            'item_num' => $orderEntity->getItemNum(),
            'item_brief' => $orderEntity->getItemBrief(),
            'item_pics' => $orderEntity->getItemPics(),
            'mobile' => $orderEntity->getMobile(),
            'total_fee' => $orderEntity->getTotalFee(),
            'step_paid_fee' => $orderEntity->getStepPaidFee(),
            'order_class' => $orderEntity->getOrderClass(),
            'order_status' => $orderEntity->getOrderStatus(),
            'order_source' => $orderEntity->getOrderSource(),
            'operator_desc' => $orderEntity->getOperatorDesc(),
            'order_type' => $orderEntity->getOrderType(),
            'item_fee' => $orderEntity->getItemFee(),
            'cost_fee' => $orderEntity->getCostFee(),
            'member_discount' => $orderEntity->getMemberDiscount(),
            'coupon_discount' => $orderEntity->getCouponDiscount(),
            'coupon_discount_desc' => [],
            'member_discount_desc' => [],
            'create_time' => $orderEntity->getCreateTime(),
            'update_time' => $orderEntity->getUpdateTime(),
            'fee_type' => $orderEntity->getFeeType(),
            'fee_rate' => $orderEntity->getFeeRate(),
            'fee_symbol' => $orderEntity->getFeeSymbol(),
        ];

        if ($orderEntity->getCouponDiscountDesc()) {
            $result['coupon_discount_desc'] = json_decode($orderEntity->getCouponDiscountDesc(), true);
        }

        if ($orderEntity->getMemberDiscountDesc()) {
            $result['member_discount_desc'] = json_decode($orderEntity->getMemberDiscountDesc(), true);
        }
        return $result;
    }
}
