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
 * MemberPrice 会员定向促销
 *
 * @ORM\Table(name="promotions_scd_rel_user", options={"comment"="定向促销会员日志"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_user_id", columns={"user_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\SpecificCrowdDiscountRelUserRepository")
 */
class SpecificCrowdDiscountRelUser
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
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"企业id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="string", options={"comment":"订单号id"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="discount_fee", type="bigint", options={"comment":"优惠金额"})
     */
    private $discount_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"定向促销id"})
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="specific_id", type="bigint", nullable=true, options={"comment":"定向条件id"})
     */
    private $specific_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="specific_name", type="string", nullable=true, options={"comment":"定向条件名称"})
     */
    private $specific_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_month", type="string", nullable=true, options={"comment":"促销月份"})
     */
    private $activity_month;

    /**
     * @var integer
     *
     * @ORM\Column(name="action_type", type="string", options={"comment":"操作方式，plus:加，less:减","default":"plus"})
     */
    private $action_type = 'plus';

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    private $created;

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
     * @return SpecificCrowdDiscountRelUser
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
     * Set userId.
     *
     * @param int $userId
     *
     * @return SpecificCrowdDiscountRelUser
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set orderId.
     *
     * @param string $orderId
     *
     * @return SpecificCrowdDiscountRelUser
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set discountFee.
     *
     * @param int $discountFee
     *
     * @return SpecificCrowdDiscountRelUser
     */
    public function setDiscountFee($discountFee)
    {
        $this->discount_fee = $discountFee;

        return $this;
    }

    /**
     * Get discountFee.
     *
     * @return int
     */
    public function getDiscountFee()
    {
        return $this->discount_fee;
    }

    /**
     * Set activityId.
     *
     * @param int $activityId
     *
     * @return SpecificCrowdDiscountRelUser
     */
    public function setActivityId($activityId)
    {
        $this->activity_id = $activityId;

        return $this;
    }

    /**
     * Get activityId.
     *
     * @return int
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * Set specificId.
     *
     * @param int $specificId
     *
     * @return SpecificCrowdDiscountRelUser
     */
    public function setSpecificId($specificId)
    {
        $this->specific_id = $specificId;

        return $this;
    }

    /**
     * Get specificId.
     *
     * @return int
     */
    public function getSpecificId()
    {
        return $this->specific_id;
    }

    /**
     * Set specificName.
     *
     * @param int $specificName
     *
     * @return SpecificCrowdDiscountRelUser
     */
    public function setSpecificName($specificName)
    {
        $this->specific_name = $specificName;

        return $this;
    }

    /**
     * Get specificName.
     *
     * @return int
     */
    public function getSpecificName()
    {
        return $this->specific_name;
    }

    /**
     * Set activityMonth.
     *
     * @param string $activityMonth
     *
     * @return SpecificCrowdDiscountRelUser
     */
    public function setActivityMonth($activityMonth)
    {
        $this->activity_month = $activityMonth;

        return $this;
    }

    /**
     * Get activityMonth.
     *
     * @return string
     */
    public function getActivityMonth()
    {
        return $this->activity_month;
    }

    /**
     * Set actionType.
     *
     * @param string $actionType
     *
     * @return SpecificCrowdDiscountRelUser
     */
    public function setActionType($actionType)
    {
        $this->action_type = $actionType;

        return $this;
    }

    /**
     * Get actionType.
     *
     * @return string
     */
    public function getActionType()
    {
        return $this->action_type;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SpecificCrowdDiscountRelUser
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
     * @return SpecificCrowdDiscountRelUser
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
