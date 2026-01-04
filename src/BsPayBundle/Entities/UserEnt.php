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
 * UserEnt 企业用户
 *
 * @ORM\Table(name="bspay_user_ent", options={"comment":"企业用户对象"},
 *     indexes={
 *         @ORM\Index(name="idx_req_seq_id", columns={"req_seq_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_sys_id", columns={"sys_id"}),
 *         @ORM\Index(name="idx_huifu_id", columns={"huifu_id"}),
 *         @ORM\Index(name="idx_audit_state", columns={"audit_state"})
 *     },
 * )
 * @ORM\Entity(repositoryClass="BsPayBundle\Repositories\UserEntRepository")
 */
class UserEnt
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
     * @ORM\Column(name="sys_id", type="string", options={"comment":"商户的huifu_id", "default": ""})
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
     * @ORM\Column(name="reg_name", type="string", length=255, options={"comment":"企业名称", "default": ""})
     */
    private $reg_name;

    /**
     * @var string
     *
     * @ORM\Column(name="license_code", type="string", length=32, options={"comment":"营业执照编号", "default": ""})
     */
    private $license_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="license_validity_type", type="integer", nullable=true, options={"comment":"营业执照有效期类型 1:长期有效 0:非长期有效；", "default": 0})
     */
    private $license_validity_type = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="license_begin_date", type="string", length=8, options={"comment":"营业执照有效期起始日期 日期格式：yyyyMMdd","default":""})
     */
    private $license_begin_date;

    /**
     * @var string
     *
     * @ORM\Column(name="license_end_date", type="string", length=8, nullable=true, options={"comment":"营业执照有效期结束日期 日期格式：yyyyMMdd;非长期有效时必填","default":""})
     */
    private $license_end_date;

    /**
     * @var string
     *
     * @ORM\Column(name="reg_prov_id", nullable=true, type="string", length=12, options={"comment":"注册地址(省)", "default": ""})
     */
    private $reg_prov_id;

    /**
     * @var string
     *
     * @ORM\Column(name="reg_area_id", type="string", length=12, options={"comment":"注册地址(市)", "default": ""})
     */
    private $reg_area_id;

    /**
     * @var string
     *
     * @ORM\Column(name="reg_district_id", type="string", length=12, options={"comment":"注册地址(区)", "default": ""})
     */
    private $reg_district_id;

    /**
     * @var string
     *
     * @ORM\Column(name="reg_detail", type="string", length=300, options={"comment":"注册地址(详细信息)","default":""})
     */
    private $reg_detail;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_name", type="string", length=500, options={"comment":"法人姓名","default":""})
     */
    private $legal_name;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_cert_no", type="string", length=255, options={"comment":"法人身份证号码","default":""})
     */
    private $legal_cert_no;

    /**
     * @var integer
     *
     * @ORM\Column(name="legal_cert_validity_type", type="integer", nullable=true, options={"comment":"法人身份证有效期类型 1:长期有效 0:非长期有效；", "default": 0})
     */
    private $legal_cert_validity_type = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_cert_begin_date", type="string", length=8, options={"comment":"法人身份证有效期开始日期","default":""})
     */
    private $legal_cert_begin_date;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_cert_end_date", type="string", length=8, nullable=true, options={"comment":"法人身份证有效期截止日期 日期格式：yyyyMMdd;非长期有效时必填","default":""})
     */
    private $legal_cert_end_date;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_name", type="string", length=500, options={"comment":"联系人姓名","default":""})
     */
    private $contact_name;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_mobile", type="string", length=255, options={"comment":"联系人手机","default":""})
     */
    private $contact_mobile;

    /**
     * @var integer
     *
     * @ORM\Column(name="ent_type", type="integer", options={"comment":"公司类型 1:政府机构 2:国营企业 3:私营企业 4:外资企业 5:个体工商户 6:其它组织 7:事业单位 8:集体经济", "default": 1})
     */
    private $ent_type;

    /**
     * @var string
     *
     * A 待审核
     * B 审核失败
     * C 开户失败
     * D 开户成功但未创建结算账户
     * E 开户和创建结算账户成功
     *
     * @ORM\Column(name="audit_state", type="string", nullable=true, length=50, options={"comment":"审核状态，状态包括：A-待审核；B-审核失败；C-开户失败；D-开户成功但未创建结算账户；E-开户和创建结算账户成功","default":""})
     */
    private $audit_state;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_desc", type="string", nullable=true, length=200, options={"comment":"审核结果描述","default":""})
     */
    private $audit_desc;

    /**
     * @var string
     *
     * @ORM\Column(name="error_info", type="string", nullable=true, length=500, options={"comment":"错误描述","default":""})
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
     * @return UserEnt
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
     * @param string $sysId
     *
     * @return UserEnt
     */
    public function setSysId($sysId)
    {
        $this->sys_id = $sysId;

        return $this;
    }

    /**
     * Get sysId.
     *
     * @return string
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
     * @return UserEnt
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
     * @return UserEnt
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
     * @return UserEnt
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
     * Set regName.
     *
     * @param string $regName
     *
     * @return UserEnt
     */
    public function setRegName($regName)
    {
        $this->reg_name = $regName;

        return $this;
    }

    /**
     * Get regName.
     *
     * @return string
     */
    public function getRegName()
    {
        return $this->reg_name;
    }

    /**
     * Set licenseCode.
     *
     * @param string $licenseCode
     *
     * @return UserEnt
     */
    public function setLicenseCode($licenseCode)
    {
        $this->license_code = $licenseCode;

        return $this;
    }

    /**
     * Get licenseCode.
     *
     * @return string
     */
    public function getLicenseCode()
    {
        return $this->license_code;
    }

    /**
     * Set licenseValidityType.
     *
     * @param int|null $licenseValidityType
     *
     * @return UserEnt
     */
    public function setLicenseValidityType($licenseValidityType = null)
    {
        $this->license_validity_type = $licenseValidityType;

        return $this;
    }

    /**
     * Get licenseValidityType.
     *
     * @return int|null
     */
    public function getLicenseValidityType()
    {
        return $this->license_validity_type;
    }

    /**
     * Set licenseBeginDate.
     *
     * @param string $licenseBeginDate
     *
     * @return UserEnt
     */
    public function setLicenseBeginDate($licenseBeginDate)
    {
        $this->license_begin_date = $licenseBeginDate;

        return $this;
    }

    /**
     * Get licenseBeginDate.
     *
     * @return string
     */
    public function getLicenseBeginDate()
    {
        return $this->license_begin_date;
    }

    /**
     * Set licenseEndDate.
     *
     * @param string|null $licenseEndDate
     *
     * @return UserEnt
     */
    public function setLicenseEndDate($licenseEndDate = null)
    {
        $this->license_end_date = $licenseEndDate;

        return $this;
    }

    /**
     * Get licenseEndDate.
     *
     * @return string|null
     */
    public function getLicenseEndDate()
    {
        return $this->license_end_date;
    }

    /**
     * Set regProvId.
     *
     * @param string|null $regProvId
     *
     * @return UserEnt
     */
    public function setRegProvId($regProvId = null)
    {
        $this->reg_prov_id = $regProvId;

        return $this;
    }

    /**
     * Get regProvId.
     *
     * @return string|null
     */
    public function getRegProvId()
    {
        return $this->reg_prov_id;
    }

    /**
     * Set regAreaId.
     *
     * @param string $regAreaId
     *
     * @return UserEnt
     */
    public function setRegAreaId($regAreaId)
    {
        $this->reg_area_id = $regAreaId;

        return $this;
    }

    /**
     * Get regAreaId.
     *
     * @return string
     */
    public function getRegAreaId()
    {
        return $this->reg_area_id;
    }

    /**
     * Set regDistrictId.
     *
     * @param string $regDistrictId
     *
     * @return UserEnt
     */
    public function setRegDistrictId($regDistrictId)
    {
        $this->reg_district_id = $regDistrictId;

        return $this;
    }

    /**
     * Get regDistrictId.
     *
     * @return string
     */
    public function getRegDistrictId()
    {
        return $this->reg_district_id;
    }

    /**
     * Set regDetail.
     *
     * @param string $regDetail
     *
     * @return UserEnt
     */
    public function setRegDetail($regDetail)
    {
        $this->reg_detail = $regDetail;

        return $this;
    }

    /**
     * Get regDetail.
     *
     * @return string
     */
    public function getRegDetail()
    {
        return $this->reg_detail;
    }

    /**
     * Set legalName.
     *
     * @param string $legalName
     *
     * @return UserEnt
     */
    public function setLegalName($legalName)
    {
        $this->legal_name = $legalName;

        return $this;
    }

    /**
     * Get legalName.
     *
     * @return string
     */
    public function getLegalName()
    {
        return $this->legal_name;
    }

    /**
     * Set legalCertNo.
     *
     * @param string $legalCertNo
     *
     * @return UserEnt
     */
    public function setLegalCertNo($legalCertNo)
    {
        $this->legal_cert_no = $legalCertNo;

        return $this;
    }

    /**
     * Get legalCertNo.
     *
     * @return string
     */
    public function getLegalCertNo()
    {
        return $this->legal_cert_no;
    }

    /**
     * Set legalCertValidityType.
     *
     * @param int|null $legalCertValidityType
     *
     * @return UserEnt
     */
    public function setLegalCertValidityType($legalCertValidityType = null)
    {
        $this->legal_cert_validity_type = $legalCertValidityType;

        return $this;
    }

    /**
     * Get legalCertValidityType.
     *
     * @return int|null
     */
    public function getLegalCertValidityType()
    {
        return $this->legal_cert_validity_type;
    }

    /**
     * Set legalCertBeginDate.
     *
     * @param string $legalCertBeginDate
     *
     * @return UserEnt
     */
    public function setLegalCertBeginDate($legalCertBeginDate)
    {
        $this->legal_cert_begin_date = $legalCertBeginDate;

        return $this;
    }

    /**
     * Get legalCertBeginDate.
     *
     * @return string
     */
    public function getLegalCertBeginDate()
    {
        return $this->legal_cert_begin_date;
    }

    /**
     * Set legalCertEndDate.
     *
     * @param string|null $legalCertEndDate
     *
     * @return UserEnt
     */
    public function setLegalCertEndDate($legalCertEndDate = null)
    {
        $this->legal_cert_end_date = $legalCertEndDate;

        return $this;
    }

    /**
     * Get legalCertEndDate.
     *
     * @return string|null
     */
    public function getLegalCertEndDate()
    {
        return $this->legal_cert_end_date;
    }

    /**
     * Set contactName.
     *
     * @param string $contactName
     *
     * @return UserEnt
     */
    public function setContactName($contactName)
    {
        $this->contact_name = $contactName;

        return $this;
    }

    /**
     * Get contactName.
     *
     * @return string
     */
    public function getContactName()
    {
        return $this->contact_name;
    }

    /**
     * Set contactMobile.
     *
     * @param string $contactMobile
     *
     * @return UserEnt
     */
    public function setContactMobile($contactMobile)
    {
        $this->contact_mobile = $contactMobile;

        return $this;
    }

    /**
     * Get contactMobile.
     *
     * @return string
     */
    public function getContactMobile()
    {
        return $this->contact_mobile;
    }

    /**
     * Set entType.
     *
     * @param int $entType
     *
     * @return UserEnt
     */
    public function setEntType($entType)
    {
        $this->ent_type = $entType;

        return $this;
    }

    /**
     * Get entType.
     *
     * @return int
     */
    public function getEntType()
    {
        return $this->ent_type;
    }

    /**
     * Set auditState.
     *
     * @param string|null $auditState
     *
     * @return UserEnt
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
     * @return UserEnt
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
     * @return UserEnt
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
     * @return UserEnt
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
     * @return UserEnt
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
