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
 * SupplierItems 供应商商品表
 *
 * @ORM\Table(name="supplier_items", options={"comment"="商品表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_default_item_id", columns={"default_item_id"}),
 *    @ORM\Index(name="ix_item_category", columns={"item_category"}),
 *    @ORM\Index(name="ix_item_bn", columns={"item_bn"}),
 *    @ORM\Index(name="ix_goods_id", columns={"goods_id"}),
 *    @ORM\Index(name="ix_supplier_id", columns={"supplier_id"}),
 *    @ORM\Index(name="ix_is_default", columns={"is_default"}),
 *    @ORM\Index(name="ix_is_medicine", columns={"is_medicine"}),
 *    @ORM\Index(name="ix_is_prescription", columns={"is_prescription"}),
 *    @ORM\Index(name="ix_default_item_list", columns={"company_id","default_item_id","approve_status","item_type","item_category"}),
 * })
 * @ORM\Entity(repositoryClass="SupplierBundle\Repositories\SupplierItemsRepository")
 */
class SupplierItems
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
     * @ORM\Column(name="barcode", type="text", options={"comment":"商品条形码"})
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
     * @ORM\Column(name="price", type="integer", options={"comment":"销售价,单位为‘分’"})
     */
    private $price;

    /**
     * @var integer
     *
     * @ORM\Column(name="cost_price", type="integer", nullable=true, options={"comment":"成本价,单位为‘分’", "default": 0})
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
     * @var string
     *
     * @ORM\Column(name="regions", nullable=true, type="text", options={"comment":"产地地区"})
     */
    private $regions;

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
     * @var integer
     *
     * @ORM\Column(name="rebate_conf", nullable=true, type="json_array", options={"comment":"分销配置"})
     */
    private $rebate_conf;

    /**
     * @var integer
     *
     * @ORM\Column(name="rebate", nullable=true, type="integer", options={"comment":"推广商品 1已选择 0未选择 2申请加入 3拒绝"})
     */
    private $rebate;

    /**
     * @var integer
     *
     * @ORM\Column(name="rebate_type", nullable=true, type="string", options={"comment":"分佣计算方式", "default":"default"})
     */
    private $rebate_type;

    /**
     * @var string
     *
     * @ORM\Column(name="approve_status", type="string", options={"comment":"商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售, only_show 前台仅展示"})
     */
    private $approve_status = "onsale";

    /**
     * @var string
     *
     * submiting 待提交
     * approved 成功
     * processing 审核中
     * rejected 审核拒绝
     *
     * @ORM\Column(name="audit_status", nullable=true, type="string", options={"comment":"审核状态 submiting待提交 approved成功 processing审核中 rejected审核拒绝", "default":"approved"})
     */
    private $audit_status = "processing";

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
     * @ORM\Column(name="is_epidemic", nullable=true, type="integer", options={"comment":"是否为疫情需要登记的商品  1:是 0:否", "default": 0})
     */
    private $is_epidemic = 0;

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
     * @ORM\Column(name="pics_create_qrcode", nullable=true, type="json_array", options={"comment":"图片是否生成小程序码"})
     */
    private $pics_create_qrcode;

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
     * @var boolean
     *
     * @ORM\Column(name="is_point", nullable=true, type="boolean", options={"comment":"是否积分兑换 true可以 false不可以", "default": false})
     */
    private $is_point;

    /**
     * @var integer
     *
     * @ORM\Column(name="point", nullable=true, type="integer", options={"comment":"积分个数", "default": 0})
     */
    private $point;

    /**
     * @var integer
     *  为0时表示该商品为商城商品，否则为店铺自有商品
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"店铺id,为0时表示该商品为商城商品，否则为店铺自有商品", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="volume", type="float", precision=15, scale=4, nullable=true, options={"comment":"商品体积"})
     */
    private $volume;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_source", type="string", options={"comment":"商品来源:mall:主商城;distributor:店铺自有;openapi:开放接口;", "default": "mall"})
     */
    private $item_source = 'mall';

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
     * @ORM\Column(name="profit_type", nullable=true, type="integer", options={"comment":"分润类型, 默认为0配置分润,1主类目分润,2商品指定分润(比例),3商品指定分润(金额)", "default": 0})
     */
    private $profit_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="origincountry_id", type="bigint", options={"comment":"产地国id", "default": 0})
     */
    private $origincountry_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="taxstrategy_id", type="bigint", options={"comment":"税费策略id", "default": 0})
     */
    private $taxstrategy_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="taxation_num", type="integer", options={"comment":"计税单位份数", "default": 0})
     */
    private $taxation_num = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="profit_fee", nullable=true, type="integer", options={"comment":"分润金额,单位为分 冗余字段", "default": 0})
     */
    private $profit_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", length=4, options={"comment":"商品类型，0普通，1跨境商品，可扩展", "default": 0})
     */
    private $type = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_profit", type="boolean", nullable=true, options={"comment":"是否支持分润", "default": false})
     */
    private $is_profit = false;

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
     * @ORM\Column(name="is_gift", type="boolean", nullable=true, options={"comment":"是否为赠品", "default": false})
     */
    private $is_gift = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_package", type="boolean", nullable=true, options={"comment":"是否为打包产品", "default": false})
     */
    private $is_package = false;

    /**
     * @var string
     *
     * @ORM\Column(name="tdk_content", type="text", nullable=true, options={"comment":"tdk详情"})
     */
    private $tdk_content;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="integer", options={"comment":"供应商id", "default": 0})
     */
    private $supplier_id = 0;

    /**
     * @var integer
     *
     * 供应商控制商品是否可售，0不可售，1可售
     *
     * @ORM\Column(name="is_market", type="integer", length=4, options={"comment":"供应商控制商品是否可售，0不可售，1可售", "default": 1})
     */
    private $is_market = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="goods_bn", type="string", nullable=true, length=255, options={"comment":"SPU编码"})
     */
    private $goods_bn;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_goods_bn", type="string", nullable=true, options={"comment":"供应商货号"})
     */
    private $supplier_goods_bn;

    /**
     * @var integer
     *
     * @ORM\Column(name="audit_date", nullable=true, type="integer", options={"comment":"商品审核时间"})
     */
    private $audit_date;

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
     * @var integer
     *
     * @ORM\Column(name="start_num", nullable=true, type="integer", options={"comment":"起订量", "default": 0})
     */
    private $start_num;

    public function setStartNum($startNum)
    {
        $this->start_num = $startNum;
        return $this;
    }

    public function getStartNum()
    {
        return $this->start_num;
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
     * Set itemType
     *
     * @param string $itemType
     *
     * @return Items
     */
    public function setItemType($itemType)
    {
        // ShopEx EcShopX Service Component
        $this->item_type = $itemType;

        return $this;
    }

    /**
     * Get itemType
     *
     * @return string
     */
    public function getItemType()
    {
        // ShopEx EcShopX Service Component
        return $this->item_type;
    }

    /**
     * Set itemCategory
     *
     * @param string $itemCategory
     *
     * @return Items
     */
    public function setItemCategory($itemCategory)
    {
        $this->item_category = $itemCategory;

        return $this;
    }

    /**
     * Get itemCategory
     *
     * @return string
     */
    public function getItemCategory()
    {
        return $this->item_category;
    }

    /**
     * Set consumeType
     *
     * @param string $consumeType
     *
     * @return Items
     */
    public function setConsumeType($consumeType)
    {
        $this->consume_type = $consumeType;

        return $this;
    }

    /**
     * Get consumeType
     *
     * @return string
     */
    public function getConsumeType()
    {
        return $this->consume_type;
    }

    /**
     * Set itemName
     *
     * @param string $itemName
     *
     * @return Items
     */
    public function setItemName($itemName)
    {
        $this->item_name = $itemName;

        return $this;
    }

    /**
     * Get itemName
     *
     * @return string
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set itemBn
     *
     * @param string $itemBn
     *
     * @return Items
     */
    public function setItemBn($itemBn)
    {
        $this->item_bn = $itemBn;

        return $this;
    }

    /**
     * Get itemBn
     *
     * @return string
     */
    public function getItemBn()
    {
        return $this->item_bn;
    }

    /**
     * Set barcode
     *
     * @param string $barcode
     *
     * @return Items
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;

        return $this;
    }

    /**
     * Get barcode
     *
     * @return string
     */
    public function getBarcode()
    {
        return $this->barcode;
    }

    /**
     * Set brief
     *
     * @param string $brief
     *
     * @return Items
     */
    public function setBrief($brief)
    {
        $this->brief = $brief;

        return $this;
    }

    /**
     * Get brief
     *
     * @return string
     */
    public function getBrief()
    {
        return $this->brief;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Items
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
     * Set price
     *
     * @param integer $price
     *
     * @return Items
     */
    public function setPrice($price)
    {
        if ($price > 2147483647) {
            throw new ResourceException('销售价超过可设置范围');
        }

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
     * Set costPrice
     *
     * @param integer $costPrice
     *
     * @return Items
     */
    public function setCostPrice($costPrice)
    {
        if ($costPrice > 2147483647) {
            throw new ResourceException('成本价超过可设置范围');
        }

        $this->cost_price = $costPrice;

        return $this;
    }

    /**
     * Get costPrice
     *
     * @return integer
     */
    public function getCostPrice()
    {
        return $this->cost_price;
    }

    /**
     * Set itemUnit
     *
     * @param string $itemUnit
     *
     * @return Items
     */
    public function setItemUnit($itemUnit)
    {
        $this->item_unit = $itemUnit;

        return $this;
    }

    /**
     * Get itemUnit
     *
     * @return string
     */
    public function getItemUnit()
    {
        return $this->item_unit;
    }

    /**
     * Set specialType
     *
     * @param string $specialType
     *
     * @return Items
     */
    public function setSpecialType($specialType)
    {
        $this->special_type = $specialType;

        return $this;
    }

    /**
     * Get specialType
     *
     * @return string
     */
    public function getSpecialType()
    {
        return $this->special_type;
    }

    /**
     * Set itemAddressProvince
     *
     * @param string $itemAddressProvince
     *
     * @return Items
     */
    public function setItemAddressProvince($itemAddressProvince)
    {
        $this->item_address_province = $itemAddressProvince;

        return $this;
    }

    /**
     * Get itemAddressProvince
     *
     * @return string
     */
    public function getItemAddressProvince()
    {
        return $this->item_address_province;
    }

    /**
     * Set itemAddressCity
     *
     * @param string $itemAddressCity
     *
     * @return Items
     */
    public function setItemAddressCity($itemAddressCity)
    {
        $this->item_address_city = $itemAddressCity;

        return $this;
    }

    /**
     * Get itemAddressCity
     *
     * @return string
     */
    public function getItemAddressCity()
    {
        return $this->item_address_city;
    }

    /**
     * Set regionsId
     *
     * @param string $regionsId
     *
     * @return Items
     */
    public function setRegionsId($regionsId)
    {
        $this->regions_id = $regionsId;

        return $this;
    }

    /**
     * Get regionsId
     *
     * @return string
     */
    public function getRegionsId()
    {
        return $this->regions_id;
    }

    /**
     * Set regions
     *
     * @param string $regions
     *
     * @return Items
     */
    public function setRegions($regions)
    {
        $this->regions = $regions;

        return $this;
    }

    /**
     * Get regions
     *
     * @return string
     */
    public function getRegions()
    {
        return $this->regions;
    }

    /**
     * Set store
     *
     * @param integer $store
     *
     * @return Items
     */
    public function setStore($store)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Get store
     *
     * @return integer
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Set sales
     *
     * @param integer $sales
     *
     * @return Items
     */
    public function setSales($sales)
    {
        $this->sales = $sales;

        return $this;
    }

    /**
     * Get sales
     *
     * @return integer
     */
    public function getSales()
    {
        return $this->sales;
    }

    /**
     * Set rebateConf
     *
     * @param array $rebateConf
     *
     * @return Items
     */
    public function setRebateConf($rebateConf)
    {
        $this->rebate_conf = $rebateConf;

        return $this;
    }

    /**
     * Get rebateConf
     *
     * @return array
     */
    public function getRebateConf()
    {
        return $this->rebate_conf;
    }

    /**
     * Set rebate
     *
     * @param integer $rebate
     *
     * @return Items
     */
    public function setRebate($rebate)
    {
        $this->rebate = $rebate;

        return $this;
    }

    /**
     * Get rebate
     *
     * @return integer
     */
    public function getRebate()
    {
        return $this->rebate;
    }

    /**
     * Set rebateType
     *
     * @param string $rebateType
     *
     * @return Items
     */
    public function setRebateType($rebateType)
    {
        $this->rebate_type = $rebateType;

        return $this;
    }

    /**
     * Get rebateType
     *
     * @return string
     */
    public function getRebateType()
    {
        return $this->rebate_type;
    }

    /**
     * Set approveStatus
     *
     * @param string $approveStatus
     *
     * @return Items
     */
    public function setApproveStatus($approveStatus)
    {
        $this->approve_status = $approveStatus;

        return $this;
    }

    /**
     * Get approveStatus
     *
     * @return string
     */
    public function getApproveStatus()
    {
        return $this->approve_status;
    }

    /**
     * Set auditStatus
     *
     * @param string $auditStatus
     *
     * @return Items
     */
    public function setAuditStatus($auditStatus)
    {
        $this->audit_status = $auditStatus;

        return $this;
    }

    /**
     * Get auditStatus
     *
     * @return string
     */
    public function getAuditStatus()
    {
        return $this->audit_status;
    }

    /**
     * Set auditReason
     *
     * @param string $auditReason
     *
     * @return Items
     */
    public function setAuditReason($auditReason)
    {
        $this->audit_reason = $auditReason;

        return $this;
    }

    /**
     * Get auditReason
     *
     * @return string
     */
    public function getAuditReason()
    {
        return $this->audit_reason;
    }

    /**
     * Set marketPrice
     *
     * @param integer $marketPrice
     *
     * @return Items
     */
    public function setMarketPrice($marketPrice)
    {
        if ($marketPrice > 2147483647) {
            throw new ResourceException('市场价超过可设置范围');
        }

        $this->market_price = $marketPrice;

        return $this;
    }

    /**
     * Get marketPrice
     *
     * @return integer
     */
    public function getMarketPrice()
    {
        return $this->market_price;
    }

    /**
     * Set goodsFunction
     *
     * @param string $goodsFunction
     *
     * @return Items
     */
    public function setGoodsFunction($goodsFunction)
    {
        $this->goods_function = $goodsFunction;

        return $this;
    }

    /**
     * Get goodsFunction
     *
     * @return string
     */
    public function getGoodsFunction()
    {
        return $this->goods_function;
    }

    /**
     * Set goodsSeries
     *
     * @param string $goodsSeries
     *
     * @return Items
     */
    public function setGoodsSeries($goodsSeries)
    {
        $this->goods_series = $goodsSeries;

        return $this;
    }

    /**
     * Get goodsSeries
     *
     * @return string
     */
    public function getGoodsSeries()
    {
        return $this->goods_series;
    }

    /**
     * Set goodsColor
     *
     * @param string $goodsColor
     *
     * @return Items
     */
    public function setGoodsColor($goodsColor)
    {
        $this->goods_color = $goodsColor;

        return $this;
    }

    /**
     * Get goodsColor
     *
     * @return string
     */
    public function getGoodsColor()
    {
        return $this->goods_color;
    }

    /**
     * Set goodsBrand
     *
     * @param string $goodsBrand
     *
     * @return Items
     */
    public function setGoodsBrand($goodsBrand)
    {
        $this->goods_brand = $goodsBrand;

        return $this;
    }

    /**
     * Get goodsBrand
     *
     * @return string
     */
    public function getGoodsBrand()
    {
        return $this->goods_brand;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     *
     * @return Items
     */
    public function setIsDefault($isDefault)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * Set defaultItemId
     *
     * @param integer $defaultItemId
     *
     * @return Items
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
     * Set goodsId
     *
     * @param integer $goodsId
     *
     * @return Items
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId
     *
     * @return integer
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * Set nospec
     *
     * @param string $nospec
     *
     * @return Items
     */
    public function setNospec($nospec)
    {
        $this->nospec = $nospec;

        return $this;
    }

    /**
     * Get nospec
     *
     * @return string
     */
    public function getNospec()
    {
        return $this->nospec;
    }

    /**
     * Set weight
     *
     * @param float $weight
     *
     * @return Items
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     *
     * @return Items
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
     * Set templatesId
     *
     * @param integer $templatesId
     *
     * @return Items
     */
    public function setTemplatesId($templatesId)
    {
        $this->templates_id = $templatesId;

        return $this;
    }

    /**
     * Get templatesId
     *
     * @return integer
     */
    public function getTemplatesId()
    {
        return $this->templates_id;
    }

    /**
     * Set pics
     *
     * @param array $pics
     *
     * @return Items
     */
    public function setPics($pics)
    {
        $this->pics = $pics;

        return $this;
    }

    /**
     * Get pics
     *
     * @return array
     */
    public function getPics()
    {
        return $this->pics;
    }

    /**
     * Set videos
     *
     * @param string $videos
     *
     * @return Items
     */
    public function setVideos($videos)
    {
        $this->videos = $videos;

        return $this;
    }

    /**
     * Get videos
     *
     * @return string
     */
    public function getVideos()
    {
        return $this->videos;
    }

    /**
     * Set videoPicUrl
     *
     * @param string $videoPicUrl
     *
     * @return Items
     */
    public function setVideoPicUrl($videoPicUrl)
    {
        $this->video_pic_url = $videoPicUrl;

        return $this;
    }

    /**
     * Get videoPicUrl
     *
     * @return string
     */
    public function getVideoPicUrl()
    {
        return $this->video_pic_url;
    }

    /**
     * Set intro
     *
     * @param string $intro
     *
     * @return Items
     */
    public function setIntro($intro)
    {
        $this->intro = $intro;

        return $this;
    }

    /**
     * Get intro
     *
     * @return string
     */
    public function getIntro()
    {
        return $this->intro;
    }

    /**
     * Set purchaseAgreement
     *
     * @param string $purchaseAgreement
     *
     * @return Items
     */
    public function setPurchaseAgreement($purchaseAgreement)
    {
        $this->purchase_agreement = $purchaseAgreement;

        return $this;
    }

    /**
     * Get purchaseAgreement
     *
     * @return string
     */
    public function getPurchaseAgreement()
    {
        return $this->purchase_agreement;
    }

    /**
     * Set isShowSpecimg
     *
     * @param boolean $isShowSpecimg
     *
     * @return Items
     */
    public function setIsShowSpecimg($isShowSpecimg)
    {
        $this->is_show_specimg = $isShowSpecimg;

        return $this;
    }

    /**
     * Get isShowSpecimg
     *
     * @return boolean
     */
    public function getIsShowSpecimg()
    {
        return $this->is_show_specimg;
    }

    /**
     * Set enableAgreement
     *
     * @param boolean $enableAgreement
     *
     * @return Items
     */
    public function setEnableAgreement($enableAgreement)
    {
        $this->enable_agreement = $enableAgreement;

        return $this;
    }

    /**
     * Get enableAgreement
     *
     * @return boolean
     */
    public function getEnableAgreement()
    {
        return $this->enable_agreement;
    }

    /**
     * Set dateType
     *
     * @param string $dateType
     *
     * @return Items
     */
    public function setDateType($dateType)
    {
        $this->date_type = $dateType;

        return $this;
    }

    /**
     * Get dateType
     *
     * @return string
     */
    public function getDateType()
    {
        return $this->date_type;
    }

    /**
     * Set beginDate
     *
     * @param integer $beginDate
     *
     * @return Items
     */
    public function setBeginDate($beginDate)
    {
        $this->begin_date = $beginDate;

        return $this;
    }

    /**
     * Get beginDate
     *
     * @return integer
     */
    public function getBeginDate()
    {
        return $this->begin_date;
    }

    /**
     * Set endDate
     *
     * @param integer $endDate
     *
     * @return Items
     */
    public function setEndDate($endDate)
    {
        $this->end_date = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return integer
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * Set fixedTerm
     *
     * @param integer $fixedTerm
     *
     * @return Items
     */
    public function setFixedTerm($fixedTerm)
    {
        $this->fixed_term = $fixedTerm;

        return $this;
    }

    /**
     * Get fixedTerm
     *
     * @return integer
     */
    public function getFixedTerm()
    {
        return $this->fixed_term;
    }

    /**
     * Set brandLogo
     *
     * @param string $brandLogo
     *
     * @return Items
     */
    public function setBrandLogo($brandLogo)
    {
        $this->brand_logo = $brandLogo;

        return $this;
    }

    /**
     * Get brandLogo
     *
     * @return string
     */
    public function getBrandLogo()
    {
        return $this->brand_logo;
    }

    /**
     * Set isPoint
     *
     * @param boolean $isPoint
     *
     * @return Items
     */
    public function setIsPoint($isPoint)
    {
        $this->is_point = $isPoint;

        return $this;
    }

    /**
     * Get isPoint
     *
     * @return boolean
     */
    public function getIsPoint()
    {
        return $this->is_point;
    }

    /**
     * Set point
     *
     * @param integer $point
     *
     * @return Items
     */
    public function setPoint($point)
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Get point
     *
     * @return integer
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return Items
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
     * Set volume
     *
     * @param float $volume
     *
     * @return Items
     */
    public function setVolume($volume)
    {
        $this->volume = $volume;

        return $this;
    }

    /**
     * Get volume
     *
     * @return float
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * Set itemSource
     *
     * @param string $itemSource
     *
     * @return Items
     */
    public function setItemSource($itemSource)
    {
        $this->item_source = $itemSource;

        return $this;
    }

    /**
     * Get itemSource
     *
     * @return string
     */
    public function getItemSource()
    {
        return $this->item_source;
    }

    /**
     * Set brandId
     *
     * @param integer $brandId
     *
     * @return Items
     */
    public function setBrandId($brandId)
    {
        $this->brand_id = $brandId;

        return $this;
    }

    /**
     * Get brandId
     *
     * @return integer
     */
    public function getBrandId()
    {
        return $this->brand_id;
    }

    /**
     * Set costPrice
     *
     * @param integer $costPrice
     *
     * @return Items
     */
    public function setTaxRate($taxRate)
    {
        $this->tax_rate = $taxRate;

        return $this;
    }

    /**
     * Get costPrice
     *
     * @return integer
     */
    public function getTaxRate()
    {
        return $this->tax_rate;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Items
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
     * @return Items
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
     * Set isGift.
     *
     * @param bool|null $isGift
     *
     * @return Items
     */
    public function setIsGift($isGift = null)
    {
        $this->is_gift = $isGift;

        return $this;
    }

    /**
     * Get isGift.
     *
     * @return bool|null
     */
    public function getIsGift()
    {
        return $this->is_gift;
    }

    /**
     * Set isPackage.
     *
     * @param bool|null $isPackage
     *
     * @return Items
     */
    public function setIsPackage($isPackage = null)
    {
        $this->is_package = $isPackage;

        return $this;
    }

    /**
     * Get isPackage.
     *
     * @return bool|null
     */
    public function getIsPackage()
    {
        return $this->is_package;
    }

    /**
     * Set videoType.
     *
     * @param string $videoType
     *
     * @return Items
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
     * Set crossborderTaxRate.
     *
     * @param string $crossborderTaxRate
     *
     * @return Items
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
     * Set profitType.
     *
     * @param int|null $profitType
     *
     * @return Items
     */
    public function setProfitType($profitType = null)
    {
        $this->profit_type = $profitType;

        return $this;
    }

    /**
     * Get profitType.
     *
     * @return int|null
     */
    public function getProfitType()
    {
        return $this->profit_type;
    }

    /**
     * Set origincountryId.
     *
     * @param int $origincountryId
     *
     * @return Items
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
     * Set profitFee.
     *
     * @param int|null $profitFee
     *
     * @return Items
     */
    public function setProfitFee($profitFee = null)
    {
        $this->profit_fee = $profitFee;

        return $this;
    }

    /**
     * Get profitFee.
     *
     * @return int|null
     */
    public function getProfitFee()
    {
        return $this->profit_fee;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return Items
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
     * Set isProfit.
     *
     * @param bool|null $isProfit
     *
     * @return Items
     */
    public function setIsProfit($isProfit = null)
    {
        $this->is_profit = $isProfit;

        return $this;
    }

    /**
     * Get isProfit.
     *
     * @return bool|null
     */
    public function getIsProfit()
    {
        return $this->is_profit;
    }


    /**
     * Set taxstrategyId.
     *
     * @param int $taxstrategyId
     *
     * @return Items
     */
    public function setTaxstrategyId($taxstrategyId)
    {
        $this->taxstrategy_id = $taxstrategyId;

        return $this;
    }

    /**
     * Get taxstrategyId.
     *
     * @return int
     */
    public function getTaxstrategyId()
    {
        return $this->taxstrategy_id;
    }

    /**
     * Set taxationNum.
     *
     * @param int $taxationNum
     *
     * @return Items
     */
    public function setTaxationNum($taxationNum)
    {
        $this->taxation_num = $taxationNum;

        return $this;
    }

    /**
     * Get taxationNum.
     *
     * @return int
     */
    public function getTaxationNum()
    {
        return $this->taxation_num;
    }

    /**
     * Set tdkContent.
     *
     * @param string|null $tdkContent
     *
     * @return Items
     */
    public function setTdkContent($tdkContent = null)
    {
        $this->tdk_content = $tdkContent;

        return $this;
    }

    /**
     * Get tdkContent.
     *
     * @return string|null
     */
    public function getTdkContent()
    {
        return $this->tdk_content;
    }


    /**
     * Set picsCreateQrcode.
     *
     * @param array $picsCreateQrcode
     *
     * @return Items
     */
    public function setPicsCreateQrcode($picsCreateQrcode)
    {
        $this->pics_create_qrcode = $picsCreateQrcode;

        return $this;
    }

    /**
     * Get picsCreateQrcode.
     *
     * @return array
     */
    public function getPicsCreateQrcode()
    {
        return $this->pics_create_qrcode;
    }


    /**
     * Set isEpidemic.
     *
     * @param int $isEpidemic
     *
     * @return Items
     */
    public function setIsEpidemic($isEpidemic)
    {
        $this->is_epidemic = $isEpidemic;

        return $this;
    }

    /**
     * Get isEpidemic.
     *
     * @return int
     */
    public function getIsEpidemic()
    {
        return $this->is_epidemic;
    }

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return Items
     */
    public function setSupplierId($supplierId = 0)
    {
        $this->supplier_id = $supplierId;

        return $this;
    }

    /**
     * Get supplierId.
     *
     * @return int
     */
    public function getSupplierId()
    {
        return $this->supplier_id;
    }

    /**
     * Set isMarket.
     *
     * @param int $isMarket
     *
     * @return Items
     */
    public function setIsMarket($isMarket)
    {
        $this->is_market = $isMarket;

        return $this;
    }

    /**
     * Get isMarket.
     *
     * @return int
     */
    public function getIsMarket()
    {
        return $this->is_market;
    }

    /**
     * Set goodsBn.
     *
     * @param string|null $goodsBn
     *
     * @return Items
     */
    public function setGoodsBn($goodsBn = null)
    {
        $this->goods_bn = $goodsBn;

        return $this;
    }

    /**
     * Get goodsBn.
     *
     * @return string|null
     */
    public function getGoodsBn()
    {
        return $this->goods_bn;
    }

    /**
     * Set supplierGoodsBn.
     *
     * @param string|null $supplierGoodsBn
     *
     * @return Items
     */
    public function setSupplierGoodsBn($supplierGoodsBn = null)
    {
        $this->supplier_goods_bn = $supplierGoodsBn;

        return $this;
    }

    /**
     * Get supplierGoodsBn.
     *
     * @return string|null
     */
    public function getSupplierGoodsBn()
    {
        return $this->supplier_goods_bn;
    }

    /**
     * Set auditDate.
     *
     * @param int|null $auditDate
     *
     * @return Items
     */
    public function setAuditDate($auditDate = null)
    {
        $this->audit_date = $auditDate;

        return $this;
    }

    /**
     * Get auditDate.
     *
     * @return int|null
     */
    public function getAuditDate()
    {
        return $this->audit_date;
    }

    /**
     * Set isMedicine.
     *
     * @param int|null $isMedicine
     *
     * @return SupplierItems
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
     * @return SupplierItems
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
