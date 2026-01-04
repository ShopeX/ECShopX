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

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SpecificCrowdDiscount 会员定向促销
 *
 * @ORM\Table(name="promotions_specific_crowd_discount", options={"comment"="特定人群促销"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_status", columns={"status"}),
 *    @ORM\Index(name="ix_specific_id", columns={"specific_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\SpecificCrowdDiscountRepository")
 */
class SpecificCrowdDiscount
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"营销id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"企业id"})
     */
    private $company_id;

    /**
     * @var integer
     * member_tag   会员标签
     *
     * @ORM\Column(name="specific_type", type="string", options={"comment":"特定人群类型", "default":"member_tag"})
     */
    private $specific_type = "member_tag";

    /**
     * @var integer
     *
     * @ORM\Column(name="specific_id", type="bigint", nullable=true, options={"comment":"营销id"})
     */
    private $specific_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="cycle_type", type="integer", options={"comment":"周期类型,1:自然月;2:指定时段", "default":1})
     */
    private $cycle_type = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="bigint", nullable=true, options={"comment":"开始时间"})
     */
    private $start_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="bigint", nullable=true, options={"comment":"结束时间"})
     */
    private $end_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="discount", type="bigint", nullable=true, options={"comment":"折扣值"})
     */
    private $discount;

    /**
     * @var string
     *
     * @ORM\Column(name="limit_total_money", nullable=true, type="integer", options={"comment":"每人累计限额"})
     */
    private $limit_total_money;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="bigint", options={"comment":"状态，1:暂存，2:已发布, 3:停用, 4:已过期"})
     */
    private $status = 1;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

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
     * @return SpecificCrowdDiscount
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
     * Set specificType.
     *
     * @param string $specificType
     *
     * @return SpecificCrowdDiscount
     */
    public function setSpecificType($specificType)
    {
        $this->specific_type = $specificType;

        return $this;
    }

    /**
     * Get specificType.
     *
     * @return string
     */
    public function getSpecificType()
    {
        return $this->specific_type;
    }

    /**
     * Set specificId.
     *
     * @param int|null $specificId
     *
     * @return SpecificCrowdDiscount
     */
    public function setSpecificId($specificId = null)
    {
        $this->specific_id = $specificId;

        return $this;
    }

    /**
     * Get specificId.
     *
     * @return int|null
     */
    public function getSpecificId()
    {
        return $this->specific_id;
    }

    /**
     * Set cycleType.
     *
     * @param int $cycleType
     *
     * @return SpecificCrowdDiscount
     */
    public function setCycleType($cycleType)
    {
        $this->cycle_type = $cycleType;

        return $this;
    }

    /**
     * Get cycleType.
     *
     * @return int
     */
    public function getCycleType()
    {
        return $this->cycle_type;
    }

    /**
     * Set startTime.
     *
     * @param int|null $startTime
     *
     * @return SpecificCrowdDiscount
     */
    public function setStartTime($startTime = null)
    {
        $this->start_time = $startTime;

        return $this;
    }

    /**
     * Get startTime.
     *
     * @return int|null
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set endTime.
     *
     * @param int|null $endTime
     *
     * @return SpecificCrowdDiscount
     */
    public function setEndTime($endTime = null)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime.
     *
     * @return int|null
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set discount.
     *
     * @param int|null $discount
     *
     * @return SpecificCrowdDiscount
     */
    public function setDiscount($discount = null)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount.
     *
     * @return int|null
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set limitTotalMoney.
     *
     * @param int|null $limitTotalMoney
     *
     * @return SpecificCrowdDiscount
     */
    public function setLimitTotalMoney($limitTotalMoney = null)
    {
        $this->limit_total_money = $limitTotalMoney;

        return $this;
    }

    /**
     * Get limitTotalMoney.
     *
     * @return int|null
     */
    public function getLimitTotalMoney()
    {
        return $this->limit_total_money;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return SpecificCrowdDiscount
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SpecificCrowdDiscount
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return SpecificCrowdDiscount
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
