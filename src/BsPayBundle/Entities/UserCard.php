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
 * UserCard 结算账户
 *
 * @ORM\Table(name="bspay_user_card", options={"comment":"用户结算账户"},
 *     indexes={
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_card_no", columns={"card_no"}, options={"lengths": {64}}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_huifu_id", columns={"huifu_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="BsPayBundle\Repositories\UserCardRepository")
 */
class UserCard
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
     * @var string
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"开户进件ID"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="req_seq_id", type="string", nullable=true, length=64, options={"comment":"请求流水号", "default": ""})
     */
    private $req_seq_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=true, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * indv 个人
     * ent 企业
     *
     * @ORM\Column(name="user_type", type="string", length=20, options={"comment":"进件类型", "default": "indv"})
     */
    private $user_type;

    /**
     * @var string
     *
     * 0 对公
     * 1 对私
     * 2 对私非法人；个人商户/用户不支持对公类型，对私非法人类型
     *
     * @ORM\Column(name="card_type", type="string", length=10, options={"comment":"结算银行卡账户类型 0：对公，1：对私，2：对私非法人；","default":0})
     */
    private $card_type;

    /**
     * @var string
     *
     * @ORM\Column(name="card_name", type="string", length=500, options={"comment":"结算银行卡持卡人姓名","default":""})
     */
    private $card_name;

    /**
     * @var string
     *
     * @ORM\Column(name="card_no", type="string", length=100, options={"comment":"结算银行卡卡号","default":""})
     */
    private $card_no;

    /**
     * @var string
     *
     * @ORM\Column(name="prov_id", type="string", length=12, options={"comment":"结算卡银行所在省", "default": ""})
     */
    private $prov_id;

    /**
     * @var string
     *
     * @ORM\Column(name="area_id", type="string", length=12, options={"comment":"结算卡银行所在市", "default": ""})
     */
    private $area_id;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_code", type="string", nullable=true, length=12, options={"comment":"结算卡银行号，对公必填","default":""})
     */
    private $bank_code;

    /**
     * @var string
     *
     * @ORM\Column(name="branch_name", type="string", nullable=true, length=100, options={"comment":"结算卡支行名称，对公时必填","default":""})
     */
    private $branch_name;

    /**
     * @var string
     *
     * @ORM\Column(name="cert_no", type="string", nullable=true, length=32, options={"comment":"结算卡持卡人身份证号，对私必填","default":""})
     */
    private $cert_no;

    /**
     * @var integer
     *
     * @ORM\Column(name="cert_validity_type", type="integer", nullable=true, options={"comment":"结算卡持卡人身份证有效期类型 1:长期有效 0:非长期有效；", "default": 0})
     */
    private $cert_validity_type = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="cert_begin_date", type="string", length=8, options={"comment":"结算卡持卡人证件有效期（起始） 日期格式：yyyyMMdd","default":""})
     */
    private $cert_begin_date;

    /**
     * @var string
     *
     * @ORM\Column(name="cert_end_date", type="string", length=8, nullable=true, options={"comment":"结算卡持卡人证件有效期（截止） 日期格式：yyyyMMdd;非长期有效时必填","default":""})
     */
    private $cert_end_date;

    /**
     * @var string
     *
     * @ORM\Column(name="mp", type="string", nullable=true, length=255, options={"comment":"结算银行卡绑定手机号","default":""})
     */
    private $mp;

    /**
     * @var string
     *
     * @ORM\Column(name="apply_no", type="string", nullable=true, length=30, options={"comment":"申请单号","default":""})
     */
    private $apply_no;

    /**
     * @var string
     *
     * A 审核中
     * B 配置成功
     * C 配置失败
     *
     * @ORM\Column(name="audit_state", type="string", nullable=true, length=50, options={"comment":"审核状态，状态包括：A-审核中；B-配置成功；C-配置失败","default":""})
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
     * Set sysId.
     *
     * @param string|null $sysId
     *
     * @return UserCard
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
     * @return UserCard
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
     * Set userId.
     *
     * @param int $userId
     *
     * @return UserCard
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set reqSeqId.
     *
     * @param string|null $reqSeqId
     *
     * @return UserCard
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
     * Set companyId.
     *
     * @param int|null $companyId
     *
     * @return UserCard
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
     * Set userType.
     *
     * @param string $userType
     *
     * @return UserCard
     */
    public function setUserType($userType)
    {
        $this->user_type = $userType;

        return $this;
    }

    /**
     * Get userType.
     *
     * @return string
     */
    public function getUserType()
    {
        return $this->user_type;
    }

    /**
     * Set cardType.
     *
     * @param string $cardType
     *
     * @return UserCard
     */
    public function setCardType($cardType)
    {
        $this->card_type = $cardType;

        return $this;
    }

    /**
     * Get cardType.
     *
     * @return string
     */
    public function getCardType()
    {
        return $this->card_type;
    }

    /**
     * Set cardName.
     *
     * @param string $cardName
     *
     * @return UserCard
     */
    public function setCardName($cardName)
    {
        $this->card_name = $cardName;

        return $this;
    }

    /**
     * Get cardName.
     *
     * @return string
     */
    public function getCardName()
    {
        return $this->card_name;
    }

    /**
     * Set cardNo.
     *
     * @param string $cardNo
     *
     * @return UserCard
     */
    public function setCardNo($cardNo)
    {
        $this->card_no = $cardNo;

        return $this;
    }

    /**
     * Get cardNo.
     *
     * @return string
     */
    public function getCardNo()
    {
        return $this->card_no;
    }

    /**
     * Set provId.
     *
     * @param string $provId
     *
     * @return UserCard
     */
    public function setProvId($provId)
    {
        $this->prov_id = $provId;

        return $this;
    }

    /**
     * Get provId.
     *
     * @return string
     */
    public function getProvId()
    {
        return $this->prov_id;
    }

    /**
     * Set areaId.
     *
     * @param string $areaId
     *
     * @return UserCard
     */
    public function setAreaId($areaId)
    {
        $this->area_id = $areaId;

        return $this;
    }

    /**
     * Get areaId.
     *
     * @return string
     */
    public function getAreaId()
    {
        return $this->area_id;
    }

    /**
     * Set bankCode.
     *
     * @param string|null $bankCode
     *
     * @return UserCard
     */
    public function setBankCode($bankCode = null)
    {
        $this->bank_code = $bankCode;

        return $this;
    }

    /**
     * Get bankCode.
     *
     * @return string|null
     */
    public function getBankCode()
    {
        return $this->bank_code;
    }

    /**
     * Set branchName.
     *
     * @param string|null $branchName
     *
     * @return UserCard
     */
    public function setBranchName($branchName = null)
    {
        $this->branch_name = $branchName;

        return $this;
    }

    /**
     * Get branchName.
     *
     * @return string|null
     */
    public function getBranchName()
    {
        return $this->branch_name;
    }

    /**
     * Set certNo.
     *
     * @param string|null $certNo
     *
     * @return UserCard
     */
    public function setCertNo($certNo = null)
    {
        $this->cert_no = $certNo;

        return $this;
    }

    /**
     * Get certNo.
     *
     * @return string|null
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
     * @return UserCard
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
     * @return UserCard
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
     * @return UserCard
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
     * Set mp.
     *
     * @param string|null $mp
     *
     * @return UserCard
     */
    public function setMp($mp = null)
    {
        $this->mp = $mp;

        return $this;
    }

    /**
     * Get mp.
     *
     * @return string|null
     */
    public function getMp()
    {
        return $this->mp;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return UserCard
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
     * @return UserCard
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
     * Set applyNo.
     *
     * @param string|null $applyNo
     *
     * @return UserCard
     */
    public function setApplyNo($applyNo = null)
    {
        $this->apply_no = $applyNo;

        return $this;
    }

    /**
     * Get applyNo.
     *
     * @return string|null
     */
    public function getApplyNo()
    {
        return $this->apply_no;
    }

    /**
     * Set auditState.
     *
     * @param string|null $auditState
     *
     * @return UserCard
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
     * @return UserCard
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
     * @return UserCard
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
}
