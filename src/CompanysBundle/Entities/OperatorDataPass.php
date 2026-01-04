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

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Operators 数据脱敏审核表
 *
 * @ORM\Table(name="operator_data_pass", options={"comment":"数据敏感信息申请"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_operator_id", columns={"operator_id"}),
 *    @ORM\Index(name="idx_merchant_id", columns={"merchant_id"}),
 * })
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\OperatorDataPassRepository")
 */
class OperatorDataPass
{
    /**
     * @var integer
     *
     * @ORM\Column(name="pass_id", type="bigint", options={"comment":"审核id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $pass_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id","default": 0})
     */
    private $company_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="integer", options={"comment":"操作者id", "default": 0})
     */
    private $operator_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", options={"comment":"审批状态 0:未审批 1:同意 2:驳回", "default": 0})
     */
    private $status = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_closed", type="integer", options={"comment":"关闭状态: 0:未关闭 1:关闭", "default": 0})
     */
    private $is_closed = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="integer", options={"comment":"生效开始时间", "default": 0})
     */
    private $start_time = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="integer", options={"comment":"生效结束时间", "default": 0})
     */
    private $end_time = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="rule", type="string", options={"comment":"生效规则 '8-18 *': 每天8到18点有效 '* 1-5':周一到周五全天有效", "default": ""})
     */
    private $rule;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", options={"comment":"申请理由", "default": ""})
     */
    private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="remarks", type="string", options={"comment":"审批备注", "default": ""})
     */
    private $remarks;

    /**
     * @var integer
     *
     * @ORM\Column(name="create_time", type="integer", options={"comment":"创建时间", "default": 0})
     */
    private $create_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="approve_time", type="integer", options={"comment":"审批时间", "default": 0})
     */
    private $approve_time;
    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id;

    /**
     * Get passId.
     *
     * @return int
     */
    public function getPassId()
    {
        return $this->pass_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OperatorDataPass
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
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return OperatorDataPass
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return OperatorDataPass
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set startTime.
     *
     * @param int $startTime
     *
     * @return OperatorDataPass
     */
    public function setStartTime($startTime)
    {
        $this->start_time = $startTime;

        return $this;
    }

    /**
     * Get startTime.
     *
     * @return int
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set endTime.
     *
     * @param int $endTime
     *
     * @return OperatorDataPass
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime.
     *
     * @return int
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set rule.
     *
     * @param string $rule
     *
     * @return OperatorDataPass
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * Get rule.
     *
     * @return string
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Set reason.
     *
     * @param string $reason
     *
     * @return OperatorDataPass
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason.
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set remarks.
     *
     * @param string $remarks
     *
     * @return OperatorDataPass
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks.
     *
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return OperatorDataPass
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
     * Set approveTime.
     *
     * @param int $approveTime
     *
     * @return OperatorDataPass
     */
    public function setApproveTime($approveTime)
    {
        $this->approve_time = $approveTime;

        return $this;
    }

    /**
     * Get approveTime.
     *
     * @return int
     */
    public function getApproveTime()
    {
        return $this->approve_time;
    }

    /**
     * Set isClosed.
     *
     * @param int $isClosed
     *
     * @return OperatorDataPass
     */
    public function setIsClosed($isClosed)
    {
        $this->is_closed = $isClosed;

        return $this;
    }

    /**
     * Get isClosed.
     *
     * @return int
     */
    public function getIsClosed()
    {
        return $this->is_closed;
    }

    /**
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return OperatorDataPass
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }
}
