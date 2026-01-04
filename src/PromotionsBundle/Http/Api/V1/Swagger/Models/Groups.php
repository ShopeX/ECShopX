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
 *     definition="GoodsBase",
 *     type="object",
 *     @SWG\Property( property="item_id", type="string", example="33", description="id"),
 *     @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
 *     @SWG\Property( property="is_show_specimg", type="boolean", example="true", description="详情页是否显示规格图片"),
 *     @SWG\Property( property="store", type="string", example="7", description="库存"),
 *     @SWG\Property( property="barcode", type="string", example="", description=""),
 *     @SWG\Property( property="sales", type="string", example="", description="销量"),
 *     @SWG\Property( property="approve_status", type="string", example="instock", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
 *     @SWG\Property( property="cost_price", type="string", example="0", description="成本价（分）"),
 *     @SWG\Property( property="point", type="string", example="10", description="赠送积分数"),
 *     @SWG\Property( property="goods_id", type="string", example="33", description="产品id"),
 *     @SWG\Property( property="brand_id", type="string", example="3", description="品牌id"),
 *     @SWG\Property( property="item_name", type="string", example="11111", description="商品名称"),
 *     @SWG\Property( property="item_unit", type="string", example="件", description="商品计量单位"),
 *     @SWG\Property( property="item_bn", type="string", example="S600653BC3D31A", description="商品编号"),
 *     @SWG\Property( property="brief", type="string", example="", description="简介"),
 *     @SWG\Property( property="price", type="string", example="0", description="价格,单位为‘分’"),
 *     @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
 *     @SWG\Property( property="goods_series", type="string", example="", description="商品系列"),
 *     @SWG\Property( property="volume", type="string", example="", description="商品体积"),
 *     @SWG\Property( property="goods_brand", type="string", example="", description="商品品牌"),
 *     @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
 *     @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
 *     @SWG\Property( property="regions_id", type="string", example="", description="产地地区id"),
 *     @SWG\Property( property="brand_logo", type="string", example="", description="品牌图片"),
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
 *     @SWG\Property( property="video_pic_url", type="string", example="", description="视频封面图"),
 *     @SWG\Property( property="itemId", type="string", example="33", description="商品id"),
 *     @SWG\Property( property="itemName", type="string", example="11111", description="商品名称"),
 *     @SWG\Property( property="itemBn", type="string", example="S600653BC3D31A", description="商品编号"),
 *     @SWG\Property( property="item_main_cat_id", type="string", example="9", description="商品主类目id"),
 *     @SWG\Property( property="item_spec_desc", type="string", example="", description="规格描述"),
 *     @SWG\Property( property="rebate_conf", type="object",
 *     		@SWG\Property( property="type", type="string", example="money", description=""),
 *          @SWG\Property( property="value", type="object",
 *          	@SWG\Property( property="first_level", type="string", example="", description="一级"),
 *           	@SWG\Property( property="second_level", type="string", example="", description="二级"),
 *          ),
 *     ),
 *
 * )
 */

/**
 * @SWG\Definition(
 *     definition="TeamBase",
 *     type="object",
 *     @SWG\Property( property="id", type="string", example="189", description="id"),
 *     @SWG\Property( property="team_id", type="string", example="3265652000090345", description="团id号"),
 *     @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
 *     @SWG\Property( property="act_id", type="string", example="112", description="活动ID号"),
 *     @SWG\Property( property="head_mid", type="string", example="20345", description="团长会员ID"),
 *     @SWG\Property( property="begin_time", type="string", example="1607501890", description="开团时间"),
 *     @SWG\Property( property="end_time", type="string", example="1609744680", description="结束时间(根据成团时效和活动结束时间算出来的)"),
 *     @SWG\Property( property="join_person_num", type="string", example="0", description="参与人数"),
 *     @SWG\Property( property="team_status", type="string", example="3", description="状态:1.进行中2.成功3.失败"),
 *     @SWG\Property( property="group_goods_type", type="string", example="normal", description="团购活动商品类型"),
 *     @SWG\Property( property="disabled", type="string", example="true", description="是否禁用 true=禁用,false=启用"),
 *     @SWG\Property( property="created", type="string", example="1607501890", description=""),
 *     @SWG\Property( property="updated", type="string", example="1609744680", description=""),
 *     @SWG\Property( property="username", type="string", example="会员用户名", description="会员用户名"),
 * )
 */
