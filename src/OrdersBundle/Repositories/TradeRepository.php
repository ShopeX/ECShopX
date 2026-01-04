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

use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Entities\Trade;

class TradeRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'trade';

    public function create(array $data)
    {
        $data['time_start'] = time();
        $data['trade_state'] = 'NOTPAY';
        $data['fee_type'] = isset($data['fee_type']) ?: 'CNY';
        // 如果为微信支付
        if ($data['pay_type'] == 'wxpay') {
            if (!$data['mch_id'] || !$data['open_id']) {
                throw new BadRequestHttpException('创建交易单失败，请检查参数');
            }
        }

        $entity = new Trade();
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
        // Hash: 0d723eca
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
        // Hash: 0d723eca
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
    public function lists($filter, $orderBy = [], $pageSize = 100, $page = 1)
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
            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    /**
     * 更新支付状态
     */
    public function updateStatus($tradeId, $status = null, $options = array())
    {
        $entity = $this->find($tradeId);
        if (!$entity) {
            throw new \Exception('更新订单不存在');
        }
        if ($entity->getTradeState() != 'NOTPAY') {
            throw new BadRequestHttpException('更新已处理，不需要更新');
        }

        $updateData = [
            'trade_state' => $status,
            'bank_type' => isset($options['bank_type']) ? $options['bank_type'] : null,
            'pay_type' => isset($options['pay_type']) ? $options['pay_type'] : null,
            // 'time_expire'    => time(),
            'coupon_fee' => isset($options['coupon_fee']) ? $options['coupon_fee'] : 0,
            'coupon_info' => isset($options['coupon_info']) ? json_encode($options['coupon_info']) : null
        ];
        if ($status == 'SUCCESS') {
            $updateData['time_expire'] = time();
            $companyId = $entity->getCompanyId();
            $distributorId = $entity->getDistributorId();
            $orderId = $entity->getOrderId();
            // 增加订单序号
            $tradeNo = (new TradeService())->getTodayTradeNo($companyId, $distributorId, $orderId);
            $updateData['trade_no'] = date('md').'-'.$tradeNo;
        }

        if (isset($options['transaction_id'])) {
            $updateData['transaction_id'] = $options['transaction_id'];
        }
        if (isset($options['pay_channel'])) {
            $updateData['pay_channel'] = $options['pay_channel'];
        }
        $entity = $this->setColumnNamesData($entity, $updateData);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        return $entity;
    }

    /**
     * 获取交易列表
     */
    public function getTradeList($filter, $orderBy = ['time_start' => 'DESC'], $pageSize = 20, $page = 1)
    {
        $fixedencryptCol = ['mobile'];
        foreach ($fixedencryptCol as $col) {
            if (isset($filter[$col])) {
                $filter[$col] = fixedencrypt($filter[$col]);
            }
        }
        if (isset($filter['order_id'])) {
            $orderBy = ['time_expire' => 'DESC', 'time_start' => 'DESC'];
        }
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
            } elseif ($field == 'time_start_begin') {
                $criteria = $criteria->andWhere(Criteria::expr()->gte("time_start", $value));
            } elseif ($field == 'time_start_end') {
                $criteria = $criteria->andWhere(Criteria::expr()->lte("time_start", $value));
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
        $res['total_count'] = intval($total);
        $data = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $list = $this->matching($criteria);
            foreach ($list as $v) {
                $value = normalize($v);
                $value['discountInfo'] = json_decode($value['discountInfo'], true);
                $value['couponInfo'] = json_decode($value['couponInfo'], true);
                $value['payDate'] = $value['timeExpire'] ? date('Y-m-d H:i:s', $value['timeExpire']) : '';
                $data[] = $value;
            }
        }
        $res['list'] = $data;
        return $res;
    }

    public function getTradeCount($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
            } elseif ($field == 'time_start_begin') {
                $criteria = $criteria->andWhere(Criteria::expr()->gte("time_start", $value));
            } elseif ($field == 'time_start_end') {
                $criteria = $criteria->andWhere(Criteria::expr()->lte("time_start", $value));
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
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        /**
         * @var \OrdersBundle\Entities\Trade  $entity
         */
        if (isset($data["trade_id"]) && $data["trade_id"]) {
            $entity->setTradeId($data["trade_id"]);
        }
        //当前字段非必填
        if (isset($data["order_id"]) && $data["order_id"]) {
            $entity->setOrderId($data["order_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["supplier_id"]) && $data["supplier_id"]) {
            $entity->setSupplierId($data["supplier_id"]);
        }
        //当前字段非必填
        if (isset($data["shop_id"]) && $data["shop_id"]) {
            $entity->setShopId($data["shop_id"]);
        }
        if (isset($data['distributor_id'])) {
            $entity->setDistributorId($data['distributor_id']);
        }

        if (isset($data["user_id"])) {
            $entity->setUserId($data["user_id"]);
        }
        //当前字段非必填
        if (isset($data["mobile"]) && $data["mobile"]) {
            $entity->setMobile($data["mobile"]);
        }
        //当前字段非必填
        if (isset($data["open_id"]) && $data["open_id"]) {
            $entity->setOpenId($data["open_id"]);
        }
        //当前字段非必填
        if (isset($data["discount_info"]) && $data["discount_info"]) {
            $entity->setDiscountInfo($data["discount_info"]);
        }
        //当前字段非必填
        if (isset($data["mch_id"]) && $data["mch_id"]) {
            $entity->setMchId($data["mch_id"]);
        }
        if (isset($data["total_fee"]) && $data["total_fee"]) {
            $entity->setTotalFee($data["total_fee"]);
        }
        //当前字段非必填
        if (isset($data["discount_fee"]) && $data["discount_fee"]) {
            $entity->setDiscountFee($data["discount_fee"]);
        }
        if (isset($data["fee_type"]) && $data["fee_type"]) {
            $entity->setFeeType($data["fee_type"]);
        }
        if (isset($data["pay_fee"]) && $data["pay_fee"]) {
            $entity->setPayFee($data["pay_fee"]);
        }
        if (isset($data['trade_no']) && $data['trade_no']) {
            $entity->setTradeNo($data["trade_no"]);
        }
        if (isset($data["trade_state"]) && $data["trade_state"]) {
            $entity->setTradeState($data["trade_state"]);
        }
        if (isset($data["pay_type"]) && $data["pay_type"]) {
            $entity->setPayType($data["pay_type"]);
        }
        if (isset($data['pay_channel'])) {
            $entity->setPayChannel($data['pay_channel']);
        }
        //当前字段非必填
        if (isset($data["transaction_id"]) && $data["transaction_id"]) {
            $entity->setTransactionId($data["transaction_id"]);
        }
        //当前字段非必填
        if (isset($data["authorizer_appid"]) && $data["authorizer_appid"]) {
            $entity->setAuthorizerAppid($data["authorizer_appid"]);
        }
        //当前字段非必填
        if (isset($data["wxa_appid"]) && $data["wxa_appid"]) {
            $entity->setWxaAppid($data["wxa_appid"]);
        }
        //当前字段非必填
        if (isset($data["bank_type"]) && $data["bank_type"]) {
            $entity->setBankType($data["bank_type"]);
        }
        if (isset($data["body"]) && $data["body"]) {
            $entity->setBody($data["body"]);
        }
        if (isset($data["detail"]) && $data["detail"]) {
            $entity->setDetail($data["detail"]);
        }
        if (isset($data["time_start"]) && $data["time_start"]) {
            $entity->setTimeStart($data["time_start"]);
        }
        //当前字段非必填
        if (isset($data["time_expire"]) && $data["time_expire"]) {
            $entity->setTimeExpire($data["time_expire"]);
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
        if (isset($data["cur_pay_fee"]) && $data["cur_pay_fee"]) {
            $entity->setCurPayFee($data["cur_pay_fee"]);
        }

        //当前字段非必填
        if (isset($data["trade_source_type"]) && $data["trade_source_type"]) {
            $entity->setTradeSourceType($data["trade_source_type"]);
        }

        //当前字段非必填
        if (isset($data["coupon_fee"]) && $data["coupon_fee"]) {
            $entity->setCouponFee($data["coupon_fee"]);
        }

        //当前字段非必填
        if (isset($data["coupon_info"]) && $data["coupon_info"]) {
            $entity->setCouponInfo($data["coupon_info"]);
        }

        if (isset($data["inital_request"]) && $data["inital_request"]) {
            $entity->setInitalRequest($data["inital_request"]);
        }

        if (isset($data["inital_response"]) && $data["inital_response"]) {
            $entity->setInitalResponse($data["inital_response"]);
        }

        if (isset($data["div_members"]) && $data["div_members"]) {
            $entity->setDivMembers($data["div_members"]);
        }

        if (isset($data["refunded_fee"]) && $data["refunded_fee"]) {
            $entity->setRefundedFee($data["refunded_fee"]);
        }

        if (isset($data["adapay_fee_mode"]) && $data["adapay_fee_mode"]) {
            $entity->setAdapayFeeMode($data["adapay_fee_mode"]);
        }

        if (isset($data["adapay_fee"]) && $data["adapay_fee"]) {
            $entity->setAdapayFee($data["adapay_fee"]);
        }

        if (isset($data["adapay_div_status"]) && $data["adapay_div_status"]) {
            $entity->setAdapayDivStatus($data["adapay_div_status"]);
        }

        if (isset($data["bspay_div_status"]) && $data["bspay_div_status"]) {
            $entity->setBspayDivStatus($data["bspay_div_status"]);
        }

        if (isset($data["dealer_id"]) && $data["dealer_id"]) {
            $entity->setDealerId($data["dealer_id"]);
        }
        if (isset($data['merchant_id'])) {
            $entity->setMerchantId($data['merchant_id']);
        }
        if (isset($data['is_settled'])) {
            $entity->setIsSettled($data['is_settled']);
        }
        if (isset($data['payment_params'])) {
            $entity->setPaymentParams($data['payment_params']);
        }
        if (isset($data['bspay_req_date'])) {
            $entity->setBspayReqDate($data['bspay_req_date']);
        }
        if (isset($data["bspay_div_members"]) && $data["bspay_div_members"]) {
            $entity->setBspayDivMembers($data["bspay_div_members"]);
        }
        if (isset($data["bspay_fee_mode"]) && $data["bspay_fee_mode"]) {
            $entity->setBspayFeeMode($data["bspay_fee_mode"]);
        }
        if (isset($data["bspay_fee"]) && $data["bspay_fee"]) {
            $entity->setBspayFee($data["bspay_fee"]);
        }
        return $entity;
    }

    public function getTradeByOrderIds($filter = [])
    {
        $cols = 'trade_id, order_id, company_id, user_id, total_fee, pay_fee, time_start, time_expire, trade_no';
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $qb = $qb->andWhere($qb->expr()->$k($v, $value));
            } elseif ($field == 'time_start_begin') {
                $qb = $qb->andWhere($qb->expr()->gte("time_start", $value));
            } elseif ($field == 'time_start_end') {
                $qb = $qb->andWhere($qb->expr()->lte("time_start", $value));
            } elseif (is_array($value)) {
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        $lists = $qb->execute()->fetchAll();
        $data = [];
        if ($lists ?? '') {
            foreach ($lists as $list) {
                $list['pay_time'] = date('Y-m-d H:i:s', $list['time_expire']);
                $data[$list['order_id']] = $list;
            }
        }
        return $data;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        /**
         * @var \OrdersBundle\Entities\Trade  $entity
         */
        return [
            'trade_id' => $entity->getTradeId(),
            'order_id' => $entity->getOrderId(),
            'company_id' => $entity->getCompanyId(),
            'supplier_id' => $entity->getSupplierId(),
            'shop_id' => $entity->getShopId(),
            'distributor_id' => $entity->getDistributorId(),
            'trade_source_type' => $entity->getTradeSourceType(),
            'user_id' => $entity->getUserId(),
            'mobile' => $entity->getMobile(),
            'open_id' => $entity->getOpenId(),
            'discount_info' => $entity->getDiscountInfo(),
            'mch_id' => $entity->getMchId(),
            'total_fee' => $entity->getTotalFee(),
            'discount_fee' => $entity->getDiscountFee(),
            'fee_type' => $entity->getFeeType(),
            'pay_fee' => $entity->getPayFee(),
            'trade_no'=> $entity->getTradeNo(),
            'trade_state' => $entity->getTradeState(),
            'pay_type' => $entity->getPayType(),
            'pay_channel' => $entity->getPayChannel(),
            'transaction_id' => $entity->getTransactionId(),
            'authorizer_appid' => $entity->getAuthorizerAppid(),
            'wxa_appid' => $entity->getWxaAppid(),
            'bank_type' => $entity->getBankType(),
            'body' => $entity->getBody(),
            'detail' => $entity->getDetail(),
            'time_start' => $entity->getTimeStart(),
            'time_expire' => $entity->getTimeExpire(),
            'cur_pay_fee' => $entity->getCurPayFee(),
            'cur_fee_symbol' => $entity->getCurFeeSymbol(),
            'cur_fee_rate' => $entity->getCurFeeRate(),
            'cur_fee_type' => $entity->getCurFeeType(),
            'coupon_fee' => $entity->getCouponFee(),
            'coupon_info' => $entity->getCouponInfo(),
            'div_members' => $entity->getDivMembers(),
            'refunded_fee' => $entity->getRefundedFee(),
            'adapay_fee_mode' => $entity->getAdapayFeeMode(),
            'adapay_fee' => $entity->getAdapayFee(),
            'dealer_id' => $entity->getDealerId(),
            'adapay_div_status' => $entity->getAdapayDivStatus(),
            'bspay_div_status' => $entity->getBspayDivStatus(),
            'inital_request' => json_decode($entity->getInitalRequest(), true),
            'inital_response' => json_decode($entity->getInitalResponse(), true),
            'merchant_id' => $entity->getMerchantId(),
            'is_settled' => $entity->getIsSettled(),
            'payment_params' => $entity->getPaymentParams(),
            'bspay_req_date' => $entity->getBspayReqDate(),
            'bspay_div_members' => $entity->getBspayDivMembers(),
            'bspay_fee_mode' => $entity->getBspayFeeMode(),
            'bspay_fee' => $entity->getBspayFee(),
        ];
    }
}
