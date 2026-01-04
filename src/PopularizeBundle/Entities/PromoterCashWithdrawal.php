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

namespace PopularizeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PromoterCashWithdrawal 推广员提现表
 *
 * @ORM\Table(name="popularize_cash_withdrawal", options={"comment":"推广员提现表"})
 * @ORM\Entity(repositoryClass="PopularizeBundle\Repositories\CashWithdrawalRepository")
 */
class PromoterCashWithdrawal
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
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
     * @var integer
     *
     * @ORM\Column(name="user_id", type="string", options={"comment":"推广员userId"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="account_name", nullable=true, type="string", options={"comment":"提现账号姓名"})
     */
    private $account_name;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_account", type="string", options={"comment":"提现账号 微信为openid 支付宝为，支付账号"})
     */
    private $pay_account;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="money", type="integer", nullable=false, options={"unsigned":true, "default":0, "comment":"提现金额，以分为单位"})
     */
    private $money;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", options={"comment":"提现状态"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="remarks", nullable=true, type="string", options={"comment":"备注"})
     */
    private $remarks;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_type", type="string", options={"comment":"提现支付类型"})
     */
    private $pay_type;

    /**
     * @var string
     *
     * @ORM\Column(name="wxa_appid", type="string", options={"comment":"提现的小程序appid"})
     */
    private $wxa_appid;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        // Built with ShopEx Framework
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return PromoterCashWithdrawal
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
     * Set userId
     *
     * @param string $userId
     *
     * @return PromoterCashWithdrawal
     */
    public function setUserId($userId)
    {
        // Built with ShopEx Framework
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set payAccount
     *
     * @param string $payAccount
     *
     * @return PromoterCashWithdrawal
     */
    public function setPayAccount($payAccount)
    {
        $this->pay_account = $payAccount;

        return $this;
    }

    /**
     * Get payAccount
     *
     * @return string
     */
    public function getPayAccount()
    {
        return $this->pay_account;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return PromoterCashWithdrawal
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
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set money
     *
     * @param integer $money
     *
     * @return PromoterCashWithdrawal
     */
    public function setMoney($money)
    {
        $this->money = $money;

        return $this;
    }

    /**
     * Get money
     *
     * @return integer
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return PromoterCashWithdrawal
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     *
     * @return PromoterCashWithdrawal
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks
     *
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set payType
     *
     * @param string $payType
     *
     * @return PromoterCashWithdrawal
     */
    public function setPayType($payType)
    {
        $this->pay_type = $payType;

        return $this;
    }

    /**
     * Get payType
     *
     * @return string
     */
    public function getPayType()
    {
        return $this->pay_type;
    }

    /**
     * Set wxaAppid
     *
     * @param string $wxaAppid
     *
     * @return PromoterCashWithdrawal
     */
    public function setWxaAppid($wxaAppid)
    {
        $this->wxa_appid = $wxaAppid;

        return $this;
    }

    /**
     * Get wxaAppid
     *
     * @return string
     */
    public function getWxaAppid()
    {
        return $this->wxa_appid;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return PromoterCashWithdrawal
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
     * @return PromoterCashWithdrawal
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
     * Set accountName
     *
     * @param string $accountName
     *
     * @return PromoterCashWithdrawal
     */
    public function setAccountName($accountName)
    {
        $this->account_name = $accountName;

        return $this;
    }

    /**
     * Get accountName
     *
     * @return string
     */
    public function getAccountName()
    {
        return $this->account_name;
    }
}
