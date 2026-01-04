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

use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\EntityRepository;
use OrdersBundle\Entities\NormalOrders;
use Doctrine\Common\Collections\Criteria;
use Dingo\Api\Exception\UpdateResourceFailedException;

class NormalOrdersRepository extends EntityRepository
{
    public $table = 'orders_normal_orders';

    public function create($params)
    {
        $normalOrdersEntity = new NormalOrders();
        $normalOrder = $this->setNormalOrderData($normalOrdersEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($normalOrder);
        $em->flush();

        $result = $this->getServiceOrderData($normalOrder);

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
                $entityProp = $this->setNormalOrderData($entityProp, $updateInfo);
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
        $normalOrder = $this->setNormalOrderData($order, $updateInfo);
        $em = $this->getEntityManager();
        $em->persist($normalOrder);
        $em->flush();

        $result = $this->getServiceOrderData($normalOrder);

        return $result;
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $qb = $qb->set($key, $qb->expr()->literal($val));
        }

        $qb = $this->_filter($filter, $qb);

        return $qb->execute();
    }

    public function getList($filter = array(), $offset = 0, $limit = -1, $orderby = null, $col = "*")
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('o.' . $col)
            ->from($this->table, 'o');

        if (isset($filter['supplier_id']) && $filter['supplier_id']) {
            $qb = $qb->leftJoin('o', 'supplier_order', 'so', 'o.order_id = so.order_id');
            foreach ($filter as $k => $v) {
                if ($k == 'supplier_id') {
                    $filter['so.supplier_id'] = $filter['supplier_id'];
                } else if (!in_array($k, ['prescription_order_filter'])) {
                    $filter['o.' . $k] = $v;
                }
                unset($filter[$k]);
            }
        }

        if (!empty($filter['prescription_order_filter'])) {
            $qb = $qb->leftJoin('o', 'orders_prescription', 'op', 'o.order_id = op.order_id');
            foreach ($filter['prescription_order_filter'] as $k => $v) {
                $filter['op.' . $k] = $v;
            }
            unset($filter['prescription_order_filter']);

            foreach ($filter as $k => $v) {
                if (in_array($k, ['company_id'])) {
                    $filter['o.' . $k] = $v;
                    unset($filter[$k]);
                }
            }
        }

        if ($orderby) {
            foreach ($orderby as $columns => $value) {
                $qb->orderBy('o.' . $columns, $value);
            }
        } else {
            $qb->orderBy('o.create_time', 'DESC');
        }

        if ($limit > 0) {
            $qb = $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $qb = $this->_filter($filter, $qb);
        $list = $qb->execute()->fetchAll();

        if (!empty($list)) {
            foreach ($list as $key => $value) {
                // 数据解密
                isset($value['mobile']) and $list[$key]['mobile'] = fixeddecrypt($value['mobile']);
                isset($value['receiver_name']) and $list[$key]['receiver_name'] = fixeddecrypt($value['receiver_name']);
                isset($value['receiver_mobile']) and $list[$key]['receiver_mobile'] = fixeddecrypt($value['receiver_mobile']);
                isset($value['receiver_address']) and $list[$key]['receiver_address'] = fixeddecrypt($value['receiver_address']);
            }
        }
        return $list;
    }

    public function getListJoinItems($filter = [], $offset = 0, $limit = -1, $orderby = null, $col = '*')
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()
            ->select($col)
            ->from($this->table, 'o')
            ->leftJoin('o', 'orders_normal_orders_items', 'oi', 'o.order_id = oi.order_id');

        if (!empty($filter['prescription_order_filter'])) {
            $qb = $qb->leftJoin('o', 'orders_prescription', 'op', 'o.order_id = op.order_id');
            foreach ($filter['prescription_order_filter'] as $k => $v) {
                $filter['op.' . $k] = $v;
            }
            unset($filter['prescription_order_filter']);

            foreach ($filter as $k => $v) {
                if (in_array($k, ['company_id'])) {
                    $filter['o.' . $k] = $v;
                    unset($filter[$k]);
                }
            }
        }

        if ($filter['supplier_id']) {
            $qb = $qb->leftJoin('o', 'supplier_order', 'so', 'o.order_id = so.order_id');
            $filter['so.supplier_id'] = $filter['supplier_id'];
            unset($filter['supplier_id']);
        }

        if ($orderby) {
            foreach ($orderby as $columns => $value) {
                $qb->orderBy('o.'.$columns, $value);
            }
        } else {
            $qb->orderBy('o.create_time', 'DESC');
        }

        if ($limit > 0) {
            $qb = $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $qb = $this->_filter($filter, $qb);
        return $qb->execute()->fetchAll();
    }

    public function count($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->table, 'o');

        if (!empty($filter['prescription_order_filter'])) {
            $qb = $qb->leftJoin('o', 'orders_prescription', 'op', 'o.order_id = op.order_id');
            foreach ($filter['prescription_order_filter'] as $k => $v) {
                $filter['op.' . $k] = $v;
            }
            unset($filter['prescription_order_filter']);

            foreach ($filter as $k => $v) {
                if (in_array($k, ['company_id'])) {
                    $filter['o.' . $k] = $v;
                    unset($filter[$k]);
                }
            }
        }

        $qb = $this->_filter($filter, $qb);
        return $qb->execute()->fetchColumn();
    }

    public function countJoinItems($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from($this->table, 'o')
            ->leftJoin('o', 'orders_normal_orders_items', 'oi', 'o.order_id = oi.order_id');

        if (!empty($filter['prescription_order_filter'])) {
            $qb = $qb->leftJoin('o', 'orders_prescription', 'op', 'o.order_id = op.order_id');
            foreach ($filter['prescription_order_filter'] as $k => $v) {
                $filter['op.' . $k] = $v;
            }
            unset($filter['prescription_order_filter']);

            foreach ($filter as $k => $v) {
                if (in_array($k, ['company_id'])) {
                    $filter['o.' . $k] = $v;
                    unset($filter[$k]);
                }
            }
        }

        $qb = $this->_filter($filter, $qb);
        return $qb->execute()->fetchColumn();
    }


    private function _filter($filter, $qb)
    {
        if ($filter) {
            $fixedencryptCol = ['mobile', 'receiver_name', 'receiver_mobile', 'receiver_address'];
            foreach ($fixedencryptCol as $col) {
                if (isset($filter[$col])) {
                    $filter[$col] = fixedencrypt($filter[$col]);
                }
            }

            if (isset($filter['delivery_status'], $filter['ziti_status'])) {
                $filterValue = $qb->expr()->literal($filter['delivery_status']);
                $qb->andWhere($qb->expr()->andX(
                    $qb->expr()->eq('delivery_status', $filterValue)
                ));
                $filterValue = $qb->expr()->literal($filter['ziti_status']);
                $qb->orWhere($qb->expr()->andX(
                    $qb->expr()->eq('ziti_status', $filterValue)
                ));
                unset($filter['delivery_status'], $filter['ziti_status']);
            }

            // 待支付订单，需要加上线下转账支付方式的判断
            // 线下转账支付方式，转账凭证待审核时，不使用auto_cancel_time
            // 其他情况，使用auto_cancel_time
            if (isset($filter['order_status']) && $filter['order_status'] == 'NOTPAY') {
                $condition = $qb->expr()->orX(
                   $qb->expr()->andX(
                       $qb->expr()->eq('pay_type', $qb->expr()->literal('offline_pay')),
                       $qb->expr()->eq('offline_payment_status', 0)
                   ),
                   $qb->expr()->andX(
                       $qb->expr()->orX(
                           $qb->expr()->neq('pay_type', $qb->expr()->literal('offline_pay')),
                           $qb->expr()->eq('offline_payment_status', -1)
                       ),
                       $qb->expr()->gt('auto_cancel_time', time())
                   )
               );
                $qb->andWhere($condition);
            }

            foreach ($filter as $key => $filterValue) {
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
        return $qb;
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

        return $this->getServiceOrderData($entity);
    }


    public function get($companyId, $orderId)
    {
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId
        ];

        return $this->findOneBy($filter);
    }

    private function setNormalOrderData($orderEntity, $data)
    {
        /**
         * @var \OrdersBundle\Entities\NormalOrders $orderEntity
         */
        if (isset($data['order_id'])) {
            $orderEntity->setOrderId($data['order_id']);
        }
        if (isset($data['title'])) {
            $orderEntity->setTitle($data['title']);
        }
        if (isset($data['company_id'])) {
            $orderEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['user_id'])) {
            $orderEntity->setUserId($data['user_id']);
        }
        if (isset($data['mobile'])) {
            $orderEntity->setMobile($data['mobile']);
        }
        if (isset($data['freight_fee'])) {
            $orderEntity->setFreightFee($data['freight_fee']);
        }
        if (isset($data['freight_point'])) {
            $orderEntity->setFreightPoint($data['freight_point']);
        }
        if (isset($data['freight_point_fee'])) {
            $orderEntity->setFreightPointFee($data['freight_point_fee']);
        }
        if (isset($data['order_holder'])) {
            $orderEntity->setOrderHolder($data['order_holder']);
        }
        if (isset($data['freight_type'])) {
            $orderEntity->setFreightType($data['freight_type']);
        }
        if (isset($data['item_fee'])) {
            $orderEntity->setItemFee($data['item_fee']);
        }
        if (isset($data['cost_fee'])) {
            $orderEntity->setCostFee($data['cost_fee']);
        }
        if (isset($data['total_fee'])) {
            $orderEntity->setTotalFee($data['total_fee']);
        }
        if (isset($data['market_fee'])) {
            $orderEntity->setMarketFee($data['market_fee']);
        }
        if (isset($data['step_paid_fee'])) {
            $orderEntity->setStepPaidFee($data['step_paid_fee']);
        }
        if (isset($data['total_rebate'])) {
            $orderEntity->setTotalRebate($data['total_rebate']);
        }
        if (isset($data['distributor_id'])) {
            $orderEntity->setDistributorId($data['distributor_id']);
        }

        if (isset($data['receipt_type'])) {
            $orderEntity->setReceiptType($data['receipt_type']);
        }
        if (isset($data['ziti_code'])) {
            $orderEntity->setZitiCode($data['ziti_code']);
        }
        if (isset($data['act_id'])) {
            $orderEntity->setActId($data['act_id']);
        }
        if (isset($data['ziti_status'])) {
            $orderEntity->setZitiStatus($data['ziti_status']);
        }

        if (isset($data['shop_id'])) {
            $orderEntity->setShopId($data['shop_id']);
        }

        if (isset($data['order_status'])) {
            $orderEntity->setOrderStatus($data['order_status']);
        }
        if (isset($data['order_source'])) {
            $orderEntity->setOrderSource($data['order_source']);
        }
        if (isset($data['order_type'])) {
            $orderEntity->setOrderType($data['order_type']);
        }
        if (isset($data['order_class'])) {
            $orderEntity->setOrderClass($data['order_class']);
        }
        if (isset($data['auto_cancel_time'])) {
            $orderEntity->setAutoCancelTime($data['auto_cancel_time']);
        }
        if (isset($data['auto_finish_time'])) {
            $orderEntity->setAutoFinishTime($data['auto_finish_time']);
        }

        if (isset($data['is_distribution'])) {
            $orderEntity->setIsDistribution($data['is_distribution']);
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
        if (isset($data['delivery_corp'])) {
            $orderEntity->setDeliveryCorp($data['delivery_corp']);
        }
        if (isset($data['delivery_corp_source'])) {
            $orderEntity->setDeliveryCorpSource($data['delivery_corp_source']);
        }
        if (isset($data['delivery_code'])) {
            $orderEntity->setDeliveryCode($data['delivery_code']);
        }
        if (isset($data['delivery_img'])) {
            $orderEntity->setDeliveryImg($data['delivery_img']);
        }
        if (isset($data['delivery_status'])) {
            $orderEntity->setDeliveryStatus($data['delivery_status']);
        }
        if (isset($data['cancel_status'])) {
            $orderEntity->setCancelStatus($data['cancel_status']);
        }
        if (isset($data['delivery_time'])) {
            $orderEntity->setDeliveryTime($data['delivery_time']);
        }
        if (isset($data['end_time']) && $data['end_time']) {
            $orderEntity->setEndTime($data['end_time']);
        }
        if (isset($data['receiver_name'])) {
            // 收货人姓名最大长度为50
            $data['receiver_name'] = mb_substr($data['receiver_name'], 0, 50);
            $orderEntity->setReceiverName($data['receiver_name']);
        }
        if (isset($data['receiver_mobile'])) {
            $orderEntity->setReceiverMobile($data['receiver_mobile']);
        }
        if (isset($data['receiver_zip']) && $data['receiver_zip']) {
            $orderEntity->setReceiverZip($data['receiver_zip']);
        }
        if (isset($data['receiver_state']) && $data['receiver_state']) {
            $orderEntity->setReceiverState($data['receiver_state']);
        }
        if (isset($data['receiver_city']) && $data['receiver_city']) {
            $orderEntity->setReceiverCity($data['receiver_city']);
        }
        if (isset($data['receiver_district']) && $data['receiver_district']) {
            $orderEntity->setReceiverDistrict($data['receiver_district']);
        }
        if (isset($data['receiver_address'])) {
            $orderEntity->setReceiverAddress($data['receiver_address']);
        }

        if (isset($data['member_discount'])) {
            $orderEntity->setMemberDiscount($data['member_discount']);
        }
        if (isset($data['coupon_discount'])) {
            $orderEntity->setCouponDiscount($data['coupon_discount']);
        }
        if (isset($data['discount_fee'])) {
            $orderEntity->setDiscountFee($data['discount_fee']);
        }
        if ($data['discount_info'] ?? []) {
            $orderEntity->setDiscountInfo(json_encode($data['discount_info']));
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
        if (isset($data['item_point'])) {
            $orderEntity->setItemPoint($data['item_point']);
        }
        if (isset($data['point'])) {
            $orderEntity->setPoint($data['point']);
        }
        if (isset($data['pay_type'])) {
            $orderEntity->setPayType($data['pay_type']);
        }
        if (isset($data['pay_channel'])) {
            $orderEntity->setPayChannel($data['pay_channel']);
        }

        if (isset($data['is_rate'])) {
            $orderEntity->setIsRate($data['is_rate']);
        }

        if (isset($data['remark'])) {
            $orderEntity->setRemark($data['remark']);
        }
        if (isset($data['third_params'])) {
            $orderEntity->setThirdParams($data['third_params']);
        }
        if (isset($data['invoice'])) {
            $orderEntity->setInvoice($data['invoice']);
        }
        if (isset($data['send_point'])) {
            $orderEntity->setSendPoint($data['send_point']);
        }
        if (isset($data['is_invoiced'])) {
            $orderEntity->setIsInvoiced($data['is_invoiced']);
        }
        if (isset($data['invoice_number'])) {
            $orderEntity->setInvoiceNumber($data['invoice_number']);
        }
        if (isset($data['type'])) {
            $orderEntity->setType($data['type']);
        }
        if (isset($data['identity_id'])) {
            $orderEntity->setIdentityId($data['identity_id']);
        }
        if (isset($data['taxable_fee'])) {
            $orderEntity->setTaxableFee($data['taxable_fee']);
        }
        if (isset($data['identity_name'])) {
            $orderEntity->setIdentityName($data['identity_name']);
        }
        if (isset($data['total_tax'])) {
            $orderEntity->setTotalTax($data['total_tax']);
        }
        if (isset($data['audit_status'])) {
            $orderEntity->setAuditStatus($data['audit_status']);
        }
        if (isset($data['audit_msg'])) {
            $orderEntity->setAuditMsg($data['audit_msg']);
        }
        if (isset($data['point_fee'])) {
            $orderEntity->setPointFee($data['point_fee']);
        }
        if (isset($data['point_use'])) {
            $orderEntity->setPointUse($data['point_use']);
        }
        if (isset($data['uppoint_use'])) {
            $orderEntity->setUppointUse($data['uppoint_use']);
        }
        if (isset($data['point_up_use'])) {
            $orderEntity->setPointUpUse($data['point_up_use']);
        }
        if (isset($data['pay_status'])) {
            $orderEntity->setPayStatus($data['pay_status']);
        }
        if (isset($data['get_points'])) {
            $orderEntity->setGetPoints($data['get_points']);
        }
        if (isset($data['bonus_points'])) {
            $orderEntity->setBonusPoints($data['bonus_points']);
        }
        if (isset($data['get_point_type'])) {
            $orderEntity->setGetPointType($data['get_point_type']);
        }
        if (isset($data['pack'])) {
            $orderEntity->setPack($data['pack']);
        }
        if (isset($data['is_shopscreen'])) {
            $orderEntity->setIsShopScreen($data['is_shopscreen']);
        }
        if (isset($data['is_logistics'])) {
            $orderEntity->setIsLogistics($data['is_logistics']);
        }
        if (isset($data['is_profitsharing'])) {
            $orderEntity->setIsProfitsharing($data['is_profitsharing']);
        }
        if (isset($data['profitsharing_status'])) {
            $orderEntity->setProfitsharingStatus($data['profitsharing_status']);
        }
        if (isset($data['order_auto_close_aftersales_time'])) {
            $orderEntity->setOrderAutoCloseAftersalesTime($data['order_auto_close_aftersales_time']);
        }
        if (isset($data['profitsharing_rate'])) {
            $orderEntity->setProfitsharingRate($data['profitsharing_rate']);
        }
        if (isset($data['bind_auth_code']) && $data['bind_auth_code']) {
            $orderEntity->setBindAuthCode($data['bind_auth_code']);
        }
        if (isset($data['extra_points'])) {
            $orderEntity->setExtraPoints($data['extra_points']);
        }
        if (isset($data['sale_salesman_distributor_id'])) {
            $orderEntity->setSaleSalesmanDistributorId($data['sale_salesman_distributor_id']);
        }
        if (isset($data['bind_salesman_id'])) {
            $orderEntity->setBindSalesmanId($data['bind_salesman_id']);
        }
        if (isset($data['bind_salesman_distributor_id'])) {
            $orderEntity->setBindSalesmanDistributorId($data['bind_salesman_distributor_id']);
        }
        if (isset($data['chat_id'])) {
            $orderEntity->setChatId($data['chat_id']);
        }
        if (isset($data['is_consumption'])) {
            $orderEntity->setIsConsumption($data['is_consumption']);
        }
        if (isset($data['app_pay_type'])) {
            $orderEntity->setAppPayType($data['app_pay_type']);
        }
        if (isset($data['distribution_remark'])) {
            $orderEntity->setDistributorRemark($data['distribution_remark']);
        }
        if (isset($data['merchant_id'])) {
            $orderEntity->setMerchantId($data['merchant_id']);
        }
        if (isset($data['subdistrict_parent_id'])) {
            $orderEntity->setSubdistrictParentId($data['subdistrict_parent_id']);
        }
        if (isset($data['subdistrict_id'])) {
            $orderEntity->setSubdistrictId($data['subdistrict_id']);
        }
        if (isset($data['building_number'])) {
            $orderEntity->setBuildingNumber($data['building_number']);
        }
        if (isset($data['house_number'])) {
            $orderEntity->setHouseNumber($data['house_number']);
        }
        if (isset($data['operator_id'])) {
            $orderEntity->setOperatorId($data['operator_id']);
        }
        if (isset($data['left_aftersales_num'])) {
            $orderEntity->setLeftAftersalesNum($data['left_aftersales_num']);
        }
        if (isset($data['source_from'])) {
            $orderEntity->setSourceFrom($data['source_from']);
        }
        if (isset($data['self_delivery_status'])) {
            $orderEntity->setSelfDeliveryStatus($data['self_delivery_status']);
        }
        if (isset($data['self_delivery_operator_id'])) {
            $orderEntity->setSelfDeliveryOperatorId($data['self_delivery_operator_id']);
        }
        if (isset($data['self_delivery_fee'])) {
            $orderEntity->setSelfDeliveryFee($data['self_delivery_fee']);
        }
        if (isset($data['self_delivery_time'])) {
            $orderEntity->setSelfDeliveryTime($data['self_delivery_time']);
        }
        if (isset($data['supplier_id'])) {
            $orderEntity->setSupplierId($data['supplier_id']);
        }
        if (isset($data['commission_fee'])) {
            $orderEntity->setCommissionFee($data['commission_fee']);
        }
        if (isset($data['self_delivery_end_time'])) {
            $orderEntity->setSelfDeliveryEndTime($data['self_delivery_end_time']);
        }
        if (isset($data['offline_payment_status'])) {
            $orderEntity->setOfflinePaymentStatus($data['offline_payment_status']);
        }
        if (isset($data['prescription_status'])) {
            $orderEntity->setPrescriptionStatus($data['prescription_status']);
        }
        if (isset($data['invoice_status'])) {
            $orderEntity->setInvoiceStatus($data['invoice_status']);
        }
        if (isset($data['dm_point_preid'])) {
            $orderEntity->setDmPointPreid($data['dm_point_preid']);
        }
        return $orderEntity;
    }

    public function getServiceOrderData($orderEntity)
    {
        /**
         * @var \OrdersBundle\Entities\NormalOrders $orderEntity
         */
        $result = [
            'order_id' => $orderEntity->getOrderId(),
            'title' => $orderEntity->getTitle(),
            'company_id' => $orderEntity->getCompanyId(),
            'user_id' => $orderEntity->getUserId(),
            'act_id' => $orderEntity->getActId(),
            'mobile' => $orderEntity->getMobile(),
            'commission_fee' => $orderEntity->getCommissionFee(),
            'freight_fee' => $orderEntity->getFreightFee(),
            'freight_point' => $orderEntity->getFreightPoint(),
            'freight_point_fee' => $orderEntity->getFreightPointFee(),
            'freight_type' => $orderEntity->getFreightType(),
            'item_fee' => $orderEntity->getItemFee(),
            'item_point' => $orderEntity->getItemPoint(),
            'cost_fee' => $orderEntity->getCostFee(),
            'total_fee' => $orderEntity->getTotalFee(),
            'market_fee' => $orderEntity->getMarketFee(),
            'step_paid_fee' => $orderEntity->getStepPaidFee(),
            'total_rebate' => $orderEntity->getTotalRebate(),
            'distributor_id' => $orderEntity->getDistributorId(),
            'order_holder' => $orderEntity->getOrderHolder(),
            'receipt_type' => $orderEntity->getReceiptType(),
            'ziti_code' => $orderEntity->getZitiCode(),
            'shop_id' => $orderEntity->getShopId(),
            'ziti_status' => $orderEntity->getZitiStatus(),
            'order_status' => $orderEntity->getOrderStatus(),
            'order_source' => $orderEntity->getOrderSource(),
            'order_type' => $orderEntity->getOrderType(),
            'order_class' => $orderEntity->getOrderClass(),
            'auto_cancel_time' => $orderEntity->getAutoCancelTime(),
            'auto_cancel_seconds' => $orderEntity->getAutoCancelTime() - time(),
            'auto_finish_time' => $orderEntity->getAutoFinishTime(),
            'is_distribution' => $orderEntity->getIsDistribution(),
            'source_id' => $orderEntity->getSourceId(),
            'monitor_id' => $orderEntity->getMonitorId(),
            'salesman_id' => $orderEntity->getSalesmanId(),
            'delivery_corp' => $orderEntity->getDeliveryCorp(),
            'delivery_corp_source' => $orderEntity->getDeliveryCorpSource(),
            'delivery_code' => $orderEntity->getDeliveryCode(),
            'delivery_img' => $orderEntity->getDeliveryImg(),
            'delivery_status' => $orderEntity->getDeliveryStatus(),
            'cancel_status' => $orderEntity->getCancelStatus(),
            'delivery_time' => $orderEntity->getDeliveryTime(),
            'end_time' => $orderEntity->getEndTime(),
            'end_date' => $orderEntity->getEndTime() ? date('Y-m-d H:i:s', $orderEntity->getEndTime()) : '',
            'receiver_name' => $orderEntity->getReceiverName(),
            'receiver_mobile' => $orderEntity->getReceiverMobile(),
            'receiver_zip' => $orderEntity->getReceiverZip(),
            'receiver_state' => $orderEntity->getReceiverState(),
            'receiver_city' => $orderEntity->getReceiverCity(),
            'receiver_district' => $orderEntity->getReceiverDistrict(),
            'receiver_address' => $orderEntity->getReceiverAddress(),
            'member_discount' => $orderEntity->getMemberDiscount() ?? 0,
            'coupon_discount' => $orderEntity->getCouponDiscount() ?? 0,
            'discount_fee' => $orderEntity->getDiscountFee(),
            'create_time' => $orderEntity->getCreateTime(),
            'update_time' => $orderEntity->getUpdateTime(),
            'fee_type' => $orderEntity->getFeeType(),
            'fee_rate' => $orderEntity->getFeeRate(),
            'fee_symbol' => $orderEntity->getFeeSymbol(),
            'cny_fee' => round(round(floatval($orderEntity->getFeeRate()), 4) * $orderEntity->getTotalFee()),
            'point' => $orderEntity->getPoint(),
            'pay_type' => $orderEntity->getPayType(),
            'pay_channel' => $orderEntity->getPayChannel(),
            'remark' => $orderEntity->getRemark(),
            'distributor_remark' => $orderEntity->getDistributorRemark(),
            'third_params' => $orderEntity->getThirdParams(),
            'invoice' => $orderEntity->getInvoice() ?: null,
            'send_point' => $orderEntity->getSendPoint(),
            'is_rate' => $orderEntity->getIsRate(),
            'is_invoiced' => $orderEntity->getIsInvoiced(),
            'invoice_number' => $orderEntity->getInvoiceNumber(),
            'audit_status' => $orderEntity->getAuditStatus(),
            'audit_msg' => $orderEntity->getAuditMsg(),
            'point_fee' => $orderEntity->getPointFee(),
            'point_use' => $orderEntity->getPointUse(),
            'uppoint_use' => $orderEntity->getUppointUse(),
            'point_up_use' => $orderEntity->getPointUpUse(),
            'pay_status' => $orderEntity->getPayStatus(),
            'get_points' => $orderEntity->getGetPoints(),
            'bonus_points' => $orderEntity->getBonusPoints(),
            'get_point_type' => $orderEntity->getGetPointType(),
            'pack' => $orderEntity->getPack(),
            'is_shopscreen' => $orderEntity->getIsShopScreen(),
            'is_logistics' => $orderEntity->getIsLogistics(),
            'is_profitsharing' => $orderEntity->getIsProfitsharing(),
            'profitsharing_status' => $orderEntity->getProfitsharingStatus(),
            'order_auto_close_aftersales_time' => $orderEntity->getOrderAutoCloseAftersalesTime(),
            'profitsharing_rate' => $orderEntity->getProfitsharingRate(),
            'bind_auth_code' => $orderEntity->getBindAuthCode(),
            'extra_points' => $orderEntity->getExtraPoints(),
            'sale_salesman_distributor_id' => $orderEntity->getSaleSalesmanDistributorId(),
            'bind_salesman_id' => $orderEntity->getBindSalesmanId(),
            'bind_salesman_distributor_id' => $orderEntity->getBindSalesmanDistributorId(),
            'chat_id' => $orderEntity->getChatId(),
            'is_consumption' => $orderEntity->getIsConsumption(),
            'app_pay_type' => $orderEntity->getAppPayType(),
            'app_pay_type_desc' => config('order.appPayType')[$orderEntity->getAppPayType()] ?? '微信小程序',
            'merchant_id' => $orderEntity->getMerchantId(),
            'subdistrict_parent_id' => $orderEntity->getSubdistrictParentId(),
            'subdistrict_id' => $orderEntity->getSubdistrictId(),
            'building_number' => $orderEntity->getBuildingNumber(),
            'house_number' => $orderEntity->getHouseNumber(),
            'operator_id' => $orderEntity->getOperatorId(),
            'left_aftersales_num' => $orderEntity->getLeftAftersalesNum(),
            'source_from' => $orderEntity->getSourceFrom(),
            'self_delivery_status' => $orderEntity->getSelfDeliveryStatus(),
            'self_delivery_operator_id' => $orderEntity->getSelfDeliveryOperatorId(),
            'self_delivery_fee' => $orderEntity->getSelfDeliveryFee(),
            'self_delivery_time' => $orderEntity->getSelfDeliveryTime(),
            'supplier_id' => $orderEntity->getSupplierId(),
            'self_delivery_end_time' => $orderEntity->getSelfDeliveryEndTime(),
            'offline_payment_status' => $orderEntity->getOfflinePaymentStatus(),
            'prescription_status' => $orderEntity->getPrescriptionStatus(),
            'invoice_status' => $orderEntity->getInvoiceStatus(),
            'dm_point_preid' => $orderEntity->getDmPointPreid(),
        ];

        // 跨境订单
//        if($orderEntity->getType() == 1){
        $result['type'] = $orderEntity->getType();
        $result['taxable_fee'] = $orderEntity->getTaxableFee();
        $result['identity_id'] = $orderEntity->getIdentityId();
        $result['identity_name'] = $orderEntity->getIdentityName();
        $result['total_tax'] = $orderEntity->getTotalTax();
//        }

        if (!$result['discount_fee']) {
            $result['discount_fee'] = $result['member_discount'] + $result['coupon_discount'];
        }

        $result['discount_info'] = [] ;
        if ($orderEntity->getDiscountInfo()) {
            $discountInfoData = json_decode($orderEntity->getDiscountInfo(), true);
            foreach ($discountInfoData as $discountInfo) {
                if ($discountInfo) {
                    $result['discount_info'][] = $discountInfo;
                }
            }
        } else {
            if ($orderEntity->getCouponDiscountDesc()) {
                $coupon_discount_desc = json_decode($orderEntity->getCouponDiscountDesc(), true);
                $coupon_discount_desc['type'] = 'coupon_discount';
                $result['discount_info'][] = $coupon_discount_desc;
            }
            if ($orderEntity->getMemberDiscountDesc()) {
                $member_discount_desc = json_decode($orderEntity->getMemberDiscountDesc(), true);
                $member_discount_desc['type'] = 'member_discount';
                $result['discount_info'][] = $member_discount_desc;
            }
        }
        return $result;
    }

    /**
     * 更新数据表字段数据
     *
     * @param array $filter 更新的条件
     * @param array $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setNormalOrderData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getServiceOrderData($entity);
    }

    /**
     * 数量求和
     */

    public function sum($filter, $field)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(' . $field . ')')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $sum = $qb->execute()->fetchColumn();
        return intval($sum);
    }

    /**
     * 退款统计
     *
     * @param array $filter
     * @param string $field
     * @return void
     */
    public function refundSum($filter, $field)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()
            ->select('sum(' . $field . ')')
            ->from($this->table, 'ono')
            ->leftJoin('ono', 'aftersales_refund', 'ar', 'ono.order_id = ar.order_id');
        $qb = $this->_filter($filter, $qb);
        $sum = $qb->execute()->fetchColumn();
        return intval($sum);
    }

    /**
     * 退款数量
     *
     * @param array $filter
     * @param string $field
     * @return void
     */
    public function refundCount($filter, $field)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()
            ->select('count(' . $field . ')')
            ->from($this->table, 'ono')
            ->leftJoin('ono', 'aftersales_refund', 'ar', 'ono.order_id = ar.order_id');
        $qb = $this->_filter($filter, $qb);
        $sum = $qb->execute()->fetchColumn();
        return intval($sum);
    }

    /**
     * 获取用户消费金额
     *
     * @param array $filter
     * @return array
     */
    public function getTotalAmountByUserId($filter = [])
    {
        // $cols = 'user_id, sum(total_fee) as fee';

        $cols = "user_id, sum(total_fee) as fee,count(1) as count_all"
        .",SUM(IF(order_status NOT IN ('CANCEL', 'NOTPAY'), total_fee, NULL)) as fee_nocancelpayed"
        .", COUNT(IF(order_status NOT IN ('CANCEL', 'NOTPAY'), 1, NULL)) as count_nocancelpayed";
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        $qb->groupBy('user_id');
        $lists = $qb->execute()->fetchAll();
        $rest = [];
        if ($lists ?? '') {
            foreach ($lists as $list) {
                $rest[$list['user_id']] = $list['fee'];
            }
        }
        return $rest;
    }

    /**
     * 获取总的销售数量
     * @param array $orderFilter 订单的过滤条件
     * @param array $orderItemFilter 订单商品的过滤条件
     * @return array
     */
    public function getTotalSalesCountByDistributorIds(array $orderFilter = [], array $orderItemFilter = []): array
    {
        $normalOrdersItemsTable = "orders_normal_orders_items";

        // 为订单过滤条件添加别名
        foreach ($orderFilter as $key => $value) {
            $orderFilter[sprintf("%s.%s", $this->table, $key)] = $value;
            unset($orderFilter[$key]);
        }

        // 为订单商品的过滤条件添加别名
        foreach ($orderItemFilter as $key => $value) {
            $orderItemFilter[sprintf("%s.%s", $normalOrdersItemsTable, $key)] = $value;
            unset($orderItemFilter[$key]);
        }

        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()
            ->select(sprintf("%s.distributor_id, SUM(%s.num) as sales_count", $this->table, $normalOrdersItemsTable))
            ->from($this->table)
            ->leftJoin(
                $this->table,
                $normalOrdersItemsTable,
                $normalOrdersItemsTable,
                sprintf("%s.order_id = %s.order_id", $this->table, $normalOrdersItemsTable)
            )
            ->groupBy(sprintf("%s.distributor_id", $this->table));

        $qb = $this->_filter($orderFilter, $qb);
        $qb = $this->_filter($orderItemFilter, $qb);

        return $qb->execute()->fetchAll();
    }

    public function getDeliveryStaffDataCountByOperatorIds(array $orderFilter = [])
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()
            ->select("self_delivery_operator_id, COUNT(DISTINCT user_id) as user_count, COUNT(order_id) as order_count, SUM(total_fee)as total_fee_count, SUM(self_delivery_fee) as self_delivery_fee_count")
            ->from($this->table)
            ->groupBy('self_delivery_operator_id');

        $qb = $this->_filter($orderFilter, $qb);
        return $qb->execute()->fetchAll();
    }
}
