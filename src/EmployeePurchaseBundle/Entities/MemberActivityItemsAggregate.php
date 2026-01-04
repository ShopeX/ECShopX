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

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MemberActivityItemsAggregate 内购活动商品会员累计使用额度
 *
 * @ORM\Table(name="employee_purchase_member_activity_items_aggregate", options={"comment"="内购活动商品会员累计使用额度"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\MemberActivityItemsAggregateRepository")
 */
class MemberActivityItemsAggregate
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"活动id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="enterprise_id", type="bigint", options={"comment":"企业id"})
     */
    private $enterprise_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员ID"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动ID"})
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品ID"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="aggregate_fee", type="integer", options={"unsigned":true, "comment":"累计使用额度，以分为单位"})
     */
    private $aggregate_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="aggregate_num", type="integer", options={"unsigned":true, "comment":"累计购买数量"})
     */
    private $aggregate_num;

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
     * Get Id.
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
     * @return MemberActivityItemsAggregate
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
     * Set enterpriseId.
     *
     * @param int $enterpriseId
     *
     * @return MemberActivityItemsAggregate
     */
    public function setEnterpriseId($enterpriseId)
    {
        $this->enterprise_id = $enterpriseId;

        return $this;
    }

    /**
     * Get enterpriseId.
     *
     * @return int
     */
    public function getEnterpriseId()
    {
        return $this->enterprise_id;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return MemberActivityItemsAggregate
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
     * Set activityId.
     *
     * @param int $activityId
     *
     * @return MemberActivityItemsAggregate
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
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return MemberActivityItemsAggregate
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set aggregateFee.
     *
     * @param int $aggregateFee
     *
     * @return MemberActivityItemsAggregate
     */
    public function setAggregateFee($aggregateFee)
    {
        $this->aggregate_fee = $aggregateFee;

        return $this;
    }

    /**
     * Get aggregateFee.
     *
     * @return int
     */
    public function getAggregateFee()
    {
        return $this->aggregate_fee;
    }

    /**
     * Set aggregateNum.
     *
     * @param int $aggregateNum
     *
     * @return MemberActivityItemsAggregate
     */
    public function setAggregateNum($aggregateNum)
    {
        $this->aggregate_num = $aggregateNum;

        return $this;
    }

    /**
     * Get aggregateNum.
     *
     * @return int
     */
    public function getAggregateNum()
    {
        return $this->aggregate_num;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return MemberActivityItemsAggregate
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
     * @param int $updated
     *
     * @return MemberActivityItemsAggregate
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
