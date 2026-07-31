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
 * SupplierItemsDraft 供应商商品待审草稿表
 *
 * @ORM\Table(name="supplier_items_draft", options={"comment"="供应商商品待审草稿表"}, indexes={
 *    @ORM\Index(name="ix_source_item_id", columns={"source_item_id"}),
 *    @ORM\Index(name="ix_goods_id", columns={"goods_id"}),
 *    @ORM\Index(name="ix_company_goods", columns={"company_id", "goods_id"}),
 * })
 * @ORM\Entity(repositoryClass="SupplierBundle\Repositories\SupplierItemsDraftRepository")
 */
class SupplierItemsDraft
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="draft_id", type="bigint", options={"comment":"待审草稿ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $draft_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="source_item_id", type="bigint", options={"comment":"主表商品SKU ID"})
     */
    private $source_item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", options={"comment":"SPU ID"})
     */
    private $goods_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="bigint", options={"comment":"供应商ID", "default": 0})
     */
    private $supplier_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_item_id", type="bigint", nullable=true, options={"comment":"默认SKU主表ID"})
     */
    private $default_item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_default", type="smallint", options={"comment":"是否默认SKU", "default": 0})
     */
    private $is_default = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="content_json", type="text", options={"comment":"待审商品内容JSON"})
     */
    private $content_json;

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

    public function getDraftId()
    {
        return $this->draft_id;
    }

    public function setSourceItemId($sourceItemId)
    {
        $this->source_item_id = $sourceItemId;
        return $this;
    }

    public function getSourceItemId()
    {
        return $this->source_item_id;
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

    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;
        return $this;
    }

    public function getCompanyId()
    {
        return $this->company_id;
    }

    public function setSupplierId($supplierId)
    {
        $this->supplier_id = $supplierId;
        return $this;
    }

    public function getSupplierId()
    {
        return $this->supplier_id;
    }

    public function setDefaultItemId($defaultItemId = null)
    {
        $this->default_item_id = $defaultItemId;
        return $this;
    }

    public function getDefaultItemId()
    {
        return $this->default_item_id;
    }

    public function setIsDefault($isDefault)
    {
        $this->is_default = $isDefault;
        return $this;
    }

    public function getIsDefault()
    {
        return $this->is_default;
    }

    public function setContentJson($contentJson)
    {
        $this->content_json = $contentJson;
        return $this;
    }

    public function getContentJson()
    {
        return $this->content_json;
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
}
