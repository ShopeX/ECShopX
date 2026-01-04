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
 * AdapaySubmitLicense adapay提交商户证照
 *
 * @ORM\Table(name="adapay_submit_license", options={"comment":"adapay提交商户证照"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapaySubmitLicenseRepository")
 */
class AdapaySubmitLicense
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
     * @var string
     *
     * @ORM\Column(name="sub_api_key", type="string", options={"comment":"渠道商下商户的apiKey"})
     */
    private $sub_api_key;

    /**
     * @var string
     *
     * @ORM\Column(name="social_credit_code_id", type="string", nullable=true, options={"comment":"统一社会信用代码的pic_id，企业商户必填，小微商户不填"})
     */
    private $social_credit_code_id;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_certId_front_id", type="string", options={"comment":"法人身份证正面的pic_id"})
     */
    private $legal_certId_front_id;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_cert_id_back_id", type="string", options={"comment":"法人身份证反面的pic_id"})
     */
    private $legal_cert_id_back_id;

    /**
     * @var string
     *
     * @ORM\Column(name="account_opening_permit_id", type="string", options={"comment":"开户许可证图片的pic_id"})
     */
    private $account_opening_permit_id;

    /**
     * @var string
     *
     * @ORM\Column(name="business_add", type="string", length=500, nullable=true, options={"comment":"若入驻的费率类型为线上时，该字段必填，请传入商户的业务网址或者商城地址"})
     */
    private $business_add;

    /**
     * @var string
     *
     * @ORM\Column(name="store_id", type="string", nullable=true, options={"comment":"门店的pic_id，若入驻的费率类型为线下时，该字段必填"})
     */
    private $store_id;

    /**
     * @var string
     *
     * @ORM\Column(name="transaction_test_record_id", type="string", nullable=true, options={"comment":"商户在业务网址或商城地址上测试的交易记录截图的pic_id"})
     */
    private $transaction_test_record_id;

    /**
     * @var string
     *
     * @ORM\Column(name="web_pic_id", type="string", nullable=true, options={"comment":"网站截图的pic_id"})
     */
    private $web_pic_id;

    /**
     * @var string
     *
     * @ORM\Column(name="lease_contract_id", type="string", nullable=true, options={"comment":"租赁合同的pic_id，如经营场所照片无法体现经营内容时上传"})
     */
    private $lease_contract_id;

    /**
     * @var string
     *
     * @ORM\Column(name="settle_account_certificate_id", type="string", nullable=true, options={"comment":"结算账号开户证明图片的pic_id"})
     */
    private $settle_account_certificate_id;

    /**
     * @var string
     *
     * @ORM\Column(name="buss_support_materials_id", type="string", nullable=true, options={"comment":"业务场景证明材料pic_id，如经营场所照片无法体现经营内容时上传"})
     */
    private $buss_support_materials_id;

    /**
     * @var string
     *
     * @ORM\Column(name="icp_registration_license_id", type="string", nullable=true, options={"comment":"icp备案许可证明或者许可证编码的pic_id"})
     */
    private $icp_registration_license_id;

    /**
     * @var string
     *
     * @ORM\Column(name="industry_qualify_doc_type", type="string", nullable=true, options={"comment":"行业资质文件类型：1游戏类，2直播类，3小说图书类，4其他"})
     */
    private $industry_qualify_doc_type;

    /**
     * @var string
     *
     * @ORM\Column(name="industry_qualify_doc_license_id", type="string", nullable=true, options={"comment":"行业资质文件的pic_id"})
     */
    private $industry_qualify_doc_license_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shareholder_info_list", type="text", nullable=true, options={"comment":"股东信息"})
     */
    private $shareholder_info_list;

    /**
     * @var string
     *
     * @ORM\Column(name="is_sms", type="string", length=20, nullable=true, options={"comment":"是否短信提醒: 1:是  0:否"})
     */
    private $is_sms;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_status", type="string", nullable=true, options={"comment":"W -> 待补充，I -> 初始，P -> 通过，R -> 拒绝"})
     */
    private $audit_status;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_desc", type="string", length=500, nullable=true, options={"comment":"审核拒绝原因"})
     */
    private $audit_desc;

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
     * @return AdapaySubmitLicense
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
     * Set subApiKey.
     *
     * @param string $subApiKey
     *
     * @return AdapaySubmitLicense
     */
    public function setSubApiKey($subApiKey)
    {
        $this->sub_api_key = $subApiKey;

        return $this;
    }

    /**
     * Get subApiKey.
     *
     * @return string
     */
    public function getSubApiKey()
    {
        return $this->sub_api_key;
    }

    /**
     * Set socialCreditCodeId.
     *
     * @param string|null $socialCreditCodeId
     *
     * @return AdapaySubmitLicense
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
     * Set legalCertIdFrontId.
     *
     * @param string $legalCertIdFrontId
     *
     * @return AdapaySubmitLicense
     */
    public function setLegalCertIdFrontId($legalCertIdFrontId)
    {
        $this->legal_certId_front_id = $legalCertIdFrontId;

        return $this;
    }

    /**
     * Get legalCertIdFrontId.
     *
     * @return string
     */
    public function getLegalCertIdFrontId()
    {
        return $this->legal_certId_front_id;
    }

    /**
     * Set legalCertIdBackId.
     *
     * @param string $legalCertIdBackId
     *
     * @return AdapaySubmitLicense
     */
    public function setLegalCertIdBackId($legalCertIdBackId)
    {
        $this->legal_cert_id_back_id = $legalCertIdBackId;

        return $this;
    }

    /**
     * Get legalCertIdBackId.
     *
     * @return string
     */
    public function getLegalCertIdBackId()
    {
        return $this->legal_cert_id_back_id;
    }

    /**
     * Set accountOpeningPermitId.
     *
     * @param string $accountOpeningPermitId
     *
     * @return AdapaySubmitLicense
     */
    public function setAccountOpeningPermitId($accountOpeningPermitId)
    {
        $this->account_opening_permit_id = $accountOpeningPermitId;

        return $this;
    }

    /**
     * Get accountOpeningPermitId.
     *
     * @return string
     */
    public function getAccountOpeningPermitId()
    {
        return $this->account_opening_permit_id;
    }

    /**
     * Set businessAdd.
     *
     * @param string|null $businessAdd
     *
     * @return AdapaySubmitLicense
     */
    public function setBusinessAdd($businessAdd = null)
    {
        $this->business_add = $businessAdd;

        return $this;
    }

    /**
     * Get businessAdd.
     *
     * @return string|null
     */
    public function getBusinessAdd()
    {
        return $this->business_add;
    }

    /**
     * Set storeId.
     *
     * @param string|null $storeId
     *
     * @return AdapaySubmitLicense
     */
    public function setStoreId($storeId = null)
    {
        $this->store_id = $storeId;

        return $this;
    }

    /**
     * Get storeId.
     *
     * @return string|null
     */
    public function getStoreId()
    {
        return $this->store_id;
    }

    /**
     * Set transactionTestRecordId.
     *
     * @param string|null $transactionTestRecordId
     *
     * @return AdapaySubmitLicense
     */
    public function setTransactionTestRecordId($transactionTestRecordId = null)
    {
        $this->transaction_test_record_id = $transactionTestRecordId;

        return $this;
    }

    /**
     * Get transactionTestRecordId.
     *
     * @return string|null
     */
    public function getTransactionTestRecordId()
    {
        return $this->transaction_test_record_id;
    }

    /**
     * Set webPicId.
     *
     * @param string|null $webPicId
     *
     * @return AdapaySubmitLicense
     */
    public function setWebPicId($webPicId = null)
    {
        $this->web_pic_id = $webPicId;

        return $this;
    }

    /**
     * Get webPicId.
     *
     * @return string|null
     */
    public function getWebPicId()
    {
        return $this->web_pic_id;
    }

    /**
     * Set leaseContractId.
     *
     * @param string|null $leaseContractId
     *
     * @return AdapaySubmitLicense
     */
    public function setLeaseContractId($leaseContractId = null)
    {
        $this->lease_contract_id = $leaseContractId;

        return $this;
    }

    /**
     * Get leaseContractId.
     *
     * @return string|null
     */
    public function getLeaseContractId()
    {
        return $this->lease_contract_id;
    }

    /**
     * Set settleAccountCertificateId.
     *
     * @param string|null $settleAccountCertificateId
     *
     * @return AdapaySubmitLicense
     */
    public function setSettleAccountCertificateId($settleAccountCertificateId = null)
    {
        $this->settle_account_certificate_id = $settleAccountCertificateId;

        return $this;
    }

    /**
     * Get settleAccountCertificateId.
     *
     * @return string|null
     */
    public function getSettleAccountCertificateId()
    {
        return $this->settle_account_certificate_id;
    }

    /**
     * Set bussSupportMaterialsId.
     *
     * @param string|null $bussSupportMaterialsId
     *
     * @return AdapaySubmitLicense
     */
    public function setBussSupportMaterialsId($bussSupportMaterialsId = null)
    {
        $this->buss_support_materials_id = $bussSupportMaterialsId;

        return $this;
    }

    /**
     * Get bussSupportMaterialsId.
     *
     * @return string|null
     */
    public function getBussSupportMaterialsId()
    {
        return $this->buss_support_materials_id;
    }

    /**
     * Set icpRegistrationLicenseId.
     *
     * @param string|null $icpRegistrationLicenseId
     *
     * @return AdapaySubmitLicense
     */
    public function setIcpRegistrationLicenseId($icpRegistrationLicenseId = null)
    {
        $this->icp_registration_license_id = $icpRegistrationLicenseId;

        return $this;
    }

    /**
     * Get icpRegistrationLicenseId.
     *
     * @return string|null
     */
    public function getIcpRegistrationLicenseId()
    {
        return $this->icp_registration_license_id;
    }

    /**
     * Set industryQualifyDocType.
     *
     * @param string|null $industryQualifyDocType
     *
     * @return AdapaySubmitLicense
     */
    public function setIndustryQualifyDocType($industryQualifyDocType = null)
    {
        $this->industry_qualify_doc_type = $industryQualifyDocType;

        return $this;
    }

    /**
     * Get industryQualifyDocType.
     *
     * @return string|null
     */
    public function getIndustryQualifyDocType()
    {
        return $this->industry_qualify_doc_type;
    }

    /**
     * Set industryQualifyDocLicenseId.
     *
     * @param string|null $industryQualifyDocLicenseId
     *
     * @return AdapaySubmitLicense
     */
    public function setIndustryQualifyDocLicenseId($industryQualifyDocLicenseId = null)
    {
        $this->industry_qualify_doc_license_id = $industryQualifyDocLicenseId;

        return $this;
    }

    /**
     * Get industryQualifyDocLicenseId.
     *
     * @return string|null
     */
    public function getIndustryQualifyDocLicenseId()
    {
        return $this->industry_qualify_doc_license_id;
    }

    /**
     * Set shareholderInfoList.
     *
     * @param string|null $shareholderInfoList
     *
     * @return AdapaySubmitLicense
     */
    public function setShareholderInfoList($shareholderInfoList = null)
    {
        $this->shareholder_info_list = $shareholderInfoList;

        return $this;
    }

    /**
     * Get shareholderInfoList.
     *
     * @return string|null
     */
    public function getShareholderInfoList()
    {
        return $this->shareholder_info_list;
    }

    /**
     * Set auditStatus.
     *
     * @param string|null $auditStatus
     *
     * @return AdapaySubmitLicense
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
     * Set auditDesc.
     *
     * @param string|null $auditDesc
     *
     * @return AdapaySubmitLicense
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
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapaySubmitLicense
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
     * @return AdapaySubmitLicense
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
     * Set isSms.
     *
     * @param string|null $isSms
     *
     * @return AdapaySubmitLicense
     */
    public function setIsSms($isSms = null)
    {
        $this->is_sms = $isSms;

        return $this;
    }

    /**
     * Get isSms.
     *
     * @return string|null
     */
    public function getIsSms()
    {
        return $this->is_sms;
    }
}
