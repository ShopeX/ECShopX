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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ItemsCategory 商品属性表
 *
 * @ORM\Table(name="items_attributes",options={"comment"="商品属性表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_attribute_type", columns={"attribute_type"}),
 *    @ORM\Index(name="ix_attribute_name", columns={"attribute_name"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsAttributesRepository")
 */
class ItemsAttributes
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="attribute_id", type="bigint", options={"comment":"商品属性id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $attribute_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_id", type="bigint", options={"comment":"店铺ID，如果为0则表示总部", "default": 0})
     */
    private $shop_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="attribute_type", type="string", length=15, options={"comment":"商品属性类型 unit单位，brand品牌，item_params商品参数, item_spec规格"})
     */
    private $attribute_type;

    /**
     * @var string
     *
     * @ORM\Column(name="attribute_show", type="string", nullable=true,  length=15, options={"comment":"属性展示方式 select 下拉 input 平铺"})
     */
    private $attribute_show = 'select';

    /**
     * @var string
     *
     * @ORM\Column(name="attribute_name", type="string", options={"comment":"商品属性名称"})
     */
    private $attribute_name;

    /**
     * @var string
     *
     * @ORM\Column(name="attribute_memo", nullable=true, type="string", options={"comment":"商品属性备注"})
     */
    private $attribute_memo;

    /**
     * @var string
     *
     * @ORM\Column(name="attribute_sort", type="string", length=15, options={"comment":"商品属性排序，越大越在前"})
     */
    private $attribute_sort;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", nullable=true, type="bigint", options={"comment":"店铺ID", "default":"0"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="is_show", type="string", length=15, options={"comment":"是否用于筛选"})
     */
    private $is_show;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_image", type="string", options={"comment":"属性是否需要配置图片"})
     */
    private $is_image;

    /**
     * @var integer
     *
     * @ORM\Column(name="image_url", nullable=true, type="text", options={"comment":"图片"})
     */
    private $image_url;

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
     * @var string
     *
     * @ORM\Column(name="attribute_code", type="string", nullable=true, length=32, options={"comment":"oms 规格编码"})
     */
    private $attribute_code;

    public function setAttributeShow($attributeShow)
    {
        // ShopEx EcShopX Core Module
        $this->attribute_show = $attributeShow;
        return $this;
    }

    public function getAttributeShow()
    {
        return $this->attribute_show;
    }

    /**
     * Get attributeId
     *
     * @return integer
     */
    public function getAttributeId()
    {
        return $this->attribute_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ItemsAttributes
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
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return ItemsAttributes
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set attributeType
     *
     * @param string $attributeType
     *
     * @return ItemsAttributes
     */
    public function setAttributeType($attributeType)
    {
        $this->attribute_type = $attributeType;

        return $this;
    }

    /**
     * Get attributeType
     *
     * @return string
     */
    public function getAttributeType()
    {
        return $this->attribute_type;
    }

    /**
     * Set attributeName
     *
     * @param string $attributeName
     *
     * @return ItemsAttributes
     */
    public function setAttributeName($attributeName)
    {
        $this->attribute_name = $attributeName;

        return $this;
    }

    /**
     * Get attributeName
     *
     * @return string
     */
    public function getAttributeName()
    {
        return $this->attribute_name;
    }

    /**
     * Set attributeMemo
     *
     * @param string $attributeMemo
     *
     * @return ItemsAttributes
     */
    public function setAttributeMemo($attributeMemo)
    {
        $this->attribute_memo = $attributeMemo;

        return $this;
    }

    /**
     * Get attributeMemo
     *
     * @return string
     */
    public function getAttributeMemo()
    {
        return $this->attribute_memo;
    }

    /**
     * Set attributeSort
     *
     * @param string $attributeSort
     *
     * @return ItemsAttributes
     */
    public function setAttributeSort($attributeSort)
    {
        $this->attribute_sort = $attributeSort;

        return $this;
    }

    /**
     * Get attributeSort
     *
     * @return string
     */
    public function getAttributeSort()
    {
        return $this->attribute_sort;
    }

    /**
     * Set isShow
     *
     * @param string $isShow
     *
     * @return ItemsAttributes
     */
    public function setIsShow($isShow)
    {
        $this->is_show = $isShow;

        return $this;
    }

    /**
     * Get isShow
     *
     * @return string
     */
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * Set isImage
     *
     * @param string $isImage
     *
     * @return ItemsAttributes
     */
    public function setIsImage($isImage)
    {
        $this->is_image = $isImage;

        return $this;
    }

    /**
     * Get isImage
     *
     * @return string
     */
    public function getIsImage()
    {
        return $this->is_image;
    }

    /**
     * Set imageUrl
     *
     * @param string $imageUrl
     *
     * @return ItemsAttributes
     */
    public function setImageUrl($imageUrl)
    {
        $this->image_url = $imageUrl;

        return $this;
    }

    /**
     * Get imageUrl
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->image_url;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ItemsAttributes
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
     * @return ItemsAttributes
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
     * @return ItemsAttributes
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
     * Set attributeCode
     *
     * @param integer $attributeCode
     *
     * @return ItemRelAttributes
     */
    public function setAttributeCode($attributeCode)
    {
        $this->attribute_code = $attributeCode;

        return $this;
    }

    /**
     * Get attributeCode
     *
     * @return integer
     */
    public function getAttributeCode()
    {
        return $this->attribute_code;
    }
}
