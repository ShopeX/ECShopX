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

/**
 * SupplierItemsAttrDraft 供应商商品待审属性草稿表
 *
 * @ORM\Table(name="supplier_items_attr_draft", options={"comment"="供应商商品待审属性草稿表"}, indexes={
 *    @ORM\Index(name="ix_item_id", columns={"item_id"}),
 *    @ORM\Index(name="ix_goods_id", columns={"goods_id"}),
 * })
 * @ORM\Entity(repositoryClass="SupplierBundle\Repositories\SupplierItemsAttrDraftRepository")
 */
class SupplierItemsAttrDraft
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
     * @ORM\Column(name="goods_id", type="bigint", options={"comment":"SPU ID"})
     */
    private $goods_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"主表商品SKU ID"})
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
     *
     * @ORM\Column(name="attribute_type", type="string", length=15, options={"comment":"商品属性类型"})
     */
    private $attribute_type;

    /**
     * @var string
     *
     * @ORM\Column(name="attr_data", type="text", nullable=true, options={"comment":"属性值", "default":""})
     */
    private $attr_data = '';

    /**
     * @var integer
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var integer
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

    public function getId()
    {
        return $this->id;
    }

    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;
        return $this;
    }

    public function getCompanyId()
    {
        return $this->company_id;
    }

    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;
        return $this;
    }

    public function getGoodsId()
    {
        return $this->goods_id;
    }

    public function setItemId($itemId)
    {
        $this->item_id = $itemId;
        return $this;
    }

    public function getItemId()
    {
        return $this->item_id;
    }

    public function setAttributeId($attributeId)
    {
        $this->attribute_id = $attributeId;
        return $this;
    }

    public function getAttributeId()
    {
        return $this->attribute_id;
    }

    public function setAttributeType($attributeType)
    {
        $this->attribute_type = $attributeType;
        return $this;
    }

    public function getAttributeType()
    {
        return $this->attribute_type;
    }

    public function setAttrData($attrData = null)
    {
        $this->attr_data = $attrData;
        return $this;
    }

    public function getAttrData()
    {
        return $this->attr_data;
    }

    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setUpdated($updated = null)
    {
        $this->updated = $updated;
        return $this;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setIsDel($isDel)
    {
        $this->is_del = $isDel;
        return $this;
    }

    public function getIsDel()
    {
        return $this->is_del;
    }
}
