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
 * DistributorData 商城门店数据统计表
 *
 * @ORM\Table(
 *    name="datacube_distributor_data",
 *    options={"comment"="商城门店数据统计表"},
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="ix_date_distributor", columns={"count_date", "company_id", "distributor_id"})
 *    }
 * )
 * @ORM\Entity(repositoryClass="DataCubeBundle\Repositories\DistributorDataRepository")
 */
class DistributorData
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
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"经销商ID"})
     */
    private $distributor_id;

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
     * @ORM\Column(name="amount_point_payed_count", type="bigint", options={"comment":"新增交易额(积分)", "default":0})
     */
    private $amount_point_payed_count;

    /**
     * @var int
     *
     * @ORM\Column(name="order_count", type="integer", options={"comment":"新增订单数", "default":0})
     */
    private $order_count;

    /**
     * @var int
     *
     * @ORM\Column(name="order_point_count", type="integer", options={"comment":"新增订单数(积分)", "default":0})
     */
    private $order_point_count;

    /**
     * @var int
     *
     * @ORM\Column(name="order_payed_count", type="integer", options={"comment":"新增已付款订单数", "default":0})
     */
    private $order_payed_count;

    /**
     * @var int
     *
     * @ORM\Column(name="order_point_payed_count", type="integer", options={"comment":"新增已付款订单数(积分)", "default":0})
     */
    private $order_point_payed_count;

    /**
     * @var int
     *
     * @ORM\Column(name="gmv_count", type="bigint", options={"comment":"新增gmv", "default":0})
     */
    private $gmv_count;

    /**
     * @var int
     *
     * @ORM\Column(name="gmv_point_count", type="bigint", options={"comment":"新增gmv(积分)", "default":0})
     */
    private $gmv_point_count;
    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id;

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
     * Set amountPointPayedCount
     *
     * @param integer $amountPointPayedCount
     *
     * @return CompanyData
     */
    public function setAmountPointPayedCount($amountPointPayedCount)
    {
        $this->amount_point_payed_count = $amountPointPayedCount;

        return $this;
    }

    /**
     * Get amountPointPayedCount
     *
     * @return integer
     */
    public function getAmountPointPayedCount()
    {
        return $this->amount_point_payed_count;
    }

    /**
     * Set orderPointCount
     *
     * @param integer $orderPointCount
     *
     * @return CompanyData
     */
    public function setOrderPointCount($orderPointCount)
    {
        $this->order_point_count = $orderPointCount;

        return $this;
    }

    /**
     * Get orderPointCount
     *
     * @return integer
     */
    public function getOrderPointCount()
    {
        return $this->order_point_count;
    }

    /**
     * Set orderPointPayedCount
     *
     * @param integer $orderPointPayedCount
     *
     * @return CompanyData
     */
    public function setOrderPointPayedCount($orderPointPayedCount)
    {
        $this->order_point_payed_count = $orderPointPayedCount;

        return $this;
    }

    /**
     * Get orderPointPayedCount
     *
     * @return integer
     */
    public function getOrderPointPayedCount()
    {
        return $this->order_point_payed_count;
    }

    /**
     * Set gmvPointCount
     *
     * @param integer $gmvPointCount
     *
     * @return CompanyData
     */
    public function setGmvPointCount($gmvPointCount)
    {
        $this->gmv_point_count = $gmvPointCount;

        return $this;
    }

    /**
     * Get gmvPointCount
     *
     * @return integer
     */
    public function getGmvPointCount()
    {
        return $this->gmv_point_count;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return DistributorData
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
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return DistributorData
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }
}
