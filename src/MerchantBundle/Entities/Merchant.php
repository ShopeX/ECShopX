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
 * Merchant 商户表
 *
 * @ORM\Table(name="merchant", options={"comment":"商户表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_settlement_apply_id", columns={"settlement_apply_id"}),
 *         @ORM\Index(name="idx_legal_mobile", columns={"legal_mobile"}, options={"lengths": {64}})
 *     },
 * )
 * @ORM\Entity(repositoryClass="MerchantBundle\Repositories\MerchantRepository")
 */
class Merchant
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
     * @var integer
     *
     * @ORM\Column(name="settlement_apply_id", type="bigint", nullable=true, options={"comment":"入驻申请id"})
     */
    private $settlement_apply_id;

    /**
     * @var string
     *
     * @ORM\Column(name="merchant_name", type="string", nullable=true, options={"comment":"商户名称"})
     */
    private $merchant_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_type_id", type="bigint", options={"comment":"商户类型ID"})
     */
    private $merchant_type_id;

    /**
    * @var string
    *
    * @ORM\Column(name="settled_type", type="string", options={"comment":"商户入驻类型。enterprise:企业;soletrader:个体户", "default": ""})
    */
    private $settled_type;

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
     * @ORM\Column(name="legal_name", type="string", length=500, options={"comment":"联系人"})
     */
    private $legal_name;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_cert_id", type="string", length=255, options={"comment":"法人身份证号码","default":""})
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
     * @ORM\Column(name="email", type="string", nullable=true, options={"comment":"联系邮箱"})
     */
    private $email;

    /**
     * @var string
     *
     * 1 对公
     * 2 对私
     *
     * @ORM\Column(name="bank_acct_type", type="string", length=10, options={"comment":"银行账户类型：1-对公；2-对私","default":""})
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
     *
     * @ORM\Column(name="contract_url", type="string", nullable=true, options={"comment"="合同url"})
     */
    private $contract_url;

    /**
     * @var string
     * @ORM\Column(name="settled_succ_sendsms", type="string", length=10, options={"comment":"入驻成功发送时间  1:立即 2:商家H5确认入驻协议后","default":"1"})
     */
    private $settled_succ_sendsms = "1";

    /**
     * @var boolean
     * @ORM\Column(name="audit_goods", type="boolean", options={"comment":"是否需要平台审核商品 0:不需要 1:需要","default":1})
     */
    private $audit_goods = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", nullable=true, length=30, options={"comment":"来源 admin:平台管理员;h5:h5入驻;", "default":"h5"})
     */
    private $source = "h5";

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
     * @return Merchant
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
     * Set settlementApplyId.
     *
     * @param int|null $settlementApplyId
     *
     * @return Merchant
     */
    public function setSettlementApplyId($settlementApplyId = null)
    {
        $this->settlement_apply_id = $settlementApplyId;

        return $this;
    }

    /**
     * Get settlementApplyId.
     *
     * @return int|null
     */
    public function getSettlementApplyId()
    {
        return $this->settlement_apply_id;
    }

    /**
     * Set merchantName.
     *
     * @param string|null $merchantName
     *
     * @return Merchant
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
     * Set merchantTypeId.
     *
     * @param int $merchantTypeId
     *
     * @return Merchant
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
     * @param string $settledType
     *
     * @return Merchant
     */
    public function setSettledType($settledType)
    {
        $this->settled_type = $settledType;

        return $this;
    }

    /**
     * Get settledType.
     *
     * @return string
     */
    public function getSettledType()
    {
        return $this->settled_type;
    }

    /**
     * Set socialCreditCodeId.
     *
     * @param string|null $socialCreditCodeId
     *
     * @return Merchant
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
     * @return Merchant
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
     * @return Merchant
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
     * @return Merchant
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
     * @return Merchant
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
     * @return Merchant
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
     * @param string $legalName
     *
     * @return Merchant
     */
    public function setLegalName($legalName)
    {
        $this->legal_name = fixedencrypt($legalName);

        return $this;
    }

    /**
     * Get legalName.
     *
     * @return string
     */
    public function getLegalName()
    {
        return fixeddecrypt($this->legal_name);
    }

    /**
     * Set legalCertId.
     *
     * @param string $legalCertId
     *
     * @return Merchant
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
     * Set legalMobile.
     *
     * @param string|null $legalMobile
     *
     * @return Merchant
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
     * Set email.
     *
     * @param string|null $email
     *
     * @return Merchant
     */
    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set bankAcctType.
     *
     * @param string $bankAcctType
     *
     * @return Merchant
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
     * Set cardIdMask.
     *
     * @param string $cardIdMask
     *
     * @return Merchant
     */
    public function setCardIdMask($cardIdMask)
    {
        $this->card_id_mask = $cardIdMask;

        return $this;
    }

    /**
     * Get cardIdMask.
     *
     * @return string
     */
    public function getCardIdMask()
    {
        return $this->card_id_mask;
    }

    /**
     * Set bankName.
     *
     * @param string $bankName
     *
     * @return Merchant
     */
    public function setBankName($bankName = null)
    {
        $this->bank_name = $bankName;

        return $this;
    }

    /**
     * Get bankName.
     *
     * @return string
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
     * @return Merchant
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
     * @return Merchant
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
     * @return Merchant
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
     * @return Merchant
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
     * @return Merchant
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
     * Set contractUrl.
     *
     * @param string|null $contractUrl
     *
     * @return Merchant
     */
    public function setContractUrl($contractUrl = null)
    {
        $this->contract_url = $contractUrl;

        return $this;
    }

    /**
     * Get contractUrl.
     *
     * @return string|null
     */
    public function getContractUrl()
    {
        return $this->contract_url;
    }

    /**
     * Set settledSuccSendsms.
     *
     * @param string $settledSuccSendsms
     *
     * @return Merchant
     */
    public function setSettledSuccSendsms($settledSuccSendsms)
    {
        $this->settled_succ_sendsms = $settledSuccSendsms;

        return $this;
    }

    /**
     * Get settledSuccSendsms.
     *
     * @return string
     */
    public function getSettledSuccSendsms()
    {
        return $this->settled_succ_sendsms;
    }

    /**
     * Set auditGoods.
     *
     * @param bool $auditGoods
     *
     * @return Merchant
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
     * Set source.
     *
     * @param string|null $source
     *
     * @return Merchant
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
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return Merchant
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
     * @return Merchant
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
     * @return Merchant
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
