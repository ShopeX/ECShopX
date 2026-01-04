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
 * AdapaySettleAccount 结算账户
 *
 * @ORM\Table(name="adapay_settle_account", options={"comment":"结算账户"},
 *     indexes={
 *         @ORM\Index(name="idx_member_id", columns={"member_id"}),
 *         @ORM\Index(name="idx_card_id", columns={"card_id"}, options={"lengths": {64}}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_cert_id", columns={"cert_id"}, options={"lengths": {64}}),
 *         @ORM\Index(name="idx_settle_account_id", columns={"settle_account_id"})
 *     },
 * )
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapaySettleAccountRepository")
 */
class AdapaySettleAccount
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
     * @ORM\Column(name="app_id", type="string", nullable=true, length=100, options={"comment":"应用app_id", "default": ""})
     */
    private $app_id;

    /**
     * @var string
     *
     * @ORM\Column(name="settle_account_id", type="string", nullable=true, length=100, options={"comment":"由 Adapay 生成的结算账户对象 id", "default": ""})
     */
    private $settle_account_id;

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
     * @var string
     *
     * @ORM\Column(name="channel", type="string", nullable=true, length=50, options={"comment":"目前仅支持：bank_account（银行卡）", "default": "bank_account"})
     */
    private $channel;

    /**
     * @var string
     *
     * @ORM\Column(name="card_id", type="string", nullable=true, length=255, options={"comment":"银行卡号，如果需要自动开结算账户，本字段必填","default":""})
     */
    private $card_id;

    /**
     * @var string
     *
     * @ORM\Column(name="card_name", type="string", nullable=true, length=500, options={"comment":"银行卡对应的户名，如果需要自动开结算账户，本字段必填；若银行账户类型是对公，必须与企业名称一致","default":""})
     */
    private $card_name;

    /**
     * @var string
     *
     * @ORM\Column(name="cert_id", type="string", nullable=true, length=255, options={"comment":"证件号","default":""})
     */
    private $cert_id;

    /**
     * @var string
     *
     * 00 身份证
     *
     * @ORM\Column(name="cert_type", type="string", nullable=true, length=10, options={"comment":"证件类型，仅支持：00-身份证","default":"00"})
     */
    private $cert_type;

    /**
     * @var string
     *
     * @ORM\Column(name="tel_no", type="string", nullable=true, length=255, options={"comment":"用户手机号","default":""})
     */
    private $tel_no;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_code", type="string", nullable=true, length=30, options={"comment":"银行编码，详见附录 银行代码，银行账户类型对公时，必填","default":""})
     */
    private $bank_code;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", type="string", nullable=true, length=30, options={"comment":"开户银行名称","default":""})
     */
    private $bank_name;

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
     * @ORM\Column(name="prov_code", nullable=true, type="string", length=10, options={"comment":"省份编码, 银行账户类型为对公时，必填", "default": ""})
     */
    private $prov_code;

    /**
     * @var string
     *
     * @ORM\Column(name="area_code", type="string", nullable=true, length=10, options={"comment":"地区编码, 银行账户类型为对公时，必填", "default": ""})
     */
    private $area_code;

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
        // ShopEx EcShopX Service Component
        return $this->id;
    }

    /**
     * Set appId.
     *
     * @param string $appId
     *
     * @return AdapaySettleAccount
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
        return $this->app_id;
    }

    /**
     * Set memberId.
     *
     * @param int $memberId
     *
     * @return AdapaySettleAccount
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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return AdapaySettleAccount
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
     * Set channel.
     *
     * @param string $channel
     *
     * @return AdapaySettleAccount
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Get channel.
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set cardId.
     *
     * @param string $cardId
     *
     * @return AdapaySettleAccount
     */
    public function setCardId($cardId)
    {
        $this->card_id = fixedencrypt($cardId);

        return $this;
    }

    /**
     * Get cardId.
     *
     * @return string
     */
    public function getCardId()
    {
        return fixeddecrypt($this->card_id);
    }

    /**
     * Set cardName.
     *
     * @param string $cardName
     *
     * @return AdapaySettleAccount
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
     * Set certId.
     *
     * @param string $certId
     *
     * @return AdapaySettleAccount
     */
    public function setCertId($certId)
    {
        $this->cert_id = fixedencrypt($certId);

        return $this;
    }

    /**
     * Get certId.
     *
     * @return string
     */
    public function getCertId()
    {
        return fixeddecrypt($this->cert_id);
    }

    /**
     * Set certType.
     *
     * @param string $certType
     *
     * @return AdapaySettleAccount
     */
    public function setCertType($certType)
    {
        $this->cert_type = $certType;

        return $this;
    }

    /**
     * Get certType.
     *
     * @return string
     */
    public function getCertType()
    {
        return $this->cert_type;
    }

    /**
     * Set telNo.
     *
     * @param string $telNo
     *
     * @return AdapaySettleAccount
     */
    public function setTelNo($telNo)
    {
        $this->tel_no = fixedencrypt($telNo);

        return $this;
    }

    /**
     * Get telNo.
     *
     * @return string
     */
    public function getTelNo()
    {
        return fixeddecrypt($this->tel_no);
    }

    /**
     * Set bankCode.
     *
     * @param string $bankCode
     *
     * @return AdapaySettleAccount
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
     * Set bankName.
     *
     * @param string $bankName
     *
     * @return AdapaySettleAccount
     */
    public function setBankName($bankName)
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
     * Set bankAcctType.
     *
     * @param string $bankAcctType
     *
     * @return AdapaySettleAccount
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
     * @param string|null $provCode
     *
     * @return AdapaySettleAccount
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
     * @return AdapaySettleAccount
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
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AdapaySettleAccount
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
     * @return AdapaySettleAccount
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
     * Set settleAccountId.
     *
     * @param string|null $settleAccountId
     *
     * @return AdapaySettleAccount
     */
    public function setSettleAccountId($settleAccountId = null)
    {
        $this->settle_account_id = $settleAccountId;

        return $this;
    }

    /**
     * Get settleAccountId.
     *
     * @return string|null
     */
    public function getSettleAccountId()
    {
        return $this->settle_account_id;
    }
}
