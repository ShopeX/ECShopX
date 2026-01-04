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
 * MarketingActivityItems 活动赠品表
 *
 * @ORM\Table(name="promotions_marketing_gift_items", options={"comment"="活动赠品列表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_item_id", columns={"item_id"}),
 *    @ORM\Index(name="ix_marketing_id", columns={"marketing_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\MarketingGiftItemsRepository")
 */
class MarketingGiftItems
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
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"关联商品id"})
     */
    private $item_id;

    /**
     * @var string
     *  normal 实体类
     *  services 服务类
     *
     * @ORM\Column(name="item_type", type="string", options={"comment":"活动商品类型: normal:实体类商品,service:服务类商品", "default":"normal"})
     */
    private $item_type = 'normal';

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", length=255, options={"comment":"商品标题"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="integer", options={"comment":"赠品价格", "default": 0})
     */
    private $price = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="store", type="integer", options={"comment":"赠品库存", "default": 0})
     */
    private $store = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="gift_num", type="integer", options={"comment":"赠品数量", "default": 0})
     */
    private $gift_num = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="pics", type="text", nullable=true, options={"comment":"商品图片"})
     */
    private $pics;

    /**
     * @var string
     *
     * @ORM\Column(name="without_return", type="boolean", options={"comment":"退货无需退回赠品", "default": false})
     */
    private $without_return = false;

    /**
     * @var string
     *
     * @ORM\Column(name="condition_type", type="text", options={"comment":"营销条件标准  quantity:按总件数, totalfee:按总金额","default":"totalfee"})
     */
    private $condition_type = "totalfee";

    /**
     * @var string
     *
     * @ORM\Column(name="filter_full", type="integer", nullable=true, options={"comment":"赠品满足所需条件"})
     */
    private $filter_full;

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
        return $this->id;
    }

    /**
     * Set marketingId
     *
     * @param integer $marketingId
     *
     * @return MarketingGiftItems
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
     * @return MarketingGiftItems
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
     * @return MarketingGiftItems
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
     * @return MarketingGiftItems
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
     * @return MarketingGiftItems
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
     * Set giftNum
     *
     * @param integer $giftNum
     *
     * @return MarketingGiftItems
     */
    public function setGiftNum($giftNum)
    {
        $this->gift_num = $giftNum;

        return $this;
    }

    /**
     * Get giftNum
     *
     * @return integer
     */
    public function getGiftNum()
    {
        return $this->gift_num;
    }

    /**
     * Set pics
     *
     * @param string $pics
     *
     * @return MarketingGiftItems
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
     * Set withoutReturn
     *
     * @param boolean $withoutReturn
     *
     * @return MarketingGiftItems
     */
    public function setWithoutReturn($withoutReturn)
    {
        $this->without_return = $withoutReturn;

        return $this;
    }

    /**
     * Get withoutReturn
     *
     * @return boolean
     */
    public function getWithoutReturn()
    {
        return $this->without_return;
    }

    /**
     * Set conditionType
     *
     * @param string $conditionType
     *
     * @return MarketingGiftItems
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
     * Set filterFull
     *
     * @param integer $filterFull
     *
     * @return MarketingGiftItems
     */
    public function setFilterFull($filterFull)
    {
        $this->filter_full = $filterFull;

        return $this;
    }

    /**
     * Get filterFull
     *
     * @return integer
     */
    public function getFilterFull()
    {
        return $this->filter_full;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MarketingGiftItems
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
     * @return MarketingGiftItems
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
     * @return MarketingGiftItems
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
     * Set store
     *
     * @param integer $store
     *
     * @return MarketingGiftItems
     */
    public function setStore($store)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Get store
     *
     * @return integer
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Set itemSpecDesc
     *
     * @param string $itemSpecDesc
     *
     * @return MarketingGiftItems
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
}
