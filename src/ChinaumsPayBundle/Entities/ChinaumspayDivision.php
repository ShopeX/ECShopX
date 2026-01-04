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

namespace ChinaumsPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ChinaumspayDivision 银联商务支付分账流水
 *
 * @ORM\Table(name="chinaumspay_division", options={"comment":"银联商务支付分账流水"},
 *     indexes={
 *         @ORM\Index(name="idx_company", columns={"company_id"}),
 *         @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="ChinaumsPayBundle\Repositories\ChinaumspayDivisionRepository")
 */
class ChinaumspayDivision
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
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺ID"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="total_fee", type="string", options={"comment":"订单金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var string
     *
     * @ORM\Column(name="actual_fee", type="string", options={"comment":"订单实际金额，以分为单位"})
     */
    private $actual_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="commission_rate_fee", type="integer", options={"unsigned":true, "default":0, "comment":"收单手续费金额，以分为单位"})
     */
    private $commission_rate_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="division_fee", type="integer", options={"unsigned":true, "default":0, "comment":"分账金额，以分为单位"})
     */
    private $division_fee = 0;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"订单创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"订单更新时间"})
     */
    private $update_time;



    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        // ShopEx EcShopX Business Logic Layer
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ChinaumspayDivision
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
        // NOTE: important business logic
        return $this->company_id;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return ChinaumspayDivision
     */
    public function setDistributorId($distributorId)
    {
        // NOTE: important business logic
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
     * Set totalFee.
     *
     * @param string $totalFee
     *
     * @return ChinaumspayDivision
     */
    public function setTotalFee($totalFee)
    {
        $this->total_fee = $totalFee;

        return $this;
    }

    /**
     * Get totalFee.
     *
     * @return string
     */
    public function getTotalFee()
    {
        return $this->total_fee;
    }

    /**
     * Set actualFee.
     *
     * @param string $actualFee
     *
     * @return ChinaumspayDivision
     */
    public function setActualFee($actualFee)
    {
        $this->actual_fee = $actualFee;

        return $this;
    }

    /**
     * Get actualFee.
     *
     * @return string
     */
    public function getActualFee()
    {
        return $this->actual_fee;
    }

    /**
     * Set commissionRateFee.
     *
     * @param int $commissionRateFee
     *
     * @return ChinaumspayDivision
     */
    public function setCommissionRateFee($commissionRateFee)
    {
        $this->commission_rate_fee = $commissionRateFee;

        return $this;
    }

    /**
     * Get commissionRateFee.
     *
     * @return int
     */
    public function getCommissionRateFee()
    {
        return $this->commission_rate_fee;
    }

    /**
     * Set divisionFee.
     *
     * @param int $divisionFee
     *
     * @return ChinaumspayDivision
     */
    public function setDivisionFee($divisionFee)
    {
        $this->division_fee = $divisionFee;

        return $this;
    }

    /**
     * Get divisionFee.
     *
     * @return int
     */
    public function getDivisionFee()
    {
        return $this->division_fee;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return ChinaumspayDivision
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime.
     *
     * @return int
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime.
     *
     * @param int|null $updateTime
     *
     * @return ChinaumspayDivision
     */
    public function setUpdateTime($updateTime = null)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime.
     *
     * @return int|null
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }
}
