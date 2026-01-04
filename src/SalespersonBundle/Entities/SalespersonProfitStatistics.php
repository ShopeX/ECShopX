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

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * SalespersonProfitStatistics 导购员销售提成数据统计表
 *
 * @ORM\Table(name="salesperson_profit_statistics", options={"comment":"导购员销售提成数据统计表"}, indexes={
 *    @ORM\Index(name="ix_salesperson_date", columns={"salesperson_id", "date"}),
 * })
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonProfitStatisticsRepository")
 */
class SalespersonProfitStatistics
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     *
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id"})
     *
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment":"导购员id"})
     *
     */
    private $salesperson_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="date", type="bigint", options={"comment":"统计日期 Ymd"})
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="unconfirmed_seller_fee", type="bigint", options={"comment":"未确认绑定会员提成"})
     */
    private $unconfirmed_seller_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="confirm_seller_fee", type="bigint", options={"comment":"已确认绑定会员提成"})
     */
    private $confirm_seller_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="unconfirmed_offline_seller_fee", type="bigint", options={"comment":"未确认门店开单提成"})
     */
    private $unconfirmed_offline_seller_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="confirm_offline_seller_fee", type="bigint", options={"comment":"已确认门店开单提成"})
     */
    private $confirm_offline_seller_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="unconfirmed_popularize_seller_fee", type="bigint", options={"comment":"未确认客户推广提成"})
     */
    private $unconfirmed_popularize_seller_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="confirm_popularize_seller_fee", type="bigint", options={"comment":"已确认客户推广提成"})
     */
    private $confirm_popularize_seller_fee;



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
     * @return SalespersonProfitStatistics
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
     * @param int $distributorId
     *
     * @return SalespersonProfitStatistics
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set salespersonId.
     *
     * @param int $salespersonId
     *
     * @return SalespersonProfitStatistics
     */
    public function setSalespersonId($salespersonId)
    {
        $this->salesperson_id = $salespersonId;

        return $this;
    }

    /**
     * Get salespersonId.
     *
     * @return int
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set date.
     *
     * @param int $date
     *
     * @return SalespersonProfitStatistics
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set unconfirmedSellerFee.
     *
     * @param int $unconfirmedSellerFee
     *
     * @return SalespersonProfitStatistics
     */
    public function setUnconfirmedSellerFee($unconfirmedSellerFee)
    {
        $this->unconfirmed_seller_fee = $unconfirmedSellerFee;

        return $this;
    }

    /**
     * Get unconfirmedSellerFee.
     *
     * @return int
     */
    public function getUnconfirmedSellerFee()
    {
        return $this->unconfirmed_seller_fee;
    }

    /**
     * Set confirmSellerFee.
     *
     * @param int $confirmSellerFee
     *
     * @return SalespersonProfitStatistics
     */
    public function setConfirmSellerFee($confirmSellerFee)
    {
        $this->confirm_seller_fee = $confirmSellerFee;

        return $this;
    }

    /**
     * Get confirmSellerFee.
     *
     * @return int
     */
    public function getConfirmSellerFee()
    {
        return $this->confirm_seller_fee;
    }

    /**
     * Set unconfirmedOfflineSellerFee.
     *
     * @param int $unconfirmedOfflineSellerFee
     *
     * @return SalespersonProfitStatistics
     */
    public function setUnconfirmedOfflineSellerFee($unconfirmedOfflineSellerFee)
    {
        $this->unconfirmed_offline_seller_fee = $unconfirmedOfflineSellerFee;

        return $this;
    }

    /**
     * Get unconfirmedOfflineSellerFee.
     *
     * @return int
     */
    public function getUnconfirmedOfflineSellerFee()
    {
        return $this->unconfirmed_offline_seller_fee;
    }

    /**
     * Set confirmOfflineSellerFee.
     *
     * @param int $confirmOfflineSellerFee
     *
     * @return SalespersonProfitStatistics
     */
    public function setConfirmOfflineSellerFee($confirmOfflineSellerFee)
    {
        $this->confirm_offline_seller_fee = $confirmOfflineSellerFee;

        return $this;
    }

    /**
     * Get confirmOfflineSellerFee.
     *
     * @return int
     */
    public function getConfirmOfflineSellerFee()
    {
        return $this->confirm_offline_seller_fee;
    }

    /**
     * Set unconfirmedPopularizeSellerFee.
     *
     * @param int $unconfirmedPopularizeSellerFee
     *
     * @return SalespersonProfitStatistics
     */
    public function setUnconfirmedPopularizeSellerFee($unconfirmedPopularizeSellerFee)
    {
        $this->unconfirmed_popularize_seller_fee = $unconfirmedPopularizeSellerFee;

        return $this;
    }

    /**
     * Get unconfirmedPopularizeSellerFee.
     *
     * @return int
     */
    public function getUnconfirmedPopularizeSellerFee()
    {
        return $this->unconfirmed_popularize_seller_fee;
    }

    /**
     * Set confirmPopularizeSellerFee.
     *
     * @param int $confirmPopularizeSellerFee
     *
     * @return SalespersonProfitStatistics
     */
    public function setConfirmPopularizeSellerFee($confirmPopularizeSellerFee)
    {
        $this->confirm_popularize_seller_fee = $confirmPopularizeSellerFee;

        return $this;
    }

    /**
     * Get confirmPopularizeSellerFee.
     *
     * @return int
     */
    public function getConfirmPopularizeSellerFee()
    {
        return $this->confirm_popularize_seller_fee;
    }
}
