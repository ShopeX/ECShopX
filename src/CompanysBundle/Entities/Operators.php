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

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Operators 账号表
 *
 * @ORM\Table(name="operators", options={"comment":"账号表"}, indexes={
 *    @ORM\Index(name="idx_eid", columns={"eid"})
 * },uniqueConstraints={
 *    @ORM\UniqueConstraint(name="idx_passportuid", columns={"passport_uid"}),
 * })
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\OperatorsRepository")
 */
class Operators
{
    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", options={"comment":"账号id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="login_name", type="string", nullable=true, options={"comment":"员工账号名"})
     */
    private $login_name;

    /**
    * @var string
    *
    * @ORM\Column(name="operator_type", type="string", options={"comment":"操作员类型类型。admin:超级管理员;staff:员工;distributor:店铺管理员;dealer:经销商;merchant:商户;supplier:供应商;self_delivery_staff:自配送员", "default": "admin"})
    */
    private $operator_type = "admin";

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string",  nullable=true)
     */
    private $password;

    /**
     * @var integer
     *
     * @ORM\Column(name="eid", type="string", nullable=true)
     */
    private $eid;

    /**
     * @var integer
     *
     * @ORM\Column(name="passport_uid", type="string", nullable=true)
     */
    private $passport_uid;

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
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=true, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_ids", type="text",  nullable=true, options={"comment":"员工管理的店铺id集合"})
     */
    private $distributor_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_ids", type="text",  nullable=true, options={"comment":"员工管理的门店id集合"})
     */
    private $shop_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string",  nullable=true, options={"comment":"名称"})
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="head_portrait", type="string",  nullable=true, options={"comment":"头像"})
     */
    private $head_portrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="regionauth_id", type="bigint", options={"comment":"区域id", "default": 0})
     */
    private $regionauth_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="contact", type="string", length=500, nullable=true, options={"comment":"联系人姓名"})
     */
    private $contact;

    /**
     * @var string
     *
     * @ORM\Column(name="split_ledger_info", type="string", nullable=true, options={"comment":"分账信息"})
     */
    private $split_ledger_info;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_disable", type="boolean", nullable=true, options={"comment":"是否禁用。1:是 0:否", "default": 0})
     */
    private $is_disable = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="adapay_open_account_time", type="string", nullable=true, options={"comment":"adapay子商户开户时间"})
     */
    private $adapay_open_account_time;

    /**
     * @var string
     *
     * @ORM\Column(name="dealer_parent_id", type="string", nullable=true, options={"comment":"经销商子账号父级id"})
     */
    private $dealer_parent_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_dealer_main", type="boolean", options={"comment":"是否是经销商主账号。1:是,0:否", "default": 1})
     */
    private $is_dealer_main = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", nullable=true, options={"comment":"商户id", "default": 0})
     */
    private $merchant_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_merchant_main", type="boolean", options={"comment":"是否是商户端超级管理员。1:是,0:否", "default": 0})
     */
    private $is_merchant_main = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_distributor_main", type="boolean", options={"comment":"是否是店铺超级管理员.1:是,0:否", "default": 0})
     */
    private $is_distributor_main = 0;

    /**
     * Get operatorId
     *
     * @return integer
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return Operators
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        // CRC: 2367340174
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return Operators
     */
    public function setPassword($password)
    {
        // CRC: 2367340174
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set eid
     *
     * @param string $eid
     *
     * @return Operators
     */
    public function setEid($eid)
    {
        $this->eid = $eid;

        return $this;
    }

    /**
     * Get eid
     *
     * @return string
     */
    public function getEid()
    {
        return $this->eid;
    }

    /**
     * Set passportUid
     *
     * @param string $passportUid
     *
     * @return Operators
     */
    public function setPassportUid($passportUid)
    {
        $this->passport_uid = $passportUid;

        return $this;
    }

    /**
     * Get passportUid
     *
     * @return string
     */
    public function getPassportUid()
    {
        return $this->passport_uid;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Operators
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return Operators
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set operatorsType
     *
     * @param string $operatorType
     *
     * @return Operators
     */
    public function setOperatorType($operatorType)
    {
        $this->operator_type = $operatorType;

        return $this;
    }

    /**
     * Get operatorsType
     *
     * @return string
     */
    public function getOperatorType()
    {
        return $this->operator_type;
    }

    /**
     * Set loginName
     *
     * @param string $loginName
     *
     * @return Operators
     */
    public function setLoginName($loginName)
    {
        $this->login_name = $loginName;

        return $this;
    }

    /**
     * Get loginName
     *
     * @return string
     */
    public function getLoginName()
    {
        return $this->login_name;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Operators
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set distributorIds
     *
     * @param string $distributorIds
     *
     * @return Operators
     */
    public function setDistributorIds($distributorIds)
    {
        $this->distributor_ids = $distributorIds;

        return $this;
    }

    /**
     * Get distributorIds
     *
     * @return string
     */
    public function getDistributorIds()
    {
        return $this->distributor_ids;
    }

    /**
     * Set shopIds
     *
     * @param string $shopIds
     *
     * @return Operators
     */
    public function setShopIds($shopIds)
    {
        $this->shop_ids = $shopIds;

        return $this;
    }

    /**
     * Get shopIds
     *
     * @return string
     */
    public function getShopIds()
    {
        return $this->shop_ids;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return Operators
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set headPortrait
     *
     * @param string $headPortrait
     *
     * @return Operators
     */
    public function setHeadPortrait($headPortrait)
    {
        $this->head_portrait = $headPortrait;

        return $this;
    }

    /**
     * Get headPortrait
     *
     * @return string
     */
    public function getHeadPortrait()
    {
        return $this->head_portrait;
    }

    /**
     * Set regionauthId.
     *
     * @param int $regionauthId
     *
     * @return Operators
     */
    public function setRegionauthId($regionauthId)
    {
        $this->regionauth_id = $regionauthId;

        return $this;
    }

    /**
     * Get regionauthId.
     *
     * @return int
     */
    public function getRegionauthId()
    {
        return $this->regionauth_id;
    }

    /**
     * Set contact
     *
     * @param string $contact
     *
     * @return Operators
     */
    public function setContact($contact)
    {
        $this->contact = fixedencrypt($contact);

        return $this;
    }

    /**
     * Get contact
     *
     * @return string
     */
    public function getContact()
    {
        return fixeddecrypt($this->contact);
    }


    /**
     * Set splitLedgerInfo.
     *
     * @param string|null $splitLedgerInfo
     *
     * @return Operators
     */
    public function setSplitLedgerInfo($splitLedgerInfo = null)
    {
        $this->split_ledger_info = $splitLedgerInfo;

        return $this;
    }

    /**
     * Get splitLedgerInfo.
     *
     * @return string|null
     */
    public function getSplitLedgerInfo()
    {
        return $this->split_ledger_info;
    }

    /**
     * Set isDisable.
     *
     * @param int|null $isDisable
     *
     * @return Operators
     */
    public function setIsDisable($isDisable = null)
    {
        $this->is_disable = $isDisable;

        return $this;
    }

    /**
     * Get isDisable.
     *
     * @return int|null
     */
    public function getIsDisable()
    {
        return $this->is_disable;
    }

    /**
     * Set adapayOpenAccountTime.
     *
     * @param string|null $adapayOpenAccountTime
     *
     * @return Operators
     */
    public function setAdapayOpenAccountTime($adapayOpenAccountTime = null)
    {
        $this->adapay_open_account_time = $adapayOpenAccountTime;

        return $this;
    }

    /**
     * Get adapayOpenAccountTime.
     *
     * @return string|null
     */
    public function getAdapayOpenAccountTime()
    {
        return $this->adapay_open_account_time;
    }

    /**
     * Set dealerParentId.
     *
     * @param string|null $dealerParentId
     *
     * @return Operators
     */
    public function setDealerParentId($dealerParentId = null)
    {
        $this->dealer_parent_id = $dealerParentId;

        return $this;
    }

    /**
     * Get dealerParentId.
     *
     * @return string|null
     */
    public function getDealerParentId()
    {
        return $this->dealer_parent_id;
    }

    /**
     * Set isDealerMain.
     *
     * @param string|null $isDealerMain
     *
     * @return Operators
     */
    public function setIsDealerMain($isDealerMain = null)
    {
        $this->is_dealer_main = $isDealerMain;

        return $this;
    }

    /**
     * Get isDealerMain.
     *
     * @return string|null
     */
    public function getIsDealerMain()
    {
        return $this->is_dealer_main;
    }

    /**
     * Set merchantId.
     *
     * @param int|null $merchantId
     *
     * @return Operators
     */
    public function setMerchantId($merchantId = null)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return int|null
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set isMerchantMain.
     *
     * @param string $isMerchantMain
     *
     * @return Operators
     */
    public function setIsMerchantMain($isMerchantMain)
    {
        $this->is_merchant_main = $isMerchantMain;

        return $this;
    }

    /**
     * Get isMerchantMain.
     *
     * @return string
     */
    public function getIsMerchantMain()
    {
        return $this->is_merchant_main;
    }

    /**
     * Set isDistributorMain.
     *
     * @param bool $isDistributorMain
     *
     * @return Operators
     */
    public function setIsDistributorMain($isDistributorMain)
    {
        $this->is_distributor_main = $isDistributorMain;

        return $this;
    }

    /**
     * Get isDistributorMain.
     *
     * @return bool
     */
    public function getIsDistributorMain()
    {
        return $this->is_distributor_main;
    }
}
