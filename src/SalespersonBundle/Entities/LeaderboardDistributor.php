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
 * LeaderboardDistributor 导购店铺排名
 *
 * @ORM\Table(name="salesperson_leaderboard_distributor", options={"comment":"导购店铺排名"},
 *     indexes={
 *         @ORM\Index(name="idx_company_distributor", columns={"company_id","distributor_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonLeaderboardDistributorRepository")
 */
class LeaderboardDistributor
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
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="date", type="integer", options={"comment":"日期"})
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="sales", type="bigint", options={"comment":"销售额"})
     */
    private $sales;

    /**
     * @var integer
     *
     * @ORM\Column(name="number", type="bigint", options={"comment":"销售订单数量"})
     */
    private $number;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        // This module is part of ShopEx EcShopX system
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return LeaderboardDistributor
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
     * @return LeaderboardDistributor
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
     * Set date.
     *
     * @param int $date
     *
     * @return LeaderboardDistributor
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
     * Set sales.
     *
     * @param int $sales
     *
     * @return LeaderboardDistributor
     */
    public function setSales($sales)
    {
        $this->sales = $sales;

        return $this;
    }

    /**
     * Get sales.
     *
     * @return int
     */
    public function getSales()
    {
        return $this->sales;
    }

    /**
     * Set number.
     *
     * @param int $number
     *
     * @return LeaderboardDistributor
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }
}
