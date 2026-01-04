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

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * ItemsRecommend 商品推荐表
 *
 * @ORM\Table(name="items_recommend", options={"comment"="商品推荐表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_main_item_id", columns={"main_item_id"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsRecommendRepository")
 */
class ItemsRecommend
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment"="id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="main_item_id", type="bigint", options={"comment":"主商品"})
     */
    private $main_item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="brief", type="string",nullable=true, length=255, options={"comment":"简洁的描述"})
     */
    private $brief;

    /**
     * @var string
     *
     * @ORM\Column(name="pics", type="text", options={"comment":"商品图片"})
     */
    private $pics;

    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="integer", options={"comment":"价格,单位为‘分’"})
     */
    private $price;

    /**
     * @var integer
     *
     * @ORM\Column(name="market_price", type="integer", options={"comment":"原价,单位为‘分’", "default": 0})
     */
    private $market_price;

    /**
     * @var string
     *
     * @ORM\Column(name="item_spec_desc", nullable=true, type="string", options={"comment":"产品规格描述", "default":""})
     */
    private $item_spec_desc;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", options={"comment":"商品排序", "default": 0})
     */
    private $sort = 0;




    /**
     * Set id.
     *
     * @param int $id
     *
     * @return ItemsRecommend
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set mainItemId.
     *
     * @param int $mainItemId
     *
     * @return ItemsRecommend
     */
    public function setMainItemId($mainItemId)
    {
        $this->main_item_id = $mainItemId;

        return $this;
    }

    /**
     * Get mainItemId.
     *
     * @return int
     */
    public function getMainItemId()
    {
        return $this->main_item_id;
    }

    /**
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return ItemsRecommend
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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ItemsRecommend
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
     * Set itemName.
     *
     * @param string $itemName
     *
     * @return ItemsRecommend
     */
    public function setItemName($itemName)
    {
        $this->item_name = $itemName;

        return $this;
    }

    /**
     * Get itemName.
     *
     * @return string
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set pics.
     *
     * @param string $pics
     *
     * @return ItemsRecommend
     */
    public function setPics($pics)
    {
        $this->pics = $pics;

        return $this;
    }

    /**
     * Get pics.
     *
     * @return string
     */
    public function getPics()
    {
        return $this->pics;
    }

    /**
     * Set price.
     *
     * @param int $price
     *
     * @return ItemsRecommend
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price.
     *
     * @return int
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set itemSpecDesc.
     *
     * @param string|null $itemSpecDesc
     *
     * @return ItemsRecommend
     */
    public function setItemSpecDesc($itemSpecDesc = null)
    {
        $this->item_spec_desc = $itemSpecDesc;

        return $this;
    }

    /**
     * Get itemSpecDesc.
     *
     * @return string|null
     */
    public function getItemSpecDesc()
    {
        return $this->item_spec_desc;
    }

    /**
     * Set sort.
     *
     * @param int $sort
     *
     * @return ItemsRecommend
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort.
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }


    /**
     * Set brief.
     *
     * @param string $brief
     *
     * @return ItemsRecommend
     */
    public function setBrief($brief)
    {
        $this->brief = $brief;

        return $this;
    }

    /**
     * Get brief.
     *
     * @return string
     */
    public function getBrief()
    {
        return $this->brief;
    }

    /**
     * Set marketPrice.
     *
     * @param int|null $marketPrice
     *
     * @return ItemsRecommend
     */
    public function setMarketPrice($marketPrice = null)
    {
        $this->market_price = $marketPrice;

        return $this;
    }

    /**
     * Get marketPrice.
     *
     * @return int|null
     */
    public function getMarketPrice()
    {
        return $this->market_price;
    }
}
