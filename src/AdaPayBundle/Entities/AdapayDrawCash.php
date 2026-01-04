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

namespace AdaPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * AdapayDrawCash adapay提现表
 *
 * @ORM\Table(name="adapay_draw_cash", options={"comment":"adapay提现表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_order_no", columns={"order_no"}),
 *         @ORM\Index(name="idx_adapay_member_id", columns={"adapay_member_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayDrawCashRepository")
 */
class AdapayDrawCash
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", nullable=true, type="integer", options={"comment":"提现账号id", "default": 0})
     */
    private $operator_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_type", type="string", options={"comment":"操作账号类型:distributor-店铺;dealer-经销;admin:超级管理员"})
     */
    private $operator_type = "admin";

    /**
     * @var string
     *
     * @ORM\Column(name="operator", type="string", nullable=true, options={"comment":"操作人"})
     */
    private $operator;


    /**
     * @var string
     *
     * @ORM\Column(name="app_id", type="string", length=100, options={"comment":"应用app_id"})
     */
    private $app_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_no", type="string", length=64, options={"comment":"请求订单号"})
     */
    private $order_no;

    /**
     * @var string
     *
     * @ORM\Column(name="cash_id", type="string", nullable=true, length=64, options={"comment":"取现对象 id"})
     */
    private $cash_id;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_card_id", type="string", nullable=true, length=100, options={"comment":"提现银行卡号"})
     */
    private $bank_card_id;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_card_name", type="string", nullable=true, length=100, options={"comment":"银行卡对应的户名"})
     */
    private $bank_card_name;

    /**
     * @var string
     *
     * @ORM\Column(name="cash_type", type="string", length=10, options={"comment":"取现类型：T1-T+1取现；D1-D+1取现；D0-即时取现"})
     */
    private $cash_type;

    /**
     * @var string
     *
     * @ORM\Column(name="cash_amt", type="string", options={"comment":"取现金额，必须大于0，人民币为分"})
     */
    private $cash_amt;

    /**
     * @var string
     *
     * @ORM\Column(name="adapay_member_id", type="string", options={"comment":"汇付账号id"})
     */
    private $adapay_member_id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", options={"comment":"提现状态"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="request_params", type="text", options={"comment":"请求参数 json"})
     */
    private $request_params;

    /**
     * @var string
     *
     * @ORM\Column(name="response_params", type="text", nullable=true, options={"comment":"回调参数 json"})
     */
    private $response_params;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", nullable=true, options={"comment":"备注"})
     */
    private $remark = "";

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $update_time;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return AdapayDrawCash
     */
    public function setCompanyId($companyId)
    {
        // ModuleID: 76fe2a3d
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
     * Set appId.
     *
     * @param string $appId
     *
     * @return AdapayDrawCash
     */
    public function setAppId($appId)
    {
        $this->app_id = $appId;

        return $this;
    }

    /**
     * Get appId.
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->app_id;
    }

    /**
     * Set orderNo.
     *
     * @param string $orderNo
     *
     * @return AdapayDrawCash
     */
    public function setOrderNo($orderNo)
    {
        $this->order_no = $orderNo;

        return $this;
    }

    /**
     * Get orderNo.
     *
     * @return string
     */
    public function getOrderNo()
    {
        return $this->order_no;
    }

    /**
     * Set cashType.
     *
     * @param string $cashType
     *
     * @return AdapayDrawCash
     */
    public function setCashType($cashType)
    {
        $this->cash_type = $cashType;

        return $this;
    }

    /**
     * Get cashType.
     *
     * @return string
     */
    public function getCashType()
    {
        return $this->cash_type;
    }

    /**
     * Set cashAmt.
     *
     * @param string $cashAmt
     *
     * @return AdapayDrawCash
     */
    public function setCashAmt($cashAmt)
    {
        $this->cash_amt = $cashAmt;

        return $this;
    }

    /**
     * Get cashAmt.
     *
     * @return string
     */
    public function getCashAmt()
    {
        return $this->cash_amt;
    }

    /**
     * Set adapayMemberId.
     *
     * @param string $adapayMemberId
     *
     * @return AdapayDrawCash
     */
    public function setAdapayMemberId($adapayMemberId)
    {
        $this->adapay_member_id = $adapayMemberId;

        return $this;
    }

    /**
     * Get adapayMemberId.
     *
     * @return string
     */
    public function getAdapayMemberId()
    {
        return $this->adapay_member_id;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return AdapayDrawCash
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set requestParams.
     *
     * @param string $requestParams
     *
     * @return AdapayDrawCash
     */
    public function setRequestParams($requestParams)
    {
        $this->request_params = $requestParams;

        return $this;
    }

    /**
     * Get requestParams.
     *
     * @return string
     */
    public function getRequestParams()
    {
        return $this->request_params;
    }

    /**
     * Set responseParams.
     *
     * @param string $responseParams
     *
     * @return AdapayDrawCash
     */
    public function setResponseParams($responseParams)
    {
        $this->response_params = $responseParams;

        return $this;
    }

    /**
     * Get responseParams.
     *
     * @return string
     */
    public function getResponseParams()
    {
        return $this->response_params;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapayDrawCash
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
     * Set updateTime.
     *
     * @param int|null $updateTime
     *
     * @return AdapayDrawCash
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

    /**
     * Set operatorId.
     *
     * @param int|null $operatorId
     *
     * @return AdapayMember
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


    /**
     * Set operatorType.
     *
     * @param int|null $operatorType
     *
     * @return AdapayMember
     */
    public function setOperatorType($operatorType = null)
    {
        $this->operator_type = $operatorType;

        return $this;
    }

    /**
     * Get operatorType.
     *
     * @return int|null
     */
    public function getOperatorType()
    {
        return $this->operator_type;
    }

    /**
     * Set remark.
     *
     * @param int|null $operatorType
     *
     * @return AdapayDrawCash
     */
    public function setRemark($remark = null)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark.
     *
     * @return int|null
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set operator.
     *
     * @param string|null $operator
     *
     * @return AdapayDrawCash
     */
    public function setOperator($operator = null)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator.
     *
     * @return string|null
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Set cashId.
     *
     * @param string|null $cashId
     *
     * @return AdapayDrawCash
     */
    public function setCashId($cashId = null)
    {
        $this->cash_id = $cashId;

        return $this;
    }

    /**
     * Get cashId.
     *
     * @return string|null
     */
    public function getCashId()
    {
        return $this->cash_id;
    }

    /**
     * Set bankCardId.
     *
     * @param string|null $bankCardId
     *
     * @return AdapayDrawCash
     */
    public function setBankCardId($bankCardId = null)
    {
        $this->bank_card_id = $bankCardId;

        return $this;
    }

    /**
     * Get bankCardId.
     *
     * @return string|null
     */
    public function getBankCardId()
    {
        return $this->bank_card_id;
    }

    /**
     * Set bankCardName.
     *
     * @param string|null $bankCardName
     *
     * @return AdapayDrawCash
     */
    public function setBankCardName($bankCardName = null)
    {
        $this->bank_card_name = $bankCardName;

        return $this;
    }

    /**
     * Get bankCardName.
     *
     * @return string|null
     */
    public function getBankCardName()
    {
        return $this->bank_card_name;
    }
}
