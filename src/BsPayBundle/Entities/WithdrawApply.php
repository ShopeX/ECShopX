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

namespace BsPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * 汇付斗拱提现申请表
 *
 * @ORM\Table(name="bspay_withdraw_apply", indexes={
 *     @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     @ORM\Index(name="idx_company_status", columns={"company_id", "status"}),
 *     @ORM\Index(name="idx_operator", columns={"operator_type", "operator_id"}),
 *     @ORM\Index(name="idx_status", columns={"status"}),
 *     @ORM\Index(name="idx_created", columns={"created"}),
 * })
 * @ORM\Entity(repositoryClass="BsPayBundle\Repositories\WithdrawApplyRepository")
 */
class WithdrawApply
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=false, options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var int
     *
     * @ORM\Column(name="merchant_id", type="bigint", nullable=true, options={"comment":"商户ID", "default": 0})
     */
    private $merchant_id;

    /**
     * @var int
     *
     * @ORM\Column(name="distributor_id", type="bigint", nullable=true, options={"comment":"店铺ID", "default": 0})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_type", type="string", length=20, nullable=false, options={"comment":"操作者类型：distributor=店铺, merchant=商户, admin=超级管理员, staff=员工", "default": ""})
     */
    private $operator_type;

    /**
     * @var int
     *
     * @ORM\Column(name="operator_id", type="bigint", nullable=false, options={"comment":"操作账号ID"})
     */
    private $operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="huifu_id", type="string", length=255, nullable=true, options={"comment":"汇付ID", "default": ""})
     */
    private $huifu_id;

    /**
     * @var int
     *
     * @ORM\Column(name="amount", type="integer", nullable=false, options={"comment":"申请金额（分）", "default": 0, "unsigned":true})
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\Column(name="withdraw_type", type="string", length=10, nullable=false, options={"comment":"提现类型", "default": "T1"})
     */
    private $withdraw_type;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_file", type="string", length=500, nullable=true, options={"comment":"发票文件路径", "default": ""})
     */
    private $invoice_file;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", nullable=false, options={"comment":"申请状态 0=审核中 1=审核通过 2=已拒绝 3=处理中 4=处理成功 5=处理失败 (参见WithdrawStatus枚举)", "default": 0})
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="audit_time", type="integer", nullable=true, options={"comment":"审核时间"})
     */
    private $audit_time;

    /**
     * @var string
     *
     * @ORM\Column(name="auditor", type="string", length=100, nullable=true, options={"comment":"审核人", "default": ""})
     */
    private $auditor;

    /**
     * @var int
     *
     * @ORM\Column(name="auditor_operator_id", type="bigint", nullable=true, options={"comment":"审核人操作账号ID"})
     */
    private $auditor_operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_remark", type="text", nullable=true, options={"comment":"审核备注"})
     */
    private $audit_remark;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_seq_id", type="string", length=128, nullable=true, options={"comment":"汇付全局流水号", "default": ""})
     */
    private $hf_seq_id;

    /**
     * @var string
     *
     * @ORM\Column(name="req_seq_id", type="string", length=128, nullable=true, options={"comment":"请求流水号", "default": ""})
     */
    private $req_seq_id;

    /**
     * @var int
     *
     * @ORM\Column(name="request_time", type="integer", nullable=true, options={"comment":"请求汇付时间"})
     */
    private $request_time;

    /**
     * @var string
     *
     * @ORM\Column(name="failure_reason", type="text", nullable=true, options={"comment":"失败原因"})
     */
    private $failure_reason;

    /**
     * @var string
     *
     * @ORM\Column(name="operator", type="string", length=32, nullable=true, options={"comment":"申请人账号", "default": ""})
     */
    private $operator;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    protected $updated;

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
     * @return WithdrawApply
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
     * Set merchantId.
     *
     * @param int|null $merchantId
     *
     * @return WithdrawApply
     */
    public function setMerchantId($merchantId = null)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return int|null
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set distributorId.
     *
     * @param int|null $distributorId
     *
     * @return WithdrawApply
     */
    public function setDistributorId($distributorId = null)
    {
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
     * Set operatorType.
     *
     * @param string $operatorType
     *
     * @return WithdrawApply
     */
    public function setOperatorType($operatorType)
    {
        $this->operator_type = $operatorType;

        return $this;
    }

    /**
     * Get operatorType.
     *
     * @return string
     */
    public function getOperatorType()
    {
        return $this->operator_type;
    }

    /**
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return WithdrawApply
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
     * Set huifuId.
     *
     * @param string|null $huifuId
     *
     * @return WithdrawApply
     */
    public function setHuifuId($huifuId = null)
    {
        $this->huifu_id = $huifuId;

        return $this;
    }

    /**
     * Get huifuId.
     *
     * @return string|null
     */
    public function getHuifuId()
    {
        return $this->huifu_id;
    }

    /**
     * Set amount.
     *
     * @param int $amount
     *
     * @return WithdrawApply
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount.
     *
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set withdrawType.
     *
     * @param string $withdrawType
     *
     * @return WithdrawApply
     */
    public function setWithdrawType($withdrawType)
    {
        $this->withdraw_type = $withdrawType;

        return $this;
    }

    /**
     * Get withdrawType.
     *
     * @return string
     */
    public function getWithdrawType()
    {
        return $this->withdraw_type;
    }

    /**
     * Set invoiceFile.
     *
     * @param string|null $invoiceFile
     *
     * @return WithdrawApply
     */
    public function setInvoiceFile($invoiceFile = null)
    {
        $this->invoice_file = $invoiceFile;

        return $this;
    }

    /**
     * Get invoiceFile.
     *
     * @return string|null
     */
    public function getInvoiceFile()
    {
        return $this->invoice_file;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return WithdrawApply
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
     * Set auditTime.
     *
     * @param int|null $auditTime
     *
     * @return WithdrawApply
     */
    public function setAuditTime($auditTime = null)
    {
        $this->audit_time = $auditTime;

        return $this;
    }

    /**
     * Get auditTime.
     *
     * @return int|null
     */
    public function getAuditTime()
    {
        return $this->audit_time;
    }

    /**
     * Set auditor.
     *
     * @param string|null $auditor
     *
     * @return WithdrawApply
     */
    public function setAuditor($auditor = null)
    {
        $this->auditor = $auditor;

        return $this;
    }

    /**
     * Get auditor.
     *
     * @return string|null
     */
    public function getAuditor()
    {
        return $this->auditor;
    }

    /**
     * Set auditorOperatorId.
     *
     * @param int|null $auditorOperatorId
     *
     * @return WithdrawApply
     */
    public function setAuditorOperatorId($auditorOperatorId = null)
    {
        $this->auditor_operator_id = $auditorOperatorId;

        return $this;
    }

    /**
     * Get auditorOperatorId.
     *
     * @return int|null
     */
    public function getAuditorOperatorId()
    {
        return $this->auditor_operator_id;
    }

    /**
     * Set auditRemark.
     *
     * @param string|null $auditRemark
     *
     * @return WithdrawApply
     */
    public function setAuditRemark($auditRemark = null)
    {
        $this->audit_remark = $auditRemark;

        return $this;
    }

    /**
     * Get auditRemark.
     *
     * @return string|null
     */
    public function getAuditRemark()
    {
        return $this->audit_remark;
    }

    /**
     * Set hfSeqId.
     *
     * @param string|null $hfSeqId
     *
     * @return WithdrawApply
     */
    public function setHfSeqId($hfSeqId = null)
    {
        $this->hf_seq_id = $hfSeqId;

        return $this;
    }

    /**
     * Get hfSeqId.
     *
     * @return string|null
     */
    public function getHfSeqId()
    {
        return $this->hf_seq_id;
    }

    /**
     * Set reqSeqId.
     *
     * @param string|null $reqSeqId
     *
     * @return WithdrawApply
     */
    public function setReqSeqId($reqSeqId = null)
    {
        $this->req_seq_id = $reqSeqId;

        return $this;
    }

    /**
     * Get reqSeqId.
     *
     * @return string|null
     */
    public function getReqSeqId()
    {
        return $this->req_seq_id;
    }

    /**
     * Set requestTime.
     *
     * @param int|null $requestTime
     *
     * @return WithdrawApply
     */
    public function setRequestTime($requestTime = null)
    {
        $this->request_time = $requestTime;

        return $this;
    }

    /**
     * Get requestTime.
     *
     * @return int|null
     */
    public function getRequestTime()
    {
        return $this->request_time;
    }

    /**
     * Set operator.
     *
     * @param string|null $operator
     *
     * @return WithdrawApply
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
     * Set created.
     *
     * @param int $created
     *
     * @return WithdrawApply
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return WithdrawApply
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set failureReason.
     *
     * @param string|null $failureReason
     *
     * @return WithdrawApply
     */
    public function setFailureReason($failureReason = null)
    {
        $this->failure_reason = $failureReason;

        return $this;
    }

    /**
     * Get failureReason.
     *
     * @return string|null
     */
    public function getFailureReason()
    {
        return $this->failure_reason;
    }
} 