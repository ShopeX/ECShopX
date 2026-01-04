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

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CustomDeclareOrderResult 订单附加信息提交信息接口请求结果表
 *
 * @ORM\Table(name="custom_declare_order_result", options={"comment":"订单附加信息提交信息接口请求结果表"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\CustomDeclareOrderResultRepository")
 */
class CustomDeclareOrderResult
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="trade_id", type="string", length=64, options={"comment":"交易单号"})
     */
    private $trade_id;

    /**
     * @var string
     *
     * UNDECLARED -- 未申报
     * SUBMITTED -- 申报已提交（订单已经送海关，商户重新申报，并且海关还有修改接口，那么记录的状态会是这个）
     * PROCESSING -- 申报中
     * SUCCESS -- 申报成功
     * FAIL-- 申报失败
     * EXCEPT --海关接口异常
     *
     * @ORM\Column(name="state", type="string", options={"comment":"状态码"})
     */
    private $state;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="transaction_id", type="string", length=28, options={"comment":"微信支付返回的订单号"})
     */
    private $transaction_id;

    /**
     * @var string
     *
     * @ORM\Column(name="sub_order_no", nullable=true, type="string", length=64, options={"comment":"子订单号"})
     */
    private $sub_order_no;

    /**
     * @var string
     *
     * @ORM\Column(name="sub_order_id", nullable=true, type="string", length=64, options={"comment":"微信子订单号"})
     */
    private $sub_order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="modify_time", type="string", length=14, options={"comment":"最后更新时间"})
     */
    private $modify_time;

    /**
     * @var string
     *
     * UNCHECKED 商户未上传订购人身份信息
     * SAME 商户上传的订购人身份信息与支付人身份信息一致
     * DIFFERENT 商户上传的订购人身份信息与支付人身份信息不一致
     *
     * @ORM\Column(name="cert_check_result", type="string", length=256, options={"comment":"订购人和支付人身份信息校验结果"})
     */
    private $cert_check_result;

    /**
     * @var string
     *
     * 银联-UNIONPAY
     * 网联-NETSUNION
     * 其他-OTHERS(如余额支付，零钱通支付等)
     *
     * @ORM\Column(name="verify_department", type="string", length=16, options={"comment":"验证机构"})
     */
    private $verify_department;

    /**
     * @var string
     *
     * @ORM\Column(name="verify_department_trade_id", type="string", length=64, options={"comment":"验核机构交易流水号"})
     */
    private $verify_department_trade_id;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"请求时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    protected $update_time;

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return CustomDeclareOrderResult
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set state.
     *
     * @param string $state
     *
     * @return CustomDeclareOrderResult
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return CustomDeclareOrderResult
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set transactionId.
     *
     * @param string $transactionId
     *
     * @return CustomDeclareOrderResult
     */
    public function setTransactionId($transactionId)
    {
        $this->transaction_id = $transactionId;

        return $this;
    }

    /**
     * Get transactionId.
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * Set subOrderNo.
     *
     * @param string|null $subOrderNo
     *
     * @return CustomDeclareOrderResult
     */
    public function setSubOrderNo($subOrderNo = null)
    {
        $this->sub_order_no = $subOrderNo;

        return $this;
    }

    /**
     * Get subOrderNo.
     *
     * @return string|null
     */
    public function getSubOrderNo()
    {
        return $this->sub_order_no;
    }

    /**
     * Set subOrderId.
     *
     * @param string|null $subOrderId
     *
     * @return CustomDeclareOrderResult
     */
    public function setSubOrderId($subOrderId = null)
    {
        $this->sub_order_id = $subOrderId;

        return $this;
    }

    /**
     * Get subOrderId.
     *
     * @return string|null
     */
    public function getSubOrderId()
    {
        return $this->sub_order_id;
    }

    /**
     * Set modifyTime.
     *
     * @param string $modifyTime
     *
     * @return CustomDeclareOrderResult
     */
    public function setModifyTime($modifyTime)
    {
        $this->modify_time = $modifyTime;

        return $this;
    }

    /**
     * Get modifyTime.
     *
     * @return string
     */
    public function getModifyTime()
    {
        return $this->modify_time;
    }

    /**
     * Set certCheckResult.
     *
     * @param string $certCheckResult
     *
     * @return CustomDeclareOrderResult
     */
    public function setCertCheckResult($certCheckResult)
    {
        $this->cert_check_result = $certCheckResult;

        return $this;
    }

    /**
     * Get certCheckResult.
     *
     * @return string
     */
    public function getCertCheckResult()
    {
        return $this->cert_check_result;
    }

    /**
     * Set verifyDepartment.
     *
     * @param string $verifyDepartment
     *
     * @return CustomDeclareOrderResult
     */
    public function setVerifyDepartment($verifyDepartment)
    {
        $this->verify_department = $verifyDepartment;

        return $this;
    }

    /**
     * Get verifyDepartment.
     *
     * @return string
     */
    public function getVerifyDepartment()
    {
        return $this->verify_department;
    }

    /**
     * Set verifyDepartmentTradeId.
     *
     * @param string $verifyDepartmentTradeId
     *
     * @return CustomDeclareOrderResult
     */
    public function setVerifyDepartmentTradeId($verifyDepartmentTradeId)
    {
        $this->verify_department_trade_id = $verifyDepartmentTradeId;

        return $this;
    }

    /**
     * Get verifyDepartmentTradeId.
     *
     * @return string
     */
    public function getVerifyDepartmentTradeId()
    {
        return $this->verify_department_trade_id;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return CustomDeclareOrderResult
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime.
     *
     * @return int
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set tradeId.
     *
     * @param int $tradeId
     *
     * @return CustomDeclareOrderResult
     */
    public function setTradeId($tradeId)
    {
        $this->trade_id = $tradeId;

        return $this;
    }

    /**
     * Get tradeId.
     *
     * @return int
     */
    public function getTradeId()
    {
        return $this->trade_id;
    }

    /**
     * Set updateTime.
     *
     * @param int|null $updateTime
     *
     * @return CustomDeclareOrderResult
     */
    public function setUpdateTime($updateTime = null)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime.
     *
     * @return int|null
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }
}
