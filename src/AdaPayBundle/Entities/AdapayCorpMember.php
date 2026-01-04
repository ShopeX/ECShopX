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
 * AdapayCorpMember 企业用户对象
 *
 * @ORM\Table(name="adapay_corp_member", options={"comment":"企业用户对象"},
 *     indexes={
 *         @ORM\Index(name="idx_order_no", columns={"order_no"}),
 *         @ORM\Index(name="idx_name", columns={"name"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_card_no", columns={"card_no"}, options={"lengths": {64}}),
 *         @ORM\Index(name="idx_member_id", columns={"member_id"})
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayCorpMemberRepository")
 */
class AdapayCorpMember
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
     * @ORM\Column(name="app_id", type="string", length=100, options={"comment":"应用app_id", "default": ""})
     */
    private $app_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_no", type="string", length=64, options={"comment":"请求订单号", "default": ""})
     */
    private $order_no;

    /**
     * @var integer
     *
     * @ORM\Column(name="member_id", type="bigint", options={"comment":"member_id"})
     */
    private $member_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="dealer_id", type="bigint", nullable=true, options={"comment":"经销商id", "default": 0})
     */
    private $dealer_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", nullable=true, options={"comment":"店铺id", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", nullable=true, type="integer", options={"comment":"操作者id", "default": 0})
     */
    private $operator_id = 0;


    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, options={"comment":"企业名称", "default": ""})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="prov_code", nullable=true, type="string", length=10, options={"comment":"省份编码", "default": ""})
     */
    private $prov_code;

    /**
     * @var string
     *
     * @ORM\Column(name="area_code", type="string", length=10, options={"comment":"地区编码", "default": ""})
     */
    private $area_code;

    /**
     * @var string
     *
     * @ORM\Column(name="social_credit_code", type="string", length=32, options={"comment":"统一社会信用码","default":""})
     */
    private $social_credit_code;

    /**
     * @var string
     *
     * @ORM\Column(name="social_credit_code_expires", type="string", length=32, options={"comment":"统一社会信用证有效期","default":""})
     */
    private $social_credit_code_expires;

    /**
     * @var string
     *
     * @ORM\Column(name="business_scope", type="string", length=800, options={"comment":"经营范围","default":""})
     */
    private $business_scope;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_person", type="string", length=500, options={"comment":"法人姓名","default":""})
     */
    private $legal_person;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_cert_id", type="string", length=255, options={"comment":"法人身份证号码","default":""})
     */
    private $legal_cert_id;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_cert_id_expires", type="string", length=16, options={"comment":"法人身份证有效期","default":""})
     */
    private $legal_cert_id_expires;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_mp", type="string", length=255, options={"comment":"法人手机号","default":""})
     */
    private $legal_mp;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=300, options={"comment":"企业地址","default":""})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="zip_code", type="string", nullable=true, length=10, options={"comment":"邮编","default":""})
     */
    private $zip_code;

    /**
     * @var string
     *
     * @ORM\Column(name="telphone", type="string", nullable=true, length=30, options={"comment":"企业电话","default":""})
     */
    private $telphone;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=true, length=100, options={"comment":"企业邮箱","default":""})
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="attach_file", type="string", nullable=true, length=300, options={"comment":"上传附件","default":""})
     */
    private $attach_file;

    /**
     * @var string
     *
     * @ORM\Column(name="attach_file_name", type="string", nullable=true, length=300, options={"comment":"附件文件名","default":""})
     */
    private $attach_file_name;

    /**
     * @var string
     *
     * @ORM\Column(name="confirm_letter_file", type="string", nullable=true, length=300, options={"comment":"经销商确认函附件","default":""})
     */
    private $confirm_letter_file;

    /**
     * @var string
     *
     * @ORM\Column(name="confirm_letter_file_name", type="string", nullable=true, length=300, options={"comment":"经销商确认函附件文件名","default":""})
     */
    private $confirm_letter_file_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_code", type="string", nullable=true, length=30, options={"comment":"银行代码，如果需要自动开结算账户，本字段必填","default":""})
     */
    private $bank_code;

    /**
     * @var string
     *
     * 1 对公
     * 2 对私
     *
     * @ORM\Column(name="bank_acct_type", type="string", length=10, options={"comment":"银行账户类型：1-对公；2-对私，如果需要自动开结算账户，本字段必填","default":""})
     */
    private $bank_acct_type;

    /**
     * @var string
     *
     * @ORM\Column(name="card_no", type="string", length=255, options={"comment":"银行卡号，如果需要自动开结算账户，本字段必填","default":""})
     */
    private $card_no;

    /**
     * @var string
     *
     * @ORM\Column(name="card_name", type="string", length=100, options={"comment":"银行卡对应的户名，如果需要自动开结算账户，本字段必填；若银行账户类型是对公，必须与企业名称一致","default":""})
     */
    private $card_name;

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
     * pending 交易处理中
     * succeeded 交易成功
     * failed 交易失败
     *
     * @ORM\Column(name="status", type="string", nullable=true, length=50, options={"comment":"当前交易状态","default":""})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="error_info", type="string", nullable=true, length=500, options={"comment":"错误描述","default":""})
     */
    private $error_info;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $update_time;


    /**
     * Set id.
     *
     * @param int $id
     *
     * @return AdapayCorpMember
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set appId.
     *
     * @param string $appId
     *
     * @return AdapayCorpMember
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
        // Ver: 8d1abe8e
        return $this->app_id;
    }

    /**
     * Set dealerId.
     *
     * @param int|null $distributorId
     *
     * @return adapayCashRecord
     */
    public function setDealerId($dealerId = null)
    {
        $this->dealer_id = $dealerId;

        return $this;
    }

    /**
     * Get dealerId.
     *
     * @return int|null
     */
    public function getDealerId()
    {
        return $this->dealer_id;
    }

    /**
     * Set distributorId.
     *
     * @param int|null $distributorId
     *
     * @return adapayCashRecord
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
     * Set orderNo.
     *
     * @param string $orderNo
     *
     * @return AdapayCorpMember
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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return AdapayCorpMember
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
     * Set name.
     *
     * @param string $name
     *
     * @return AdapayCorpMember
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
     * Set provCode.
     *
     * @param string|null $provCode
     *
     * @return AdapayCorpMember
     */
    public function setProvCode($provCode = null)
    {
        $this->prov_code = $provCode;

        return $this;
    }

    /**
     * Get provCode.
     *
     * @return string|null
     */
    public function getProvCode()
    {
        return $this->prov_code;
    }

    /**
     * Set areaCode.
     *
     * @param string $areaCode
     *
     * @return AdapayCorpMember
     */
    public function setAreaCode($areaCode)
    {
        $this->area_code = $areaCode;

        return $this;
    }

    /**
     * Get areaCode.
     *
     * @return string
     */
    public function getAreaCode()
    {
        return $this->area_code;
    }

    /**
     * Set socialCreditCode.
     *
     * @param string $socialCreditCode
     *
     * @return AdapayCorpMember
     */
    public function setSocialCreditCode($socialCreditCode)
    {
        $this->social_credit_code = $socialCreditCode;

        return $this;
    }

    /**
     * Get socialCreditCode.
     *
     * @return string
     */
    public function getSocialCreditCode()
    {
        return $this->social_credit_code;
    }

    /**
     * Set socialCreditCodeExpires.
     *
     * @param string $socialCreditCodeExpires
     *
     * @return AdapayCorpMember
     */
    public function setSocialCreditCodeExpires($socialCreditCodeExpires)
    {
        $this->social_credit_code_expires = $socialCreditCodeExpires;

        return $this;
    }

    /**
     * Get socialCreditCodeExpires.
     *
     * @return string
     */
    public function getSocialCreditCodeExpires()
    {
        return $this->social_credit_code_expires;
    }

    /**
     * Set businessScope.
     *
     * @param string $businessScope
     *
     * @return AdapayCorpMember
     */
    public function setBusinessScope($businessScope)
    {
        $this->business_scope = $businessScope;

        return $this;
    }

    /**
     * Get businessScope.
     *
     * @return string
     */
    public function getBusinessScope()
    {
        return $this->business_scope;
    }

    /**
     * Set legalPerson.
     *
     * @param string $legalPerson
     *
     * @return AdapayCorpMember
     */
    public function setLegalPerson($legalPerson)
    {
        $this->legal_person = fixedencrypt($legalPerson);

        return $this;
    }

    /**
     * Get legalPerson.
     *
     * @return string
     */
    public function getLegalPerson()
    {
        return fixeddecrypt($this->legal_person);
    }

    /**
     * Set legalCertId.
     *
     * @param string $legalCertId
     *
     * @return AdapayCorpMember
     */
    public function setLegalCertId($legalCertId)
    {
        $this->legal_cert_id = fixedencrypt($legalCertId);

        return $this;
    }

    /**
     * Get legalCertId.
     *
     * @return string
     */
    public function getLegalCertId()
    {
        return fixeddecrypt($this->legal_cert_id);
    }

    /**
     * Set legalCertIdExpires.
     *
     * @param string $legalCertIdExpires
     *
     * @return AdapayCorpMember
     */
    public function setLegalCertIdExpires($legalCertIdExpires)
    {
        $this->legal_cert_id_expires = $legalCertIdExpires;

        return $this;
    }

    /**
     * Get legalCertIdExpires.
     *
     * @return string
     */
    public function getLegalCertIdExpires()
    {
        return $this->legal_cert_id_expires;
    }


    /**
     * Set address.
     *
     * @param string $address
     *
     * @return AdapayCorpMember
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set zipCode.
     *
     * @param string $zipCode
     *
     * @return AdapayCorpMember
     */
    public function setZipCode($zipCode)
    {
        $this->zip_code = $zipCode;

        return $this;
    }

    /**
     * Get zipCode.
     *
     * @return string
     */
    public function getZipCode()
    {
        return $this->zip_code;
    }

    /**
     * Set telphone.
     *
     * @param string $telphone
     *
     * @return AdapayCorpMember
     */
    public function setTelphone($telphone)
    {
        $this->telphone = $telphone;

        return $this;
    }

    /**
     * Get telphone.
     *
     * @return string
     */
    public function getTelphone()
    {
        return $this->telphone;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return AdapayCorpMember
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set attachFile.
     *
     * @param string $attachFile
     *
     * @return AdapayCorpMember
     */
    public function setAttachFile($attachFile)
    {
        $this->attach_file = $attachFile;

        return $this;
    }

    /**
     * Get attachFile.
     *
     * @return string
     */
    public function getAttachFile()
    {
        return $this->attach_file;
    }

    /**
     * Set attachFileName.
     *
     * @param string $attachFileName
     *
     * @return AdapayCorpMember
     */
    public function setAttachFileName($attachFileName)
    {
        $this->attach_file_name = $attachFileName;

        return $this;
    }

    /**
     * Get attachFileName.
     *
     * @return string
     */
    public function getAttachFileName()
    {
        return $this->attach_file_name;
    }

    /**
     * Set bankCode.
     *
     * @param string $bankCode
     *
     * @return AdapayCorpMember
     */
    public function setBankCode($bankCode)
    {
        $this->bank_code = $bankCode;

        return $this;
    }

    /**
     * Get bankCode.
     *
     * @return string
     */
    public function getBankCode()
    {
        return $this->bank_code;
    }

    /**
     * Set bankAcctType.
     *
     * @param string $bankAcctType
     *
     * @return AdapayCorpMember
     */
    public function setBankAcctType($bankAcctType)
    {
        $this->bank_acct_type = $bankAcctType;

        return $this;
    }

    /**
     * Get bankAcctType.
     *
     * @return string
     */
    public function getBankAcctType()
    {
        return $this->bank_acct_type;
    }

    /**
     * Set cardNo.
     *
     * @param string $cardNo
     *
     * @return AdapayCorpMember
     */
    public function setCardNo($cardNo)
    {
        $this->card_no = fixedencrypt($cardNo);

        return $this;
    }

    /**
     * Get cardNo.
     *
     * @return string
     */
    public function getCardNo()
    {
        return fixeddecrypt($this->card_no);
    }

    /**
     * Set cardName.
     *
     * @param string $cardName
     *
     * @return AdapayCorpMember
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
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapayCorpMember
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
     * @return AdapayCorpMember
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
     * Set legalMp.
     *
     * @param string $legalMp
     *
     * @return AdapayCorpMember
     */
    public function setLegalMp($legalMp)
    {
        $this->legal_mp = fixedencrypt($legalMp);

        return $this;
    }

    /**
     * Get legalMp.
     *
     * @return string
     */
    public function getLegalMp()
    {
        return fixeddecrypt($this->legal_mp);
    }

    /**
     * Set auditState.
     *
     * @param string $auditState
     *
     * @return AdapayCorpMember
     */
    public function setAuditState($auditState)
    {
        $this->audit_state = $auditState;

        return $this;
    }

    /**
     * Get auditState.
     *
     * @return string
     */
    public function getAuditState()
    {
        return $this->audit_state;
    }

    /**
     * Set auditDesc.
     *
     * @param string $auditDesc
     *
     * @return AdapayCorpMember
     */
    public function setAuditDesc($auditDesc)
    {
        $this->audit_desc = $auditDesc;

        return $this;
    }

    /**
     * Get auditDesc.
     *
     * @return string
     */
    public function getAuditDesc()
    {
        return $this->audit_desc;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return AdapayCorpMember
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
     * Set errorInfo.
     *
     * @param string $errorInfo
     *
     * @return AdapayCorpMember
     */
    public function setErrorInfo($errorInfo)
    {
        $this->error_info = $errorInfo;

        return $this;
    }

    /**
     * Get errorInfo.
     *
     * @return string
     */
    public function getErrorInfo()
    {
        return $this->error_info;
    }

    /**
     * Set memberId.
     *
     * @param int $memberId
     *
     * @return AdapayCorpMember
     */
    public function setMemberId($memberId)
    {
        $this->member_id = $memberId;

        return $this;
    }

    /**
     * Get memberId.
     *
     * @return int
     */
    public function getMemberId()
    {
        return $this->member_id;
    }

    /**
     * Set confirmLetterFile.
     *
     * @param string|null $confirmLetterFile
     *
     * @return AdapayCorpMember
     */
    public function setConfirmLetterFile($confirmLetterFile = null)
    {
        $this->confirm_letter_file = $confirmLetterFile;

        return $this;
    }

    /**
     * Get confirmLetterFile.
     *
     * @return string|null
     */
    public function getConfirmLetterFile()
    {
        return $this->confirm_letter_file;
    }

    /**
     * Set confirmLetterFileName.
     *
     * @param string|null $confirmLetterFileName
     *
     * @return AdapayCorpMember
     */
    public function setConfirmLetterFileName($confirmLetterFileName = null)
    {
        $this->confirm_letter_file_name = $confirmLetterFileName;

        return $this;
    }

    /**
     * Get confirmLetterFileName.
     *
     * @return string|null
     */
    public function getConfirmLetterFileName()
    {
        return $this->confirm_letter_file_name;
    }

    /**
     * Set operatorId.
     *
     * @param int|null $operatorId
     *
     * @return AdapayCorpMember
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
