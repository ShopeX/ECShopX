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

namespace PointsmallBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * PointsmallItemRelAttributes 积分商品关联属性表
 *
 * @ORM\Table(name="pointsmall_items_rel_attributes", options={"comment"="积分商品关联属性表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_item_id", columns={"item_id"}),
 *    @ORM\Index(name="ix_attribute_type", columns={"attribute_type"}),
 * })
 * @ORM\Entity(repositoryClass="PointsmallBundle\Repositories\PointsmallItemRelAttributesRepository")
 */
class PointsmallItemRelAttributes
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return PointsmallItemRelAttributes
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
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return PointsmallItemRelAttributes
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
     * Set attributeSort.
     *
     * @param int|null $attributeSort
     *
     * @return PointsmallItemRelAttributes
     */
    public function setAttributeSort($attributeSort = null)
    {
        $this->attribute_sort = $attributeSort;

        return $this;
    }

    /**
     * Get attributeSort.
     *
     * @return int|null
     */
    public function getAttributeSort()
    {
        return $this->attribute_sort;
    }

    /**
     * Set attributeId.
     *
     * @param int $attributeId
     *
     * @return PointsmallItemRelAttributes
     */
    public function setAttributeId($attributeId)
    {
        $this->attribute_id = $attributeId;

        return $this;
    }

    /**
     * Get attributeId.
     *
     * @return int
     */
    public function getAttributeId()
    {
        return $this->attribute_id;
    }

    /**
     * Set attributeType.
     *
     * @param string $attributeType
     *
     * @return PointsmallItemRelAttributes
     */
    public function setAttributeType($attributeType)
    {
        $this->attribute_type = $attributeType;

        return $this;
    }

    /**
     * Get attributeType.
     *
     * @return string
     */
    public function getAttributeType()
    {
        return $this->attribute_type;
    }

    /**
     * Set attributeValueId.
     *
     * @param int|null $attributeValueId
     *
     * @return PointsmallItemRelAttributes
     */
    public function setAttributeValueId($attributeValueId = null)
    {
        $this->attribute_value_id = $attributeValueId;

        return $this;
    }

    /**
     * Get attributeValueId.
     *
     * @return int|null
     */
    public function getAttributeValueId()
    {
        return $this->attribute_value_id;
    }

    /**
     * Set customAttributeValue.
     *
     * @param string|null $customAttributeValue
     *
     * @return PointsmallItemRelAttributes
     */
    public function setCustomAttributeValue($customAttributeValue = null)
    {
        $this->custom_attribute_value = $customAttributeValue;

        return $this;
    }

    /**
     * Get customAttributeValue.
     *
     * @return string|null
     */
    public function getCustomAttributeValue()
    {
        return $this->custom_attribute_value;
    }

    /**
     * Set imageUrl.
     *
     * @param array|null $imageUrl
     *
     * @return PointsmallItemRelAttributes
     */
    public function setImageUrl($imageUrl = null)
    {
        $this->image_url = $imageUrl;

        return $this;
    }

    /**
     * Get imageUrl.
     *
     * @return array|null
     */
    public function getImageUrl()
    {
        return $this->image_url;
    }
}
