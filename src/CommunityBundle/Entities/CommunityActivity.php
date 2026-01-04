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

namespace CommunityBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * community_activity 社区拼团活动表
 *
 * @ORM\Table(name="community_activity", options={"comment"="社区拼团活动表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_chief_id", columns={"chief_id"}),
 *    @ORM\Index(name="ix_activity_name", columns={"activity_name"}),
 *    @ORM\Index(name="ix_start_time", columns={"start_time"}),
 *    @ORM\Index(name="ix_end_time", columns={"end_time"}),
 *    @ORM\Index(name="ix_activity_status", columns={"activity_status"})
 * })
 * @ORM\Entity(repositoryClass="CommunityBundle\Repositories\CommunityActivityRepository")
 */
class CommunityActivity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动id"})
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
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"店铺id,为0时表示该活动为平台活动", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="chief_id", type="bigint", options={"comment":"团长ID"})
     */
    private $chief_id;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_name", type="string", options={"comment":"活动名称"})
     */
    private $activity_name;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_pics", type="text", options={"comment":"活动图片"})
     */
    private $activity_pics;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_desc", type="string", options={"comment":"活动简介"})
     */
    private $activity_desc;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_intro", type="text", options={"comment":"活动详细介绍"})
     */
    private $activity_intro;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="integer", length=11, options={"comment":"开始时间"})
     */
    private $start_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="integer", length=11, options={"comment":"结束时间"})
     */
    private $end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_status", type="string", options={"comment":"活动状态 private私有 public公开 protected隐藏 success确认成团 fail成团失败", "defaukt": "private"})
     */
    private $activity_status = 'public';

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_status", type="string", options={"default": "PENDING", "comment":"发货状态。可选值有 DONE—已发货;PENDING—待发货;SUCCESS-已收货"})
     */
    private $delivery_status = 'PENDING';

    /**
     * @var integer
     *
     * @ORM\Column(name="delivery_time", type="integer", nullable=true, options={"comment":"发货时间"})
     */
    private $delivery_time;

    /**
     * @var string
     *
     * @ORM\Column(name="aftersales_setting", type="string", options={"comment":"售后配置 ban禁止售后", "default": "ban"})
     */
    private $aftersales_setting = 'ban';

    /**
     * @var \DateTime $created_at
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created_at;

    /**
     * @var \DateTime $updated_at
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated_at;

    /**
     * @var string
     *
     * @ORM\Column(name="share_image_url", type="text", nullable=true, options={"comment":"分享图片"})
     */
    private $share_image_url;

    /**
     * get ActivityId
     *
     * @return int
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * get CompanyId
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * set CompanyId
     *
     * @param int $company_id
     *
     * @return self
     */
    public function setCompanyId($company_id)
    {
        $this->company_id = $company_id;
        return $this;
    }

    /**
     * get DistributorId
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * set DistributorId
     *
     * @param int $distributor_id
     *
     * @return self
     */
    public function setDistributorId($distributor_id)
    {
        $this->distributor_id = $distributor_id;
        return $this;
    }

    /**
     * get ChiefId
     *
     * @return int
     */
    public function getChiefId()
    {
        return $this->chief_id;
    }

    /**
     * set ChiefId
     *
     * @param int $chief_id
     *
     * @return self
     */
    public function setChiefId($chief_id)
    {
        $this->chief_id = $chief_id;
        return $this;
    }

    /**
     * get ActivityName
     *
     * @return string
     */
    public function getActivityName()
    {
        return $this->activity_name;
    }

    /**
     * set ActivityName
     *
     * @param string $activity_name
     *
     * @return self
     */
    public function setActivityName($activity_name)
    {
        $this->activity_name = $activity_name;
        return $this;
    }

    /**
     * get ActivityPics
     *
     * @return string
     */
    public function getActivityPics()
    {
        return $this->activity_pics;
    }

    /**
     * set ActivityPics
     *
     * @param string $activity_pics
     *
     * @return self
     */
    public function setActivityPics($activity_pics)
    {
        $this->activity_pics = $activity_pics;
        return $this;
    }

    /**
     * get ActivityDesc
     *
     * @return string
     */
    public function getActivityDesc()
    {
        return $this->activity_desc;
    }

    /**
     * set ActivityDesc
     *
     * @param string $activity_desc
     *
     * @return self
     */
    public function setActivityDesc($activity_desc)
    {
        $this->activity_desc = $activity_desc;
        return $this;
    }

    /**
     * get ActivityIntro
     *
     * @return string
     */
    public function getActivityIntro()
    {
        return $this->activity_intro;
    }

    /**
     * set ActivityIntro
     *
     * @param string $activity_intro
     *
     * @return self
     */
    public function setActivityIntro($activity_intro)
    {
        $this->activity_intro = $activity_intro;
        return $this;
    }

    /**
     * get StartTime
     *
     * @return int
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * set StartTime
     *
     * @param int $start_time
     *
     * @return self
     */
    public function setStartTime($start_time)
    {
        $this->start_time = $start_time;
        return $this;
    }

    /**
     * get EndTime
     *
     * @return int
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * set EndTime
     *
     * @param int $end_time
     *
     * @return self
     */
    public function setEndTime($end_time)
    {
        $this->end_time = $end_time;
        return $this;
    }

    /**
     * get ActivityStatus
     *
     * @return string
     */
    public function getActivityStatus()
    {
        return $this->activity_status;
    }

    /**
     * set ActivityStatus
     *
     * @param string $activity_status
     *
     * @return self
     */
    public function setActivityStatus($activity_status)
    {
        $this->activity_status = $activity_status;
        return $this;
    }

    /**
     * get DeliveryStatus
     *
     * @return string
     */
    public function getDeliveryStatus()
    {
        return $this->delivery_status;
    }

    /**
     * set DeliveryStatus
     *
     * @param string $delivery_status
     *
     * @return self
     */
    public function setDeliveryStatus($delivery_status)
    {
        $this->delivery_status = $delivery_status;
        return $this;
    }

    /**
     * get DeliveryTime
     *
     * @return int
     */
    public function getDeliveryTime()
    {
        return $this->delivery_time;
    }

    /**
     * set DeliveryTime
     *
     * @param int $delivery_time
     *
     * @return self
     */
    public function setDeliveryTime($delivery_time)
    {
        $this->delivery_time = $delivery_time;
        return $this;
    }

    /**
     * get AftersalesSetting
     *
     * @return string
     */
    public function getAftersalesSetting()
    {
        return $this->aftersales_setting;
    }

    /**
     * set AftersalesSetting
     *
     * @param string $aftersales_setting
     *
     * @return self
     */
    public function setAftersalesSetting($aftersales_setting)
    {
        $this->aftersales_setting = $aftersales_setting;
        return $this;
    }

    /**
     * get CreatedAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * set CreatedAt
     *
     * @param \DateTime $created_at
     *
     * @return self
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * get UpdatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * set UpdatedAt
     *
     * @param \DateTime $updated_at
     *
     * @return self
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    /**
     * Set shareImageUrl
     *
     * @param string $shareImageUrl
     *
     * @return self
     */
    public function setShareImageUrl($shareImageUrl)
    {
        $this->share_image_url = $shareImageUrl;

        return $this;
    }

    /**
     * Get shareImageUrl
     *
     * @return string
     */
    public function getShareImageUrl()
    {
        return $this->share_image_url;
    }
}
