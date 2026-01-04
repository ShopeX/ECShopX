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

namespace DataCubeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyData 商城数据统计表
 *
 * @ORM\Table(
 *    name="datacube_company_data",
 *    options={"comment"="商城数据统计表"},
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="ix_date_company_act", columns={"count_date", "company_id", "act_id"})
 *    }
 * )
 * @ORM\Entity(repositoryClass="DataCubeBundle\Repositories\CompanyDataRepository")
 */
class CompanyData
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
     * @var date
     *
     * @ORM\Column(name="count_date", type="date", options={"comment":"日期"})
     */
    private $count_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var int
     *
     * @ORM\Column(name="member_count", type="integer", options={"comment":"新增会员数", "default":0})
     */
    private $member_count;

    /**
     * @var int
     *
     * @ORM\Column(name="aftersales_count", type="integer", options={"comment":"新增售后单数", "default":0})
     */
    private $aftersales_count;

    /**
     * @var int
     *
     * @ORM\Column(name="refunded_count", type="integer", options={"comment":"新增退款额", "default":0})
     */
    private $refunded_count;

    /**
     * @var int
     *
     * @ORM\Column(name="amount_payed_count", type="bigint", options={"comment":"新增交易额", "default":0})
     */
    private $amount_payed_count;

    /**
     * @var int
     *
     * @ORM\Column(name="order_count", type="integer", options={"comment":"新增订单数", "default":0})
     */
    private $order_count;

    /**
     * @var int
     *
     * @ORM\Column(name="order_payed_count", type="integer", options={"comment":"新增已付款订单数", "default":0})
     */
    private $order_payed_count;

    /**
     * @var int
     *
     * @ORM\Column(name="gmv_count", type="bigint", options={"comment":"新增gmv", "default":0})
     */
    private $gmv_count;

    /**
     * @var string
     *
     * @ORM\Column(name="order_class", nullable=true, type="string", options={"comment":"订单种类,可选值有 employee_purchase:内购订单"})
     */
    private $order_class;

    /**
     * @var integer
     *
     * @ORM\Column(name="act_id", type="bigint", options={"comment":"活动ID", "default": 0})
     */
    private $act_id = 0;

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
     * Set countDate
     *
     * @param \DateTime $countDate
     *
     * @return CompanyData
     */
    public function setCountDate($countDate)
    {
        $this->count_date = $countDate;

        return $this;
    }

    /**
     * Get countDate
     *
     * @return \DateTime
     */
    public function getCountDate()
    {
        return $this->count_date;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return CompanyData
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
     * Set memberCount
     *
     * @param integer $memberCount
     *
     * @return CompanyData
     */
    public function setMemberCount($memberCount)
    {
        $this->member_count = $memberCount;

        return $this;
    }

    /**
     * Get memberCount
     *
     * @return integer
     */
    public function getMemberCount()
    {
        return $this->member_count;
    }

    /**
     * Set aftersalesCount
     *
     * @param integer $aftersalesCount
     *
     * @return CompanyData
     */
    public function setAftersalesCount($aftersalesCount)
    {
        $this->aftersales_count = $aftersalesCount;

        return $this;
    }

    /**
     * Get aftersalesCount
     *
     * @return integer
     */
    public function getAftersalesCount()
    {
        return $this->aftersales_count;
    }

    /**
     * Set refundedCount
     *
     * @param integer $refundedCount
     *
     * @return CompanyData
     */
    public function setRefundedCount($refundedCount)
    {
        $this->refunded_count = $refundedCount;

        return $this;
    }

    /**
     * Get refundedCount
     *
     * @return integer
     */
    public function getRefundedCount()
    {
        return $this->refunded_count;
    }

    /**
     * Set amountPayedCount
     *
     * @param integer $amountPayedCount
     *
     * @return CompanyData
     */
    public function setAmountPayedCount($amountPayedCount)
    {
        $this->amount_payed_count = $amountPayedCount;

        return $this;
    }

    /**
     * Get amountPayedCount
     *
     * @return integer
     */
    public function getAmountPayedCount()
    {
        return $this->amount_payed_count;
    }

    /**
     * Set orderCount
     *
     * @param integer $orderCount
     *
     * @return CompanyData
     */
    public function setOrderCount($orderCount)
    {
        $this->order_count = $orderCount;

        return $this;
    }

    /**
     * Get orderCount
     *
     * @return integer
     */
    public function getOrderCount()
    {
        return $this->order_count;
    }

    /**
     * Set orderPayedCount
     *
     * @param integer $orderPayedCount
     *
     * @return CompanyData
     */
    public function setOrderPayedCount($orderPayedCount)
    {
        $this->order_payed_count = $orderPayedCount;

        return $this;
    }

    /**
     * Get orderPayedCount
     *
     * @return integer
     */
    public function getOrderPayedCount()
    {
        return $this->order_payed_count;
    }

    /**
     * Set gmvCount
     *
     * @param integer $gmvCount
     *
     * @return CompanyData
     */
    public function setGmvCount($gmvCount)
    {
        $this->gmv_count = $gmvCount;

        return $this;
    }

    /**
     * Get gmvCount
     *
     * @return integer
     */
    public function getGmvCount()
    {
        return $this->gmv_count;
    }

    /**
     * Set orderClass.
     *
     * @param string $orderClass
     *
     * @return CompanyData
     */
    public function setOrderClass($orderClass)
    {
        $this->order_class = $orderClass;

        return $this;
    }

    /**
     * Get orderClass.
     *
     * @return string
     */
    public function getOrderClass()
    {
        return $this->order_class;
    }

    /**
     * Set actId.
     *
     * @param int $actId
     *
     * @return CompanyData
     */
    public function setActId($actId)
    {
        $this->act_id = $actId;

        return $this;
    }

    /**
     * Get actId.
     *
     * @return int
     */
    public function getActId()
    {
        return $this->act_id;
    }
}
