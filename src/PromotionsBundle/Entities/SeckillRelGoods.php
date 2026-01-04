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
 * SeckillRelGoods 秒杀关联商品表
 *
 * @ORM\Table(name="promotions_seckill_rel_goods", options={"comment"="秒杀关联商品表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_seckill_item_id", columns={"seckill_id", "item_id"}),
 *    @ORM\Index(name="ix_item_type", columns={"item_type"}),
 *    @ORM\Index(name="ix_release_time", columns={"activity_release_time"}),
 *    @ORM\Index(name="ix_end_time", columns={"activity_end_time"}),
 *    @ORM\Index(name="ix_itemid_activityreleasetime_activityendtime", columns={"item_id","activity_release_time","activity_end_time"})
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\SeckillRelGoodsRepository")
 */
class SeckillRelGoods
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"关联id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="seckill_id", type="bigint", options={"comment":"秒杀活动id"})
     */
    private $seckill_id;

    /**
     * @var string
     *  normal正常的秒杀活动
     *  limited_time_sale限时特惠
     *
     * @ORM\Column(name="seckill_type", type="string", options={"comment":"秒杀类型 normal正常的秒杀活动， limited_time_sale限时特惠", "default":"normal"})
     */
    private $seckill_type = 'normal';

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"秒杀活动商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_type", type="string", options={"comment":"秒杀活动商品类型。可选值有 normal-实体类;services-服务类", "default":"normal"})
     */
    private $item_type = 'normal';

    /**
     * @var integer
     *
     * @ORM\Column(name="is_show", type="boolean", options={"comment":"查询列表是否显示", "default": true})
     */
    private $is_show = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_spec_desc", nullable=true, type="string", options={"comment":"商品规格描述"})
     */
    private $item_spec_desc;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

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
     * @ORM\Column(name="item_title", type="string", options={"comment":"秒杀活动商品名称"})
     */
    private $item_title;

    /**
     * @var string
     *
     * @ORM\Column(name="item_pic", type="text", nullable=true, options={"comment":"秒杀活动商品图片"})
     */
    private $item_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_price", type="integer", options={"comment":"秒杀活动价格", "default":0})
     */
    private $activity_price;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_store", type="integer", options={"comment":"秒杀活动库存", "default":0})
     */
    private $activity_store = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="limit_num", type="integer", options={"comment":"秒杀活动限购","default":0})
     */
    private $limit_num = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="sales_store", type="integer", options={"comment":"已购买库存", "default": 0})
     */
    private $sales_store = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", options={"comment":"商品排序", "default": 0})
     */
    private $sort = 0;

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
     * @var bool
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"是否失效", "default": 0})
     */
    private $disabled = 0;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set seckillId
     *
     * @param integer $seckillId
     *
     * @return SeckillRelGoods
     */
    public function setSeckillId($seckillId)
    {
        $this->seckill_id = $seckillId;

        return $this;
    }

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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return SeckillRelGoods
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set itemType
     *
     * @param string $itemType
     *
     * @return SeckillRelGoods
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
     * Set isShow
     *
     * @param boolean $isShow
     *
     * @return SeckillRelGoods
     */
    public function setIsShow($isShow)
    {
        $this->is_show = $isShow;

        return $this;
    }

    /**
     * Get isShow
     *
     * @return boolean
     */
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * Set itemSpecDesc
     *
     * @param string $itemSpecDesc
     *
     * @return SeckillRelGoods
     */
    public function setItemSpecDesc($itemSpecDesc)
    {
        $this->item_spec_desc = $itemSpecDesc;

        return $this;
    }

    /**
     * Get itemSpecDesc
     *
     * @return string
     */
    public function getItemSpecDesc()
    {
        return $this->item_spec_desc;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return SeckillRelGoods
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
     * Set activityStartTime
     *
     * @param integer $activityStartTime
     *
     * @return SeckillRelGoods
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
     * @return SeckillRelGoods
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
     * @return SeckillRelGoods
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
     * Set itemTitle
     *
     * @param string $itemTitle
     *
     * @return SeckillRelGoods
     */
    public function setItemTitle($itemTitle)
    {
        $this->item_title = $itemTitle;

        return $this;
    }

    /**
     * Get itemTitle
     *
     * @return string
     */
    public function getItemTitle()
    {
        return $this->item_title;
    }

    /**
     * Set itemPic
     *
     * @param string $itemPic
     *
     * @return SeckillRelGoods
     */
    public function setItemPic($itemPic)
    {
        $this->item_pic = $itemPic;

        return $this;
    }

    /**
     * Get itemPic
     *
     * @return string
     */
    public function getItemPic()
    {
        return $this->item_pic;
    }

    /**
     * Set activityPrice
     *
     * @param integer $activityPrice
     *
     * @return SeckillRelGoods
     */
    public function setActivityPrice($activityPrice)
    {
        $this->activity_price = $activityPrice;

        return $this;
    }

    /**
     * Get activityPrice
     *
     * @return integer
     */
    public function getActivityPrice()
    {
        return $this->activity_price;
    }

    /**
     * Set activityStore
     *
     * @param integer $activityStore
     *
     * @return SeckillRelGoods
     */
    public function setActivityStore($activityStore)
    {
        $this->activity_store = $activityStore;

        return $this;
    }

    /**
     * Get activityStore
     *
     * @return integer
     */
    public function getActivityStore()
    {
        return $this->activity_store;
    }

    /**
     * Set limitNum
     *
     * @param integer $limitNum
     *
     * @return SeckillRelGoods
     */
    public function setLimitNum($limitNum)
    {
        $this->limit_num = $limitNum;

        return $this;
    }

    /**
     * Get limitNum
     *
     * @return integer
     */
    public function getLimitNum()
    {
        return $this->limit_num;
    }

    /**
     * Set salesStore
     *
     * @param integer $salesStore
     *
     * @return SeckillRelGoods
     */
    public function setSalesStore($salesStore)
    {
        $this->sales_store = $salesStore;

        return $this;
    }

    /**
     * Get salesStore
     *
     * @return integer
     */
    public function getSalesStore()
    {
        return $this->sales_store;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     *
     * @return SeckillRelGoods
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return SeckillRelGoods
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
     * @return SeckillRelGoods
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
     * Set seckillType
     *
     * @param string $seckillType
     *
     * @return SeckillRelGoods
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
     * Set disabled
     *
     * @param boolean $disabled
     *
     * @return SeckillRelGoods
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
