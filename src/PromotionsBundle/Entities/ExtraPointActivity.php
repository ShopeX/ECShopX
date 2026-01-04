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
 * PointActivity 自助表单模板
 *
 * @ORM\Table(name="promotions_extrapoint_activity", options={"comment"="积分营销活动表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"})
 * }),
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\ExtraPointActivityRepository")
 */
class ExtraPointActivity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="string", options={"comment":"营销类型: shop:店铺额外积分,birthday:会员生日,item:商品额外积分", "default":"shop"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", options={"comment":"活动名称"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="trigger_condition", type="text", options={"comment":"触发条件"})
     */
    private $trigger_condition;

    /**
     * @var string
     *
     * @ORM\Column(name="condition_value", type="integer", options={"comment":"优惠配置"})
     */
    private $condition_value;

    /**
     * @var string
     *
     * @ORM\Column(name="condition_type", type="string", options={"comment":"优惠方式: multiple:倍数, plus:增加"})
     */
    private $condition_type;

    /**
     * @var string
     *
     * @ORM\Column(name="valid_grade", type="text", nullable=true, options={"comment":"会员级别集合"})
     */
    private $valid_grade;

    /**
     * @var string
     *
     * @ORM\Column(name="use_shop", type="string", options={"comment":"适用店铺: 0:全场可用,1:指定店铺可用"})
     */
    private $use_shop;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_ids", type="text", nullable=true, options={"comment":"店铺id集合", "default":"all"})
     */
    private $shop_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_status", type="string", options={"comment":"活动状态"})
     */
    private $activity_status;

    /**
     * @var integer
     *
     * @ORM\Column(name="begin_time", type="bigint", options={"comment":"活动开始时间"})
     */
    private $begin_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", nullable=true, type="bigint", options={"comment":"活动结束时间"})
     */
    private $end_time;

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
     * Get activityId
     *
     * @return integer
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return PointActivity
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
     * Set activityType
     *
     * @param string $activityType
     *
     * @return PointActivity
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get activityType
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return PointActivity
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set triggerCondition
     *
     * @param string $triggerCondition
     *
     * @return PointActivity
     */
    public function setTriggerCondition($triggerCondition)
    {
        $this->trigger_condition = $triggerCondition;

        return $this;
    }

    /**
     * Get triggerCondition
     *
     * @return string
     */
    public function getTriggerCondition()
    {
        return $this->trigger_condition;
    }

    /**
     * Set discountConfig
     *
     * @param string $discountConfig
     *
     * @return PointActivity
     */
    public function setConditionValue($conditionValue)
    {
        $this->condition_value = $conditionValue;

        return $this;
    }

    /**
     * Get discountConfig
     *
     * @return string
     */
    public function getConditionValue()
    {
        return $this->condition_value;
    }

    /**
     * Set smsParams
     *
     * @param string $smsParams
     *
     * @return PointActivity
     */
    public function setConditionType($conditionType)
    {
        $this->condition_type = $conditionType;

        return $this;
    }

    /**
     * Get smsParams
     *
     * @return string
     */
    public function getConditionType()
    {
        return $this->condition_type;
    }

    /**
     * Set validGrade
     *
     * @param string $validGrade
     *
     * @return PointActivity
     */
    public function setValidGrade($validGrade)
    {
        $this->valid_grade = $validGrade;

        return $this;
    }

    /**
     * Get validGrade
     *
     * @return string
     */
    public function getValidGrade()
    {
        return $this->valid_grade;
    }

    /**
     * Set activityStatus
     *
     * @param string $activityStatus
     *
     * @return PointActivity
     */
    public function setActivityStatus($activity_status)
    {
        $this->activity_status = $activity_status;

        return $this;
    }

    /**
     * Get activityStatus
     *
     * @return string
     */
    public function getActivityStatus()
    {
        return $this->activity_status;
    }


    /**
     * Set useShop
     *
     * @param string $useShop
     *
     * @return PointActivity
     */
    public function setUseShop($useShop)
    {
        $this->use_shop = $useShop;

        return $this;
    }

    /**
     * Get useShop
     *
     * @return string
     */
    public function getUseShop()
    {
        return $this->use_shop;
    }

    /**
         * Set shopIds
         *
         * @param string $shopIds
         *
         * @return PointActivity
         */
    public function setShopIds($shopIds)
    {
        $this->shop_ids = $shopIds;

        return $this;
    }

    /**
     * Get shopIds
     *
     * @return string
     */
    public function getShopIds()
    {
        return $this->shop_ids;
    }


    /**
     * Set beginTime
     *
     * @param integer $beginTime
     *
     * @return PointActivity
     */
    public function setBeginTime($beginTime)
    {
        $this->begin_time = $beginTime;

        return $this;
    }

    /**
     * Get beginTime
     *
     * @return integer
     */
    public function getBeginTime()
    {
        return $this->begin_time;
    }

    /**
     * Set endTime
     *
     * @param integer $endTime
     *
     * @return PointActivity
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return PointActivity
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return PointActivity
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
