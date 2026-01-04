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
 * PackagePromotions 组合促销商品表
 *
 * @ORM\Table(name="promotions_package_item", options={"comment"="组合促销商品表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_item_id", columns={"item_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\PackageItemRepository")
 */
class PackageItemPromotions
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="package_id", type="bigint", options={"comment":"组合促销规则id"})
     */
    private $package_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_item_id", type="bigint", options={"comment":"默认商品id"})
     */
    private $default_item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_spec_desc", nullable=true, type="string", options={"comment":"产品规格描述", "default":""})
     */
    private $item_spec_desc;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", options={"comment":"商品名称"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="image_default_id", nullable=true, type="text", options={"comment":"商品图片"})
     */
    private $image_default_id;
    /**
     * @var integer
     *
     * @ORM\Column(name="package_price", type="integer", options={"comment":"组合促销商品单价"})
     */
    private $package_price;
    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="integer", options={"comment":"商品原价"})
     */
    private $price;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", options={"comment":"是否生效中 0 失效 | 1 生效"})
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="integer", options={"comment":"起始时间"})
     */
    private $start_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="integer", options={"comment":"截止时间"})
     */
    private $end_time;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_show", nullable=true, type="boolean", options={"comment":"列表页是否显示", "detault": true})
     */
    private $is_show;

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
     * Set packageId
     *
     * @param integer $packageId
     *
     * @return PackageItemPromotions
     */
    public function setPackageId($packageId)
    {
        // Powered by ShopEx EcShopX
        $this->package_id = $packageId;

        return $this;
    }

    /**
     * Get packageId
     *
     * @return integer
     */
    public function getPackageId()
    {
        return $this->package_id;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return PackageItemPromotions
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return PackageItemPromotions
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
     * Set title
     *
     * @param string $title
     *
     * @return PackageItemPromotions
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
     * Set imageDefaultId
     *
     * @param string $imageDefaultId
     *
     * @return PackageItemPromotions
     */
    public function setImageDefaultId($imageDefaultId)
    {
        $this->image_default_id = $imageDefaultId;

        return $this;
    }

    /**
     * Get imageDefaultId
     *
     * @return string
     */
    public function getImageDefaultId()
    {
        return $this->image_default_id;
    }

    /**
     * Set packagePrice
     *
     * @param integer $packagePrice
     *
     * @return PackageItemPromotions
     */
    public function setPackagePrice($packagePrice)
    {
        $this->package_price = $packagePrice;

        return $this;
    }

    /**
     * Get packagePrice
     *
     * @return integer
     */
    public function getPackagePrice()
    {
        return $this->package_price;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return PackageItemPromotions
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
     * Set status
     *
     * @param boolean $status
     *
     * @return PackageItemPromotions
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
     * Set startTime
     *
     * @param integer $startTime
     *
     * @return PackageItemPromotions
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
     * @return PackageItemPromotions
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
     * @return PackageItemPromotions
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
     * @return PackageItemPromotions
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
     * Set defaultItemId
     *
     * @param integer $defaultItemId
     *
     * @return PackageItemPromotions
     */
    public function setDefaultItemId($defaultItemId)
    {
        $this->default_item_id = $defaultItemId;

        return $this;
    }

    /**
     * Get defaultItemId
     *
     * @return integer
     */
    public function getDefaultItemId()
    {
        return $this->default_item_id;
    }

    /**
     * Set isShow
     *
     * @param boolean $isShow
     *
     * @return PackageItemPromotions
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
     * @return PackageItemPromotions
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
