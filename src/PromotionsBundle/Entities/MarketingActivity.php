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
 * MarketingActivity 各种促销活动表
 *
 * @ORM\Table(name="promotions_marketing_activity", options={"comment":"各种促销活动表", "collate":"utf8mb4_unicode_ci", "charset":"utf8mb4"}, indexes={
 *    @ORM\Index(name="ix_marketing_type", columns={"marketing_type"}),
 *    @ORM\Index(name="ix_start_time", columns={"start_time"}),
 *    @ORM\Index(name="ix_source_id", columns={"source_id"}),
 *    @ORM\Index(name="idx_companyid_starttime_endtime", columns={"company_id","start_time","end_time"})
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\MarketingActivityRepository")
 */
class MarketingActivity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="marketing_id", type="bigint", options={"comment":"营销id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $marketing_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="marketing_type", type="string", options={"comment":"营销类型: full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购,member_preference:会员优先购"})
     */
    private $marketing_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="rel_marketing_id", type="bigint", nullable=true, options={"comment":"关联其他营销id","default":0})
     */
    private $rel_marketing_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="marketing_name", type="string", options={"comment":"营销活动名称"})
     */
    private $marketing_name;

    /**
     * @var string
     *
     * @ORM\Column(name="ad_pic", type="string", nullable=true, options={"comment":"活动广告图"})
     */
    private $ad_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="marketing_desc", type="string", options={"comment":"营销活动描述"})
     */
    private $marketing_desc;

    /**
     * @var string
     *
     * @ORM\Column(name="start_time", type="integer", options={"comment":"活动开始时间"})
     */
    private $start_time;

    /**
     * @var string
     *
     * @ORM\Column(name="end_time", type="integer", options={"comment":"活动结束时间"})
     */
    private $end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="release_time", type="integer", nullable=true, options={"comment":"活动发布时间"})
     */
    private $release_time;

    /**
     * @var string
     *
     * @ORM\Column(name="used_platform", type="integer", options={"comment":"适用平台:  0:全场可用,1:只用于pc端,2:小程序端,3:h5端", "default":0})
     */
    private $used_platform = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="use_bound", type="integer", options={"comment":"适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用", "default":0})
     */
    private $use_bound = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="tag_ids", type="text", nullable=true, options={"comment":"标签id集合"})
     */
    private $tag_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="brand_ids", type="text", nullable=true, options={"comment":"品牌id集合"})
     */
    private $brand_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="use_shop", type="integer", options={"comment":"适用店铺: 0:全场可用,1:指定店铺可用", "default":0})
     */
    private $use_shop = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_ids", type="text", nullable=true, options={"comment":"店铺id集合", "default":"all"})
     */
    private $shop_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="valid_grade", type="text", nullable=true, options={"comment":"会员级别集合"})
     */
    private $valid_grade;

    /**
     * @var string
     *
     * @ORM\Column(name="condition_type", type="text", options={"comment":"营销条件标准  quantity:按总件数, totalfee:按总金额","default":"totalfee"})
     */
    private $condition_type = "totalfee";

    /**
     * @var string
     *
     * @ORM\Column(name="condition_value", type="text", options={"comment":"营销规则值"})
     */
    private $condition_value;

    /**
     * @var string
     *
     * @ORM\Column(name="in_proportion", type="boolean", nullable=true, options={"comment":"是否按比例多次赠送", "default":false})
     */
    private $in_proportion = false;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_background", type="string", nullable=true, options={"comment":"加价购活动页面背景"})
     */
    private $activity_background;

    /**
     * @var string
     *
     * @ORM\Column(name="navbar_color", type="string", nullable=true, options={"comment":"加价购活动页面导航栏颜色"})
     */
    private $navbar_color;

    /**
     * @var string
     *
     * @ORM\Column(name="timeBackgroundColor", type="string", nullable=true, options={"comment":"加价购活动页面时间背景颜色"})
     */
    private $timeBackgroundColor;

    /**
     * @var string
     *
     * @ORM\Column(name="canjoin_repeat", type="boolean", nullable=true, options={"comment":"是否上不封顶", "default":false})
     */
    private $canjoin_repeat = false;

    /**
     * @var string
     *
     * @ORM\Column(name="join_limit", type="integer", options={"comment":"可参与次数", "default":0})
     */
    private $join_limit = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="free_postage", type="boolean", options={"comment":"是否免邮","default":false})
     */
    private $free_postage = false;

    /**
     * @var string
     *
     * @ORM\Column(name="promotion_tag", type="string", length=15, options={"comment":"促销标签"})
     */
    private $promotion_tag;

    /**
     * @var string
     *
     * @ORM\Column(name="check_status", type="string", options={"comment":"促销状态:  non-reviewed:未审核,pending:待审核,agree:审核通过,refuse:已拒绝,cancel:已取消,overdue:已过期", "default":"agree"})
     */
    private $check_status = "agree";
    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", length=500, nullable=true, options={"comment":"审核不通过原因"})
     */
    private $reason;

    /**
     * @var string
     *  normal 实体类
     *  service 服务类
     *
     * @ORM\Column(name="item_type", type="string", options={"comment":"活动商品类型: normal:实体类商品,service:服务类商品", "default":"normal"})
     */
    private $item_type = 'normal';

    /**
     * @var string
     *
     * @ORM\Column(name="is_increase_purchase", type="boolean", nullable=true, options={"comment":"开启加价购，满赠时启用"})
     */
    private $is_increase_purchase;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

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
     * Get marketingId
     *
     * @return integer
     */
    public function getMarketingId()
    {
        return $this->marketing_id;
    }

    /**
     * Set marketingType
     *
     * @param string $marketingType
     *
     * @return MarketingActivity
     */
    public function setMarketingType($marketingType)
    {
        $this->marketing_type = $marketingType;

        return $this;
    }

    /**
     * Get marketingType
     *
     * @return string
     */
    public function getMarketingType()
    {
        return $this->marketing_type;
    }

    /**
     * Set relMarketingId
     *
     * @param integer $relMarketingId
     *
     * @return MarketingActivity
     */
    public function setRelMarketingId($relMarketingId)
    {
        $this->rel_marketing_id = $relMarketingId;

        return $this;
    }

    /**
     * Get relMarketingId
     *
     * @return integer
     */
    public function getRelMarketingId()
    {
        return $this->rel_marketing_id;
    }

    /**
     * Set marketingName
     *
     * @param string $marketingName
     *
     * @return MarketingActivity
     */
    public function setMarketingName($marketingName)
    {
        $this->marketing_name = $marketingName;

        return $this;
    }

    /**
     * Get marketingName
     *
     * @return string
     */
    public function getMarketingName()
    {
        return $this->marketing_name;
    }

    /**
     * Set marketingDesc
     *
     * @param string $marketingDesc
     *
     * @return MarketingActivity
     */
    public function setMarketingDesc($marketingDesc)
    {
        $this->marketing_desc = $marketingDesc;

        return $this;
    }

    /**
     * Get marketingDesc
     *
     * @return string
     */
    public function getMarketingDesc()
    {
        return $this->marketing_desc;
    }

    /**
     * Set startTime
     *
     * @param integer $startTime
     *
     * @return MarketingActivity
     */
    public function setStartTime($startTime)
    {
        $this->start_time = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return integer
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set endTime
     *
     * @param integer $endTime
     *
     * @return MarketingActivity
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
     * Set releaseTime
     *
     * @param integer $releaseTime
     *
     * @return MarketingActivity
     */
    public function setReleaseTime($releaseTime)
    {
        $this->release_time = $releaseTime;

        return $this;
    }

    /**
     * Get releaseTime
     *
     * @return integer
     */
    public function getReleaseTime()
    {
        return $this->release_time;
    }

    /**
     * Set usedPlatform
     *
     * @param integer $usedPlatform
     *
     * @return MarketingActivity
     */
    public function setUsedPlatform($usedPlatform)
    {
        $this->used_platform = $usedPlatform;

        return $this;
    }

    /**
     * Get usedPlatform
     *
     * @return integer
     */
    public function getUsedPlatform()
    {
        return $this->used_platform;
    }

    /**
     * Set useBound
     *
     * @param integer $useBound
     *
     * @return MarketingActivity
     */
    public function setUseBound($useBound)
    {
        $this->use_bound = $useBound;

        return $this;
    }

    /**
     * Get useBound
     *
     * @return integer
     */
    public function getUseBound()
    {
        return $this->use_bound;
    }

    /**
     * Set useShop
     *
     * @param integer $useShop
     *
     * @return MarketingActivity
     */
    public function setUseShop($useShop)
    {
        $this->use_shop = $useShop;

        return $this;
    }

    /**
     * Get useShop
     *
     * @return integer
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
     * @return MarketingActivity
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
     * Set validGrade
     *
     * @param string $validGrade
     *
     * @return MarketingActivity
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
     * Set timeBackgroundColor
     *
     * @param string $timeBackgroundColor
     *
     * @return MarketingActivity
     */
    public function settimeBackgroundColor($timeBackgroundColor)
    {
        $this->timeBackgroundColor = $timeBackgroundColor;

        return $this;
    }

    /**
     * Get timeBackgroundColor
     *
     * @return string
     */
    public function gettimeBackgroundColor()
    {
        return $this->timeBackgroundColor;
    }



    /**
     * Set conditionType
     *
     * @param string $conditionType
     *
     * @return MarketingActivity
     */
    public function setConditionType($conditionType)
    {
        $this->condition_type = $conditionType;

        return $this;
    }

    /**
     * Get conditionType
     *
     * @return string
     */
    public function getConditionType()
    {
        return $this->condition_type;
    }

    /**
     * Set conditionValue
     *
     * @param string $conditionValue
     *
     * @return MarketingActivity
     */
    public function setConditionValue($conditionValue)
    {
        $this->condition_value = $conditionValue;

        return $this;
    }

    /**
     * Get conditionValue
     *
     * @return string
     */
    public function getConditionValue()
    {
        return $this->condition_value;
    }

    /**
     * Set canjoinRepeat
     *
     * @param boolean $canjoinRepeat
     *
     * @return MarketingActivity
     */
    public function setCanjoinRepeat($canjoinRepeat)
    {
        $this->canjoin_repeat = $canjoinRepeat;

        return $this;
    }

    /**
     * Get canjoinRepeat
     *
     * @return boolean
     */
    public function getCanjoinRepeat()
    {
        return $this->canjoin_repeat;
    }

    /**
     * Set joinLimit
     *
     * @param integer $joinLimit
     *
     * @return MarketingActivity
     */
    public function setJoinLimit($joinLimit)
    {
        $this->join_limit = $joinLimit;

        return $this;
    }

    /**
     * Get joinLimit
     *
     * @return integer
     */
    public function getJoinLimit()
    {
        return $this->join_limit;
    }

    /**
     * Set freePostage
     *
     * @param boolean $freePostage
     *
     * @return MarketingActivity
     */
    public function setFreePostage($freePostage)
    {
        $this->free_postage = $freePostage;

        return $this;
    }

    /**
     * Get freePostage
     *
     * @return boolean
     */
    public function getFreePostage()
    {
        return $this->free_postage;
    }

    /**
     * Set promotionTag
     *
     * @param string $promotionTag
     *
     * @return MarketingActivity
     */
    public function setPromotionTag($promotionTag)
    {
        $this->promotion_tag = $promotionTag;

        return $this;
    }

    /**
     * Get promotionTag
     *
     * @return string
     */
    public function getPromotionTag()
    {
        return $this->promotion_tag;
    }

    /**
     * Set checkStatus
     *
     * @param string $checkStatus
     *
     * @return MarketingActivity
     */
    public function setCheckStatus($checkStatus)
    {
        $this->check_status = $checkStatus;

        return $this;
    }

    /**
     * Get checkStatus
     *
     * @return string
     */
    public function getCheckStatus()
    {
        return $this->check_status;
    }

    /**
     * Set reason
     *
     * @param string $reason
     *
     * @return MarketingActivity
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set itemType
     *
     * @param string $itemType
     *
     * @return MarketingActivity
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MarketingActivity
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
     * Set activityBackground
     *
     * @param string $activityBackground
     *
     * @return MarketingActivity
     */
    public function setActivityBackground($activityBackground)
    {
        $this->activity_background = $activityBackground;

        return $this;
    }

    /**
     * Get activityBackground
     *
     * @return string
     */
    public function getActivityBackground()
    {
        return $this->activity_background;
    }

    /**
     * Set navbarColor
     *
     * @param string $navbarColor
     *
     * @return MarketingActivity
     */
    public function setNavbarColor($navbarColor)
    {
        $this->navbar_color = $navbarColor;

        return $this;
    }

    /**
     * Get navbarColor
     *
     * @return string
     */
    public function getNavbarColor()
    {
        return $this->navbar_color;
    }


    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MarketingActivity
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
     * @return MarketingActivity
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
     * Set isIncreasePurchase
     *
     * @param boolean $isIncreasePurchase
     *
     * @return MarketingActivity
     */
    public function setIsIncreasePurchase($isIncreasePurchase)
    {
        $this->is_increase_purchase = $isIncreasePurchase;

        return $this;
    }

    /**
     * Get isIncreasePurchase
     *
     * @return boolean
     */
    public function getIsIncreasePurchase()
    {
        return $this->is_increase_purchase;
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
     * Set tagIds.
     *
     * @param string|null $tagIds
     *
     * @return MarketingActivity
     */
    public function setTagIds($tagIds = null)
    {
        $this->tag_ids = $tagIds;

        return $this;
    }

    /**
     * Get tagIds.
     *
     * @return string|null
     */
    public function getTagIds()
    {
        return $this->tag_ids;
    }

    /**
     * Set brandIds.
     *
     * @param string|null $brandIds
     *
     * @return MarketingActivity
     */
    public function setBrandIds($brandIds = null)
    {
        $this->brand_ids = $brandIds;

        return $this;
    }

    /**
     * Get brandIds.
     *
     * @return string|null
     */
    public function getBrandIds()
    {
        return $this->brand_ids;
    }

    /**
     * Set inProportion.
     *
     * @param bool|null $inProportion
     *
     * @return MarketingActivity
     */
    public function setInProportion($inProportion = null)
    {
        $this->in_proportion = $inProportion;

        return $this;
    }

    /**
     * Get inProportion.
     *
     * @return bool|null
     */
    public function getInProportion()
    {
        return $this->in_proportion;
    }

    /**
     * Set sourceType.
     *
     * @param string|null $sourceType
     *
     * @return MarketingActivity
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
     * @return MarketingActivity
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
}
