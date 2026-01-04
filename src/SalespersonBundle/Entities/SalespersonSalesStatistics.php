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
 * SalespersonSalesStatistics 导购员销售额数据统计表
 *
 * @ORM\Table(name="salesperson_sales_statistics", options={"comment":"导购员销售额数据统计表"}, indexes={
 *    @ORM\Index(name="ix_salesperson_date", columns={"salesperson_id", "date"}),
 * })
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonSalesStatisticsRepository")
 */
class SalespersonSalesStatistics
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
     * @ORM\Column(name="popularize_order_fee", type="bigint", options={"comment":"推广销售额"})
     */
    private $popularize_order_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="popularize_order_count", type="bigint", options={"comment":"推广订单数"})
     */
    private $popularize_order_count;

    /**
     * @var integer
     *
     * @ORM\Column(name="offline_order_fee", type="bigint", options={"comment":"门店开单金额"})
     */
    private $offline_order_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="offline_order_count", type="bigint", options={"comment":"门店开单数"})
     */
    private $offline_order_count;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_refund_fee", type="bigint", options={"comment":"退款金额"})
     */
    private $total_refund_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_refund_count", type="bigint", options={"comment":"退款单数"})
     */
    private $total_refund_count;



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
     * @return SalespersonSalesStatistics
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
     * @return SalespersonSalesStatistics
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
     * @return SalespersonSalesStatistics
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
     * @return SalespersonSalesStatistics
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
     * Set popularizeOrderFee.
     *
     * @param int $popularizeOrderFee
     *
     * @return SalespersonSalesStatistics
     */
    public function setPopularizeOrderFee($popularizeOrderFee)
    {
        $this->popularize_order_fee = $popularizeOrderFee;

        return $this;
    }

    /**
     * Get popularizeOrderFee.
     *
     * @return int
     */
    public function getPopularizeOrderFee()
    {
        return $this->popularize_order_fee;
    }

    /**
     * Set popularizeOrderCount.
     *
     * @param int $popularizeOrderCount
     *
     * @return SalespersonSalesStatistics
     */
    public function setPopularizeOrderCount($popularizeOrderCount)
    {
        $this->popularize_order_count = $popularizeOrderCount;

        return $this;
    }

    /**
     * Get popularizeOrderCount.
     *
     * @return int
     */
    public function getPopularizeOrderCount()
    {
        return $this->popularize_order_count;
    }

    /**
     * Set offlineOrderFee.
     *
     * @param int $offlineOrderFee
     *
     * @return SalespersonSalesStatistics
     */
    public function setOfflineOrderFee($offlineOrderFee)
    {
        $this->offline_order_fee = $offlineOrderFee;

        return $this;
    }

    /**
     * Get offlineOrderFee.
     *
     * @return int
     */
    public function getOfflineOrderFee()
    {
        return $this->offline_order_fee;
    }

    /**
     * Set offlineOrderCount.
     *
     * @param int $offlineOrderCount
     *
     * @return SalespersonSalesStatistics
     */
    public function setOfflineOrderCount($offlineOrderCount)
    {
        $this->offline_order_count = $offlineOrderCount;

        return $this;
    }

    /**
     * Get offlineOrderCount.
     *
     * @return int
     */
    public function getOfflineOrderCount()
    {
        return $this->offline_order_count;
    }

    /**
     * Set totalRefundFee.
     *
     * @param int $totalRefundFee
     *
     * @return SalespersonSalesStatistics
     */
    public function setTotalRefundFee($totalRefundFee)
    {
        $this->total_refund_fee = $totalRefundFee;

        return $this;
    }

    /**
     * Get totalRefundFee.
     *
     * @return int
     */
    public function getTotalRefundFee()
    {
        return $this->total_refund_fee;
    }

    /**
     * Set totalRefundCount.
     *
     * @param int $totalRefundCount
     *
     * @return SalespersonSalesStatistics
     */
    public function setTotalRefundCount($totalRefundCount)
    {
        $this->total_refund_count = $totalRefundCount;

        return $this;
    }

    /**
     * Get totalRefundCount.
     *
     * @return int
     */
    public function getTotalRefundCount()
    {
        return $this->total_refund_count;
    }
}
