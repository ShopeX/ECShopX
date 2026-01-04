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
 * SeckillActivity 秒杀活动表
 *
 * @ORM\Table(name="promotions_seckill_activity", options={"comment"="秒杀活动表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}, indexes={
 *    @ORM\Index(name="ix_item_type", columns={"item_type"}),
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\SeckillActivityRepository")
 */
class SeckillActivity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="seckill_id", type="bigint", options={"comment":"秒杀活动id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $seckill_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", nullable=true, type="string", options={"comment":"店铺ID, 多个逗号隔开"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_name", type="string", nullable=true, options={"comment":"秒杀活动名称"})
     */
    private $activity_name;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_pic", type="string", nullable=true, options={"comment":"秒杀活动广告图"})
     */
    private $ad_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_start_time", type="integer", options={"comment":"秒杀开始时间"})
     */
    private $activity_start_time;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_end_time", type="integer", options={"comment":"秒杀结束时间"})
     */
    private $activity_end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_release_time", type="integer", options={"comment":"秒杀活动发布时间"})
     */
    private $activity_release_time;

    /**
     * @var string
     *
     * @ORM\Column(name="is_activity_rebate", type="boolean", options={"comment":"秒杀活动是否返佣", "default":false})
     */
    private $is_activity_rebate = false;

    /**
     * @var string
     *
     * @ORM\Column(name="is_free_shipping", type="boolean", options={"comment":"秒杀活动是否包邮", "default":false})
     */
    private $is_free_shipping = false;

    /**
     * @var string
     *
     * @ORM\Column(name="limit_total_money", nullable=true, type="bigint", options={"comment":"每人累计限额"})
     */
    private $limit_total_money;

    /**
     * @var string
     *
     * @ORM\Column(name="limit_money", nullable=true, type="bigint", options={"comment":"每人单笔限额"})
     */
    private $limit_money;

    /**
     * @var string
     *
     * @ORM\Column(name="validity_period", nullable=true, type="integer", options={"comment":"未付款订单保留时长（分钟）"})
     */
    private $validity_period;

    /**
     * @var string
     *
     * @ORM\Column(name="otherext", nullable=true, type="text", options={"comment":"其他扩展字段"})
     */
    private $otherext;

    /**
     * @var string
     *
     * @ORM\Column(name="description", nullable=true, type="string", options={"comment":"秒杀活动描述"})
     */
    private $description;

    /**
     * @var string
     *  normal正常的秒杀活动
     *  limited_time_sale限时特惠
     *
     * @ORM\Column(name="seckill_type", type="string", options={"comment":"秒杀类型 normal正常的秒杀活动， limited_time_sale限时特惠", "default":"normal"})
     */
    private $seckill_type = 'normal';

    /**
     * @var string
     *  normal 实体类
     *  services 服务类
     *
     * @ORM\Column(name="item_type", type="string", options={"comment":"秒杀活动商品类型", "default":"normal"})
     */
    private $item_type = 'normal';

    /**
     * @var string
     *
     * @ORM\Column(name="use_bound", type="integer", options={"comment":"适用范围: 1:指定商品可用", "default":1})
     */
    private $use_bound = 1;

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
    private $updated;

    /**
     * @var string
     *
     * @ORM\Column(name="source_type", type="string", length=20, nullable=true, options={"comment":"添加者类型：distributor"})
     */
    private $source_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="source_id", type="bigint", nullable=true, options={"comment":"添加者ID: 如店铺ID", "default":0})
     */
    private $source_id = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"是否失效", "default": 0})
     */
    private $disabled = 0;

    /**
     * Get seckillId
     *
     * @return integer
     */
    public function getSeckillId()
    {
        return $this->seckill_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return SeckillActivity
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
     * Set activityName
     *
     * @param string $activityName
     *
     * @return SeckillActivity
     */
    public function setActivityName($activityName)
    {
        $this->activity_name = $activityName;

        return $this;
    }

    /**
     * Get activityName
     *
     * @return string
     */
    public function getActivityName()
    {
        return $this->activity_name;
    }

    /**
     * Set adPic
     *
     * @param string $adPic
     *
     * @return SeckillActivity
     */
    public function setAdPic($adPic)
    {
        $this->ad_pic = $adPic;

        return $this;
    }

    /**
     * Get adPic
     *
     * @return string
     */
    public function getAdPic()
    {
        return $this->ad_pic;
    }

    /**
     * Set activityStartTime
     *
     * @param integer $activityStartTime
     *
     * @return SeckillActivity
     */
    public function setActivityStartTime($activityStartTime)
    {
        $this->activity_start_time = $activityStartTime;

        return $this;
    }

    /**
     * Get activityStartTime
     *
     * @return integer
     */
    public function getActivityStartTime()
    {
        return $this->activity_start_time;
    }

    /**
     * Set activityEndTime
     *
     * @param integer $activityEndTime
     *
     * @return SeckillActivity
     */
    public function setActivityEndTime($activityEndTime)
    {
        $this->activity_end_time = $activityEndTime;

        return $this;
    }

    /**
     * Get activityEndTime
     *
     * @return integer
     */
    public function getActivityEndTime()
    {
        return $this->activity_end_time;
    }

    /**
     * Set activityReleaseTime
     *
     * @param integer $activityReleaseTime
     *
     * @return SeckillActivity
     */
    public function setActivityReleaseTime($activityReleaseTime)
    {
        $this->activity_release_time = $activityReleaseTime;

        return $this;
    }

    /**
     * Get activityReleaseTime
     *
     * @return integer
     */
    public function getActivityReleaseTime()
    {
        return $this->activity_release_time;
    }

    /**
     * Set isActivityRebate
     *
     * @param boolean $isActivityRebate
     *
     * @return SeckillActivity
     */
    public function setIsActivityRebate($isActivityRebate)
    {
        $this->is_activity_rebate = $isActivityRebate;

        return $this;
    }

    /**
     * Get isActivityRebate
     *
     * @return boolean
     */
    public function getIsActivityRebate()
    {
        return $this->is_activity_rebate;
    }

    /**
     * Set isFreeShipping
     *
     * @param boolean $isFreeShipping
     *
     * @return SeckillActivity
     */
    public function setIsFreeShipping($isFreeShipping)
    {
        $this->is_free_shipping = $isFreeShipping;

        return $this;
    }

    /**
     * Get isFreeShipping
     *
     * @return boolean
     */
    public function getIsFreeShipping()
    {
        return $this->is_free_shipping;
    }

    /**
     * Set validityPeriod
     *
     * @param integer $validityPeriod
     *
     * @return SeckillActivity
     */
    public function setValidityPeriod($validityPeriod)
    {
        $this->validity_period = $validityPeriod;

        return $this;
    }

    /**
     * Get validityPeriod
     *
     * @return integer
     */
    public function getValidityPeriod()
    {
        return $this->validity_period;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return SeckillActivity
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set itemType
     *
     * @param string $itemType
     *
     * @return SeckillActivity
     */
    public function setItemType($itemType)
    {
        $this->item_type = $itemType;

        return $this;
    }

    /**
     * Get itemType
     *
     * @return string
     */
    public function getItemType()
    {
        return $this->item_type;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return SeckillActivity
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
     * @return SeckillActivity
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

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return SeckillActivity
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set seckillType
     *
     * @param string $seckillType
     *
     * @return SeckillActivity
     */
    public function setSeckillType($seckillType)
    {
        $this->seckill_type = $seckillType;

        return $this;
    }

    /**
     * Get seckillType
     *
     * @return string
     */
    public function getSeckillType()
    {
        return $this->seckill_type;
    }

    /**
     * Set limitTotalMoney
     *
     * @param integer $limitTotalMoney
     *
     * @return SeckillActivity
     */
    public function setLimitTotalMoney($limitTotalMoney)
    {
        $this->limit_total_money = $limitTotalMoney;

        return $this;
    }

    /**
     * Get limitTotalMoney
     *
     * @return integer
     */
    public function getLimitTotalMoney()
    {
        return $this->limit_total_money;
    }

    /**
     * Set limitMoney
     *
     * @param integer $limitMoney
     *
     * @return SeckillActivity
     */
    public function setLimitMoney($limitMoney)
    {
        $this->limit_money = $limitMoney;

        return $this;
    }

    /**
     * Get limitMoney
     *
     * @return integer
     */
    public function getLimitMoney()
    {
        return $this->limit_money;
    }

    /**
     * Set otherext
     *
     * @param string $otherext
     *
     * @return SeckillActivity
     */
    public function setOtherext($otherext)
    {
        $this->otherext = $otherext;

        return $this;
    }

    /**
     * Get otherext
     *
     * @return string
     */
    public function getOtherext()
    {
        return $this->otherext;
    }

    /**
     * Set useBound.
     *
     * @param int $useBound
     *
     * @return SeckillActivity
     */
    public function setUseBound($useBound)
    {
        $this->use_bound = $useBound;

        return $this;
    }

    /**
     * Get useBound.
     *
     * @return int
     */
    public function getUseBound()
    {
        return $this->use_bound;
    }

    /**
     * Set sourceType.
     *
     * @param string|null $sourceType
     *
     * @return SeckillActivity
     */
    public function setSourceType($sourceType = null)
    {
        $this->source_type = $sourceType;

        return $this;
    }

    /**
     * Get sourceType.
     *
     * @return string|null
     */
    public function getSourceType()
    {
        return $this->source_type;
    }

    /**
     * Set sourceId.
     *
     * @param int|null $sourceId
     *
     * @return SeckillActivity
     */
    public function setSourceId($sourceId = null)
    {
        $this->source_id = $sourceId;

        return $this;
    }

    /**
     * Get sourceId.
     *
     * @return int|null
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * Set disabled
     *
     * @param boolean $disabled
     *
     * @return SeckillActivity
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled
     *
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }
}
