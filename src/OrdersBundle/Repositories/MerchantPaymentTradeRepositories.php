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
use OrdersBundle\Entities\MerchantPaymentTrade;

use Dingo\Api\Exception\ResourceException;

class MerchantPaymentTradeRepositories extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new MerchantPaymentTrade();
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
        if (isset($data["merchant_trade_id"]) && $data["merchant_trade_id"]) {
            $entity->setMerchantTradeId($data["merchant_trade_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        //当前字段非必填
        if (isset($data["rel_scene_id"]) && $data["rel_scene_id"]) {
            $entity->setRelSceneId($data["rel_scene_id"]);
        }
        //当前字段非必填
        if (isset($data["rel_scene_name"]) && $data["rel_scene_name"]) {
            $entity->setRelSceneName($data["rel_scene_name"]);
        }
        //当前字段非必填
        if (isset($data["mch_appid"]) && $data["mch_appid"]) {
            $entity->setMchAppid($data["mch_appid"]);
        }
        //当前字段非必填
        if (isset($data["mchid"]) && $data["mchid"]) {
            $entity->setMchid($data["mchid"]);
        }
        if (isset($data["payment_action"]) && $data["payment_action"]) {
            $entity->setPaymentAction($data["payment_action"]);
        }
        if (isset($data["check_name"]) && $data["check_name"]) {
            $entity->setCheckName($data["check_name"]);
        }
        //当前字段非必填
        if (isset($data["mobile"]) && $data["mobile"]) {
            $entity->setMobile($data["mobile"]);
        }
        //当前字段非必填
        if (isset($data["re_user_name"]) && $data["re_user_name"]) {
            $entity->setReUserName($data["re_user_name"]);
        }
        if (isset($data["user_id"]) && $data["user_id"]) {
            $entity->setUserId($data["user_id"]);
        }
        //当前字段非必填
        if (isset($data["open_id"]) && $data["open_id"]) {
            $entity->setOpenId($data["open_id"]);
        }
        if (isset($data["amount"]) && $data["amount"]) {
            $entity->setAmount($data["amount"]);
        }
        if (isset($data["payment_desc"]) && $data["payment_desc"]) {
            $entity->setPaymentDesc($data["payment_desc"]);
        }
        if (isset($data["spbill_create_ip"]) && $data["spbill_create_ip"]) {
            $entity->setSpbillCreateIp($data["spbill_create_ip"]);
        }
        if (isset($data["status"]) && $data["status"]) {
            $entity->setStatus($data["status"]);
        }
        //当前字段非必填
        if (isset($data["payment_no"]) && $data["payment_no"]) {
            $entity->setPaymentNo($data["payment_no"]);
        }
        //当前字段非必填
        if (isset($data["payment_time"]) && $data["payment_time"]) {
            $entity->setPaymentTime($data["payment_time"]);
        }
        //当前字段非必填
        if (isset($data["error_code"]) && $data["error_code"]) {
            $entity->setErrorCode($data["error_code"]);
        }
        //当前字段非必填
        if (isset($data["error_desc"]) && $data["error_desc"]) {
            $entity->setErrorDesc($data["error_desc"]);
        }
        if (isset($data["create_time"]) && $data["create_time"]) {
            $entity->setCreateTime($data["create_time"]);
        }
        //当前字段非必填
        if (isset($data["update_time"]) && $data["update_time"]) {
            $entity->setUpdateTime($data["update_time"]);
        }

        //当前字段非必填
        if (isset($data["fee_type"]) && $data["fee_type"]) {
            $entity->setFeeType($data["fee_type"]);
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
        } else {
            if (isset($data["amount"]) && $data["amount"]) {
                $entity->setCurPayFee($data["amount"]);
            }
        }
        if (isset($data["hf_order_id"]) && $data["hf_order_id"]) {
            $entity->setHfOrderId($data["hf_order_id"]);
        }
        if (isset($data["hf_order_date"]) && $data["hf_order_date"]) {
            $entity->setHfOrderDate($data["hf_order_date"]);
        }
        if (isset($data["hf_cash_type"]) && $data["hf_cash_type"]) {
            $entity->setHfCashType($data["hf_cash_type"]);
        }
        if (isset($data["user_cust_id"]) && $data["user_cust_id"]) {
            $entity->setUserCustId($data["user_cust_id"]);
        }
        if (isset($data["bind_card_id"]) && $data["bind_card_id"]) {
            $entity->setBindCardId($data["bind_card_id"]);
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
            'merchant_trade_id' => $entity->getMerchantTradeId(),
            'company_id' => $entity->getCompanyId(),
            'rel_scene_id' => $entity->getRelSceneId(),
            'rel_scene_name' => $entity->getRelSceneName(),
            'mch_appid' => $entity->getMchAppid(),
            'mchid' => $entity->getMchid(),
            'payment_action' => $entity->getPaymentAction(),
            'check_name' => $entity->getCheckName(),
            'mobile' => $entity->getMobile(),
            're_user_name' => $entity->getReUserName(),
            'user_id' => $entity->getUserId(),
            'open_id' => $entity->getOpenId(),
            'amount' => $entity->getAmount(),
            'payment_desc' => $entity->getPaymentDesc(),
            'spbill_create_ip' => $entity->getSpbillCreateIp(),
            'status' => $entity->getStatus(),
            'payment_no' => $entity->getPaymentNo(),
            'payment_time' => $entity->getPaymentTime(),
            'error_code' => $entity->getErrorCode(),
            'error_desc' => $entity->getErrorDesc(),
            'create_time' => $entity->getCreateTime(),
            'update_time' => $entity->getUpdateTime(),
            'cur_pay_fee' => $entity->getCurPayFee(),
            'cur_fee_symbol' => $entity->getCurFeeSymbol(),
            'cur_fee_rate' => $entity->getCurFeeRate(),
            'cur_fee_type' => $entity->getCurFeeType(),
            'fee_type' => $entity->getFeeType(),
            'hf_order_id' => $entity->getHfOrderId(),
            'hf_order_date' => $entity->getHfOrderDate(),
            'hf_cash_type' => $entity->getHfCashType(),
            'user_cust_id' => $entity->getUserCustId(),
            'bind_card_id' => $entity->getBindCardId()
        ];
    }
}
