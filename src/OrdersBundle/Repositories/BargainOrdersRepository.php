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
use OrdersBundle\Entities\BargainOrders;

use Doctrine\Common\Collections\Criteria;

use Dingo\Api\Exception\UpdateResourceFailedException;

class BargainOrdersRepository extends EntityRepository
{
    public $table = 'orders_bargain';

    public function create($params)
    {
        $ordersEntity = new BargainOrders();
        $bargainOrder = $this->setBargainOrderData($ordersEntity, $params);

        $em = $this->getEntityManager();
        $em->persist($bargainOrder);
        $em->flush();

        $result = $this->getBargainOrderData($bargainOrder);

        return $result;
    }

    public function update($filter, $updateInfo)
    {
        $order = $this->findOneBy($filter);
        if (!$order) {
            throw new UpdateResourceFailedException("订单不存在");
        }
        $bargainOrder = $this->setBargainOrderData($order, $updateInfo);
        $em = $this->getEntityManager();
        $em->persist($bargainOrder);
        $em->flush();

        $result = $this->getBargainOrderData($bargainOrder);

        return $result;
    }

    public function get($filter)
    {
        $order = $this->findOneBy($filter);

        $result = [];
        if ($order) {
            $result = $this->getBargainOrderData($order);
        }

        return $result;
    }

    public function getList($filter, $offset = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'])
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                if ($value) {
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
                $order = $this->getBargainOrderData($v);
                $prderList[] = $order;
            }
        }
        $res['list'] = $prderList;

        return $res;
    }

    private function setBargainOrderData($orderEntity, $data)
    {
        if (isset($data['order_id']) && $data['order_id']) {
            $orderEntity->setOrderId($data['order_id']);
        }
        if (isset($data['title']) && $data['title']) {
            $orderEntity->setTitle($data['title']);
        }
        if (isset($data['company_id']) && $data['company_id']) {
            $orderEntity->setCompanyId($data['company_id']);
        }
        if (isset($data['user_id']) && $data['user_id']) {
            $orderEntity->setUserId($data['user_id']);
        }
        if (isset($data['bargain_id']) && $data['bargain_id']) {
            $orderEntity->setBargainId($data['bargain_id']);
        }
        if (isset($data['item_name']) && $data['item_name']) {
            $orderEntity->setItemName($data['item_name']);
        }
        if (isset($data['item_price']) && $data['item_price']) {
            $orderEntity->setItemPrice($data['item_price']);
        }
        if (isset($data['item_pics']) && $data['item_pics']) {
            $orderEntity->setItemPics($data['item_pics']);
        }
        if (isset($data['item_num']) && $data['item_num']) {
            $orderEntity->setItemNum($data['item_num']);
        }

        $data['templates_id'] = isset($data['templates_id']) ? $data['templates_id'] : 0;
        $orderEntity->setTemplatesId($data['templates_id']);

        if (isset($data['mobile']) && $data['mobile']) {
            $orderEntity->setMobile($data['mobile']);
        }
        if (isset($data['freight_fee']) && $data['freight_fee']) {
            $orderEntity->setFreightFee($data['freight_fee']);
        }

        $data['item_fee'] = isset($data['item_fee']) ? $data['item_fee'] : 0;
        $orderEntity->setItemFee($data['item_fee']);

        if (isset($data['total_fee']) && $data['total_fee']) {
            $orderEntity->setTotalFee($data['total_fee']);
        }
        if (isset($data['order_status']) && $data['order_status']) {
            $orderEntity->setOrderStatus($data['order_status']);
        }
        if (isset($data['order_type']) && $data['order_type']) {
            $orderEntity->setOrderType($data['order_type']);
        }
        if (isset($data['order_source']) && $data['order_source']) {
            $orderEntity->setOrderSource($data['order_source']);
        }
        if (isset($data['receiver_name']) && $data['receiver_name']) {
            $orderEntity->setReceiverName($data['receiver_name']);
        }
        if (isset($data['receiver_mobile']) && $data['receiver_mobile']) {
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
        if (isset($data['receiver_address']) && $data['receiver_address']) {
            $orderEntity->setReceiverAddress($data['receiver_address']);
        }
        if (isset($data['auto_cancel_time']) && $data['auto_cancel_time']) {
            $orderEntity->setAutoCancelTime($data['auto_cancel_time']);
        }
        if (isset($data['source_id'])) {
            $orderEntity->setSourceId($data['source_id']);
        }
        if (isset($data['monitor_id'])) {
            $orderEntity->setMonitorId($data['monitor_id']);
        }
        if (isset($data['remark'])) {
            $orderEntity->setRemark($data['remark']);
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
        if (isset($data['third_params'])) {
            $orderEntity->setThirdParams($data['third_params']);
        }
        return $orderEntity;
    }

    public function getBargainOrderData($orderEntity)
    {
        $result = [
            'order_id' => $orderEntity->getOrderId(),
            'title' => $orderEntity->getTitle(),
            'company_id' => $orderEntity->getCompanyId(),
            'user_id' => $orderEntity->getUserId(),
            'bargain_id' => $orderEntity->getBargainId(),
            'item_name' => $orderEntity->getItemName(),
            'item_price' => $orderEntity->getItemPrice(),
            'item_pics' => $orderEntity->getItemPics(),
            'item_num' => $orderEntity->getItemNum(),
            'templates_id' => $orderEntity->getTemplatesId(),
            'mobile' => $orderEntity->getMobile(),
            'item_fee' => $orderEntity->getItemFee(),
            'total_fee' => $orderEntity->getTotalFee(),
            'freight_fee' => $orderEntity->getFreightFee(),
            'order_status' => $orderEntity->getOrderStatus(),
            'order_type' => $orderEntity->getOrderType(),
            'order_source' => $orderEntity->getOrderSource(),
            'receiver_name' => $orderEntity->getReceiverName(),
            'receiver_mobile' => $orderEntity->getReceiverMobile(),
            'receiver_zip' => $orderEntity->getReceiverZip(),
            'receiver_state' => $orderEntity->getReceiverState(),
            'receiver_city' => $orderEntity->getReceiverCity(),
            'receiver_district' => $orderEntity->getReceiverDistrict(),
            'receiver_address' => $orderEntity->getReceiverAddress(),
            'create_time' => $orderEntity->getCreateTime(),
            'update_time' => $orderEntity->getUpdateTime(),
            'auto_cancel_time' => $orderEntity->getAutoCancelTime(),
            'source_id' => $orderEntity->getSourceId(),
            'monitor_id' => $orderEntity->getMonitorId(),
            'remark' => $orderEntity->getRemark(),
            'member_discount' => $orderEntity->getMemberDiscount(),
            'coupon_discount' => $orderEntity->getCouponDiscount(),
            'coupon_discount_desc' => [],
            'member_discount_desc' => [],
            'fee_type' => $orderEntity->getFeeType(),
            'fee_rate' => $orderEntity->getFeeRate(),
            'fee_symbol' => $orderEntity->getFeeSymbol(),
            'third_params' => $orderEntity->getThirdParams(),
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
