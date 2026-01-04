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

namespace MerchantBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MerchantSettlementApply 商户入驻申请表
 *
 * @ORM\Table(name="merchant_settlement_apply", options={"comment":"商户入驻申请表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_audit_status", columns={"audit_status"}),
 *         @ORM\Index(name="idx_settled_type", columns={"settled_type"}),
 *         @ORM\Index(name="idx_source", columns={"source"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="MerchantBundle\Repositories\MerchantSettlementApplyRepository")
 */
class MerchantSettlementApply
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
     * @ORM\Column(name="company_id", type="bigint", nullable=true, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_agree_agreement", type="boolean", options={"comment":"是否同意入驻协议", "default":false})
     */
    private $is_agree_agreement;

    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_type_id", type="bigint", options={"comment":"商户类型ID","default":0})
     */
    private $merchant_type_id = 0;

    /**
    * @var string
    *
    * @ORM\Column(name="settled_type", type="string", nullable=true, options={"comment":"入驻类型。enterprise:企业;soletrader:个体户", "default": ""})
    */
    private $settled_type;

    /**
     * @var string
     *
     * @ORM\Column(name="merchant_name", type="string", nullable=true, options={"comment":"商户名称"})
     */
    private $merchant_name;

    /**
     * @var string
     *
     * @ORM\Column(name="social_credit_code_id", type="string", nullable=true, options={"comment":"统一社会信用代码"})
     */
    private $social_credit_code_id;

    /**
     * @var string
     *
     * @ORM\Column(name="province", type="string", length=50, nullable=true)
     */
    private $province;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=50, nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="area", type="string", length=50, nullable=true)
     */
    private $area;

    /**
     * @var string
     *
     * @ORM\Column(name="regions_id", type="text", nullable=true, options={"comment":"国家行政区划编码组合，逗号隔开"})
     */
    private $regions_id;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", nullable=true, options={"comment":"详细地址"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_name", type="string", nullable=true, length=50, options={"comment":"法人姓名"})
     */
    private $legal_name;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_cert_id", type="string", nullable=true, length=255, options={"comment":"法人身份证号码","default":""})
     */
    private $legal_cert_id;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_mobile", type="string", nullable=true, options={"comment":"法人手机号码"})
     */
    private $legal_mobile;

    /**
     * @var string
     *
     * 1 对公
     * 2 对私
     *
     * @ORM\Column(name="bank_acct_type", type="string", nullable=true, length=10, options={"comment":"银行账户类型：1-对公；2-对私","default":""})
     */
    private $bank_acct_type;

    /**
     * @var string
     *
     * @ORM\Column(name="card_id_mask", type="string", nullable=true, options={"comment":"结算银行卡号"})
     */
    private $card_id_mask;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", type="string", nullable=true, length=100, options={"comment":"结算银行卡所属银行名称"})
     */
    private $bank_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_mobile", type="string", nullable=true, options={"comment":"银行预留手机号"})
     */
    private $bank_mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="license_url", type="string", nullable=true, options={"comment"="营业执照图片url"})
     */
    private $license_url;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_certid_front_url", type="string", nullable=true, options={"comment"="法人手持身份证正面url"})
     */
    private $legal_certid_front_url;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_cert_id_back_url", type="string", nullable=true, options={"comment"="法人手持身份证反面url"})
     */
    private $legal_cert_id_back_url;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_card_front_url", type="string", nullable=true, options={"comment"="结算银行卡正面url"})
     */
    private $bank_card_front_url;

    /**
     * @var string
     * @ORM\Column(name="audit_status", type="string", nullable=true, length=10, options={"comment":"审核状态：1:审核中 2:审核成功 3:审核驳回","default":"1"})
     */
    private $audit_status = "1";

    /**
     * @var string
     * @ORM\Column(name="audit_memo", type="string", nullable=true,  length=500, options={"comment":"审核备注"})
     */
    private $audit_memo;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", nullable=true, length=30, options={"comment":"来源 admin:平台管理员;h5:h5入驻;", "default":"h5"})
     */
    private $source = "h5";

    /**
     * @var boolean
     * @ORM\Column(name="audit_goods", type="boolean", options={"comment":"是否需要平台审核商品 0:不需要 1:需要","default":1})
     */
    private $audit_goods = 1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"禁用", "default": 0})
     */
    private $disabled = 0;

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
     * @ORM\Column(type="integer", nullable=true)
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
     * @param int|null $companyId
     *
     * @return MerchantSettlementApply
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
     * Set mobile.
     *
     * @param string $mobile
     *
     * @return MerchantSettlementApply
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set isAgreeAgreement.
     *
     * @param bool $isAgreeAgreement
     *
     * @return MerchantSettlementApply
     */
    public function setIsAgreeAgreement($isAgreeAgreement)
    {
        $this->is_agree_agreement = $isAgreeAgreement;

        return $this;
    }

    /**
     * Get isAgreeAgreement.
     *
     * @return bool
     */
    public function getIsAgreeAgreement()
    {
        return $this->is_agree_agreement;
    }

    /**
     * Set merchantTypeId.
     *
     * @param int $merchantTypeId
     *
     * @return MerchantSettlementApply
     */
    public function setMerchantTypeId($merchantTypeId)
    {
        $this->merchant_type_id = $merchantTypeId;

        return $this;
    }

    /**
     * Get merchantTypeId.
     *
     * @return int
     */
    public function getMerchantTypeId()
    {
        return $this->merchant_type_id;
    }

    /**
     * Set settledType.
     *
     * @param string|null $settledType
     *
     * @return MerchantSettlementApply
     */
    public function setSettledType($settledType = null)
    {
        $this->settled_type = $settledType;

        return $this;
    }

    /**
     * Get settledType.
     *
     * @return string|null
     */
    public function getSettledType()
    {
        return $this->settled_type;
    }

    /**
     * Set merchantName.
     *
     * @param string|null $merchantName
     *
     * @return MerchantSettlementApply
     */
    public function setMerchantName($merchantName = null)
    {
        $this->merchant_name = $merchantName;

        return $this;
    }

    /**
     * Get merchantName.
     *
     * @return string|null
     */
    public function getMerchantName()
    {
        return $this->merchant_name;
    }

    /**
     * Set socialCreditCodeId.
     *
     * @param string|null $socialCreditCodeId
     *
     * @return MerchantSettlementApply
     */
    public function setSocialCreditCodeId($socialCreditCodeId = null)
    {
        $this->social_credit_code_id = $socialCreditCodeId;

        return $this;
    }

    /**
     * Get socialCreditCodeId.
     *
     * @return string|null
     */
    public function getSocialCreditCodeId()
    {
        return $this->social_credit_code_id;
    }

    /**
     * Set province.
     *
     * @param string|null $province
     *
     * @return MerchantSettlementApply
     */
    public function setProvince($province = null)
    {
        $this->province = $province;

        return $this;
    }

    /**
     * Get province.
     *
     * @return string|null
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * Set city.
     *
     * @param string|null $city
     *
     * @return MerchantSettlementApply
     */
    public function setCity($city = null)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set area.
     *
     * @param string|null $area
     *
     * @return MerchantSettlementApply
     */
    public function setArea($area = null)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * Get area.
     *
     * @return string|null
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * Set regionsId.
     *
     * @param string|null $regionsId
     *
     * @return MerchantSettlementApply
     */
    public function setRegionsId($regionsId = null)
    {
        $this->regions_id = $regionsId;

        return $this;
    }

    /**
     * Get regionsId.
     *
     * @return string|null
     */
    public function getRegionsId()
    {
        return $this->regions_id;
    }

    /**
     * Set address.
     *
     * @param string|null $address
     *
     * @return MerchantSettlementApply
     */
    public function setAddress($address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set legalName.
     *
     * @param string|null $legalName
     *
     * @return MerchantSettlementApply
     */
    public function setLegalName($legalName = null)
    {
        $this->legal_name = $legalName;

        return $this;
    }

    /**
     * Get legalName.
     *
     * @return string|null
     */
    public function getLegalName()
    {
        return $this->legal_name;
    }

    /**
     * Set legalCertId.
     *
     * @param string|null $legalCertId
     *
     * @return MerchantSettlementApply
     */
    public function setLegalCertId($legalCertId = null)
    {
        $this->legal_cert_id = fixedencrypt($legalCertId);

        return $this;
    }

    /**
     * Get legalCertId.
     *
     * @return string|null
     */
    public function getLegalCertId()
    {
        return fixeddecrypt($this->legal_cert_id);
    }

    /**
     * Set legalMobile.
     *
     * @param string|null $legalMobile
     *
     * @return MerchantSettlementApply
     */
    public function setLegalMobile($legalMobile = null)
    {
        $this->legal_mobile = fixedencrypt($legalMobile);

        return $this;
    }

    /**
     * Get legalMobile.
     *
     * @return string|null
     */
    public function getLegalMobile()
    {
        return fixeddecrypt($this->legal_mobile);
    }

    /**
     * Set bankAcctType.
     *
     * @param string|null $bankAcctType
     *
     * @return MerchantSettlementApply
     */
    public function setBankAcctType($bankAcctType = null)
    {
        $this->bank_acct_type = $bankAcctType;

        return $this;
    }

    /**
     * Get bankAcctType.
     *
     * @return string|null
     */
    public function getBankAcctType()
    {
        return $this->bank_acct_type;
    }

    /**
     * Set cardIdMask.
     *
     * @param string|null $cardIdMask
     *
     * @return MerchantSettlementApply
     */
    public function setCardIdMask($cardIdMask = null)
    {
        $this->card_id_mask = fixedencrypt($cardIdMask);

        return $this;
    }

    /**
     * Get cardIdMask.
     *
     * @return string|null
     */
    public function getCardIdMask()
    {
        return fixeddecrypt($this->card_id_mask);
    }

    /**
     * Set bankName.
     *
     * @param string|null $bankName
     *
     * @return MerchantSettlementApply
     */
    public function setBankName($bankName = null)
    {
        $this->bank_name = $bankName;

        return $this;
    }

    /**
     * Get bankName.
     *
     * @return string|null
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * Set bankMobile.
     *
     * @param string|null $bankMobile
     *
     * @return MerchantSettlementApply
     */
    public function setBankMobile($bankMobile = null)
    {
        $this->bank_mobile = fixedencrypt($bankMobile);

        return $this;
    }

    /**
     * Get bankMobile.
     *
     * @return string|null
     */
    public function getBankMobile()
    {
        return fixeddecrypt($this->bank_mobile);
    }

    /**
     * Set licenseUrl.
     *
     * @param string|null $licenseUrl
     *
     * @return MerchantSettlementApply
     */
    public function setLicenseUrl($licenseUrl = null)
    {
        $this->license_url = $licenseUrl;

        return $this;
    }

    /**
     * Get licenseUrl.
     *
     * @return string|null
     */
    public function getLicenseUrl()
    {
        return $this->license_url;
    }

    /**
     * Set legalCertidFrontUrl.
     *
     * @param string|null $legalCertidFrontUrl
     *
     * @return MerchantSettlementApply
     */
    public function setLegalCertidFrontUrl($legalCertidFrontUrl = null)
    {
        $this->legal_certid_front_url = $legalCertidFrontUrl;

        return $this;
    }

    /**
     * Get legalCertidFrontUrl.
     *
     * @return string|null
     */
    public function getLegalCertidFrontUrl()
    {
        return $this->legal_certid_front_url;
    }

    /**
     * Set legalCertIdBackUrl.
     *
     * @param string|null $legalCertIdBackUrl
     *
     * @return MerchantSettlementApply
     */
    public function setLegalCertIdBackUrl($legalCertIdBackUrl = null)
    {
        $this->legal_cert_id_back_url = $legalCertIdBackUrl;

        return $this;
    }

    /**
     * Get legalCertIdBackUrl.
     *
     * @return string|null
     */
    public function getLegalCertIdBackUrl()
    {
        return $this->legal_cert_id_back_url;
    }

    /**
     * Set bankCardFrontUrl.
     *
     * @param string|null $bankCardFrontUrl
     *
     * @return MerchantSettlementApply
     */
    public function setBankCardFrontUrl($bankCardFrontUrl = null)
    {
        $this->bank_card_front_url = $bankCardFrontUrl;

        return $this;
    }

    /**
     * Get bankCardFrontUrl.
     *
     * @return string|null
     */
    public function getBankCardFrontUrl()
    {
        return $this->bank_card_front_url;
    }

    /**
     * Set auditStatus.
     *
     * @param string|null $auditStatus
     *
     * @return MerchantSettlementApply
     */
    public function setAuditStatus($auditStatus = null)
    {
        $this->audit_status = $auditStatus;

        return $this;
    }

    /**
     * Get auditStatus.
     *
     * @return string|null
     */
    public function getAuditStatus()
    {
        return $this->audit_status;
    }

    /**
     * Set auditMemo.
     *
     * @param string|null $auditMemo
     *
     * @return MerchantSettlementApply
     */
    public function setAuditMemo($auditMemo = null)
    {
        $this->audit_memo = $auditMemo;

        return $this;
    }

    /**
     * Get auditMemo.
     *
     * @return string|null
     */
    public function getAuditMemo()
    {
        return $this->audit_memo;
    }

    /**
     * Set source.
     *
     * @param string|null $source
     *
     * @return MerchantSettlementApply
     */
    public function setSource($source = null)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set auditGoods.
     *
     * @param bool $auditGoods
     *
     * @return MerchantSettlementApply
     */
    public function setAuditGoods($auditGoods)
    {
        $this->audit_goods = $auditGoods;

        return $this;
    }

    /**
     * Get auditGoods.
     *
     * @return bool
     */
    public function getAuditGoods()
    {
        return $this->audit_goods;
    }

    /**
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return MerchantSettlementApply
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled.
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return MerchantSettlementApply
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
     * @return MerchantSettlementApply
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
