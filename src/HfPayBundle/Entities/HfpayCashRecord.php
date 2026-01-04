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

namespace HfPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * HfpayCash 汇付取现记录表
 *
 * @ORM\Table(name="hfpay_cash_record", options={"comment":"汇付取现记录表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="HfPayBundle\Repositories\HfpayCashRecordRepository")
 */

class HfpayCashRecord
{
    use Timestamps;
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="hfpay_cash_record_id", type="bigint", options={"comment":"汇付取现记录表id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $hfpay_cash_record_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", nullable=true, options={"comment":"分销商id", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id", "default": 0})
     */
    private $user_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=true, options={"comment":"操作人ID", "default": 0})
     */
    private $operator_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="user_cust_id", type="string", nullable=true, options={"comment":"汇付商户客户号"})
     */
    private $user_cust_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="trans_amt", type="integer", options={"comment":"取现金额"})
     */
    private $trans_amt;

    /**
     * @var string
     *
     * @ORM\Column(name="cash_type", type="string", options={"comment":"取现方式 T0：T0取现; T1：T1取现 D1：D1取现"})
     */
    private $cash_type;

    /**
     * @var string
     *
     * @ORM\Column(name="bind_card_id", type="string", nullable=true, options={"comment":"取现绑卡"})
     */
    private $bind_card_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="real_trans_amt", type="integer", nullable=true, options={"comment":"实际到账金额"})
     */
    private $real_trans_amt;

    /**
     * @var integer
     *
     * @ORM\Column(name="fee_amt", type="integer", nullable=true, options={"comment":"取现手续费"})
     */
    private $fee_amt;

    /**
     * @var integer
     *
     * @ORM\Column(name="cash_status", type="integer", options={"comment":"取现状态 0 未提交 1已提交 2取现成功 3取现失败", "default": 0})
     */
    private $cash_status = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="resp_code", type="string", nullable=true, options={"comment":"汇付接口返回码"})
     */
    private $resp_code;

    /**
     * @var string
     *
     * @ORM\Column(name="resp_desc", type="string", nullable=true, options={"comment":"汇付接口返回码描述"})
     */
    private $resp_desc;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_order_id", type="string", nullable=true, options={"comment":"汇付接口请求order_id"})
     */
    private $hf_order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_order_date", type="string",nullable=true, options={"comment":"汇付接口请求order_date"})
     */
    private $hf_order_date;

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return HfpayCashRecord
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
        // TS: 53686f704578
        return $this->company_id;
    }

    /**
     * Set distributorId.
     *
     * @param int|null $distributorId
     *
     * @return HfpayCashRecord
     */
    public function setDistributorId($distributorId = null)
    {
        // TS: 53686f704578
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int|null
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set userId.
     *
     * @param int|null $userId
     *
     * @return HfpayCashRecord
     */
    public function setUserId($userId = null)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int|null
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set transAmt.
     *
     * @param int|null $transAmt
     *
     * @return HfpayCashRecord
     */
    public function setTransAmt($transAmt = null)
    {
        $this->trans_amt = $transAmt;

        return $this;
    }

    /**
     * Get transAmt.
     *
     * @return int|null
     */
    public function getTransAmt()
    {
        return $this->trans_amt;
    }

    /**
     * Set cashType.
     *
     * @param string|null $cashType
     *
     * @return HfpayCashRecord
     */
    public function setCashType($cashType = null)
    {
        $this->cash_type = $cashType;

        return $this;
    }

    /**
     * Get cashType.
     *
     * @return string|null
     */
    public function getCashType()
    {
        return $this->cash_type;
    }

    /**
     * Set bindCardId.
     *
     * @param string|null $bindCardId
     *
     * @return HfpayCashRecord
     */
    public function setBindCardId($bindCardId = null)
    {
        $this->bind_card_id = $bindCardId;

        return $this;
    }

    /**
     * Get bindCardId.
     *
     * @return string|null
     */
    public function getBindCardId()
    {
        return $this->bind_card_id;
    }

    /**
     * Set realTransAmt.
     *
     * @param int $realTransAmt
     *
     * @return HfpayCashRecord
     */
    public function setRealTransAmt($realTransAmt)
    {
        $this->real_trans_amt = $realTransAmt;

        return $this;
    }

    /**
     * Get realTransAmt.
     *
     * @return int
     */
    public function getRealTransAmt()
    {
        return $this->real_trans_amt;
    }

    /**
     * Set feeAmt.
     *
     * @param int $feeAmt
     *
     * @return HfpayCashRecord
     */
    public function setFeeAmt($feeAmt)
    {
        $this->fee_amt = $feeAmt;

        return $this;
    }

    /**
     * Get feeAmt.
     *
     * @return int
     */
    public function getFeeAmt()
    {
        return $this->fee_amt;
    }

    /**
     * Set cashStatus.
     *
     * @param int $cashStatus
     *
     * @return HfpayCashRecord
     */
    public function setCashStatus($cashStatus)
    {
        $this->cash_status = $cashStatus;

        return $this;
    }

    /**
     * Get cashStatus.
     *
     * @return int
     */
    public function getCashStatus()
    {
        return $this->cash_status;
    }

    /**
     * Get hfpayCashRecordId.
     *
     * @return int
     */
    public function getHfpayCashRecordId()
    {
        return $this->hfpay_cash_record_id;
    }

    /**
     * Set respCode.
     *
     * @param string $respCode
     *
     * @return HfpayCashRecord
     */
    public function setRespCode($respCode)
    {
        $this->resp_code = $respCode;

        return $this;
    }

    /**
     * Get respCode.
     *
     * @return string
     */
    public function getRespCode()
    {
        return $this->resp_code;
    }

    /**
     * Set respDesc.
     *
     * @param string $respDesc
     *
     * @return HfpayCashRecord
     */
    public function setRespDesc($respDesc)
    {
        $this->resp_desc = $respDesc;

        return $this;
    }

    /**
     * Get respDesc.
     *
     * @return string
     */
    public function getRespDesc()
    {
        return $this->resp_desc;
    }

    /**
     * Set userCustId.
     *
     * @param string|null $userCustId
     *
     * @return HfpayCashRecord
     */
    public function setUserCustId($userCustId = null)
    {
        $this->user_cust_id = $userCustId;

        return $this;
    }

    /**
     * Get userCustId.
     *
     * @return string|null
     */
    public function getUserCustId()
    {
        return $this->user_cust_id;
    }

    /**
     * Set hfOrderId.
     *
     * @param string $hfOrderId
     *
     * @return HfpayCashRecord
     */
    public function setHfOrderId($hfOrderId)
    {
        $this->hf_order_id = $hfOrderId;

        return $this;
    }

    /**
     * Get hfOrderId.
     *
     * @return string
     */
    public function getHfOrderId()
    {
        return $this->hf_order_id;
    }

    /**
     * Set hfOrderDate.
     *
     * @param string $hfOrderDate
     *
     * @return HfpayCashRecord
     */
    public function setHfOrderDate($hfOrderDate)
    {
        $this->hf_order_date = $hfOrderDate;

        return $this;
    }

    /**
     * Get hfOrderDate.
     *
     * @return string
     */
    public function getHfOrderDate()
    {
        return $this->hf_order_date;
    }

    /**
     * Set orderId.
     *
     * @param string $orderId
     *
     * @return HfpayCashRecord
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set operatorId.
     *
     * @param int|null $operatorId
     *
     * @return HfpayCashRecord
     */
    public function setOperatorId($operatorId = null)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int|null
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }
}
