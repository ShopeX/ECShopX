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

/**
 * ItemsPromotionsTag 各种促销活动商品表
 *
 * @ORM\Table(name="promotions_items_tag", options={"comment"="商品的促销标签表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}, indexes={
 *    @ORM\Index(name="ix_tag_type", columns={"tag_type"}),
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\PromotionsItemsTagRepository")
 */
class PromotionsItemsTag
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
     * @ORM\Column(name="promotion_id", type="bigint", options={"comment":"关联营销id"})
     */
    private $promotion_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"关联对象id(商品ID,标签ID，品牌ID等)", "default": 0})
     */
    private $item_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", nullable=true, options={"comment":"关联商品id", "default": 0})
     */
    private $goods_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="tag_type", type="string", options={"comment":"标签类型: full_discount:满折,full_minus:满减,full_gift:满赠, seckill: 秒杀,"})
     */
    private $tag_type;

    /**
    * @var integer
    *
    * @ORM\Column(name="activity_price", type="bigint", options={"comment":"商品活动价", "default": 0})
    */
    private $activity_price = 0;

    /**
     * @var string
     *  normal 实体类商品
     *  services 服务类商品
     *  tag 标签
     *  category 商品主类目
     *  brand 品牌
     *
     * @ORM\Column(name="item_type", type="string", options={"comment":"活动对象类型: normal:实体类商品,service:服务类商品,tag:标签,category:商品主类目,brand:品牌", "default":"normal"})
     */
    private $item_type = 'normal';

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
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_all_items", type="bigint", options={"comment":"是否全部商品,1:全部商品，2:非全部商品", "default": 2})
     */
    private $is_all_items = 2;

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
     * Set promotionId
     *
     * @param integer $promotionId
     *
     * @return PromotionsItemsTag
     */
    public function setPromotionId($promotionId)
    {
        $this->promotion_id = $promotionId;

        return $this;
    }

    /**
     * Get promotionId
     *
     * @return integer
     */
    public function getPromotionId()
    {
        return $this->promotion_id;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return PromotionsItemsTag
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
     * Set goodsId
     *
     * @param integer $goodsId
     *
     * @return PromotionsItemsTag
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

    /**
     * Set tagType
     *
     * @param string $tagType
     *
     * @return PromotionsItemsTag
     */
    public function setTagType($tagType)
    {
        $this->tag_type = $tagType;

        return $this;
    }

    /**
     * Get tagType
     *
     * @return string
     */
    public function getTagType()
    {
        return $this->tag_type;
    }

    /**
     * Set itemType
     *
     * @param string $itemType
     *
     * @return PromotionsItemsTag
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
     * Set startTime
     *
     * @param integer $startTime
     *
     * @return PromotionsItemsTag
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
     * @return PromotionsItemsTag
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return PromotionsItemsTag
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
     * Set isAllItems
     *
     * @param integer $isAllItems
     *
     * @return PromotionsItemsTag
     */
    public function setIsAllItems($isAllItems)
    {
        $this->is_all_items = $isAllItems;

        return $this;
    }

    /**
     * Get isAllItems
     *
     * @return integer
     */
    public function getIsAllItems()
    {
        return $this->is_all_items;
    }

    /**
     * Set activityPrice
     *
     * @param integer $activityPrice
     *
     * @return PromotionsItemsTag
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
}
