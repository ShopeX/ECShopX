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
 * ChinaumspayDivisionDetail 银联商务支付分账流水明细
 *
 * @ORM\Table(name="chinaumspay_division_detail", options={"comment":"银联商务支付分账流水明细"},
 *     indexes={
 *         @ORM\Index(name="idx_company", columns={"company_id"}),
 *         @ORM\Index(name="idx_division_id", columns={"division_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="ChinaumsPayBundle\Repositories\ChinaumspayDivisionDetailRepository")
 */
class ChinaumspayDivisionDetail
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
     * @ORM\Column(name="division_id", type="bigint", options={"comment":"分账流水ID"})
     */
    private $division_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", options={"comment":"订单号"})
     */
    private $order_id;

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
     * @var string
     *
     * @ORM\Column(name="commission_rate", type="float", precision=15, scale=4, options={"comment":"收单手续费费率"})
     */
    private $commission_rate;

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
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ChinaumspayDivisionDetail
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
     * Set divisionId.
     *
     * @param int $divisionId
     *
     * @return ChinaumspayDivisionDetail
     */
    public function setDivisionId($divisionId)
    {
        $this->division_id = $divisionId;

        return $this;
    }

    /**
     * Get divisionId.
     *
     * @return int
     */
    public function getDivisionId()
    {
        return $this->division_id;
    }

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return ChinaumspayDivisionDetail
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return ChinaumspayDivisionDetail
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
     * Set totalFee.
     *
     * @param string $totalFee
     *
     * @return ChinaumspayDivisionDetail
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
     * @return ChinaumspayDivisionDetail
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
     * Set commissionRate.
     *
     * @param float $commissionRate
     *
     * @return ChinaumspayDivisionDetail
     */
    public function setCommissionRate($commissionRate)
    {
        $this->commission_rate = $commissionRate;

        return $this;
    }

    /**
     * Get commissionRate.
     *
     * @return float
     */
    public function getCommissionRate()
    {
        return $this->commission_rate;
    }

    /**
     * Set commissionRateFee.
     *
     * @param int $commissionRateFee
     *
     * @return ChinaumspayDivisionDetail
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
     * @return ChinaumspayDivisionDetail
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
     * @return ChinaumspayDivisionDetail
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
     * @return ChinaumspayDivisionDetail
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
