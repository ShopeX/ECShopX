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
 * PromotionActivity 自助表单模板
 *
 * @ORM\Table(name="promotions_activity", options={"comment"="自动化营销活动表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"})
 * }),
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\PromotionActivityRepository")
 */
class PromotionActivity
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
     * @var string
     *
     * @ORM\Column(name="activity_type", type="string", options={"comment":"活动类型"})
     */
    private $activity_type;

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
     * @ORM\Column(name="discount_config", type="text", options={"comment":"优惠配置"})
     */
    private $discount_config;

    /**
     * @var string
     *
     * @ORM\Column(name="sms_params", nullable=true, type="string", options={"comment":"发送短信相关参数"})
     */
    private $sms_params;

    /**
     * @var string
     *
     * @ORM\Column(name="sms_isopen", type="string", options={"comment":"是否开启发送短信"})
     */
    private $sms_isopen;

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
     * @return PromotionActivity
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
     * @return PromotionActivity
     */
    public function setActivityType($activityType)
    {
        $this->activity_type = $activityType;

        return $this;
    }

    /**
     * Get activityType
     *
     * @return string
     */
    public function getActivityType()
    {
        return $this->activity_type;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return PromotionActivity
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
     * @return PromotionActivity
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
     * @return PromotionActivity
     */
    public function setDiscountConfig($discountConfig)
    {
        $this->discount_config = $discountConfig;

        return $this;
    }

    /**
     * Get discountConfig
     *
     * @return string
     */
    public function getDiscountConfig()
    {
        return $this->discount_config;
    }

    /**
     * Set smsParams
     *
     * @param string $smsParams
     *
     * @return PromotionActivity
     */
    public function setSmsParams($smsParams)
    {
        $this->sms_params = $smsParams;

        return $this;
    }

    /**
     * Get smsParams
     *
     * @return string
     */
    public function getSmsParams()
    {
        return $this->sms_params;
    }

    /**
     * Set smsIsopen
     *
     * @param string $smsIsopen
     *
     * @return PromotionActivity
     */
    public function setSmsIsopen($smsIsopen)
    {
        $this->sms_isopen = $smsIsopen;

        return $this;
    }

    /**
     * Get smsIsopen
     *
     * @return string
     */
    public function getSmsIsopen()
    {
        return $this->sms_isopen;
    }

    /**
     * Set activityStatus
     *
     * @param string $activityStatus
     *
     * @return PromotionActivity
     */
    public function setActivityStatus($activityStatus)
    {
        $this->activity_status = $activityStatus;

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
     * Set beginTime
     *
     * @param integer $beginTime
     *
     * @return PromotionActivity
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
     * @return PromotionActivity
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
     * @return PromotionActivity
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
     * @return PromotionActivity
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
