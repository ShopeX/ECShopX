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
 * UserIndv 个人用户
 *
 * @ORM\Table(name="bspay_user_indv", options={"comment":"个人用户"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_sys_id", columns={"sys_id"}),
 *         @ORM\Index(name="idx_huifu_id", columns={"huifu_id"}),
 *         @ORM\Index(name="idx_audit_state", columns={"audit_state"})
 *     }),
 * )
 * @ORM\Entity(repositoryClass="BsPayBundle\Repositories\UserIndvRepository")
 */
class UserIndv
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
     * @var string
     *
     * @ORM\Column(name="req_seq_id", type="string", nullable=true, length=64, options={"comment":"请求流水号", "default": ""})
     */
    private $req_seq_id;

    /**
     * @var string
     *
     * @ORM\Column(name="sys_id", nullable=true, type="string", options={"comment":"商户的huifu_id", "default": ""})
     */
    private $sys_id;
    
    /**
     * @var string
     *
     * @ORM\Column(name="huifu_id", nullable=true, type="string", options={"comment":"汇付ID", "default": ""})
     */
    private $huifu_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=true, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_update", type="integer", length=10, options={"comment":"是否审核成功后修改 1:是  0:否", "default": 0})
     */
    private $is_update = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=500, options={"comment":"个人姓名","default":""})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="cert_no", type="string", length=255, options={"comment":"个人身份证号码","default":""})
     */
    private $cert_no;

    /**
     * @var integer
     *
     * @ORM\Column(name="cert_validity_type", type="integer", nullable=true, options={"comment":"个人身份证有效期类型 1:长期有效 0:非长期有效；", "default": 0})
     */
    private $cert_validity_type = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="cert_begin_date", type="string", length=8, options={"comment":"个人身份证有效期开始日期","default":""})
     */
    private $cert_begin_date;

    /**
     * @var string
     *
     * @ORM\Column(name="cert_end_date", type="string", length=8, nullable=true, options={"comment":"个人身份证有效期截止日期 日期格式：yyyyMMdd;非长期有效时必填","default":""})
     */
    private $cert_end_date;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile_no", type="string", length=255, options={"comment":"手机号","default":""})
     */
    private $mobile_no;

    /**
     * @var string
     *
     * A 待审核
     * B 审核失败
     * C 开户失败
     * D 开户成功但未创建结算账户
     * E 开户和创建结算账户成功
     *
     * @ORM\Column(name="audit_state", nullable=true, type="string", length=50, options={"comment":"审核状态，状态包括：A-待审核；B-审核失败；C-开户失败；D-开户成功但未创建结算账户；E-开户和创建结算账户成功","default":"A"})
     */
    private $audit_state;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_desc", nullable=true, type="string", length=500, options={"comment":"审核结果描述","default":""})
     */
    private $audit_desc;

    /**
     * @var string
     *
     * @ORM\Column(name="error_info", nullable=true, type="string", length=500, options={"comment":"错误描述","default":""})
     */
    private $error_info;

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
     * Set reqSeqId.
     *
     * @param string|null $reqSeqId
     *
     * @return UserIndv
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
     * Set sysId.
     *
     * @param string|null $sysId
     *
     * @return UserIndv
     */
    public function setSysId($sysId = null)
    {
        $this->sys_id = $sysId;

        return $this;
    }

    /**
     * Get sysId.
     *
     * @return string|null
     */
    public function getSysId()
    {
        return $this->sys_id;
    }

    /**
     * Set huifuId.
     *
     * @param string|null $huifuId
     *
     * @return UserIndv
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
     * Set companyId.
     *
     * @param int|null $companyId
     *
     * @return UserIndv
     */
    public function setCompanyId($companyId = null)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int|null
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set isUpdate.
     *
     * @param int $isUpdate
     *
     * @return UserIndv
     */
    public function setIsUpdate($isUpdate)
    {
        $this->is_update = $isUpdate;

        return $this;
    }

    /**
     * Get isUpdate.
     *
     * @return int
     */
    public function getIsUpdate()
    {
        return $this->is_update;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return UserIndv
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set certNo.
     *
     * @param string $certNo
     *
     * @return UserIndv
     */
    public function setCertNo($certNo)
    {
        $this->cert_no = $certNo;

        return $this;
    }

    /**
     * Get certNo.
     *
     * @return string
     */
    public function getCertNo()
    {
        return $this->cert_no;
    }

    /**
     * Set certValidityType.
     *
     * @param int|null $certValidityType
     *
     * @return UserIndv
     */
    public function setCertValidityType($certValidityType = null)
    {
        $this->cert_validity_type = $certValidityType;

        return $this;
    }

    /**
     * Get certValidityType.
     *
     * @return int|null
     */
    public function getCertValidityType()
    {
        return $this->cert_validity_type;
    }

    /**
     * Set certBeginDate.
     *
     * @param string $certBeginDate
     *
     * @return UserIndv
     */
    public function setCertBeginDate($certBeginDate)
    {
        $this->cert_begin_date = $certBeginDate;

        return $this;
    }

    /**
     * Get certBeginDate.
     *
     * @return string
     */
    public function getCertBeginDate()
    {
        return $this->cert_begin_date;
    }

    /**
     * Set certEndDate.
     *
     * @param string|null $certEndDate
     *
     * @return UserIndv
     */
    public function setCertEndDate($certEndDate = null)
    {
        $this->cert_end_date = $certEndDate;

        return $this;
    }

    /**
     * Get certEndDate.
     *
     * @return string|null
     */
    public function getCertEndDate()
    {
        return $this->cert_end_date;
    }

    /**
     * Set mobileNo.
     *
     * @param string $mobileNo
     *
     * @return UserIndv
     */
    public function setMobileNo($mobileNo)
    {
        $this->mobile_no = $mobileNo;

        return $this;
    }

    /**
     * Get mobileNo.
     *
     * @return string
     */
    public function getMobileNo()
    {
        return $this->mobile_no;
    }

    /**
     * Set auditState.
     *
     * @param string|null $auditState
     *
     * @return UserIndv
     */
    public function setAuditState($auditState = null)
    {
        $this->audit_state = $auditState;

        return $this;
    }

    /**
     * Get auditState.
     *
     * @return string|null
     */
    public function getAuditState()
    {
        return $this->audit_state;
    }

    /**
     * Set auditDesc.
     *
     * @param string|null $auditDesc
     *
     * @return UserIndv
     */
    public function setAuditDesc($auditDesc = null)
    {
        $this->audit_desc = $auditDesc;

        return $this;
    }

    /**
     * Get auditDesc.
     *
     * @return string|null
     */
    public function getAuditDesc()
    {
        return $this->audit_desc;
    }

    /**
     * Set errorInfo.
     *
     * @param string|null $errorInfo
     *
     * @return UserIndv
     */
    public function setErrorInfo($errorInfo = null)
    {
        $this->error_info = $errorInfo;

        return $this;
    }

    /**
     * Get errorInfo.
     *
     * @return string|null
     */
    public function getErrorInfo()
    {
        return $this->error_info;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return UserIndv
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
     * @return UserIndv
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
}
