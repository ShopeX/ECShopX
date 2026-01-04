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

namespace SupplierBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Dingo\Api\Exception\ResourceException;

/**
 * SupplierItemsAttr 新供应商商品属性表
 *
 * @ORM\Table(name="supplier_items_attr", options={"comment"="新供应商商品属性表"}, indexes={
 *    @ORM\Index(name="ix_item_id", columns={"item_id"}),
 * })
 * @ORM\Entity(repositoryClass="SupplierBundle\Repositories\SupplierItemsAttrRepository")
 */
class SupplierItemsAttr
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
     * @ORM\Column(name="attribute_id", type="bigint", options={"comment":"商品属性id", "default": 0})
     */
    private $attribute_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_del", type="bigint", options={"comment":"是否需要删除", "default": 0})
     */
    private $is_del = 0;

    /**
     * @var string
     * unit 单位，
     * brand 品牌，
     * item_params 商品参数, 
     * category 商品销售分类, 
     * item_spec 规格
     * @ORM\Column(name="attribute_type", type="string", length=15, options={"comment":"商品属性类型 unit 单位，brand 品牌，item_params 商品参数, item_spec 规格, category 商品销售分类"})
     */
    private $attribute_type;

    /**
     * @var string
     *
     * @ORM\Column(name="attr_data", type="text", nullable=true, options={"comment":"属性值", "default":""})
     */
    private $attr_data = '';

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
     * @return SupplierItemsAttr
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
     * @return SupplierItemsAttr
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
     * Set attributeId.
     *
     * @param int $attributeId
     *
     * @return SupplierItemsAttr
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
     * @return SupplierItemsAttr
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
     * Set attrData.
     *
     * @param string|null $attrData
     *
     * @return SupplierItemsAttr
     */
    public function setAttrData($attrData = null)
    {
        $this->attr_data = $attrData;

        return $this;
    }

    /**
     * Get attrData.
     *
     * @return string|null
     */
    public function getAttrData()
    {
        return $this->attr_data;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SupplierItemsAttr
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return SupplierItemsAttr
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set isDel.
     *
     * @param int $isDel
     *
     * @return SupplierItemsAttr
     */
    public function setIsDel($isDel)
    {
        $this->is_del = $isDel;

        return $this;
    }

    /**
     * Get isDel.
     *
     * @return int
     */
    public function getIsDel()
    {
        return $this->is_del;
    }
}
