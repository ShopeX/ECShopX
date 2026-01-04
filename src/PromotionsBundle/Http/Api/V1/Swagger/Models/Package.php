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

namespace PromotionsBundle\Http\Api\V1\Swagger\Models;

/**
 * @SWG\Definition(
 *     definition="PackageResult",
 *     type="object",
 *     @SWG\Property( property="package_id", type="string", example="100", description="组合促销规则id"),
 *     @SWG\Property( property="main_item_id", type="string", example="5040", description="主商品id"),
 *     @SWG\Property( property="main_item_price", type="string", example="100", description="主商品价格（分）"),
 *     @SWG\Property( property="package_name", type="string", example="组合商品多规格", description="组合促销名称"),
 *     @SWG\Property( property="valid_grade", type="string", example="", description="会员级别集合数组"),
 *     @SWG\Property( property="used_platform", type="string", example="0", description="适用平台: 0:全场可用,1:只用于pc端,2:小程序端,3:h5端 暂时只支持0"),
 *     @SWG\Property( property="free_postage", type="string", example="1", description="是否免邮 | 0 包邮|1 商品"),
 *     @SWG\Property( property="package_total_price", type="string", example="21", description="组合促销各商品总价（分）"),
 *     @SWG\Property( property="start_time", type="string", example="1611504000", description="开始时间"),
 *     @SWG\Property( property="end_time", type="string", example="1612108799", description="结束时间"),
 *     @SWG\Property( property="package_status", type="string", example="AGREE", description="NO_REVIEWED 未审核|PENDING 待审核|AGREE 审核通过|REFUSE 审核拒绝|CANCEL 已取消"),
 *     @SWG\Property( property="reason", type="string", example="", description="审核不通过原因"),
 *     @SWG\Property( property="created", type="string", example="1611546794", description=""),
 *     @SWG\Property( property="updated", type="string", example="1611546794", description="修改时间"),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Package",
 *     type="object",
 *     @SWG\Property( property="package_id", type="string", example="100", description="组合促销规则id"),
 *     @SWG\Property( property="main_item_id", type="string", example="5040", description="主商品id"),
 *     @SWG\Property( property="main_item_price", type="string", example="100", description="主商品价格（分）"),
 *     @SWG\Property( property="package_name", type="string", example="组合商品多规格", description="组合促销名称"),
 *     @SWG\Property( property="valid_grade", type="string", example="4,8,26,27,vip,svip", description="会员级别集合"),
 *     @SWG\Property( property="used_platform", type="string", example="0", description="适用平台: 0:全场可用,1:只用于pc端,2:小程序端,3:h5端 暂时只支持0"),
 *     @SWG\Property( property="free_postage", type="string", example="1", description="是否免邮 | 0 包邮|1 商品"),
 *     @SWG\Property( property="package_total_price", type="string", example="21", description="组合促销各商品总价（分）"),
 *     @SWG\Property( property="start_time", type="string", example="1611504000", description="开始时间"),
 *     @SWG\Property( property="end_time", type="string", example="1612108799", description="结束时间"),
 *     @SWG\Property( property="package_status", type="string", example="AGREE", description="NO_REVIEWED 未审核|PENDING 待审核|AGREE 审核通过|REFUSE 审核拒绝|CANCEL 已取消"),
 *     @SWG\Property( property="reason", type="string", example="", description="审核不通过原因"),
 *     @SWG\Property( property="created", type="string", example="1611546794", description=""),
 *     @SWG\Property( property="updated", type="string", example="1611546794", description="修改时间"),
 *     @SWG\Property( property="goods_id", type="string", example="5040", description="商品id"),
 *     @SWG\Property( property="status", type="string", example="ongoing", description="活动状态 waiting:未开始，ongoing:进行中，end:已结束"),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="PackageDetail",
 *     type="object",
 *     @SWG\Property( property="package_id", type="string", example="100", description="组合促销规则id"),
 *     @SWG\Property( property="main_item_id", type="string", example="5040", description="主商品id"),
 *     @SWG\Property( property="main_item_price", type="string", example="100", description="主商品价格（分）"),
 *     @SWG\Property( property="package_name", type="string", example="组合商品多规格", description="组合促销名称"),
 *     @SWG\Property( property="valid_grade", type="array",
 *         @SWG\Items( type="string", example="4992", description="会员级别集合"),
 *     ),
 *     @SWG\Property( property="used_platform", type="string", example="0", description="适用平台: 0:全场可用,1:只用于pc端,2:小程序端,3:h5端 暂时只支持0"),
 *     @SWG\Property( property="free_postage", type="string", example="1", description="是否免邮 | 0 包邮|1 商品"),
 *     @SWG\Property( property="package_total_price", type="string", example="21", description="组合促销各商品总价（分）"),
 *     @SWG\Property( property="start_time", type="string", example="1611504000", description="开始时间"),
 *     @SWG\Property( property="end_time", type="string", example="1612108799", description="结束时间"),
 *     @SWG\Property( property="package_status", type="string", example="AGREE", description="NO_REVIEWED 未审核|PENDING 待审核|AGREE 审核通过|REFUSE 审核拒绝|CANCEL 已取消"),
 *     @SWG\Property( property="reason", type="string", example="", description="审核不通过原因"),
 *     @SWG\Property( property="created", type="string", example="1611546794", description=""),
 *     @SWG\Property( property="updated", type="string", example="1611546794", description="修改时间"),
 *     @SWG\Property( property="goods_id", type="string", example="5040", description="商品id"),
 *     @SWG\Property(
 *         property="itemTreeLists",
 *         type="array",
 *         description="子商品的商品数据",
 *         @SWG\Items( type="object",
 *             ref="#definitions/ItemList",
 *         ),
 *     ),
 *     @SWG\Property(
 *         property="items",
 *         type="array",
 *         description="子商品的sku数据",
 *         @SWG\Items( type="object",
 *             ref="#definitions/ItemList",
 *         ),
 *     ),
 *     @SWG\Property(
 *     		property="mainItem",
 *         	type="array",
 *          description="主商品的商品数据",
 *         	@SWG\Items( type="object",
 *             ref="#definitions/ItemList",
 *         ),
 *     ),
 *     @SWG\Property(
 *     		property="main_items",
 *         	type="array",
 *          description="主商品的sku数据",
 *         	@SWG\Items( type="object",
 *             ref="#definitions/ItemList",
 *         ),
 *     ),
 *     @SWG\Property( property="new_price", type="object",
 *         @SWG\Property( property="4992", type="string", example="100", description="子商品sku的id和活动价数据"),
 *     ),
 *     @SWG\Property( property="package_items", type="array",
 *         @SWG\Items( type="string", example="4992", description="子商品skuid"),
 *     ),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="ItemList",
 *     type="object",
 *     @SWG\Property( property="item_id", type="string", example="33", description="id"),
 *     @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
 *     @SWG\Property( property="is_show_specimg", type="boolean", example="true", description="详情页是否显示规格图片"),
 *     @SWG\Property( property="store", type="string", example="7", description="库存"),
 *     @SWG\Property( property="barcode", type="string", example="", description=""),
 *     @SWG\Property( property="sales", type="string", example="null", description="销量"),
 *     @SWG\Property( property="approve_status", type="string", example="instock", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
 *     @SWG\Property( property="cost_price", type="string", example="0", description="成本价（分）"),
 *     @SWG\Property( property="point", type="string", example="10", description="赠送积分数"),
 *     @SWG\Property( property="goods_id", type="string", example="33", description="产品id"),
 *     @SWG\Property( property="brand_id", type="string", example="3", description="品牌id"),
 *     @SWG\Property( property="item_name", type="string", example="商品名称", description="商品名称"),
 *     @SWG\Property( property="item_unit", type="string", example="件", description="商品计量单位"),
 *     @SWG\Property( property="item_bn", type="string", example="S600653BC3D31A", description="商品编号"),
 *     @SWG\Property( property="brief", type="string", example="", description="简介"),
 *     @SWG\Property( property="price", type="string", example="0", description="价格,单位为‘分’"),
 *     @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
 *     @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
 *     @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
 *     @SWG\Property( property="goods_brand", type="string", example="null", description="商品品牌"),
 *     @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
 *     @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
 *     @SWG\Property( property="regions_id", type="string", example="null", description="产地地区id"),
 *     @SWG\Property( property="brand_logo", type="string", example="null", description="品牌图片"),
 *     @SWG\Property( property="sort", type="string", example="1", description="排序"),
 *     @SWG\Property( property="templates_id", type="string", example="101", description="运费模板id"),
 *     @SWG\Property( property="is_default", type="string", example="true", description="商品是否为默认商品"),
 *     @SWG\Property( property="nospec", type="string", example="false", description="商品是否为单规格"),
 *     @SWG\Property( property="default_item_id", type="string", example="33", description="默认商品ID"),
 *     @SWG\Property( property="pics", type="string",description="图片数组"),
 *     @SWG\Property( property="date_type", type="string", example="", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"),
 *     @SWG\Property( property="item_category", type="string", example="9", description="商品主类目"),
 *     @SWG\Property( property="weight", type="string", example="0", description="商品重量"),
 *     @SWG\Property( property="created", type="string", example="1611027388", description=""),
 *     @SWG\Property( property="updated", type="string", example="1611207705", description=""),
 *     @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
 *     @SWG\Property( property="videos", type="string", example="", description="视频"),
 *     @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
 *     @SWG\Property( property="itemId", type="string", example="33", description="商品id"),
 *     @SWG\Property( property="itemName", type="string", example="11111", description="商品名称"),
 *     @SWG\Property( property="itemBn", type="string", example="S600653BC3D31A", description="商品编号"),
 *     @SWG\Property( property="item_main_cat_id", type="string", example="9", description="商品主类目id"),
 *     @SWG\Property( property="item_spec_desc", type="string", example="图片规格:02,文字规格:一", description="规格描述"),
 *     @SWG\Property( property="item_spec", type="array",
 *         @SWG\Items( type="object",
 *             ref="#/definitions/ItemSpec"
 *         )
 *     ),
 *     @SWG\Property( property="rebate_conf", type="object",
 *     		@SWG\Property( property="type", type="string", example="money", description=""),
 *          @SWG\Property( property="value", type="object",
 *          	@SWG\Property( property="first_level", type="string", example="", description="一级"),
 *           	@SWG\Property( property="second_level", type="string", example="", description="二级"),
 *          ),
 *          @SWG\Property( property="ratio_type", type="string", example="order_money", description="分佣方式"),
 *          @SWG\Property( property="rebate_task", type="array",
 *              @SWG\Items( type="object",
 *                  @SWG\Property( property="money", type="string", example="1", description=""),
 *                  @SWG\Property( property="ratio", type="string", example="", description=""),
 *                  @SWG\Property( property="filter", type="string", example="1", description=""),
 *               ),
 *          ),
 *          @SWG\Property( property="rebate_task_type", type="string", example="money", description="返佣计算类型"),
 *     ),
 *
 * )
 */

/**
 * @SWG\Definition(
 *     definition="ItemSpec",
 *     type="object",
 *     @SWG\Property(property="item_id", type="integer", description="商品id", example=1),
 *     @SWG\Property(property="spec_id", type="integer", description="规格项id", example=1),
 *     @SWG\Property(property="spec_value_id", type="integer", description="规格值id", example=1),
 *     @SWG\Property(property="spec_name", type="string", description="规格项名称", example="尺码"),
 *     @SWG\Property(property="spec_custom_value_name", description="规格值自定义名称", type="string", example="S"),
 *     @SWG\Property(property="spec_value_name", type="integer", description="规格值名称", example="S"),
 *     @SWG\Property(property="item_image_url", type="string", description="商品图片地址数组", example="商品图片地址数组"),
 *    @SWG\Property(property="spec_image_url", type="string", description="规格图片地址数组", example="规格图片地址数组"),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="SpecItems",
 *     type="object",
 *     @SWG\Property( property="item_id", type="string", example="5402", description="商品id"),
 *     @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售, only_show:前台仅展示"),
 *     @SWG\Property( property="barcode", type="string", example="BDCS123", description="条形码"),
 *     @SWG\Property( property="cost_price", type="string", example="1", description="成本价（分）"),
 *     @SWG\Property( property="is_default", type="boolean", example=true, description="商品是否为默认商品"),
 *     @SWG\Property( property="item_bn", type="string", example="S5FF5332CCEEC4", description="货品编码"),
 *     @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
 *     @SWG\Property( property="market_price", type="string", example="1", description="市场价（分）"),
 *     @SWG\Property( property="point_num", type="string", example="0", description=""),
 *     @SWG\Property( property="price", type="string", example="5500", description="销售价(分)"),
 *     @SWG\Property( property="store", type="string", example="10", description="库存"),
 *     @SWG\Property( property="volume", type="string", example="10", description="体积"),
 *     @SWG\Property( property="weight", type="string", example="10", description="重量"),
 *     @SWG\Property( property="item_spec", type="array",
 *     		@SWG\Items(
 *     			@SWG\Property(property="item_id", type="integer", description="商品id", example=1),
 *     			@SWG\Property(property="spec_id", type="integer", description="规格项id", example=1),
 *     			@SWG\Property(property="spec_value_id", type="integer", description="规格值id", example=1),
 *     			@SWG\Property(property="spec_name", type="string", description="规格项名称", example="尺码"),
 *     			@SWG\Property(property="spec_custom_value_name", description="规格值自定义名称", type="string", example="S"),
 *     			@SWG\Property(property="spec_value_name", type="integer", description="规格值名称", example="S"),
 *     			@SWG\Property(property="item_image_url", type="string", description="商品图片地址数组", example="商品图片地址数组"),
 *     			@SWG\Property(property="spec_image_url", type="string", description="规格图片地址数组", example="规格图片地址数组"),
 *     		),
 *     ),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="ItemSpecDesc",
 *     type="object",
 *     @SWG\Property( property="spec_id", type="string", example="9", description="自行更改字段描述"),
 *     @SWG\Property( property="spec_name", type="string", example="图片规格", description="自行更改字段描述"),
 *     @SWG\Property( property="is_image", type="string", example="true", description="属性是否需要配置图片"),
 *     @SWG\Property( property="spec_values", type="array",
 *     		@SWG\Items( type="object",
 *     			ref="#/definitions/SpecValues",
 *     		),
 *     ),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="SpecValues",
 *     type="object",
 *     @SWG\Property(property="spec_value_id", type="integer", example=1),
 *           @SWG\Property(property="spec_custom_value_name", description="规格值自定义名称", type="string", example="S"),
 *           @SWG\Property(property="spec_value_name", type="integer", description="规格值名称", example="S"),
 *           @SWG\Property(property="item_image_url", type="string", description="商品图片地址数组", example="商品图片地址数组"),
 *           @SWG\Property(property="spec_image_url", type="string", description="规格图片地址数组", example="规格图片地址数组"),
 * )
 */
