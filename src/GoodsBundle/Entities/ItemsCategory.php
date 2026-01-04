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
 * ItemsCategory 商品分类表
 *
 * @ORM\Table(name="items_category", options={"comment"="商品分类表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_distributor_id", columns={"distributor_id"}),
 *    @ORM\Index(name="ix_parent_id", columns={"parent_id"}),
 *    @ORM\Index(name="ix_is_main_category", columns={"is_main_category"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsCategoryRepository")
 */
class ItemsCategory
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="category_id", type="bigint", options={"comment":"商品分类id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="category_name", type="string", length=50, options={"comment":"分类名称"})
     */
    private $category_name;

    /**
     * @var string
     *
     * @ORM\Column(name="category_code", type="string", nullable=true, length=50, options={"comment":"分类编码"})
     */
    private $category_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="bigint", options={"comment":"父级id, 0为顶级", "default":"0"})
     */
    private $parent_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_level", nullable=true, type="integer", options={"comment":"商品分类等级", "default":1})
     */
    private $category_level;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_main_category", nullable=true, type="boolean", options={"comment":"是否为商品主类目", "default":false})
     */
    private $is_main_category;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", nullable=true, length=255, options={"comment":"路径", "default":"0"})
     */
    private $path;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", nullable=true, type="bigint", options={"comment":"店铺ID", "default":0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="commission_ratio", type="integer", options={"comment":"佣金比例", "default": 0})
     */
    private $commission_ratio = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", nullable=true, type="bigint", options={"comment":"排序", "default":"0"})
     */
    private $sort;

    /**
     * @var string
     *
     * @ORM\Column(name="goods_params", nullable=true, type="text", options={"comment":"商品参数"})
     */
    private $goods_params;

    /**
     * @var string
     *
     * @ORM\Column(name="goods_spec", nullable=true, type="text", options={"comment":"商品规格"})
     */
    private $goods_spec;

    /**
     * @var integer
     *
     * @ORM\Column(name="image_url", nullable=true, type="text", options={"comment":"分类图片链接"})
     */
    private $image_url;


    /**
     * @var string
     *
     * @ORM\Column(name="crossborder_tax_rate", type="string", length=10, nullable=true, options={"comment":"跨境税率，百分比，小数点2位"})
     */
    private $crossborder_tax_rate;

    /**
     * @var integer
     * @ORM\Column(name="invoice_tax_rate_id", type="bigint", nullable=true, options={"comment":"发票税率ID"})
     */
    private $invoice_tax_rate_id;

    /**
     * @var string
     * @ORM\Column(name="invoice_tax_rate", type="string", length=16, nullable=true, options={"comment":"发票税率"})
     */
    private $invoice_tax_rate;

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
     * @ORM\Column(name="customize_page_id", type="bigint", nullable=true, options={"comment":"自定义模板id", "default":"0"})
     */
    private $customize_page_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id_taobao", type="bigint", nullable=true, options={"comment":"淘宝分类id", "default":"0"})
     */
    private $category_id_taobao = 0;


    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id_taobao", nullable=true, type="bigint", options={"comment":"淘宝父级分类ID", "default":0})
     */
    private $parent_id_taobao = 0;

    /**
     * @var json_array
     *
     * @ORM\Column(name="taobao_category_info", nullable=true, type="json_array", options={"comment":"淘宝分类信息行"})
     */
    private $taobao_category_info;

    /**
     * Get categoryId
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ItemsCategory
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
     * Set categoryName
     *
     * @param string $categoryName
     *
     * @return ItemsCategory
     */
    public function setCategoryName($categoryName)
    {
        $this->category_name = $categoryName;

        return $this;
    }

    /**
     * Get categoryName
     *
     * @return string
     */
    public function getCategoryName()
    {
        return $this->category_name;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     *
     * @return ItemsCategory
     */
    public function setParentId($parentId)
    {
        $this->parent_id = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return ItemsCategory
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     *
     * @return ItemsCategory
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ItemsCategory
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
     * @return ItemsCategory
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
     * Set imageUrl
     *
     * @param string $imageUrl
     *
     * @return ItemsCategory
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
     * Set categoryLevel
     *
     * @param integer $categoryLevel
     *
     * @return ItemsCategory
     */
    public function setCategoryLevel($categoryLevel)
    {
        $this->category_level = $categoryLevel;

        return $this;
    }

    /**
     * Get categoryLevel
     *
     * @return integer
     */
    public function getCategoryLevel()
    {
        return $this->category_level;
    }

    /**
     * Set isMainCategory
     *
     * @param boolean $isMainCategory
     *
     * @return ItemsCategory
     */
    public function setIsMainCategory($isMainCategory)
    {
        $this->is_main_category = $isMainCategory;

        return $this;
    }

    /**
     * Get isMainCategory
     *
     * @return boolean
     */
    public function getIsMainCategory()
    {
        return $this->is_main_category;
    }

    /**
     * Set goodsParams
     *
     * @param string $goodsParams
     *
     * @return ItemsCategory
     */
    public function setGoodsParams($goodsParams)
    {
        $this->goods_params = $goodsParams;

        return $this;
    }

    /**
     * Get goodsParams
     *
     * @return string
     */
    public function getGoodsParams()
    {
        return $this->goods_params;
    }

    /**
     * Set goodsSpec
     *
     * @param string $goodsSpec
     *
     * @return ItemsCategory
     */
    public function setGoodsSpec($goodsSpec)
    {
        $this->goods_spec = $goodsSpec;

        return $this;
    }

    /**
     * Get goodsSpec
     *
     * @return string
     */
    public function getGoodsSpec()
    {
        return $this->goods_spec;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return ItemsCategory
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
     * Set crossborderTaxRate.
     *
     * @param string $crossborderTaxRate
     *
     * @return ItemsCategory
     */
    public function setCrossborderTaxRate($crossborderTaxRate)
    {
        $this->crossborder_tax_rate = $crossborderTaxRate;

        return $this;
    }

    /**
     * Get crossborderTaxRate.
     *
     * @return string
     */
    public function getCrossborderTaxRate()
    {
        return $this->crossborder_tax_rate;
    }

    public function getCategoryCode()
    {
        return $this->category_code;
    }
    public function setCategoryCode($categoryCode)
    {
        $this->category_code = $categoryCode;
        return $this;
    }

    /**
     * Set customizePageId.
     *
     * @param int|null $customizePageId
     *
     * @return ItemsCategory
     */
    public function setCustomizePageId($customizePageId = null)
    {
        $this->customize_page_id = $customizePageId;

        return $this;
    }

    /**
     * Get customizePageId.
     *
     * @return int|null
     */
    public function getCustomizePageId()
    {
        return $this->customize_page_id;
    }

    /**
     * Set commissionRatio.
     *
     * @param int $commissionRatio
     *
     * @return ItemsCategory
     */
    public function setCommissionRatio($commissionRatio)
    {
        $this->commission_ratio = $commissionRatio;

        return $this;
    }

    /**
     * Get commissionRatio.
     *
     * @return int
     */
    public function getCommissionRatio()
    {
        return $this->commission_ratio;
    }

    /**
     * Set categoryIdTaobao.
     *
     * @param int|null $categoryIdTaobao
     *
     * @return ItemsCategory
     */
    public function setCategoryIdTaobao($categoryIdTaobao = null)
    {
        $this->category_id_taobao = $categoryIdTaobao;

        return $this;
    }

    /**
     * Get categoryIdTaobao.
     *
     * @return int|null
     */
    public function getCategoryIdTaobao()
    {
        return $this->category_id_taobao;
    }

    /**
     * Set parentIdTaobao.
     *
     * @param int|null $parentIdTaobao
     *
     * @return ItemsCategory
     */
    public function setParentIdTaobao($parentIdTaobao = null)
    {
        $this->parent_id_taobao = $parentIdTaobao;

        return $this;
    }

    /**
     * Get parentIdTaobao.
     *
     * @return int|null
     */
    public function getParentIdTaobao()
    {
        return $this->parent_id_taobao;
    }

    /**
     * Set taobaoCategoryInfo.
     *
     * @param array|null $taobaoCategoryInfo
     *
     * @return ItemsCategory
     */
    public function setTaobaoCategoryInfo($taobaoCategoryInfo = null)
    {
        $this->taobao_category_info = $taobaoCategoryInfo;

        return $this;
    }

    /**
     * Get taobaoCategoryInfo.
     *
     * @return array|null
     */
    public function getTaobaoCategoryInfo()
    {
        return $this->taobao_category_info;
    }
    public function setInvoiceTaxRateId($id) { $this->invoice_tax_rate_id = $id; return $this; }
    public function getInvoiceTaxRateId() { return $this->invoice_tax_rate_id; }
    public function setInvoiceTaxRate($rate) { $this->invoice_tax_rate = $rate; return $this; }
    public function getInvoiceTaxRate() { return $this->invoice_tax_rate; }
}
