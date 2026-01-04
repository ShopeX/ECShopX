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

namespace KaquanBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * SalespersonGiveCoupons 导购员发放给用户优惠券记录
 *
 * @ORM\Table(name="kaquan_salesperson_give_coupons", options={"comment":"导购员发放给用户优惠券记录"})
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\SalespersonGiveCouponsRepository")
 */
class SalespersonGiveCoupons
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", length=64, options={"comment":"自增id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", length=64, options={"comment":""})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_id", type="bigint", length=64, options={"comment":"导购员id"})
     */
    private $salesperson_id;

    /**
     * @var string
     *
     * @ORM\Column(name="salesperson_name", type="string", options={"comment":"导购员名称"})
     */
    private $salesperson_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_name", type="string", options={"comment":"会员名称", "default": ""})
     */
    private $user_name = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="coupons_id", type="bigint", options={"comment":"优惠券id"})
     */
    private $coupons_id;

    /**
     * @var string
     *
     * @ORM\Column(name="coupons_name", type="string", options={"comment":"优惠券名称"})
     */
    private $coupons_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="number", type="integer", options={"comment":"发送优惠券数量", "default": 0})
     */
    private $number;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", options={"comment":"发放优惠券状态，1成功，0失败", "default":1})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="fail_reason", nullable=true, type="string", options={"comment":"失败原因"})
     */
    private $fail_reason;


    /**
     * @var integer
     *
     * @ORM\Column(name="give_time", type="integer", options={"comment":"发送优惠券时间"})
     */
    private $give_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated", type="integer", options={"comment":"修改时间"})
     */
    private $updated;

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
     * @return SalespersonGiveCoupons
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
     * Set salespersonId
     *
     * @param integer $salespersonId
     *
     * @return SalespersonGiveCoupons
     */
    public function setSalespersonId($salespersonId)
    {
        $this->salesperson_id = $salespersonId;

        return $this;
    }

    /**
     * Get salespersonId
     *
     * @return integer
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set salespersonName
     *
     * @param string $salespersonName
     *
     * @return SalespersonGiveCoupons
     */
    public function setSalespersonName($salespersonName)
    {
        $this->salesperson_name = $salespersonName;

        return $this;
    }

    /**
     * Get salespersonName
     *
     * @return string
     */
    public function getSalespersonName()
    {
        return $this->salesperson_name;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return SalespersonGiveCoupons
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set userName
     *
     * @param string $userName
     *
     * @return SalespersonGiveCoupons
     */
    public function setUserName($userName)
    {
        $this->user_name = $userName;

        return $this;
    }

    /**
     * Get userName
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Set couponsId
     *
     * @param integer $couponsId
     *
     * @return SalespersonGiveCoupons
     */
    public function setCouponsId($couponsId)
    {
        $this->coupons_id = $couponsId;

        return $this;
    }

    /**
     * Get couponsId
     *
     * @return integer
     */
    public function getCouponsId()
    {
        return $this->coupons_id;
    }

    /**
     * Set couponsName
     *
     * @param string $couponsName
     *
     * @return SalespersonGiveCoupons
     */
    public function setCouponsName($couponsName)
    {
        $this->coupons_name = $couponsName;

        return $this;
    }

    /**
     * Get couponsName
     *
     * @return string
     */
    public function getCouponsName()
    {
        return $this->coupons_name;
    }

    /**
     * Set number
     *
     * @param integer $number
     *
     * @return SalespersonGiveCoupons
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return integer
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return SalespersonGiveCoupons
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set failReason
     *
     * @param string $failReason
     *
     * @return SalespersonGiveCoupons
     */
    public function setFailReason($failReason)
    {
        $this->fail_reason = $failReason;

        return $this;
    }

    /**
     * Get failReason
     *
     * @return string
     */
    public function getFailReason()
    {
        return $this->fail_reason;
    }

    /**
     * Set giveTime
     *
     * @param integer $giveTime
     *
     * @return SalespersonGiveCoupons
     */
    public function setGiveTime($giveTime)
    {
        $this->give_time = $giveTime;

        return $this;
    }

    /**
     * Get giveTime
     *
     * @return integer
     */
    public function getGiveTime()
    {
        return $this->give_time;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return SalespersonGiveCoupons
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
}
