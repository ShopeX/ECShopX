<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PointsmallBundle\Http\FrontApi\V1\Swagger\Models;

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
