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
use Gedmo\Mapping\Annotation as Gedmo;
use GoodsBundle\Entities\SupplierItems;

/**
 * PointsmallItems 积分商品表
 *
 * @ORM\Table(name="pointsmall_items", options={"comment"="积分商品表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_default_item_id", columns={"default_item_id"}),
 *    @ORM\Index(name="ix_item_category", columns={"item_category"}),
 *    @ORM\Index(name="ix_is_default", columns={"is_default"}),
 *    @ORM\Index(name="ix_goods_id", columns={"goods_id"}),
 *    @ORM\Index(name="ix_default_item_list", columns={"company_id","default_item_id","approve_status","item_type","item_category"}),
 * })
 * @ORM\Entity(repositoryClass="PointsmallBundle\Repositories\PointsmallItemsRepository")
 */
class PointsmallItems
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_type", type="string", length=15, options={"comment":"商品类型，services：服务商品，normal: 普通商品", "default": "services"})
     */
    private $item_type;

    /**
     * @var string
     *
     * @ORM\Column(name="item_category", nullable=true, type="string", length=15, options={"comment":"商品主类目", "default": "null"})
     */
    private $item_category;

    /**
     * @var string
     *
     * @ORM\Column(name="consume_type", type="string", length=15, options={"comment":"核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)", "default":"every"})
     */
    private $consume_type;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", length=255, options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="item_bn", type="string", length=255, options={"comment":"商品编号"})
     */
    private $item_bn;

    /**
     * @var string
     *
     * @ORM\Column(name="barcode", type="string", length=255, options={"comment":"商品条形码"})
     */
    private $barcode;

    /**
     * @var string
     *
     * @ORM\Column(name="brief", type="string", length=255, options={"comment":"简洁的描述"})
     */
    private $brief;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="integer", options={"comment":"价格,单位为‘分’"})
     */
    private $price;

    /**
     * @var integer
     *
     * @ORM\Column(name="cost_price", type="integer", nullable=true, options={"comment":"价格,单位为‘分’", "default": 0})
     */
    private $cost_price = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_unit", type="string", nullable=true, options={"comment":"商品计量单位"})
     */
    private $item_unit;

    /**
     * @var integer
     *
     * @ORM\Column(name="special_type", type="string", nullable=true, options={"comment":"商品特殊类型 drug 处方药 normal 普通商品", "default":"normal"})
     */
    private $special_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_address_province", nullable=true, type="string", options={"comment":"产地省"})
     */
    private $item_address_province;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_address_city", nullable=true, type="string", options={"comment":"产地市"})
     */
    private $item_address_city;

    /**
     * @var integer
     *
     * @ORM\Column(name="regions_id", nullable=true, type="string", options={"comment":"产地地区id"})
     */
    private $regions_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="store", nullable=true, type="integer", options={"comment":"库存"})
     */
    private $store;

    /**
     * @var integer
     *
     * @ORM\Column(name="sales", nullable=true, type="integer", options={"comment":"销量", "default":"0"})
     */
    private $sales;

    /**
     * @var string
     *
     * @ORM\Column(name="approve_status", type="string", options={"comment":"商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"})
     */
    private $approve_status = "onsale";

    /**
     * @var string
     *
     * @ORM\Column(name="audit_status", nullable=true, type="string", options={"comment":"审核状态 approved成功 processing审核中 rejected审核拒绝", "default":"approved"})
     */
    private $audit_status = "approved";

    /**
     * @var string
     *
     * @ORM\Column(name="audit_reason", nullable=true, type="text", options={"comment":"审核拒绝原因"})
     */
    private $audit_reason;

    /**
     * @var integer
     *
     * @ORM\Column(name="market_price", type="integer", options={"comment":"原价,单位为‘分’"})
     */
    private $market_price;

    /**
     * @var string
     *
     * @ORM\Column(name="goods_function", nullable=true, type="string", length=255, options={"comment":"商品功能"})
     */
    private $goods_function;

    /**
     * @var string
     *
     * @ORM\Column(name="goods_series", nullable=true, type="string", length=255, options={"comment":"商品系列"})
     */
    private $goods_series;

    /**
     * @var string
     *
     * @ORM\Column(name="goods_color", nullable=true, type="string", length=255, options={"comment":"商品颜色"})
     */
    private $goods_color;

    /**
     * @var string
     *
     * @ORM\Column(name="goods_brand", nullable=true, type="string", length=255, options={"comment":"商品品牌"})
     */
    private $goods_brand;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_default", nullable=true, type="boolean", options={"comment":"商品是否为默认商品", "default": true})
     */
    private $is_default;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_item_id", type="bigint", nullable=true, options={"comment":"默认商品ID", "default":0})
     */
    private $default_item_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", nullable=true, options={"comment":"产品ID", "default":0})
     */
    private $goods_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="nospec", nullable=true, type="string", options={"comment":"商品是否为单规格", "default": "true"})
     */
    private $nospec;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", nullable=true, type="float", precision=15, scale=4, options={"comment":"商品重量", "default": 0})
     */
    private $weight;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", options={"comment":"商品排序", "default": 0})
     */
    private $sort;

    /**
     * @var integer
     *
     * @ORM\Column(name="templates_id", nullable=true, type="integer", options={"comment":"运费模板id"})
     */
    private $templates_id;

    /**
     * @var string
     *
     * @ORM\Column(name="pics", type="json_array", options={"comment":"图片"})
     */
    private $pics;

    /**
     * @var string
     *
     * @ORM\Column(name="video_type", type="string", options={"comment":"视频类型 local:本地视频 tencent:腾讯视频", "default": "local"})
     */
    private $video_type;

    /**
     * @var string
     *
     * @ORM\Column(name="videos", type="text", nullable=true, options={"comment":"视频"})
     */
    private $videos;

    /**
     * @var string
     *
     * @ORM\Column(name="video_pic_url", type="text", nullable=true, options={"comment":"视频封面图"})
     */
    private $video_pic_url;

    /**
     * @var string
     *
     * @ORM\Column(name="intro", type="text", nullable=true, options={"comment":"图文详情"})
     */
    private $intro;

    /**
     * @var string
     *
     * @ORM\Column(name="purchase_agreement", type="text", nullable=true, options={"comment":"购买协议"})
     */
    private $purchase_agreement;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_show_specimg", type="boolean", options={"comment":"详情页是否显示规格图片", "default":false})
     */
    private $is_show_specimg;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enable_agreement", type="boolean", options={"comment":"开启购买协议", "default":false})
     */
    private $enable_agreement;

    /**
     * @var string
     *
     * @ORM\Column(name="date_type", nullable=true, type="string", options={"comment":"有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"})
     */
    private $date_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="begin_date", nullable=true, type="integer", options={"comment":"有效期开始时间"})
     */
    private $begin_date;

    /**
     * @var datetime
     *
     * @ORM\Column(name="end_date", nullable=true, type="integer", options={"comment":"有效期结束时间"})
     */
    private $end_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="fixed_term", nullable=true, type="integer", options={"comment":"有效期的有效天数"})
     */
    private $fixed_term;

    /**
     * @var string
     *
     * @ORM\Column(name="brand_logo", nullable=true, type="string", length=1024, options={"comment":"品牌图片"})
     */
    private $brand_logo;

    /**
     * @var integer
     *
     * @ORM\Column(name="point", nullable=true, type="integer", options={"comment":"积分兑换价格", "default": 0})
     */
    private $point;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_class", nullable=true, type="string", options={"comment":"支付方式 point积分支付 online在线支付 mix混合支付", "default":"point"})
     */
    private $pay_class = "point";

    /**
     * @var integer
     *
     * @ORM\Column(name="volume", type="float", precision=15, scale=4, nullable=true, options={"comment":"商品体积"})
     */
    private $volume;

    /**
     * @var integer
     *
     * @ORM\Column(name="brand_id", nullable=true, type="integer", options={"comment":"品牌id", "default": 0})
     */
    private $brand_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="tax_rate", type="integer", options={"comment":"税率, 百分之～/100"})
     */
    private $tax_rate;

    /**
     * @var string
     *
     * @ORM\Column(name="crossborder_tax_rate", type="string", length=10, nullable=false, options={"comment":"跨境税率，百分比，小数点2位"})
     */
    private $crossborder_tax_rate;

    /**
     * @var integer
     *
     * @ORM\Column(name="origincountry_id", type="bigint", options={"comment":"产地国id", "default": 0})
     */
    private $origincountry_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", length=4, options={"comment":"商品类型，0普通，1跨境商品，可扩展", "default": 0})
     */
    private $type = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_medicine", type="smallint", options={"comment":"是否为药品，0否 1是", "default": 0})
     */
    private $is_medicine = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_prescription", type="smallint", options={"comment":"是否为处方药，0否 1是", "default": 0})
     */
    private $is_prescription = 0;

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
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set itemType.
     *
     * @param string $itemType
     *
     * @return PointsmallItems
     */
    public function setItemType($itemType)
    {
        // Debug: 1e2364
        $this->item_type = $itemType;

        return $this;
    }

    /**
     * Get itemType.
     *
     * @return string
     */
    public function getItemType()
    {
        // Debug: 1e2364
        return $this->item_type;
    }

    /**
     * Set itemCategory.
     *
     * @param string|null $itemCategory
     *
     * @return PointsmallItems
     */
    public function setItemCategory($itemCategory = null)
    {
        $this->item_category = $itemCategory;

        return $this;
    }

    /**
     * Get itemCategory.
     *
     * @return string|null
     */
    public function getItemCategory()
    {
        return $this->item_category;
    }

    /**
     * Set consumeType.
     *
     * @param string $consumeType
     *
     * @return PointsmallItems
     */
    public function setConsumeType($consumeType)
    {
        $this->consume_type = $consumeType;

        return $this;
    }

    /**
     * Get consumeType.
     *
     * @return string
     */
    public function getConsumeType()
    {
        return $this->consume_type;
    }

    /**
     * Set itemName.
     *
     * @param string $itemName
     *
     * @return PointsmallItems
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
     * Set itemBn.
     *
     * @param string $itemBn
     *
     * @return PointsmallItems
     */
    public function setItemBn($itemBn)
    {
        $this->item_bn = $itemBn;

        return $this;
    }

    /**
     * Get itemBn.
     *
     * @return string
     */
    public function getItemBn()
    {
        return $this->item_bn;
    }

    /**
     * Set barcode.
     *
     * @param string $barcode
     *
     * @return PointsmallItems
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;

        return $this;
    }

    /**
     * Get barcode.
     *
     * @return string
     */
    public function getBarcode()
    {
        return $this->barcode;
    }

    /**
     * Set brief.
     *
     * @param string $brief
     *
     * @return PointsmallItems
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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return PointsmallItems
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
     * Set price.
     *
     * @param int $price
     *
     * @return PointsmallItems
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
     * Set costPrice.
     *
     * @param int|null $costPrice
     *
     * @return PointsmallItems
     */
    public function setCostPrice($costPrice = null)
    {
        $this->cost_price = $costPrice;

        return $this;
    }

    /**
     * Get costPrice.
     *
     * @return int|null
     */
    public function getCostPrice()
    {
        return $this->cost_price;
    }

    /**
     * Set itemUnit.
     *
     * @param string|null $itemUnit
     *
     * @return PointsmallItems
     */
    public function setItemUnit($itemUnit = null)
    {
        $this->item_unit = $itemUnit;

        return $this;
    }

    /**
     * Get itemUnit.
     *
     * @return string|null
     */
    public function getItemUnit()
    {
        return $this->item_unit;
    }

    /**
     * Set specialType.
     *
     * @param string|null $specialType
     *
     * @return PointsmallItems
     */
    public function setSpecialType($specialType = null)
    {
        $this->special_type = $specialType;

        return $this;
    }

    /**
     * Get specialType.
     *
     * @return string|null
     */
    public function getSpecialType()
    {
        return $this->special_type;
    }

    /**
     * Set itemAddressProvince.
     *
     * @param string|null $itemAddressProvince
     *
     * @return PointsmallItems
     */
    public function setItemAddressProvince($itemAddressProvince = null)
    {
        $this->item_address_province = $itemAddressProvince;

        return $this;
    }

    /**
     * Get itemAddressProvince.
     *
     * @return string|null
     */
    public function getItemAddressProvince()
    {
        return $this->item_address_province;
    }

    /**
     * Set itemAddressCity.
     *
     * @param string|null $itemAddressCity
     *
     * @return PointsmallItems
     */
    public function setItemAddressCity($itemAddressCity = null)
    {
        $this->item_address_city = $itemAddressCity;

        return $this;
    }

    /**
     * Get itemAddressCity.
     *
     * @return string|null
     */
    public function getItemAddressCity()
    {
        return $this->item_address_city;
    }

    /**
     * Set regionsId.
     *
     * @param string|null $regionsId
     *
     * @return PointsmallItems
     */
    public function setRegionsId($regionsId = null)
    {
        $this->regions_id = $regionsId;

        return $this;
    }

    /**
     * Get regionsId.
     *
     * @return string|null
     */
    public function getRegionsId()
    {
        return $this->regions_id;
    }

    /**
     * Set store.
     *
     * @param int|null $store
     *
     * @return PointsmallItems
     */
    public function setStore($store = null)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Get store.
     *
     * @return int|null
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Set sales.
     *
     * @param int|null $sales
     *
     * @return PointsmallItems
     */
    public function setSales($sales = null)
    {
        $this->sales = $sales;

        return $this;
    }

    /**
     * Get sales.
     *
     * @return int|null
     */
    public function getSales()
    {
        return $this->sales;
    }

    /**
     * Set approveStatus.
     *
     * @param string $approveStatus
     *
     * @return PointsmallItems
     */
    public function setApproveStatus($approveStatus)
    {
        $this->approve_status = $approveStatus;

        return $this;
    }

    /**
     * Get approveStatus.
     *
     * @return string
     */
    public function getApproveStatus()
    {
        return $this->approve_status;
    }

    /**
     * Set auditStatus.
     *
     * @param string|null $auditStatus
     *
     * @return PointsmallItems
     */
    public function setAuditStatus($auditStatus = null)
    {
        $this->audit_status = $auditStatus;

        return $this;
    }

    /**
     * Get auditStatus.
     *
     * @return string|null
     */
    public function getAuditStatus()
    {
        return $this->audit_status;
    }

    /**
     * Set auditReason.
     *
     * @param string|null $auditReason
     *
     * @return PointsmallItems
     */
    public function setAuditReason($auditReason = null)
    {
        $this->audit_reason = $auditReason;

        return $this;
    }

    /**
     * Get auditReason.
     *
     * @return string|null
     */
    public function getAuditReason()
    {
        return $this->audit_reason;
    }

    /**
     * Set marketPrice.
     *
     * @param int $marketPrice
     *
     * @return PointsmallItems
     */
    public function setMarketPrice($marketPrice)
    {
        $this->market_price = $marketPrice;

        return $this;
    }

    /**
     * Get marketPrice.
     *
     * @return int
     */
    public function getMarketPrice()
    {
        return $this->market_price;
    }

    /**
     * Set goodsFunction.
     *
     * @param string|null $goodsFunction
     *
     * @return PointsmallItems
     */
    public function setGoodsFunction($goodsFunction = null)
    {
        $this->goods_function = $goodsFunction;

        return $this;
    }

    /**
     * Get goodsFunction.
     *
     * @return string|null
     */
    public function getGoodsFunction()
    {
        return $this->goods_function;
    }

    /**
     * Set goodsSeries.
     *
     * @param string|null $goodsSeries
     *
     * @return PointsmallItems
     */
    public function setGoodsSeries($goodsSeries = null)
    {
        $this->goods_series = $goodsSeries;

        return $this;
    }

    /**
     * Get goodsSeries.
     *
     * @return string|null
     */
    public function getGoodsSeries()
    {
        return $this->goods_series;
    }

    /**
     * Set goodsColor.
     *
     * @param string|null $goodsColor
     *
     * @return PointsmallItems
     */
    public function setGoodsColor($goodsColor = null)
    {
        $this->goods_color = $goodsColor;

        return $this;
    }

    /**
     * Get goodsColor.
     *
     * @return string|null
     */
    public function getGoodsColor()
    {
        return $this->goods_color;
    }

    /**
     * Set goodsBrand.
     *
     * @param string|null $goodsBrand
     *
     * @return PointsmallItems
     */
    public function setGoodsBrand($goodsBrand = null)
    {
        $this->goods_brand = $goodsBrand;

        return $this;
    }

    /**
     * Get goodsBrand.
     *
     * @return string|null
     */
    public function getGoodsBrand()
    {
        return $this->goods_brand;
    }

    /**
     * Set isDefault.
     *
     * @param bool|null $isDefault
     *
     * @return PointsmallItems
     */
    public function setIsDefault($isDefault = null)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get isDefault.
     *
     * @return bool|null
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * Set defaultItemId.
     *
     * @param int|null $defaultItemId
     *
     * @return PointsmallItems
     */
    public function setDefaultItemId($defaultItemId = null)
    {
        $this->default_item_id = $defaultItemId;

        return $this;
    }

    /**
     * Get defaultItemId.
     *
     * @return int|null
     */
    public function getDefaultItemId()
    {
        return $this->default_item_id;
    }

    /**
     * Set goodsId.
     *
     * @param int|null $goodsId
     *
     * @return PointsmallItems
     */
    public function setGoodsId($goodsId = null)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId.
     *
     * @return int|null
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * Set nospec.
     *
     * @param string|null $nospec
     *
     * @return PointsmallItems
     */
    public function setNospec($nospec = null)
    {
        $this->nospec = $nospec;

        return $this;
    }

    /**
     * Get nospec.
     *
     * @return string|null
     */
    public function getNospec()
    {
        return $this->nospec;
    }

    /**
     * Set weight.
     *
     * @param float|null $weight
     *
     * @return PointsmallItems
     */
    public function setWeight($weight = null)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight.
     *
     * @return float|null
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set sort.
     *
     * @param int $sort
     *
     * @return PointsmallItems
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
     * Set templatesId.
     *
     * @param int|null $templatesId
     *
     * @return PointsmallItems
     */
    public function setTemplatesId($templatesId = null)
    {
        $this->templates_id = $templatesId;

        return $this;
    }

    /**
     * Get templatesId.
     *
     * @return int|null
     */
    public function getTemplatesId()
    {
        return $this->templates_id;
    }

    /**
     * Set pics.
     *
     * @param array $pics
     *
     * @return PointsmallItems
     */
    public function setPics($pics)
    {
        $this->pics = $pics;

        return $this;
    }

    /**
     * Get pics.
     *
     * @return array
     */
    public function getPics()
    {
        return $this->pics;
    }

    /**
     * Set videoType.
     *
     * @param string $videoType
     *
     * @return PointsmallItems
     */
    public function setVideoType($videoType)
    {
        $this->video_type = $videoType;

        return $this;
    }

    /**
     * Get videoType.
     *
     * @return string
     */
    public function getVideoType()
    {
        return $this->video_type;
    }

    /**
     * Set videos.
     *
     * @param string|null $videos
     *
     * @return PointsmallItems
     */
    public function setVideos($videos = null)
    {
        $this->videos = $videos;

        return $this;
    }

    /**
     * Get videos.
     *
     * @return string|null
     */
    public function getVideos()
    {
        return $this->videos;
    }

    /**
     * Set videoPicUrl.
     *
     * @param string|null $videoPicUrl
     *
     * @return PointsmallItems
     */
    public function setVideoPicUrl($videoPicUrl = null)
    {
        $this->video_pic_url = $videoPicUrl;

        return $this;
    }

    /**
     * Get videoPicUrl.
     *
     * @return string|null
     */
    public function getVideoPicUrl()
    {
        return $this->video_pic_url;
    }

    /**
     * Set intro.
     *
     * @param string|null $intro
     *
     * @return PointsmallItems
     */
    public function setIntro($intro = null)
    {
        $this->intro = $intro;

        return $this;
    }

    /**
     * Get intro.
     *
     * @return string|null
     */
    public function getIntro()
    {
        return $this->intro;
    }

    /**
     * Set purchaseAgreement.
     *
     * @param string|null $purchaseAgreement
     *
     * @return PointsmallItems
     */
    public function setPurchaseAgreement($purchaseAgreement = null)
    {
        $this->purchase_agreement = $purchaseAgreement;

        return $this;
    }

    /**
     * Get purchaseAgreement.
     *
     * @return string|null
     */
    public function getPurchaseAgreement()
    {
        return $this->purchase_agreement;
    }

    /**
     * Set isShowSpecimg.
     *
     * @param bool $isShowSpecimg
     *
     * @return PointsmallItems
     */
    public function setIsShowSpecimg($isShowSpecimg)
    {
        $this->is_show_specimg = $isShowSpecimg;

        return $this;
    }

    /**
     * Get isShowSpecimg.
     *
     * @return bool
     */
    public function getIsShowSpecimg()
    {
        return $this->is_show_specimg;
    }

    /**
     * Set enableAgreement.
     *
     * @param bool $enableAgreement
     *
     * @return PointsmallItems
     */
    public function setEnableAgreement($enableAgreement)
    {
        $this->enable_agreement = $enableAgreement;

        return $this;
    }

    /**
     * Get enableAgreement.
     *
     * @return bool
     */
    public function getEnableAgreement()
    {
        return $this->enable_agreement;
    }

    /**
     * Set dateType.
     *
     * @param string|null $dateType
     *
     * @return PointsmallItems
     */
    public function setDateType($dateType = null)
    {
        $this->date_type = $dateType;

        return $this;
    }

    /**
     * Get dateType.
     *
     * @return string|null
     */
    public function getDateType()
    {
        return $this->date_type;
    }

    /**
     * Set beginDate.
     *
     * @param int|null $beginDate
     *
     * @return PointsmallItems
     */
    public function setBeginDate($beginDate = null)
    {
        $this->begin_date = $beginDate;

        return $this;
    }

    /**
     * Get beginDate.
     *
     * @return int|null
     */
    public function getBeginDate()
    {
        return $this->begin_date;
    }

    /**
     * Set endDate.
     *
     * @param int|null $endDate
     *
     * @return PointsmallItems
     */
    public function setEndDate($endDate = null)
    {
        $this->end_date = $endDate;

        return $this;
    }

    /**
     * Get endDate.
     *
     * @return int|null
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * Set fixedTerm.
     *
     * @param int|null $fixedTerm
     *
     * @return PointsmallItems
     */
    public function setFixedTerm($fixedTerm = null)
    {
        $this->fixed_term = $fixedTerm;

        return $this;
    }

    /**
     * Get fixedTerm.
     *
     * @return int|null
     */
    public function getFixedTerm()
    {
        return $this->fixed_term;
    }

    /**
     * Set brandLogo.
     *
     * @param string|null $brandLogo
     *
     * @return PointsmallItems
     */
    public function setBrandLogo($brandLogo = null)
    {
        $this->brand_logo = $brandLogo;

        return $this;
    }

    /**
     * Get brandLogo.
     *
     * @return string|null
     */
    public function getBrandLogo()
    {
        return $this->brand_logo;
    }

    /**
     * Set point.
     *
     * @param int|null $point
     *
     * @return PointsmallItems
     */
    public function setPoint($point = null)
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Get point.
     *
     * @return int|null
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * get PayClass
     *
     * @return string
     */
    public function getPayClass()
    {
        return $this->pay_class;
    }

    /**
     * set PayClass
     *
     * @param string $pay_class
     *
     * @return PointsmallItems
     */
    public function setPayClass($pay_class)
    {
        $this->pay_class = $pay_class;
        return $this;
    }

    /**
     * Set volume.
     *
     * @param float|null $volume
     *
     * @return PointsmallItems
     */
    public function setVolume($volume = null)
    {
        $this->volume = $volume;

        return $this;
    }

    /**
     * Get volume.
     *
     * @return float|null
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * Set brandId.
     *
     * @param int|null $brandId
     *
     * @return PointsmallItems
     */
    public function setBrandId($brandId = null)
    {
        $this->brand_id = $brandId;

        return $this;
    }

    /**
     * Get brandId.
     *
     * @return int|null
     */
    public function getBrandId()
    {
        return $this->brand_id;
    }

    /**
     * Set taxRate.
     *
     * @param int $taxRate
     *
     * @return PointsmallItems
     */
    public function setTaxRate($taxRate)
    {
        $this->tax_rate = $taxRate;

        return $this;
    }

    /**
     * Get taxRate.
     *
     * @return int
     */
    public function getTaxRate()
    {
        return $this->tax_rate;
    }

    /**
     * Set crossborderTaxRate.
     *
     * @param string $crossborderTaxRate
     *
     * @return PointsmallItems
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

    /**
     * Set origincountryId.
     *
     * @param int $origincountryId
     *
     * @return PointsmallItems
     */
    public function setOrigincountryId($origincountryId)
    {
        $this->origincountry_id = $origincountryId;

        return $this;
    }

    /**
     * Get origincountryId.
     *
     * @return int
     */
    public function getOrigincountryId()
    {
        return $this->origincountry_id;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return PointsmallItems
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return PointsmallItems
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
     * @return PointsmallItems
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
     * Set isMedicine.
     *
     * @param int|null $isMedicine
     *
     * @return PointsmallItems
     */
    public function setIsMedicine($isMedicine = 0)
    {
        $this->is_medicine = $isMedicine;

        return $this;
    }

    /**
     * Get isMedicine.
     *
     * @return int|null
     */
    public function getIsMedicine()
    {
        return $this->is_medicine;
    }

    /**
     * Set isPrescription.
     *
     * @param int|null $isPrescription
     *
     * @return PointsmallItems
     */
    public function setIsPrescription($isPrescription = 0)
    {
        $this->is_prescription = $isPrescription;

        return $this;
    }

    /**
     * Get isPrescription.
     *
     * @return int|null
     */
    public function getIsPrescription()
    {
        return $this->is_prescription;
    }
}
