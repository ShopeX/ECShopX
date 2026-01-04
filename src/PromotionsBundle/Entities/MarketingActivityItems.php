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
 * MarketingActivityItems 各种促销活动商品表
 *
 * @ORM\Table(name="promotions_marketing_activity_items", options={"comment"="各种促销活动商品表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}, indexes={
 *    @ORM\Index(name="ix_marketing_type", columns={"marketing_type"}),
 *    @ORM\Index(name="ix_marketing_item", columns={"item_id", "marketing_id"}),
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\MarketingActivityItemsRepository")
 */
class MarketingActivityItems
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"自增id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="marketing_id", type="bigint", options={"comment":"关联营销id"})
     */
    private $marketing_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"关联id(商品ID，标签ID，品牌ID等)"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", nullable=true, options={"comment":"关联商品id"})
     */
    private $goods_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_show", type="boolean", options={"comment":"列表页是否显示", "default": true})
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
     * @ORM\Column(name="marketing_type", type="string", options={"comment":"营销类型: full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购,member_preference:会员优先购"})
     */
    private $marketing_type;

    /**
     * @var string
     *  normal 实体类
     *  services 服务类
     *  tag 标签
     *  category 商品主类目
     *  brand 品牌
     *
     * @ORM\Column(name="item_type", type="string", options={"comment":"活动商品类型: normal:实体类商品,service:服务类商品,tag:标签,category:商品主类目,brand:品牌", "default":"normal"})
     */
    private $item_type = 'normal';

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", length=150, options={"comment":"商品标题"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="integer", options={"comment":"商品价格", "default": 0})
     */
    private $price = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="item_brief", type="string", length=250, nullable=true, options={"comment":"商品简介"})
     */
    private $item_brief;

    /**
     * @var string
     *
     * @ORM\Column(name="pics", type="text", nullable=true, options={"comment":"商品图片"})
     */
    private $pics;
    /**
     * @var string
     *
     * @ORM\Column(name="promotion_tag", type="string", length=15, options={"comment":"促销标签"})
     */
    private $promotion_tag;

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
     * @ORM\Column(name="status", type="boolean", options={"comment":"是否生效中", "default": true})
     */
    private $status = true;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        // ShopEx EcShopX Core Module
        return $this->id;
    }

    /**
     * Set marketingId
     *
     * @param integer $marketingId
     *
     * @return MarketingActivityItems
     */
    public function setMarketingId($marketingId)
    {
        $this->marketing_id = $marketingId;

        return $this;
    }

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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return MarketingActivityItems
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
     * Set isShow
     *
     * @param boolean $isShow
     *
     * @return MarketingActivityItems
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
     * @return MarketingActivityItems
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
     * Set marketingType
     *
     * @param string $marketingType
     *
     * @return MarketingActivityItems
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
     * Set itemType
     *
     * @param string $itemType
     *
     * @return MarketingActivityItems
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
     * Set itemName
     *
     * @param string $itemName
     *
     * @return MarketingActivityItems
     */
    public function setItemName($itemName)
    {
        $this->item_name = $itemName;

        return $this;
    }

    /**
     * Get itemName
     *
     * @return string
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return MarketingActivityItems
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set itemBrief
     *
     * @param string $itemBrief
     *
     * @return MarketingActivityItems
     */
    public function setItemBrief($itemBrief)
    {
        $this->item_brief = $itemBrief;

        return $this;
    }

    /**
     * Get itemBrief
     *
     * @return string
     */
    public function getItemBrief()
    {
        return $this->item_brief;
    }

    /**
     * Set pics
     *
     * @param string $pics
     *
     * @return MarketingActivityItems
     */
    public function setPics($pics)
    {
        $this->pics = $pics;

        return $this;
    }

    /**
     * Get pics
     *
     * @return string
     */
    public function getPics()
    {
        return $this->pics;
    }

    /**
     * Set promotionTag
     *
     * @param string $promotionTag
     *
     * @return MarketingActivityItems
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
     * Set startTime
     *
     * @param integer $startTime
     *
     * @return MarketingActivityItems
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
     * @return MarketingActivityItems
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
     * Set status
     *
     * @param boolean $status
     *
     * @return MarketingActivityItems
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MarketingActivityItems
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
     * Set created
     *
     * @param integer $created
     *
     * @return MarketingActivityItems
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
     * @return MarketingActivityItems
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
     * Set goodsId
     *
     * @param integer $goodsId
     *
     * @return MarketingActivityItems
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId
     *
     * @return integer
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }
}
