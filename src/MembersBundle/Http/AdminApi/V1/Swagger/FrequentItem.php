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

namespace MembersBundle\Http\AdminApi\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class FrequentItem
{
    // This module is part of ShopEx EcShopX system
    /**
     *                          @SWG\Property( property="item_id", type="string", example="5291", description="商品id"),
     *                          @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                          @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                          @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                          @SWG\Property( property="store", type="string", example="104", description="商品库存"),
     *                          @SWG\Property( property="barcode", type="string", example="", description="商品条形码"),
     *                          @SWG\Property( property="sales", type="string", example="5", description="商品销量"),
     *                          @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                          @SWG\Property( property="rebate", type="string", example="0", description="单个分销金额，以分为单位"),
     *                          @SWG\Property( property="rebate_conf", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="cost_price", type="string", example="0", description="价格,单位为‘分’"),
     *                          @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                          @SWG\Property( property="point", type="string", example="0", description="商品总积分"),
     *                          @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                          @SWG\Property( property="goods_id", type="string", example="5291", description="商品集合ID"),
     *                          @SWG\Property( property="brand_id", type="string", example="1390", description="品牌id"),
     *                          @SWG\Property( property="item_name", type="string", example="社区拼团", description="商品名称"),
     *                          @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
     *                          @SWG\Property( property="item_bn", type="string", example="S5FD1AF7830A63", description="商品编码"),
     *                          @SWG\Property( property="brief", type="string", example="", description="简介"),
     *                          @SWG\Property( property="price", type="string", example="1", description="商品价格"),
     *                          @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
     *                          @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                          @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                          @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                          @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                          @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                          @SWG\Property( property="goods_brand", type="string", example="null", description="商品品牌"),
     *                          @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
     *                          @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
     *                          @SWG\Property( property="regions_id", type="string", example="null", description="地区id"),
     *                          @SWG\Property( property="brand_logo", type="string", example="null", description="品牌图片"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="商品排序"),
     *                          @SWG\Property( property="templates_id", type="string", example="1", description="运费模板id"),
     *                          @SWG\Property( property="is_default", type="string", example="true", description="商品是否为默认商品"),
     *                          @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                          @SWG\Property( property="default_item_id", type="string", example="5291", description="默认商品ID"),
     *                          @SWG\Property( property="pics", type="array",
     *                              @SWG\Items( type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrfOrTwEtYr8iahWh0W6icC572w3E2icsWbdHsbLEqt020mib55P3pJ4Ll53WaaLibSavoicF7z9vB1SSqww/0?wx_fmt=jpeg", description=""),
     *                          ),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="店铺ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                          @SWG\Property( property="date_type", type="string", example="", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"),
     *                          @SWG\Property( property="item_category", type="string", example="1718", description="商品主类目"),
     *                          @SWG\Property( property="rebate_type", type="string", example="default", description="分佣计算方式"),
     *                          @SWG\Property( property="weight", type="string", example="0", description="商品重量"),
     *                          @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                          @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间"),
     *                          @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                          @SWG\Property( property="tax_rate", type="string", example="0", description="税率"),
     *                          @SWG\Property( property="created", type="string", example="1607577464", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1608277417", description="修改时间"),
     *                          @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                          @SWG\Property( property="videos", type="string", example="", description="视频"),
     *                          @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                          @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态"),
     *                          @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                          @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                          @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                          @SWG\Property( property="profit_type", type="string", example="0", description="分润类型, 默认为0配置分润,1主类目分润,2商品指定分润(比例),3商品指定分润(金额)"),
     *                          @SWG\Property( property="profit_fee", type="string", example="0", description="分润金额,单位为分 冗余字段"),
     *                          @SWG\Property( property="is_profit", type="string", example="true", description="是否支持分润"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                          @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                          @SWG\Property( property="taxation_num", type="string", example="0", description="计税单位份数"),
     *                          @SWG\Property( property="type", type="string", example="0", description="类型"),
     *                          @SWG\Property( property="tdk_content", type="string", example="", description="tdk详情"),
     *                          @SWG\Property( property="itemId", type="string", example="5291", description=""),
     *                          @SWG\Property( property="consumeType", type="string", example="every", description=""),
     *                          @SWG\Property( property="itemName", type="string", example="社区拼团", description=""),
     *                          @SWG\Property( property="itemBn", type="string", example="S5FD1AF7830A63", description=""),
     *                          @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                          @SWG\Property( property="item_main_cat_id", type="string", example="1718", description=""),
     *                          @SWG\Property( property="item_cat_id", type="array",
     *                              @SWG\Items( type="string", example="410", description=""),
     *                          ),
     *                          @SWG\Property( property="type_labels", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="buy_num", type="string", example="1", description=""),
     */
}
