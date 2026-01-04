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

namespace DistributionBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CashWithdrawal 分销商佣金提现记录表
 *
 * @ORM\Table(name="distribution_cash_withdrawal", options={"comment"="分销商佣金提现记录表"})
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\CashWithdrawalRepository")
 */
class CashWithdrawal
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
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="string", options={"comment":"分销商id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"门店id", "default": 0})
     */
    private $shop_id = 0 ;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_name", type="string", options={"comment":"分销商真实姓名"})
     */
    private $distributor_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="open_id", type="string", options={"comment":"分销商open_id"})
     */
    private $open_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="string", options={"comment":"会员ID"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_mobile", type="string", length=32)
     */
    private $distributor_mobile;

    /**
     * @var string
     *
     * 提现金额
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
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return CashWithdrawal
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
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return CashWithdrawal
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set distributorName
     *
     * @param integer $distributorName
     *
     * @return CashWithdrawal
     */
    public function setDistributorName($distributorName)
    {
        $this->distributor_name = $distributorName;

        return $this;
    }

    /**
     * Get distributorName
     *
     * @return integer
     */
    public function getDistributorName()
    {
        return $this->distributor_name;
    }

    /**
     * Set openId
     *
     * @param string $openId
     *
     * @return CashWithdrawal
     */
    public function setOpenId($openId)
    {
        $this->open_id = $openId;

        return $this;
    }

    /**
     * Get openId
     *
     * @return string
     */
    public function getOpenId()
    {
        return $this->open_id;
    }

    /**
     * Set userId
     *
     * @param string $userId
     *
     * @return CashWithdrawal
     */
    public function setUserId($userId)
    {
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
     * Set distributorMobile
     *
     * @param string $distributorMobile
     *
     * @return CashWithdrawal
     */
    public function setDistributorMobile($distributorMobile)
    {
        $this->distributor_mobile = $distributorMobile;

        return $this;
    }

    /**
     * Get distributorMobile
     *
     * @return string
     */
    public function getDistributorMobile()
    {
        return $this->distributor_mobile;
    }

    /**
     * Set money
     *
     * @param integer $money
     *
     * @return CashWithdrawal
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
     * @return CashWithdrawal
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
     * @return CashWithdrawal
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
     * Set wxaAppid
     *
     * @param string $wxaAppid
     *
     * @return CashWithdrawal
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
     * @return CashWithdrawal
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
     * @return CashWithdrawal
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
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return CashWithdrawal
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }
}
