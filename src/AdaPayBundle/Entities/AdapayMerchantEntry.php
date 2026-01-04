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
 * AdapayMerchantEntry adapay开户进件
 *
 * @ORM\Table(name="adapay_merchant_entry", options={"comment":"adapay开户进件"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_request_id", columns={"request_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayMerchantEntryRepository")
 */
class AdapayMerchantEntry
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
     * @ORM\Column(name="request_id", type="string", length=100, options={"comment":"请求ID"})
     */
    private $request_id;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_phone", type="string", length=255, options={"comment":"注册手机号"})
     */
    private $usr_phone;

    /**
     * @var string
     *
     * @ORM\Column(name="cont_name", type="string", length=500, options={"comment":"联系人姓名"})
     */
    private $cont_name;

    /**
     * @var string
     *
     * @ORM\Column(name="cont_phone", type="string", length=255, options={"comment":"联系人手机号码"})
     */
    private $cont_phone;

    /**
     * @var string
     *
     * @ORM\Column(name="customer_email", type="string", length=100, options={"comment":"电子邮箱"})
     */
    private $customer_email;

    /**
     * @var string
     *
     * @ORM\Column(name="mer_name", type="string", length=50, options={"comment":"商户名，小微商户填负责人姓名"})
     */
    private $mer_name;

    /**
     * @var string
     *
     * @ORM\Column(name="mer_short_name", type="string", length=50, options={"comment":"商户名简称"})
     */
    private $mer_short_name;

    /**
     * @var string
     *
     * @ORM\Column(name="license_code", type="string", length=50, nullable=true, options={"comment":"营业执照编码，如三证合一传三证合一码，企业时必填"})
     */
    private $license_code;

    /**
     * @var string
     *
     * @ORM\Column(name="reg_addr", type="string", length=100, options={"comment":"注册地址"})
     */
    private $reg_addr;

    /**
     * @var string
     *
     * @ORM\Column(name="cust_addr", type="string", length=100, options={"comment":"经营地址"})
     */
    private $cust_addr;

    /**
     * @var string
     *
     * @ORM\Column(name="cust_tel", type="string", length=255, options={"comment":"商户电话"})
     */
    private $cust_tel;

    /**
     * @var string
     *
     * @ORM\Column(name="mer_start_valid_date", type="string", length=30, nullable=true, options={"comment":"商户有效日期（始），格式 YYYYMMDD （若开户企业类商户，必填）"})
     */
    private $mer_start_valid_date;

    /**
     * @var string
     *
     * @ORM\Column(name="mer_valid_date", type="string", length=30, nullable=true, options={"comment":"商户有效日期（至），格式 YYYYMMDD（若为长期有效，固定为“20991231”;若开户企业类商户，必填）"})
     */
    private $mer_valid_date;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_name", type="string", length=500, options={"comment":"法人/负责人 姓名"})
     */
    private $legal_name;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_type", type="string", length=10, options={"comment":"法人/负责人证件类型，0-身份证"})
     */
    private $legal_type;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_idno", type="string", length=255, options={"comment":"法人/负责人证件号码"})
     */
    private $legal_idno;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_mp", type="string", length=255, options={"comment":"法人/负责人手机号"})
     */
    private $legal_mp;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_start_cert_id_expires", type="string", length=30, options={"comment":"法人/负责人身份证有效期（始），格式 YYYYMMDD"})
     */
    private $legal_start_cert_id_expires;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_id_expires", type="string", length=30, options={"comment":"法人/负责人身份证有效期（至），格式 YYYYMMDD"})
     */
    private $legal_id_expires;

    /**
     * @var string
     *
     * @ORM\Column(name="card_id_mask", type="string", length=255, options={"comment":"结算银行卡号"})
     */
    private $card_id_mask;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_code", type="string", length=20, options={"comment":"结算银行卡所属银行code"})
     */
    private $bank_code;

    /**
     * @var string
     *
     * @ORM\Column(name="card_name", type="string", length=500, options={"comment":"结算银行卡开户姓名"})
     */
    private $card_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_acct_type", type="string", length=5, options={"comment":"结算银行账户类型，1 : 对公， 2 : 对私。小微只能是对私"})
     */
    private $bank_acct_type;

    /**
     * @var string
     *
     * @ORM\Column(name="prov_code", type="string", length=10, options={"comment":"结算银行卡省份编码"})
     */
    private $prov_code;

    /**
     * @var string
     *
     * @ORM\Column(name="area_code", type="string", length=10, options={"comment":"结算银行卡地区编码"})
     */
    private $area_code;

    /**
     * @var string
     *
     * @ORM\Column(name="rsa_public_key", type="text", nullable=true, options={"comment":"商户rsa 公钥"})
     */
    private $rsa_public_key;

    /**
     * @var string
     *
     * @ORM\Column(name="entry_mer_type", type="string", length=10, options={"comment":"商户类型：1-企业；2-小微"})
     */
    private $entry_mer_type;

    /**
     * @var string
     *
     * @ORM\Column(name="test_api_key", type="string", nullable=true, options={"comment":"测试API Key"})
     */
    private $test_api_key;

    /**
     * @var string
     *
     * @ORM\Column(name="live_api_key", type="string", nullable=true, options={"comment":"生产API Key"})
     */
    private $live_api_key;

    /**
     * @var string
     *
     * @ORM\Column(name="login_pwd", type="string", length=1024, nullable=true, options={"comment":"初始密码"})
     */
    private $login_pwd;

    /**
     * @var string
     *
     * @ORM\Column(name="app_id_list", type="text", nullable=true, options={"comment":"应用ID列表"})
     */
    private $app_id_list;

    /**
     * @var string
     *
     * @ORM\Column(name="sign_view_url", type="string", length=1024, nullable=true, options={"comment":"合同查看地址"})
     */
    private $sign_view_url;

    /**
     * @var string
     *
     * @ORM\Column(name="is_sms", type="string", length=20, nullable=true, options={"comment":"是否短信提醒: 1:是  0:否"})
     */
    private $is_sms;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, nullable=true, options={"comment":"接口调用状态，succeeded - 成功 failed - 失败 pending - 处理中"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="error_msg", type="string", length=500, nullable=true, options={"comment":"错误描述"})
     */
    private $error_msg;

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
     * @return AdapayMerchantEntry
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
     * Set requestId.
     *
     * @param string $requestId
     *
     * @return AdapayMerchantEntry
     */
    public function setRequestId($requestId)
    {
        // Ref: 1996368445
        $this->request_id = $requestId;

        return $this;
    }

    /**
     * Get requestId.
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->request_id;
    }

    /**
     * Set usrPhone.
     *
     * @param string $usrPhone
     *
     * @return AdapayMerchantEntry
     */
    public function setUsrPhone($usrPhone)
    {
        $this->usr_phone = fixedencrypt($usrPhone);

        return $this;
    }

    /**
     * Get usrPhone.
     *
     * @return string
     */
    public function getUsrPhone()
    {
        return fixeddecrypt($this->usr_phone);
    }

    /**
     * Set contName.
     *
     * @param string $contName
     *
     * @return AdapayMerchantEntry
     */
    public function setContName($contName)
    {
        $this->cont_name = fixedencrypt($contName);

        return $this;
    }

    /**
     * Get contName.
     *
     * @return string
     */
    public function getContName()
    {
        return fixeddecrypt($this->cont_name);
    }

    /**
     * Set contPhone.
     *
     * @param string $contPhone
     *
     * @return AdapayMerchantEntry
     */
    public function setContPhone($contPhone)
    {
        $this->cont_phone = fixedencrypt($contPhone);

        return $this;
    }

    /**
     * Get contPhone.
     *
     * @return string
     */
    public function getContPhone()
    {
        return fixeddecrypt($this->cont_phone);
    }

    /**
     * Set customerEmail.
     *
     * @param string $customerEmail
     *
     * @return AdapayMerchantEntry
     */
    public function setCustomerEmail($customerEmail)
    {
        $this->customer_email = $customerEmail;

        return $this;
    }

    /**
     * Get customerEmail.
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->customer_email;
    }

    /**
     * Set merName.
     *
     * @param string $merName
     *
     * @return AdapayMerchantEntry
     */
    public function setMerName($merName)
    {
        $this->mer_name = $merName;

        return $this;
    }

    /**
     * Get merName.
     *
     * @return string
     */
    public function getMerName()
    {
        return $this->mer_name;
    }

    /**
     * Set merShortName.
     *
     * @param string $merShortName
     *
     * @return AdapayMerchantEntry
     */
    public function setMerShortName($merShortName)
    {
        $this->mer_short_name = $merShortName;

        return $this;
    }

    /**
     * Get merShortName.
     *
     * @return string
     */
    public function getMerShortName()
    {
        return $this->mer_short_name;
    }

    /**
     * Set licenseCode.
     *
     * @param string|null $licenseCode
     *
     * @return AdapayMerchantEntry
     */
    public function setLicenseCode($licenseCode = null)
    {
        $this->license_code = $licenseCode;

        return $this;
    }

    /**
     * Get licenseCode.
     *
     * @return string|null
     */
    public function getLicenseCode()
    {
        return $this->license_code;
    }

    /**
     * Set regAddr.
     *
     * @param string $regAddr
     *
     * @return AdapayMerchantEntry
     */
    public function setRegAddr($regAddr)
    {
        $this->reg_addr = $regAddr;

        return $this;
    }

    /**
     * Get regAddr.
     *
     * @return string
     */
    public function getRegAddr()
    {
        return $this->reg_addr;
    }

    /**
     * Set custAddr.
     *
     * @param string $custAddr
     *
     * @return AdapayMerchantEntry
     */
    public function setCustAddr($custAddr)
    {
        $this->cust_addr = $custAddr;

        return $this;
    }

    /**
     * Get custAddr.
     *
     * @return string
     */
    public function getCustAddr()
    {
        return $this->cust_addr;
    }

    /**
     * Set custTel.
     *
     * @param string $custTel
     *
     * @return AdapayMerchantEntry
     */
    public function setCustTel($custTel)
    {
        $this->cust_tel = fixedencrypt($custTel);

        return $this;
    }

    /**
     * Get custTel.
     *
     * @return string
     */
    public function getCustTel()
    {
        return fixeddecrypt($this->cust_tel);
    }

    /**
     * Set merStartValidDate.
     *
     * @param string|null $merStartValidDate
     *
     * @return AdapayMerchantEntry
     */
    public function setMerStartValidDate($merStartValidDate = null)
    {
        $this->mer_start_valid_date = $merStartValidDate;

        return $this;
    }

    /**
     * Get merStartValidDate.
     *
     * @return string|null
     */
    public function getMerStartValidDate()
    {
        return $this->mer_start_valid_date;
    }

    /**
     * Set merValidDate.
     *
     * @param string|null $merValidDate
     *
     * @return AdapayMerchantEntry
     */
    public function setMerValidDate($merValidDate = null)
    {
        $this->mer_valid_date = $merValidDate;

        return $this;
    }

    /**
     * Get merValidDate.
     *
     * @return string|null
     */
    public function getMerValidDate()
    {
        return $this->mer_valid_date;
    }

    /**
     * Set legalName.
     *
     * @param string $legalName
     *
     * @return AdapayMerchantEntry
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
     * Set legalType.
     *
     * @param string $legalType
     *
     * @return AdapayMerchantEntry
     */
    public function setLegalType($legalType)
    {
        $this->legal_type = $legalType;

        return $this;
    }

    /**
     * Get legalType.
     *
     * @return string
     */
    public function getLegalType()
    {
        return $this->legal_type;
    }

    /**
     * Set legalIdno.
     *
     * @param string $legalIdno
     *
     * @return AdapayMerchantEntry
     */
    public function setLegalIdno($legalIdno)
    {
        $this->legal_idno = fixedencrypt($legalIdno);

        return $this;
    }

    /**
     * Get legalIdno.
     *
     * @return string
     */
    public function getLegalIdno()
    {
        return fixeddecrypt($this->legal_idno);
    }

    /**
     * Set legalMp.
     *
     * @param string $legalMp
     *
     * @return AdapayMerchantEntry
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
     * Set legalStartCertIdExpires.
     *
     * @param string $legalStartCertIdExpires
     *
     * @return AdapayMerchantEntry
     */
    public function setLegalStartCertIdExpires($legalStartCertIdExpires)
    {
        $this->legal_start_cert_id_expires = $legalStartCertIdExpires;

        return $this;
    }

    /**
     * Get legalStartCertIdExpires.
     *
     * @return string
     */
    public function getLegalStartCertIdExpires()
    {
        return $this->legal_start_cert_id_expires;
    }

    /**
     * Set legalIdExpires.
     *
     * @param string $legalIdExpires
     *
     * @return AdapayMerchantEntry
     */
    public function setLegalIdExpires($legalIdExpires)
    {
        $this->legal_id_expires = $legalIdExpires;

        return $this;
    }

    /**
     * Get legalIdExpires.
     *
     * @return string
     */
    public function getLegalIdExpires()
    {
        return $this->legal_id_expires;
    }

    /**
     * Set cardIdMask.
     *
     * @param string $cardIdMask
     *
     * @return AdapayMerchantEntry
     */
    public function setCardIdMask($cardIdMask)
    {
        $this->card_id_mask = fixedencrypt($cardIdMask);

        return $this;
    }

    /**
     * Get cardIdMask.
     *
     * @return string
     */
    public function getCardIdMask()
    {
        return fixeddecrypt($this->card_id_mask);
    }

    /**
     * Set bankCode.
     *
     * @param string $bankCode
     *
     * @return AdapayMerchantEntry
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
     * Set cardName.
     *
     * @param string $cardName
     *
     * @return AdapayMerchantEntry
     */
    public function setCardName($cardName)
    {
        $this->card_name = fixedencrypt($cardName);

        return $this;
    }

    /**
     * Get cardName.
     *
     * @return string
     */
    public function getCardName()
    {
        return fixeddecrypt($this->card_name);
    }

    /**
     * Set bankAcctType.
     *
     * @param string $bankAcctType
     *
     * @return AdapayMerchantEntry
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
     * Set provCode.
     *
     * @param string $provCode
     *
     * @return AdapayMerchantEntry
     */
    public function setProvCode($provCode)
    {
        $this->prov_code = $provCode;

        return $this;
    }

    /**
     * Get provCode.
     *
     * @return string
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
     * @return AdapayMerchantEntry
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
     * Set rsaPublicKey.
     *
     * @param string $rsaPublicKey
     *
     * @return AdapayMerchantEntry
     */
    public function setRsaPublicKey($rsaPublicKey)
    {
        $this->rsa_public_key = $rsaPublicKey;

        return $this;
    }

    /**
     * Get rsaPublicKey.
     *
     * @return string
     */
    public function getRsaPublicKey()
    {
        return $this->rsa_public_key;
    }

    /**
     * Set entryMerType.
     *
     * @param string $entryMerType
     *
     * @return AdapayMerchantEntry
     */
    public function setEntryMerType($entryMerType)
    {
        $this->entry_mer_type = $entryMerType;

        return $this;
    }

    /**
     * Get entryMerType.
     *
     * @return string
     */
    public function getEntryMerType()
    {
        return $this->entry_mer_type;
    }

    /**
     * Set testApiKey.
     *
     * @param string|null $testApiKey
     *
     * @return AdapayMerchantEntry
     */
    public function setTestApiKey($testApiKey = null)
    {
        $this->test_api_key = $testApiKey;

        return $this;
    }

    /**
     * Get testApiKey.
     *
     * @return string|null
     */
    public function getTestApiKey()
    {
        return $this->test_api_key;
    }

    /**
     * Set liveApiKey.
     *
     * @param string|null $liveApiKey
     *
     * @return AdapayMerchantEntry
     */
    public function setLiveApiKey($liveApiKey = null)
    {
        $this->live_api_key = $liveApiKey;

        return $this;
    }

    /**
     * Get liveApiKey.
     *
     * @return string|null
     */
    public function getLiveApiKey()
    {
        return $this->live_api_key;
    }

    /**
     * Set loginPwd.
     *
     * @param string|null $loginPwd
     *
     * @return AdapayMerchantEntry
     */
    public function setLoginPwd($loginPwd = null)
    {
        $this->login_pwd = $loginPwd;

        return $this;
    }

    /**
     * Get loginPwd.
     *
     * @return string|null
     */
    public function getLoginPwd()
    {
        return $this->login_pwd;
    }

    /**
     * Set appIdList.
     *
     * @param string|null $appIdList
     *
     * @return AdapayMerchantEntry
     */
    public function setAppIdList($appIdList = null)
    {
        $this->app_id_list = $appIdList;

        return $this;
    }

    /**
     * Get appIdList.
     *
     * @return string|null
     */
    public function getAppIdList()
    {
        return $this->app_id_list;
    }

    /**
     * Set signViewUrl.
     *
     * @param string|null $signViewUrl
     *
     * @return AdapayMerchantEntry
     */
    public function setSignViewUrl($signViewUrl = null)
    {
        $this->sign_view_url = $signViewUrl;

        return $this;
    }

    /**
     * Get signViewUrl.
     *
     * @return string|null
     */
    public function getSignViewUrl()
    {
        return $this->sign_view_url;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return AdapayMerchantEntry
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set errorMsg.
     *
     * @param string|null $errorMsg
     *
     * @return AdapayMerchantEntry
     */
    public function setErrorMsg($errorMsg = null)
    {
        $this->error_msg = $errorMsg;

        return $this;
    }

    /**
     * Get errorMsg.
     *
     * @return string|null
     */
    public function getErrorMsg()
    {
        return $this->error_msg;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapayMerchantEntry
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
     * @return AdapayMerchantEntry
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
     * @return AdapayMerchantEntry
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
