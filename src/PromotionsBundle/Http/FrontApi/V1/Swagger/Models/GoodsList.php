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

namespace PromotionsBundle\Http\FrontApi\V1\Swagger\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="GoodsList"))
 */
class GoodsList
{
    public $item_id;

    /**
     * @SWG\Property(description="商品名称", example="商品名称")
     * @var string
     */
    public $item_name;

    /**
     * @SWG\Property(description="商品计量单位", example="件")
     * @var string
     */
    public $item_unit;

    /**
     * @SWG\Property(description="简介", example="简介")
     * @var string
     */
    public $brief;

    /**
     * @SWG\Property(description="图片地址数组", example="图片地址数组")
     * @var string
     */
    public $pics;

    /**
     * @SWG\Property(description="商品主类目", example="1")
     * @var int
     */
    public $item_main_cat_id;

    /**
     * @SWG\Property(description="分类id数组", example="")
     * @var string
     */
    public $item_cat_id;

    /**
     * @SWG\Property(description="商品类型，services：服务商品，normal: 普通商品", example="normal")
     * @var string
     */
    public $item_type;

    /**
     * @SWG\Property(description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)", example="every")
     * @var string
     */
    public $consume_type;

    /**
     * @SWG\Property(description="推广商品 1已选择 0未选择 2申请加入 3拒绝", example="1")
     * @var int
     */
    public $rebate;

    /**
     * @SWG\Property(description="分销配置 json", example="")
     * @var string
     */
    public $rebate_conf;

    /**
     * @SWG\Property(description="是否积分兑换 true可以 false不可以", example="false")
     * @var boolean
     */
    public $is_point;

    /**
     * @SWG\Property(description="详情页是否显示规格图片", example=false),
     * @var boolean
     */
    public $is_show_specimg;

    /**
     * @SWG\Property(description="商品特殊类型 drug 处方药 normal 普通商品", example="normal"),
     * @var string
     */
    public $special_type;

    /**
     * @SWG\Property(description="产地省", example="河北省"),
     * @var string
     */
    public $item_address_province;

    /**
     * @SWG\Property(description="产地市", example="石家庄"),
     * @var string
     */
    public $item_address_city;

    /**
     * @SWG\Property(description="产地地区id", example="110000,110001,110101"),
     * @var string
     */
    public $regions_id;

    /**
     * @SWG\Property(description="店铺id", example=""),
     * @var boolean
     */
    public $distributor_id;

    /**
     * @SWG\Property(description="商品主类目id", example="1"),
     * @var boolean
     */
    public $item_category;

    /**
     * @SWG\Property(description="分佣计算方式", example="default"),
     * @var string
     */
    public $rebate_type;

    /**
     * @SWG\Property(description="税率, 百分之～/100", example="0"),
     * @var int
     */
    public $tax_rate;

    /**
     * @SWG\Property(description="是否为赠品", example=true),
     * @var boolean
     */
    public $is_gift;

    /**
     * @SWG\Property(description="是否为打包产品", example=true),
     * @var boolean
     */
    public $is_package;

    /**
     * @SWG\Property(description="是否支持分润", example=false),
     * @var boolean
     */
    public $is_profit;

    /**
     * @SWG\Property(description="分润类型, 默认为0配置分润,1主类目分润,2商品指定分润(比例),3商品指定分润(金额)", example=0),
     * @var int
     */
    public $profit_type;

    /**
     * @SWG\Property(description="分润金额,单位为分 冗余字段", example=0),
     * @var int
     */
    public $profit_fee;

    /**
     * @SWG\Property(description="跨境税率，百分比，小数点2位", example=0),
     * @var string
     */
    public $crossborder_tax_rate;

    /**
     * @SWG\Property(description="产地国id", example=0),
     * @var string
     */
    public $int;

    /**
     * @SWG\Property(description="税费策略id", example=0),
     * @var int
     */
    public $taxstrategy_id;

    /**
     * @SWG\Property(description="计税单位份数", example=0),
     * @var string
     */
    public $taxation_num;

    /**
     * @SWG\Property(description="商品类型，0普通，1跨境商品，可扩展", example=0),
     * @var int
     */
    public $type;

    /**
     * @SWG\Property(description="库存", example=100)
     * @var int
     */
    public $store;

    /**
     * @SWG\Property(description="条形码", example="BDCS123")
     * @var string
     */
    public $barcode;

    /**
     * @SWG\Property(description="销量", example=1)
     * @var int
     */
    public $sales;

    /**
     * @SWG\Property(description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售, only_show:前台仅展示", example="onsale")
     * @var string
     */
    public $approve_status;

    /**
     * @SWG\Property(description="销售价（分）", example="1")
     * @var int
     */
    public $price;

    /**
     * @SWG\Property(description="成本价（分）", example="1")
     * @var int
     */
    public $cost_price;

    /**
     * @SWG\Property(description="市场价（分）", example="1")
     * @var int
     */
    public $market_price;

    /**
     * @SWG\Property(description="会员价（分）", example="1")
     * @var int
     */
    public $member_price;

    /**
     * @SWG\Property(description="获取积分个数", example="1")
     * @var int
     */
    public $point;

    /**
     * @SWG\Property(description="商品来源:mall:主商城，distributor:店铺自有", example="1")
     * @var int
     */
    public $item_source;

    /**
     * @SWG\Property(description="货品编码", example="S5F990B4BB3468")
     * @var string
     */
    public $item_bn;

    /**
     * @SWG\Property(description="体积", example="1")
     * @var string
     */
    public $volume;

    /**
     * @SWG\Property(description="重量(kg)", example="1")
     * @var string
     */
    public $weight;

    /**
     * @SWG\Property(description="排序", example="1")
     * @var string
     */
    public $sort;

    /**
     * @SWG\Property(description="商品ID", example="1")
     * @var int
     */
    public $goods_id;

    /**
     * @SWG\Property(description="默认skuid", example="1")
     * @var int
     */
    public $default_item_id;

    /**
     * @SWG\Property(description="品牌id", example="onsale")
     * @var int
     */
    public $brand_id;

    /**
     * @SWG\Property(description="运费模板id", example="1")
     * @var int
     */
    public $templates_id;

    /**
     * @SWG\Property(description="商品是否为默认商品", example=true)
     * @var boolean
     */
    public $is_default;

    /**
     * @SWG\Property(description="商品是否为单规格", example="true")
     * @var string
     */
    public $nospec;

    /**
     * @SWG\Property(description="视频类型 local:本地视频 tencent:腾讯视频", example="local")
     * @var string
     */
    public $video_type;

    /**
     * @SWG\Property(description="视频地址", example="http://b-video-cdn.yuanyuanke.cn/videos/1/2020/08/27/4c461f5068e0ca5118ad5c821b7795beN9ERxSrCiSn8ivpqDPYuA0uN9YbsjI17")
     * @var string
     */
    public $videos;

    /**
     * @SWG\Property(description="审核状态 approved成功 processing审核中 rejected审核拒绝", example="approved")
     * @var string
     */
    public $audit_status;

    /**
     * @SWG\Property(description="审核拒绝原因", example="审核拒绝原因")
     * @var string
     */
    public $audit_reason;

    /**
     * @SWG\Property(description="商品Id", example="1")
     * @var int
     */
    public $itemId;

    /**
     * @SWG\Property(description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)", example="every")
     * @var int
     */
    public $consumeType;

    /**
     * @SWG\Property(description="商品名称", example="商品名称")
     * @var int
     */
    public $itemName;

    /**
     * @SWG\Property(description="商品编号", example="S5F990B4BB3468")
     * @var int
     */
    public $itemBn;

    /**
     * @SWG\Property(description="tdk详情 json", example="")
     * @var string
     */
    public $tdk_content;

    /**
     * @SWG\Property(description="创建时间", example="1599533901")
     * @var int
     */
    public $created;

    /**
     * @SWG\Property(description="更新时间", example="1605696853")
     * @var int
     */
    public $updated;
}
