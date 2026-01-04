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

/**
 * DistributorSalesCount
 *
 * @ORM\Table(name="distribution_distributor_sales_count",options={"comment":"店铺销量表"},
 *     indexes={
 *         @ORM\Index(name="ix_company_id_distributor_id_time", columns={"company_id", "distributor_id", "year_month_time"}),
 *     },)
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\DistributorSalesCountRepository")
 */
class DistributorSalesCount
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
     * @ORM\Column(name="company_id", type="bigint", nullable=false, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", nullable=false, options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_item_count", type="bigint", nullable=false, options={"comment":"已经关闭售后的订单的商品数量", "default":0})
     */
    private $order_item_count;

    /**
     * @var integer
     *
     * @ORM\Column(name="aftersales_item_count", type="bigint", nullable=false, options={"comment":"已经关闭售后的订单的商品数量", "default":0})
     */
    private $aftersales_item_count;

    /**
     * @var integer
     *
     * @ORM\Column(name="year_month_time", type="bigint", nullable=false, options={"comment":"统计的年月时间"})
     */
    private $year_month_time;

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
     * @return DistributorSalesCount
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
     * @return DistributorSalesCount
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
     * Set orderItemCount.
     *
     * @param int $orderItemCount
     *
     * @return DistributorSalesCount
     */
    public function setOrderItemCount($orderItemCount)
    {
        $this->order_item_count = $orderItemCount;

        return $this;
    }

    /**
     * Get orderItemCount.
     *
     * @return int
     */
    public function getOrderItemCount()
    {
        return $this->order_item_count;
    }

    /**
     * Set aftersalesItemCount.
     *
     * @param int $aftersalesItemCount
     *
     * @return DistributorSalesCount
     */
    public function setAftersalesItemCount($aftersalesItemCount)
    {
        $this->aftersales_item_count = $aftersalesItemCount;

        return $this;
    }

    /**
     * Get aftersalesItemCount.
     *
     * @return int
     */
    public function getAftersalesItemCount()
    {
        return $this->aftersales_item_count;
    }

    /**
     * Set yearMonthTime.
     *
     * @param int $yearMonthTime
     *
     * @return DistributorSalesCount
     */
    public function setYearMonthTime($yearMonthTime)
    {
        $this->year_month_time = $yearMonthTime;

        return $this;
    }

    /**
     * Get yearMonthTime.
     *
     * @return int
     */
    public function getYearMonthTime()
    {
        return $this->year_month_time;
    }
}
