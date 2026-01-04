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

namespace PointsmallBundle\Http\Api\V1\Swagger\Models;

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
     * @SWG\Property(description="商品类型，services：服务商品，normal: 普通商品", example="normal")
     * @var string
     */
    public $item_type;

    /**
     * @SWG\Property(description="详情页是否显示规格图片", example=false),
     * @var boolean
     */
    public $is_show_specimg;

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
     * @SWG\Property(description="积分价格", example="1")
     * @var int
     */
    public $point;

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
     * @SWG\Property(description="商品名称", example="商品名称")
     * @var int
     */
    public $itemName;

    /**
     * @SWG\Property(description="商品编号", example="S5F990B4BB3468")
     * @var int
     */
    public $itemBn;
}


/**
 * @SWG\Definition(
 *     definition="ItemList",
 *     type="object",
 *     @SWG\Property( property="item_id", type="string", example="33", description="id"),
 *     @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
 *     @SWG\Property( property="is_show_specimg", type="boolean", example="true", description="详情页是否显示规格图片"),
 *     @SWG\Property( property="store", type="string", example="7", description="库存"),
 *     @SWG\Property( property="barcode", type="string", example="", description=""),
 *     @SWG\Property( property="sales", type="string", example="null", description="自行更改字段描述"),
 *     @SWG\Property( property="approve_status", type="string", example="instock", description="自行更改字段描述"),
 *     @SWG\Property( property="cost_price", type="string", example="0", description="自行更改字段描述"),
 *     @SWG\Property( property="point", type="string", example="10", description="自行更改字段描述"),
 *     @SWG\Property( property="goods_id", type="string", example="33", description="自行更改字段描述"),
 *     @SWG\Property( property="brand_id", type="string", example="3", description="自行更改字段描述"),
 *     @SWG\Property( property="item_name", type="string", example="11111", description="自行更改字段描述"),
 *     @SWG\Property( property="item_unit", type="string", example="", description="自行更改字段描述"),
 *     @SWG\Property( property="item_bn", type="string", example="S600653BC3D31A", description="自行更改字段描述"),
 *     @SWG\Property( property="brief", type="string", example="", description="自行更改字段描述"),
 *     @SWG\Property( property="price", type="string", example="0", description="自行更改字段描述"),
 *     @SWG\Property( property="market_price", type="string", example="0", description="自行更改字段描述"),
 *     @SWG\Property( property="goods_series", type="string", example="null", description="自行更改字段描述"),
 *     @SWG\Property( property="volume", type="string", example="null", description="自行更改字段描述"),
 *     @SWG\Property( property="goods_brand", type="string", example="null", description="自行更改字段描述"),
 *     @SWG\Property( property="item_address_province", type="string", example="", description="自行更改字段描述"),
 *     @SWG\Property( property="item_address_city", type="string", example="", description="自行更改字段描述"),
 *     @SWG\Property( property="regions_id", type="string", example="null", description="自行更改字段描述"),
 *     @SWG\Property( property="brand_logo", type="string", example="null", description="自行更改字段描述"),
 *     @SWG\Property( property="sort", type="string", example="1", description="自行更改字段描述"),
 *     @SWG\Property( property="templates_id", type="string", example="101", description="自行更改字段描述"),
 *     @SWG\Property( property="is_default", type="string", example="true", description="自行更改字段描述"),
 *     @SWG\Property( property="nospec", type="string", example="false", description="自行更改字段描述"),
 *     @SWG\Property( property="default_item_id", type="string", example="33", description="自行更改字段描述"),
 *     @SWG\Property( property="pics", type="string",description="图片数组"),
 *     @SWG\Property( property="company_id", type="string", example="1", description="自行更改字段描述"),
 *     @SWG\Property( property="date_type", type="string", example="", description="自行更改字段描述"),
 *     @SWG\Property( property="item_category", type="string", example="9", description="自行更改字段描述"),
 *     @SWG\Property( property="weight", type="string", example="0", description="自行更改字段描述"),
 *     @SWG\Property( property="created", type="string", example="1611027388", description="自行更改字段描述"),
 *     @SWG\Property( property="updated", type="string", example="1611207705", description="自行更改字段描述"),
 *     @SWG\Property( property="video_type", type="string", example="local", description="自行更改字段描述"),
 *     @SWG\Property( property="videos", type="string", example="", description="自行更改字段描述"),
 *     @SWG\Property( property="video_pic_url", type="string", example="null", description="自行更改字段描述"),
 *     @SWG\Property( property="itemId", type="string", example="33", description="自行更改字段描述"),
 *     @SWG\Property( property="itemName", type="string", example="11111", description="自行更改字段描述"),
 *     @SWG\Property( property="itemBn", type="string", example="S600653BC3D31A", description="自行更改字段描述"),
 *     @SWG\Property( property="companyId", type="string", example="1", description="自行更改字段描述"),
 *     @SWG\Property( property="item_main_cat_id", type="string", example="9", description="自行更改字段描述"),
 *     @SWG\Property( property="item_cat_id", type="string", description="分类id数组"),
 *     @SWG\Property( property="itemMainCatName", type="string", example="test-3", description="自行更改字段描述"),
 *     @SWG\Property( property="itemCatName", type="string", description="分类名称数组"),
 * )
 */
