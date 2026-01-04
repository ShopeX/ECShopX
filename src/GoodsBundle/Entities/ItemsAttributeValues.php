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
 * @ORM\Table(name="items_attribute_values", options={"comment"="商品属性值表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_attribute_id", columns={"attribute_id"}),
 *    @ORM\Index(name="ix_attribuattribute_valuete_name", columns={"attribuattribute_valuete_name"}),
  * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsAttributeValuesRepository")
 */
class ItemsAttributeValues
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="attribute_value_id", type="bigint", options={"comment":"商品属性值id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $attribute_value_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="attribute_id", type="bigint", options={"comment":"商品属性ID"})
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
     * @ORM\Column(name="shop_id", type="bigint", options={"comment":"店铺ID，如果为0则表示总部"})
     */
    private $shop_id;

    /**
     * @var string
     *
     * @ORM\Column(name="attribuattribute_valuete_name", type="string", options={"comment":"商品属性值"})
     */
    private $attribute_value;

    /**
     * @var string
     *
     * @ORM\Column(name="sort", type="string", length=15, options={"comment":"商品属性排序，越大越在前"})
     */
    private $sort;

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
     * @var integer
     *
     * @ORM\Column(name="oms_value_id", nullable=true, type="bigint", options={"comment":"oms商品属性值id"})
     */
    protected $oms_value_id;
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
     * Set attributeId
     *
     * @param integer $attributeId
     *
     * @return ItemsAttributeValues
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ItemsAttributeValues
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
     * @return ItemsAttributeValues
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
     * Set attributeValue
     *
     * @param string $attributeValue
     *
     * @return ItemsAttributeValues
     */
    public function setAttributeValue($attributeValue)
    {
        $this->attribute_value = $attributeValue;

        return $this;
    }

    /**
     * Get attributeValue
     *
     * @return string
     */
    public function getAttributeValue()
    {
        return $this->attribute_value;
    }

    /**
     * Set sort
     *
     * @param string $sort
     *
     * @return ItemsAttributeValues
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set imageUrl
     *
     * @param string $imageUrl
     *
     * @return ItemsAttributeValues
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
     * @return ItemsAttributeValues
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
     * @return ItemsAttributeValues
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


    public function setOmsValueId($omsValueId)
    {
        $this->oms_value_id = $omsValueId;

        return $this;
    }

    /**
     * Get attributeId
     *
     * @return integer
     */
    public function getOmsValueId()
    {
        return $this->oms_value_id;
    }
}
