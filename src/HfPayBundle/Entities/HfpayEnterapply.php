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
 * HfpayEnterapply 入驻信息表
 *
 * @ORM\Table(name="hfpay_enterapply", options={"comment":"入驻信息表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *    @ORM\Index(name="idx_user_id", columns={"user_id"}),
 * })
 * @ORM\Entity(repositoryClass="HfPayBundle\Repositories\HfpayEnterapplyRepository")
 */

class HfpayEnterapply
{
    use Timestamps;
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="hfpay_enterapply_id", type="bigint", options={"comment":"入驻信息表id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $hfpay_enterapply_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", nullable=true, options={"comment":"分销商id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_cust_id", type="string", nullable=true, options={"comment":"汇付客户号"})
     */
    private $user_cust_id;

    /**
     * @var string
     *
     * @ORM\Column(name="acct_id", type="string", nullable=true, options={"comment":"汇付子账户"})
     */
    private $acct_id;

    /**
     * @var string
     *
     * 1 企业
     * 2 个体户
     * 3 个人
     *
     * @ORM\Column(name="apply_type", type="string", nullable=true, length=1, options={"comment":"入驻类型"})
     */
    private $apply_type;

    /**
     * @var string
     *
     * 1 普通证照
     * 2 三证合一
     *
     * @ORM\Column(name="corp_license_type", type="string", nullable=true, length=1, options={"comment":"企业证照类型"})
     */
    private $corp_license_type = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="corp_name", type="string", nullable=true, length=100, options={"comment":"企业名称"})
     */
    private $corp_name;

    /**
     * @var string
     *
     * @ORM\Column(name="business_code", type="string", nullable=true, length=100, options={"comment":"营业执照注册号"})
     */
    private $business_code;

    /**
     * @var string
     *
     * @ORM\Column(name="institution_code", type="string", nullable=true, length=100, options={"comment":"组织机构代码"})
     */
    private $institution_code;

    /**
     * @var string
     *
     * @ORM\Column(name="tax_code", type="string", nullable=true, length=100, options={"comment":"税务登记证号"})
     */
    private $tax_code;

    /**
     * @var string
     *
     * @ORM\Column(name="social_credit_code", type="string", nullable=true, length=100, options={"comment":"统一社会信用代码"})
     */
    private $social_credit_code;

    /**
     * @var string
     *
     * @ORM\Column(name="license_start_date", type="string", nullable=true, length=30, options={"comment":"证照起始日期"})
     */
    private $license_start_date;

    /**
     * @var string
     *
     * @ORM\Column(name="license_end_date", type="string", nullable=true, length=30, options={"comment":"证照结束日期"})
     */
    private $license_end_date;

    /**
     * @var string
     *
     * @ORM\Column(name="controlling_shareholder", type="string", nullable=true, length=255, options={"comment":"实际控股人"})
     */
    private $controlling_shareholder;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_name", type="string", nullable=true, length=60, options={"comment":"法人姓名"})
     */
    private $legal_name;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_id_card_type", type="string", nullable=true, length=2, options={"comment":"法人证件类型"})
     */
    private $legal_id_card_type;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_id_card", type="string", nullable=true, length=30, options={"comment":"法人证件号码"})
     */
    private $legal_id_card;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_cert_start_date", type="string", nullable=true, length=60, options={"comment":"法人证件起始日期"})
     */
    private $legal_cert_start_date;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_cert_end_date", type="string", nullable=true, length=60, options={"comment":"法人证件结束日期"})
     */
    private $legal_cert_end_date;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_mobile", type="string", nullable=true, length=20, options={"comment":"法人手机号码"})
     */
    private $legal_mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_name", type="string", nullable=true, length=30, options={"comment":"企业联系人姓名"})
     */
    private $contact_name;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_mobile", type="string", nullable=true, length=20, options={"comment":"企业联系人手机号"})
     */
    private $contact_mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_email", type="string", nullable=true, length=30, options={"comment":"联系人邮箱"})
     */
    private $contact_email;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_acct_name", type="string", nullable=true, length=30, options={"comment":"开户银行账户名"})
     */
    private $bank_acct_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_id", type="string", nullable=true, length=30, options={"comment":"开户银行"})
     */
    private $bank_id;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", type="string", nullable=true, length=30, options={"comment":"开户银行名称"})
     */
    private $bank_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_acct_num", type="string", nullable=true, length=30, options={"comment":"开户银行账号"})
     */
    private $bank_acct_num;


    /**
     * @var string
     *
     * @ORM\Column(name="bank_acct_num_imgz", type="string", nullable=true, options={"comment":"银行卡正面照"})
     */
    private $bank_acct_num_imgz;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_acct_num_imgz_local", type="string", nullable=true, options={"comment":"本地银行卡正面照"})
     */
    private $bank_acct_num_imgz_local;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_acct_num_imgf", type="string", nullable=true, options={"comment":"法人银行卡反面照"})
     */
    private $bank_acct_num_imgf;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_acct_num_imgf_local", type="string", nullable=true, options={"comment":"本地银行卡反面照"})
     */
    private $bank_acct_num_imgf_local;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_prov", type="string", nullable=true, length=30, options={"comment":"开户银行省份"})
     */
    private $bank_prov;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_prov_name", type="string", nullable=true, length=30, options={"comment":"开户银行省份名称"})
     */
    private $bank_prov_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_area", type="string", nullable=true, length=30, options={"comment":"开户银行地区"})
     */
    private $bank_area;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_area_name", type="string", nullable=true, length=30, options={"comment":"开户银行地区名称"})
     */
    private $bank_area_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_branch", type="string", nullable=true, length=30, options={"comment":"企业开户银行的支行名称"})
     */
    private $bank_branch;

    /**
     * @var string
     *
     * @ORM\Column(name="solo_name", type="string", nullable=true, length=30, options={"comment":"个体户名称"})
     */
    private $solo_name;

    /**
     * @var string
     *
     * @ORM\Column(name="solo_business_address", type="string", nullable=true, length=150, options={"comment":"个体户经营地址"})
     */
    private $solo_business_address;

    /**
     * @var string
     *
     * @ORM\Column(name="solo_reg_address", type="string", nullable=true, length=150, options={"comment":"个体户注册地址"})
     */
    private $solo_reg_address;

    /**
     * @var string
     *
     * @ORM\Column(name="solo_fixed_telephone", type="string", nullable=true, length=30, options={"comment":"个体户固定电话"})
     */
    private $solo_fixed_telephone;

    /**
     * @var string
     *
     * @ORM\Column(name="business_scope", type="string", nullable=true, options={"comment":"经营范围"})
     */
    private $business_scope;

    /**
     * @var string
     *
     * @ORM\Column(name="occupation", type="string", nullable=true,length=80, options={"comment":"职业"})
     */
    private $occupation;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_cert_num", type="string", nullable=true, length=30, options={"comment":"联系人证件号"})
     */
    private $contact_cert_num;

    /**
     * @var string
     *
     * @ORM\Column(name="open_license_no", type="string", nullable=true, length=60, options={"comment":"开户许可证核准号"})
     */
    private $open_license_no;

    /**
     * @var string
     *
     * @ORM\Column(name="user_name", type="string", nullable=true, length=30, options={"comment":"用户姓名"})
     */
    private $user_name;

    /**
     * @var string
     *
     * @ORM\Column(name="id_card_type", type="string", nullable=true, length=2, options={"comment":"证件类型"})
     */
    private $id_card_type;

    /**
     * @var string
     *
     * @ORM\Column(name="id_card", type="string", nullable=true, length=30, options={"comment":"身份证号"})
     */
    private $id_card;

    /**
     * @var string
     *
     * @ORM\Column(name="user_mobile", type="string", nullable=true, length=20, options={"comment":"手机号"})
     */
    private $user_mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_order_id", type="string", nullable=true, options={"comment":"汇付订单号"})
     */
    private $hf_order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_order_date", type="string", nullable=true, options={"comment":"汇付订单日期"})
     */
    private $hf_order_date;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_apply_id", type="string", nullable=true, options={"comment":"汇付开户申请号"})
     */
    private $hf_apply_id;

    /**
     * @var string
     *
     * 1 未提交进件信息
     * 2 已经提交进件信息，审核中
     * 3 已开商户
     * 4 审核失败
     *
     * @ORM\Column(name="status", type="string",nullable=true, options={"default": 1,"comment":"状态"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="business_code_img", type="string", nullable=true, options={"comment":"营业执照注册号图片"})
     */
    private $business_code_img;

    /**
     * @var string
     *
     * @ORM\Column(name="business_code_img_local", type="string", nullable=true, options={"comment":"本地营业执照注册号图片"})
     */
    private $business_code_img_local;

    /**
     * @var string
     *
     * @ORM\Column(name="institution_code_img", type="string", nullable=true, options={"comment":"组织机构代码图片"})
     */
    private $institution_code_img;

    /**
     * @var string
     *
     * @ORM\Column(name="institution_code_img_local", type="string", nullable=true, options={"comment":"本地组织机构代码图片"})
     */
    private $institution_code_img_local;

    /**
     * @var string
     *
     * @ORM\Column(name="tax_code_img", type="string", nullable=true, options={"comment":"税务登记证号图片"})
     */
    private $tax_code_img;

    /**
     * @var string
     *
     * @ORM\Column(name="tax_code_img_local", type="string", nullable=true, options={"comment":"本地税务登记证号图片"})
     */
    private $tax_code_img_local;

    /**
     * @var string
     *
     * @ORM\Column(name="social_credit_code_img", type="string", nullable=true, options={"comment":"统一社会信用代码图片"})
     */
    private $social_credit_code_img;

    /**
     * @var string
     *
     * @ORM\Column(name="social_credit_code_img_local", type="string", nullable=true, options={"comment":"本地统一社会信用代码图片"})
     */
    private $social_credit_code_img_local;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_card_imgz", type="string", nullable=true, options={"comment":"法人身份证正面照"})
     */
    private $legal_card_imgz;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_card_imgz_local", type="string", nullable=true, options={"comment":"本地法人身份证正面照"})
     */
    private $legal_card_imgz_local;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_card_imgf", type="string", nullable=true, options={"comment":"法人身份证反面照"})
     */
    private $legal_card_imgf;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_card_imgf_local", type="string", nullable=true, options={"comment":"本地法人身份证反面照"})
     */
    private $legal_card_imgf_local;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_acct_img", type="string", nullable=true, options={"comment":"开户银行许可证图片"})
     */
    private $bank_acct_img;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_acct_img_local", type="string", nullable=true, options={"comment":"本地开户银行许可证图片"})
     */
    private $bank_acct_img_local;

    /**
     * @var string
     *
     * @ORM\Column(name="resp_code", type="string", nullable=true, options={"comment":"汇付响应码"})
     */
    private $resp_code;

    /**
     * @var string
     *
     * @ORM\Column(name="resp_desc", type="string", nullable=true, options={"comment":"汇付响应码描述"})
     */
    private $resp_desc;

    /**
     * Get hfpayEnterapplyId.
     *
     * @return int
     */
    public function getHfpayEnterapplyId()
    {
        return $this->hfpay_enterapply_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return HfpayEnterapply
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
     * Set distributorId.
     *
     * @param int|null $distributorId
     *
     * @return HfpayEnterapply
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
     * Set userId.
     *
     * @param int|null $userId
     *
     * @return HfpayEnterapply
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
     * Set userCustId.
     *
     * @param string|null $userCustId
     *
     * @return HfpayEnterapply
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
     * Set acctId.
     *
     * @param string|null $acctId
     *
     * @return HfpayEnterapply
     */
    public function setAcctId($acctId = null)
    {
        $this->acct_id = $acctId;

        return $this;
    }

    /**
     * Get acctId.
     *
     * @return string|null
     */
    public function getAcctId()
    {
        return $this->acct_id;
    }

    /**
     * Set applyType.
     *
     * @param string|null $applyType
     *
     * @return HfpayEnterapply
     */
    public function setApplyType($applyType)
    {
        $this->apply_type = $applyType;

        return $this;
    }

    /**
     * Get applyType.
     *
     * @return string|null
     */
    public function getApplyType()
    {
        return $this->apply_type;
    }

    /**
     * Set corpLicenseType.
     *
     * @param string|null $corpLicenseType
     *
     * @return HfpayEnterapply
     */
    public function setCorpLicenseType($corpLicenseType = null)
    {
        $this->corp_license_type = $corpLicenseType;

        return $this;
    }

    /**
     * Get corpLicenseType.
     *
     * @return string|null
     */
    public function getCorpLicenseType()
    {
        return $this->corp_license_type;
    }

    /**
     * Set corpName.
     *
     * @param string|null $corpName
     *
     * @return HfpayEnterapply
     */
    public function setCorpName($corpName = null)
    {
        $this->corp_name = $corpName;

        return $this;
    }

    /**
     * Get corpName.
     *
     * @return string|null
     */
    public function getCorpName()
    {
        return $this->corp_name;
    }

    /**
     * Set businessCode.
     *
     * @param string|null $businessCode
     *
     * @return HfpayEnterapply
     */
    public function setBusinessCode($businessCode = null)
    {
        $this->business_code = $businessCode;

        return $this;
    }

    /**
     * Get businessCode.
     *
     * @return string|null
     */
    public function getBusinessCode()
    {
        return $this->business_code;
    }

    /**
     * Set institutionCode.
     *
     * @param string|null $institutionCode
     *
     * @return HfpayEnterapply
     */
    public function setInstitutionCode($institutionCode = null)
    {
        $this->institution_code = $institutionCode;

        return $this;
    }

    /**
     * Get institutionCode.
     *
     * @return string|null
     */
    public function getInstitutionCode()
    {
        return $this->institution_code;
    }

    /**
     * Set taxCode.
     *
     * @param string|null $taxCode
     *
     * @return HfpayEnterapply
     */
    public function setTaxCode($taxCode = null)
    {
        $this->tax_code = $taxCode;

        return $this;
    }

    /**
     * Get taxCode.
     *
     * @return string|null
     */
    public function getTaxCode()
    {
        return $this->tax_code;
    }

    /**
     * Set socialCreditCode.
     *
     * @param string|null $socialCreditCode
     *
     * @return HfpayEnterapply
     */
    public function setSocialCreditCode($socialCreditCode = null)
    {
        $this->social_credit_code = $socialCreditCode;

        return $this;
    }

    /**
     * Get socialCreditCode.
     *
     * @return string|null
     */
    public function getSocialCreditCode()
    {
        return $this->social_credit_code;
    }

    /**
     * Set licenseStartDate.
     *
     * @param string|null $licenseStartDate
     *
     * @return HfpayEnterapply
     */
    public function setLicenseStartDate($licenseStartDate = null)
    {
        $this->license_start_date = $licenseStartDate;

        return $this;
    }

    /**
     * Get licenseStartDate.
     *
     * @return string|null
     */
    public function getLicenseStartDate()
    {
        return $this->license_start_date;
    }

    /**
     * Set licenseEndDate.
     *
     * @param string|null $licenseEndDate
     *
     * @return HfpayEnterapply
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
     * Set controllingShareholder.
     *
     * @param string|null $controllingShareholder
     *
     * @return HfpayEnterapply
     */
    public function setControllingShareholder($controllingShareholder = null)
    {
        $this->controlling_shareholder = $controllingShareholder;

        return $this;
    }

    /**
     * Get controllingShareholder.
     *
     * @return string|null
     */
    public function getControllingShareholder()
    {
        return $this->controlling_shareholder;
    }

    /**
     * Set legalName.
     *
     * @param string|null $legalName
     *
     * @return HfpayEnterapply
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
     * Set legalIdCardType.
     *
     * @param string|null $legalIdCardType
     *
     * @return HfpayEnterapply
     */
    public function setLegalIdCardType($legalIdCardType = null)
    {
        $this->legal_id_card_type = $legalIdCardType;

        return $this;
    }

    /**
     * Get legalIdCardType.
     *
     * @return string|null
     */
    public function getLegalIdCardType()
    {
        return $this->legal_id_card_type;
    }

    /**
     * Set legalIdCard.
     *
     * @param string|null $legalIdCard
     *
     * @return HfpayEnterapply
     */
    public function setLegalIdCard($legalIdCard = null)
    {
        $this->legal_id_card = $legalIdCard;

        return $this;
    }

    /**
     * Get legalIdCard.
     *
     * @return string|null
     */
    public function getLegalIdCard()
    {
        return $this->legal_id_card;
    }

    /**
     * Set legalCertStartDate.
     *
     * @param string|null $legalCertStartDate
     *
     * @return HfpayEnterapply
     */
    public function setLegalCertStartDate($legalCertStartDate = null)
    {
        $this->legal_cert_start_date = $legalCertStartDate;

        return $this;
    }

    /**
     * Get legalCertStartDate.
     *
     * @return string|null
     */
    public function getLegalCertStartDate()
    {
        return $this->legal_cert_start_date;
    }

    /**
     * Set legalCertEndDate.
     *
     * @param string|null $legalCertEndDate
     *
     * @return HfpayEnterapply
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
     * Set legalMobile.
     *
     * @param string|null $legalMobile
     *
     * @return HfpayEnterapply
     */
    public function setLegalMobile($legalMobile = null)
    {
        $this->legal_mobile = $legalMobile;

        return $this;
    }

    /**
     * Get legalMobile.
     *
     * @return string|null
     */
    public function getLegalMobile()
    {
        return $this->legal_mobile;
    }

    /**
     * Set contactName.
     *
     * @param string|null $contactName
     *
     * @return HfpayEnterapply
     */
    public function setContactName($contactName = null)
    {
        $this->contact_name = $contactName;

        return $this;
    }

    /**
     * Get contactName.
     *
     * @return string|null
     */
    public function getContactName()
    {
        return $this->contact_name;
    }

    /**
     * Set contactMobile.
     *
     * @param string|null $contactMobile
     *
     * @return HfpayEnterapply
     */
    public function setContactMobile($contactMobile = null)
    {
        $this->contact_mobile = $contactMobile;

        return $this;
    }

    /**
     * Get contactMobile.
     *
     * @return string|null
     */
    public function getContactMobile()
    {
        return $this->contact_mobile;
    }

    /**
     * Set contactEmail.
     *
     * @param string|null $contactEmail
     *
     * @return HfpayEnterapply
     */
    public function setContactEmail($contactEmail = null)
    {
        $this->contact_email = $contactEmail;

        return $this;
    }

    /**
     * Get contactEmail.
     *
     * @return string|null
     */
    public function getContactEmail()
    {
        return $this->contact_email;
    }

    /**
     * Set bankAcctName.
     *
     * @param string|null $bankAcctName
     *
     * @return HfpayEnterapply
     */
    public function setBankAcctName($bankAcctName = null)
    {
        $this->bank_acct_name = $bankAcctName;

        return $this;
    }

    /**
     * Get bankAcctName.
     *
     * @return string|null
     */
    public function getBankAcctName()
    {
        return $this->bank_acct_name;
    }

    /**
     * Set bankId.
     *
     * @param string|null $bankId
     *
     * @return HfpayEnterapply
     */
    public function setBankId($bankId = null)
    {
        $this->bank_id = $bankId;

        return $this;
    }

    /**
     * Get bankId.
     *
     * @return string|null
     */
    public function getBankId()
    {
        return $this->bank_id;
    }

    /**
     * Set bankAcctNum.
     *
     * @param string|null $bankAcctNum
     *
     * @return HfpayEnterapply
     */
    public function setBankAcctNum($bankAcctNum = null)
    {
        $this->bank_acct_num = $bankAcctNum;

        return $this;
    }

    /**
     * Get bankAcctNum.
     *
     * @return string|null
     */
    public function getBankAcctNum()
    {
        return $this->bank_acct_num;
    }

    /**
     * Set bankProv.
     *
     * @param string|null $bankProv
     *
     * @return HfpayEnterapply
     */
    public function setBankProv($bankProv = null)
    {
        $this->bank_prov = $bankProv;

        return $this;
    }

    /**
     * Get bankProv.
     *
     * @return string|null
     */
    public function getBankProv()
    {
        return $this->bank_prov;
    }

    /**
     * Set bankArea.
     *
     * @param string|null $bankArea
     *
     * @return HfpayEnterapply
     */
    public function setBankArea($bankArea = null)
    {
        $this->bank_area = $bankArea;

        return $this;
    }

    /**
     * Get bankArea.
     *
     * @return string|null
     */
    public function getBankArea()
    {
        return $this->bank_area;
    }

    /**
     * Set soloName.
     *
     * @param string|null $soloName
     *
     * @return HfpayEnterapply
     */
    public function setSoloName($soloName = null)
    {
        $this->solo_name = $soloName;

        return $this;
    }

    /**
     * Get soloName.
     *
     * @return string|null
     */
    public function getSoloName()
    {
        return $this->solo_name;
    }

    /**
     * Set soloBusinessAddress.
     *
     * @param string|null $soloBusinessAddress
     *
     * @return HfpayEnterapply
     */
    public function setSoloBusinessAddress($soloBusinessAddress = null)
    {
        $this->solo_business_address = $soloBusinessAddress;

        return $this;
    }

    /**
     * Get soloBusinessAddress.
     *
     * @return string|null
     */
    public function getSoloBusinessAddress()
    {
        return $this->solo_business_address;
    }

    /**
     * Set soloRegAddress.
     *
     * @param string|null $soloRegAddress
     *
     * @return HfpayEnterapply
     */
    public function setSoloRegAddress($soloRegAddress = null)
    {
        $this->solo_reg_address = $soloRegAddress;

        return $this;
    }

    /**
     * Get soloRegAddress.
     *
     * @return string|null
     */
    public function getSoloRegAddress()
    {
        return $this->solo_reg_address;
    }

    /**
     * Set soloFixedTelephone.
     *
     * @param string|null $soloFixedTelephone
     *
     * @return HfpayEnterapply
     */
    public function setSoloFixedTelephone($soloFixedTelephone = null)
    {
        $this->solo_fixed_telephone = $soloFixedTelephone;

        return $this;
    }

    /**
     * Get soloFixedTelephone.
     *
     * @return string|null
     */
    public function getSoloFixedTelephone()
    {
        return $this->solo_fixed_telephone;
    }

    /**
     * Set businessScope.
     *
     * @param string|null $businessScope
     *
     * @return HfpayEnterapply
     */
    public function setBusinessScope($businessScope = null)
    {
        $this->business_scope = $businessScope;

        return $this;
    }

    /**
     * Get businessScope.
     *
     * @return string|null
     */
    public function getBusinessScope()
    {
        return $this->business_scope;
    }

    /**
     * Set occupation.
     *
     * @param string|null $occupation
     *
     * @return HfpayEnterapply
     */
    public function setOccupation($occupation = null)
    {
        $this->occupation = $occupation;

        return $this;
    }

    /**
     * Get occupation.
     *
     * @return string|null
     */
    public function getOccupation()
    {
        return $this->occupation;
    }

    /**
     * Set userName.
     *
     * @param string|null $userName
     *
     * @return HfpayEnterapply
     */
    public function setUserName($userName = null)
    {
        $this->user_name = $userName;

        return $this;
    }

    /**
     * Get userName.
     *
     * @return string|null
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Set idCardType.
     *
     * @param string|null $idCardType
     *
     * @return HfpayEnterapply
     */
    public function setIdCardType($idCardType = null)
    {
        $this->id_card_type = $idCardType;

        return $this;
    }

    /**
     * Get idCardType.
     *
     * @return string|null
     */
    public function getIdCardType()
    {
        return $this->id_card_type;
    }

    /**
     * Set idCard.
     *
     * @param string|null $idCard
     *
     * @return HfpayEnterapply
     */
    public function setIdCard($idCard = null)
    {
        $this->id_card = $idCard;

        return $this;
    }

    /**
     * Get idCard.
     *
     * @return string|null
     */
    public function getIdCard()
    {
        return $this->id_card;
    }

    /**
     * Set userMobile.
     *
     * @param string|null $userMobile
     *
     * @return HfpayEnterapply
     */
    public function setUserMobile($userMobile = null)
    {
        $this->user_mobile = $userMobile;

        return $this;
    }

    /**
     * Get userMobile.
     *
     * @return string|null
     */
    public function getUserMobile()
    {
        return $this->user_mobile;
    }

    /**
     * Set hfOrderId.
     *
     * @param string|null $hfOrderId
     *
     * @return HfpayEnterapply
     */
    public function setHfOrderId($hfOrderId = null)
    {
        $this->hf_order_id = $hfOrderId;

        return $this;
    }

    /**
     * Get hfOrderId.
     *
     * @return string|null
     */
    public function getHfOrderId()
    {
        return $this->hf_order_id;
    }

    /**
     * Set hfOrderDate.
     *
     * @param string|null $hfOrderDate
     *
     * @return HfpayEnterapply
     */
    public function setHfOrderDate($hfOrderDate = null)
    {
        $this->hf_order_date = $hfOrderDate;

        return $this;
    }

    /**
     * Get hfOrderDate.
     *
     * @return string|null
     */
    public function getHfOrderDate()
    {
        return $this->hf_order_date;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return HfpayEnterapply
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
     * Set businessCodeImg.
     *
     * @param string|null $businessCodeImg
     *
     * @return HfpayEnterapply
     */
    public function setBusinessCodeImg($businessCodeImg = null)
    {
        $this->business_code_img = $businessCodeImg;

        return $this;
    }

    /**
     * Get businessCodeImg.
     *
     * @return string|null
     */
    public function getBusinessCodeImg()
    {
        return $this->business_code_img;
    }

    /**
     * Set institutionCodeImg.
     *
     * @param string|null $institutionCodeImg
     *
     * @return HfpayEnterapply
     */
    public function setInstitutionCodeImg($institutionCodeImg = null)
    {
        $this->institution_code_img = $institutionCodeImg;

        return $this;
    }

    /**
     * Get institutionCodeImg.
     *
     * @return string|null
     */
    public function getInstitutionCodeImg()
    {
        return $this->institution_code_img;
    }

    /**
     * Set taxCodeImg.
     *
     * @param string|null $taxCodeImg
     *
     * @return HfpayEnterapply
     */
    public function setTaxCodeImg($taxCodeImg = null)
    {
        $this->tax_code_img = $taxCodeImg;

        return $this;
    }

    /**
     * Get taxCodeImg.
     *
     * @return string|null
     */
    public function getTaxCodeImg()
    {
        return $this->tax_code_img;
    }

    /**
     * Set socialCreditCodeImg.
     *
     * @param string|null $socialCreditCodeImg
     *
     * @return HfpayEnterapply
     */
    public function setSocialCreditCodeImg($socialCreditCodeImg = null)
    {
        $this->social_credit_code_img = $socialCreditCodeImg;

        return $this;
    }

    /**
     * Get socialCreditCodeImg.
     *
     * @return string|null
     */
    public function getSocialCreditCodeImg()
    {
        return $this->social_credit_code_img;
    }

    /**
     * Set legalCardImgz.
     *
     * @param string|null $legalCardImgz
     *
     * @return HfpayEnterapply
     */
    public function setLegalCardImgz($legalCardImgz = null)
    {
        $this->legal_card_imgz = $legalCardImgz;

        return $this;
    }

    /**
     * Get legalCardImgz.
     *
     * @return string|null
     */
    public function getLegalCardImgz()
    {
        return $this->legal_card_imgz;
    }

    /**
     * Set legalCardImgf.
     *
     * @param string|null $legalCardImgf
     *
     * @return HfpayEnterapply
     */
    public function setLegalCardImgf($legalCardImgf = null)
    {
        $this->legal_card_imgf = $legalCardImgf;

        return $this;
    }

    /**
     * Get legalCardImgf.
     *
     * @return string|null
     */
    public function getLegalCardImgf()
    {
        return $this->legal_card_imgf;
    }

    /**
     * Set bankName.
     *
     * @param string|null $bankName
     *
     * @return HfpayEnterapply
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
     * Set bankProvName.
     *
     * @param string|null $bankProvName
     *
     * @return HfpayEnterapply
     */
    public function setBankProvName($bankProvName = null)
    {
        $this->bank_prov_name = $bankProvName;

        return $this;
    }

    /**
     * Get bankProvName.
     *
     * @return string|null
     */
    public function getBankProvName()
    {
        return $this->bank_prov_name;
    }

    /**
     * Set bankAreaName.
     *
     * @param string|null $bankAreaName
     *
     * @return HfpayEnterapply
     */
    public function setBankAreaName($bankAreaName = null)
    {
        $this->bank_area_name = $bankAreaName;

        return $this;
    }

    /**
     * Get bankAreaName.
     *
     * @return string|null
     */
    public function getBankAreaName()
    {
        return $this->bank_area_name;
    }

    /**
     * Set bankBranch.
     *
     * @param string|null $bankBranch
     *
     * @return HfpayEnterapply
     */
    public function setBankBranch($bankBranch = null)
    {
        $this->bank_branch = $bankBranch;

        return $this;
    }

    /**
     * Get bankBranch.
     *
     * @return string|null
     */
    public function getBankBranch()
    {
        return $this->bank_branch;
    }

    /**
     * Set businessCodeImgLocal.
     *
     * @param string|null $businessCodeImgLocal
     *
     * @return HfpayEnterapply
     */
    public function setBusinessCodeImgLocal($businessCodeImgLocal = null)
    {
        $this->business_code_img_local = $businessCodeImgLocal;

        return $this;
    }

    /**
     * Get businessCodeImgLocal.
     *
     * @return string|null
     */
    public function getBusinessCodeImgLocal()
    {
        return $this->business_code_img_local;
    }

    /**
     * Set institutionCodeImgLocal.
     *
     * @param string|null $institutionCodeImgLocal
     *
     * @return HfpayEnterapply
     */
    public function setInstitutionCodeImgLocal($institutionCodeImgLocal = null)
    {
        $this->institution_code_img_local = $institutionCodeImgLocal;

        return $this;
    }

    /**
     * Get institutionCodeImgLocal.
     *
     * @return string|null
     */
    public function getInstitutionCodeImgLocal()
    {
        return $this->institution_code_img_local;
    }

    /**
     * Set taxCodeImgLocal.
     *
     * @param string|null $taxCodeImgLocal
     *
     * @return HfpayEnterapply
     */
    public function setTaxCodeImgLocal($taxCodeImgLocal = null)
    {
        $this->tax_code_img_local = $taxCodeImgLocal;

        return $this;
    }

    /**
     * Get taxCodeImgLocal.
     *
     * @return string|null
     */
    public function getTaxCodeImgLocal()
    {
        return $this->tax_code_img_local;
    }

    /**
     * Set socialCreditCodeImgLocal.
     *
     * @param string|null $socialCreditCodeImgLocal
     *
     * @return HfpayEnterapply
     */
    public function setSocialCreditCodeImgLocal($socialCreditCodeImgLocal = null)
    {
        $this->social_credit_code_img_local = $socialCreditCodeImgLocal;

        return $this;
    }

    /**
     * Get socialCreditCodeImgLocal.
     *
     * @return string|null
     */
    public function getSocialCreditCodeImgLocal()
    {
        return $this->social_credit_code_img_local;
    }

    /**
     * Set legalCardImgzLocal.
     *
     * @param string|null $legalCardImgzLocal
     *
     * @return HfpayEnterapply
     */
    public function setLegalCardImgzLocal($legalCardImgzLocal = null)
    {
        $this->legal_card_imgz_local = $legalCardImgzLocal;

        return $this;
    }

    /**
     * Get legalCardImgzLocal.
     *
     * @return string|null
     */
    public function getLegalCardImgzLocal()
    {
        return $this->legal_card_imgz_local;
    }

    /**
     * Set legalCardImgfLocal.
     *
     * @param string|null $legalCardImgfLocal
     *
     * @return HfpayEnterapply
     */
    public function setLegalCardImgfLocal($legalCardImgfLocal = null)
    {
        $this->legal_card_imgf_local = $legalCardImgfLocal;

        return $this;
    }

    /**
     * Get legalCardImgfLocal.
     *
     * @return string|null
     */
    public function getLegalCardImgfLocal()
    {
        return $this->legal_card_imgf_local;
    }

    /**
     * Set respCode.
     *
     * @param string|null $respCode
     *
     * @return HfpayEnterapply
     */
    public function setRespCode($respCode = null)
    {
        $this->resp_code = $respCode;

        return $this;
    }

    /**
     * Get respCode.
     *
     * @return string|null
     */
    public function getRespCode()
    {
        return $this->resp_code;
    }

    /**
     * Set respDesc.
     *
     * @param string|null $respDesc
     *
     * @return HfpayEnterapply
     */
    public function setRespDesc($respDesc = null)
    {
        $this->resp_desc = $respDesc;

        return $this;
    }

    /**
     * Get respDesc.
     *
     * @return string|null
     */
    public function getRespDesc()
    {
        return $this->resp_desc;
    }

    /**
     * Set hfApplyId.
     *
     * @param string|null $hfApplyId
     *
     * @return HfpayEnterapply
     */
    public function setHfApplyId($hfApplyId = null)
    {
        $this->hf_apply_id = $hfApplyId;

        return $this;
    }

    /**
     * Get hfApplyId.
     *
     * @return string|null
     */
    public function getHfApplyId()
    {
        return $this->hf_apply_id;
    }

    /**
     * Set bankAcctImg.
     *
     * @param string|null $bankAcctImg
     *
     * @return HfpayEnterapply
     */
    public function setBankAcctImg($bankAcctImg = null)
    {
        $this->bank_acct_img = $bankAcctImg;

        return $this;
    }

    /**
     * Get bankAcctImg.
     *
     * @return string|null
     */
    public function getBankAcctImg()
    {
        return $this->bank_acct_img;
    }

    /**
     * Set bankAcctImgLocal.
     *
     * @param string|null $bankAcctImgLocal
     *
     * @return HfpayEnterapply
     */
    public function setBankAcctImgLocal($bankAcctImgLocal = null)
    {
        $this->bank_acct_img_local = $bankAcctImgLocal;

        return $this;
    }

    /**
     * Get bankAcctImgLocal.
     *
     * @return string|null
     */
    public function getBankAcctImgLocal()
    {
        return $this->bank_acct_img_local;
    }

    /**
     * Set bankAcctNumImgz.
     *
     * @param string|null $bankAcctNumImgz
     *
     * @return HfpayEnterapply
     */
    public function setBankAcctNumImgz($bankAcctNumImgz = null)
    {
        $this->bank_acct_num_imgz = $bankAcctNumImgz;

        return $this;
    }

    /**
     * Get bankAcctNumImgz.
     *
     * @return string|null
     */
    public function getBankAcctNumImgz()
    {
        return $this->bank_acct_num_imgz;
    }

    /**
     * Set bankAcctNumImgzLocal.
     *
     * @param string|null $bankAcctNumImgzLocal
     *
     * @return HfpayEnterapply
     */
    public function setBankAcctNumImgzLocal($bankAcctNumImgzLocal = null)
    {
        $this->bank_acct_num_imgz_local = $bankAcctNumImgzLocal;

        return $this;
    }

    /**
     * Get bankAcctNumImgzLocal.
     *
     * @return string|null
     */
    public function getBankAcctNumImgzLocal()
    {
        return $this->bank_acct_num_imgz_local;
    }

    /**
     * Set bankAcctNumImgf.
     *
     * @param string|null $bankAcctNumImgf
     *
     * @return HfpayEnterapply
     */
    public function setBankAcctNumImgf($bankAcctNumImgf = null)
    {
        $this->bank_acct_num_imgf = $bankAcctNumImgf;

        return $this;
    }

    /**
     * Get bankAcctNumImgf.
     *
     * @return string|null
     */
    public function getBankAcctNumImgf()
    {
        return $this->bank_acct_num_imgf;
    }

    /**
     * Set bankAcctNumImgfLocal.
     *
     * @param string|null $bankAcctNumImgfLocal
     *
     * @return HfpayEnterapply
     */
    public function setBankAcctNumImgfLocal($bankAcctNumImgfLocal = null)
    {
        $this->bank_acct_num_imgf_local = $bankAcctNumImgfLocal;

        return $this;
    }

    /**
     * Get bankAcctNumImgfLocal.
     *
     * @return string|null
     */
    public function getBankAcctNumImgfLocal()
    {
        return $this->bank_acct_num_imgf_local;
    }

    /**
     * Set contactCertNum.
     *
     * @param string|null $contactCertNum
     *
     * @return HfpayEnterapply
     */
    public function setContactCertNum($contactCertNum = null)
    {
        $this->contact_cert_num = $contactCertNum;

        return $this;
    }

    /**
     * Get contactCertNum.
     *
     * @return string|null
     */
    public function getContactCertNum()
    {
        return $this->contact_cert_num;
    }

    /**
     * Set openLicenseNo.
     *
     * @param string|null $openLicenseNo
     *
     * @return HfpayEnterapply
     */
    public function setOpenLicenseNo($openLicenseNo = null)
    {
        $this->open_license_no = $openLicenseNo;

        return $this;
    }

    /**
     * Get openLicenseNo.
     *
     * @return string|null
     */
    public function getOpenLicenseNo()
    {
        return $this->open_license_no;
    }
}
