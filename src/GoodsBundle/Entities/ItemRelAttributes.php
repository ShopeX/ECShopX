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
 * ItemRelAttributes 商品关联属性表
 *
 * @ORM\Table(name="items_rel_attributes", options={"comment"="商品关联属性表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_item_id", columns={"item_id"}),
 *    @ORM\Index(name="ix_attribute_type", columns={"attribute_type"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemRelAttributesRepository")
 */
class ItemRelAttributes
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品ID"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="attribute_sort", nullable=true, type="integer", options={"comment":"商品属性排序", "default": 0})
     */
    private $attribute_sort;

    /**
     * @var integer
     *
     * @ORM\Column(name="attribute_id", type="bigint", options={"comment":"商品属性id"})
     */
    private $attribute_id;

    /**
     * @var string
     *
     * @ORM\Column(name="attribute_type", type="string", length=15, options={"comment":"商品属性类型 unit单位，brand品牌，item_params商品参数, item_spec规格"})
     */
    private $attribute_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="attribute_value_id", nullable=true, type="bigint", options={"comment":"商品属性值id"})
     */
    private $attribute_value_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="custom_attribute_value", nullable=true, type="string", options={"comment":"自定义属性名称"})
     */
    private $custom_attribute_value;

    /**
     * @var string
     *
     * @ORM\Column(name="image_url", nullable=true, type="json_array", options={"comment":"规格自定义图片"})
     */
    private $image_url;

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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ItemRelAttributes
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
        // Debug: 1e2364
        return $this->company_id;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return ItemRelAttributes
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
     * Set attributeId
     *
     * @param integer $attributeId
     *
     * @return ItemRelAttributes
     */
    public function setAttributeId($attributeId)
    {
        $this->attribute_id = $attributeId;

        return $this;
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
     * Set attributeType
     *
     * @param string $attributeType
     *
     * @return ItemRelAttributes
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
     * Set attributeValueId
     *
     * @param integer $attributeValueId
     *
     * @return ItemRelAttributes
     */
    public function setAttributeValueId($attributeValueId)
    {
        $this->attribute_value_id = $attributeValueId;

        return $this;
    }

    /**
     * Get attributeValueId
     *
     * @return integer
     */
    public function getAttributeValueId()
    {
        return $this->attribute_value_id;
    }

    /**
     * Set imageUrl
     *
     * @param string $imageUrl
     *
     * @return ItemRelAttributes
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
     * Set attributeSort
     *
     * @param integer $attributeSort
     *
     * @return ItemRelAttributes
     */
    public function setAttributeSort($attributeSort)
    {
        $this->attribute_sort = $attributeSort;

        return $this;
    }

    /**
     * Get attributeSort
     *
     * @return integer
     */
    public function getAttributeSort()
    {
        return $this->attribute_sort;
    }

    /**
     * Set customAttributeValue
     *
     * @param string $customAttributeValue
     *
     * @return ItemRelAttributes
     */
    public function setCustomAttributeValue($customAttributeValue)
    {
        $this->custom_attribute_value = $customAttributeValue;

        return $this;
    }

    /**
     * Get customAttributeValue
     *
     * @return string
     */
    public function getCustomAttributeValue()
    {
        return $this->custom_attribute_value;
    }
}
