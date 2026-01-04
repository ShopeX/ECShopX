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

namespace GoodsBundle\Http\FrontApi\V2\Action;

use App\Http\Controllers\Controller as BaseController;
use GoodsBundle\Services\ItemsMedicineService;
use SupplierBundle\Services\SupplierItemsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GoodsBundle\Services\ItemsService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorItemsService;
use CompanysBundle\Traits\GetDefaultCur;
use KaquanBundle\Services\DiscountCardService;
use KaquanBundle\Services\KaquanService;
use OrdersBundle\Services\ShippingTemplatesService;
use PopularizeBundle\Services\SettingService;
use PromotionsBundle\Services\MarketingActivityService;
use PromotionsBundle\Traits\CheckPromotionsValid;
use OrdersBundle\Services\CartService;
use GoodsBundle\Services\ItemsRecommendService;
use CompanysBundle\Services\SettingService as ItemSettingService;
use TdksetBundle\Services\TdkGivenService;

class Items extends BaseController
{
    use CheckPromotionsValid;
    use GetDefaultCur;

    /**
     * @SWG\Definition(
     *     definition="ItemCategory",
     *     type="object",
     *     @SWG\Property( property="id", type="string", example="3", description=""),
     *     @SWG\Property( property="category_id", type="string", example="3", description=""),
     *     @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *     @SWG\Property( property="category_name", type="string", example="测试类目122", description="分类名称"),
     *     @SWG\Property( property="label", type="string", example="测试类目122", description="地区名称"),
     *     @SWG\Property( property="parent_id", type="string", example="0", description="父分类id,顶级为0"),
     *     @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *     @SWG\Property( property="path", type="string", example="3", description="路径"),
     *     @SWG\Property( property="sort", type="string", example="11111", description="排序，数字越大越靠前"),
     *     @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *     @SWG\Property( property="goods_params", type="array",
     *         @SWG\Items( type="string", example="undefined", description=""),
     *     ),
     *     @SWG\Property( property="goods_spec", type="array",
     *         @SWG\Items( type="string", example="undefined", description=""),
     *     ),
     *     @SWG\Property( property="category_level", type="string", example="1", description="等级"),
     *     @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *     @SWG\Property( property="crossborder_tax_rate", type="string", example="12", description="跨境税率，百分比，小数点2位"),
     *     @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *     @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *     @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *     @SWG\Property( property="children", type="array",
     *         @SWG\Items( type="object",
     *             @SWG\Property( property="id", type="string", example="4", description=""),
     *             @SWG\Property( property="category_id", type="string", example="4", description=""),
     *             @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *             @SWG\Property( property="category_name", type="string", example="测试类目1-1", description="类目名称"),
     *             @SWG\Property( property="label", type="string", example="测试类目1-1", description="地区名称"),
     *             @SWG\Property( property="parent_id", type="string", example="3", description="父分类id,顶级为0"),
     *             @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *             @SWG\Property( property="path", type="string", example="3,4", description="路径"),
     *             @SWG\Property( property="sort", type="string", example="22222222222222", description="排序，数字越大越靠前"),
     *             @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *             @SWG\Property( property="goods_params", type="array",
     *                 @SWG\Items( type="string", example="undefined", description=""),
     *             ),
     *             @SWG\Property( property="goods_spec", type="array",
     *                 @SWG\Items( type="string", example="undefined", description=""),
     *             ),
     *             @SWG\Property( property="category_level", type="string", example="2", description="等级"),
     *             @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *             @SWG\Property( property="crossborder_tax_rate", type="string", example="15.56", description="跨境税率，百分比，小数点2位"),
     *             @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *             @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *             @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *             @SWG\Property( property="children", type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property( property="id", type="string", example="5", description=""),
     *                     @SWG\Property( property="category_id", type="string", example="5", description=""),
     *                     @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                     @SWG\Property( property="category_name", type="string", example="测试类目1-1-1", description="名称"),
     *                     @SWG\Property( property="label", type="string", example="测试类目1-1-1", description="地区名称"),
     *                     @SWG\Property( property="parent_id", type="string", example="4", description="父级id, 0为顶级"),
     *                     @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                     @SWG\Property( property="path", type="string", example="3,4,5", description="路径"),
     *                     @SWG\Property( property="sort", type="string", example="0", description="排序，数字越大越靠前"),
     *                     @SWG\Property( property="is_main_category", type="string", example="true", description="是否为商品主类目"),
     *                     @SWG\Property( property="goods_params", type="string", example="2827", description="商品参数"),
     *                     @SWG\Property( property="goods_spec", type="array",
     *                         @SWG\Items( type="string", example="1346", description=""),
     *                     ),
     *                     @SWG\Property( property="category_level", type="string", example="3", description="等级"),
     *                     @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                     @SWG\Property( property="crossborder_tax_rate", type="string", example="15.4", description="跨境税率，百分比，小数点2位"),
     *                     @SWG\Property( property="created", type="string", example="1560927610", description=""),
     *                     @SWG\Property( property="updated", type="string", example="1606369584", description="修改时间"),
     *                     @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                     @SWG\Property( property="level", type="string", example="2", description=""),
     *                  ),
     *             ),
     *             @SWG\Property( property="level", type="string", example="1", description=""),
     *          ),
     *     ),
     *     @SWG\Property( property="level", type="string", example="0", description="")
     *  )
     */

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/items/{item_id}",
     *     summary="获取商品详情",
     *     tags={"商品"},
     *     description="获取商品详情",
     *     operationId="getItemsDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="item_id", in="path", description="商品id", required=true, type="integer" ),
     *     @SWG\Parameter( name="goods_id", in="query", description="产品ID，多规格商品不同规格的goods_id是一样的", type="integer" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="门店ID", type="integer" ),
     *     @SWG\Parameter( name="isShopScreen", in="query", description="是否大屏显示", type="integer" ),
     *     @SWG\Parameter( name="is_tdk", in="query", description="是否获取tdk信息，0不获取,1获取", type="number" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="item_id", type="string", example="5016", description="商品id"),
     *                  @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                  @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                  @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                  @SWG\Property( property="store", type="string", example="986", description="库存"),
     *                  @SWG\Property( property="barcode", type="string", example="12312332，fsdaflsdjf，dd", description="商品条形码"),
     *                  @SWG\Property( property="sales", type="string", example="null", description="商品销量"),
     *                  @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                  @SWG\Property( property="rebate", type="string", example="0", description="单个分销金额，以分为单位"),
     *                  @SWG\Property( property="rebate_conf", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="cost_price", type="string", example="0", description="价格,单位为‘分’"),
     *                  @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                  @SWG\Property( property="point", type="string", example="0", description="积分"),
     *                  @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                  @SWG\Property( property="goods_id", type="string", example="5016", description="商品集合ID"),
     *                  @SWG\Property( property="brand_id", type="string", example="1350", description="品牌id"),
     *                  @SWG\Property( property="item_name", type="string", example="助力测试", description="商品名称"),
     *                  @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
     *                  @SWG\Property( property="item_bn", type="string", example="1345646556", description="商品编号"),
     *                  @SWG\Property( property="brief", type="string", example="", description=""),
     *                  @SWG\Property( property="price", type="string", example="7000", description="价格,单位为‘分’"),
     *                  @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
     *                  @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                  @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                  @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                  @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                  @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                  @SWG\Property( property="goods_brand", type="string", example="测试498", description="商品品牌"),
     *                  @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
     *                  @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
     *                  @SWG\Property( property="regions_id", type="string", example="null", description=""),
     *                  @SWG\Property( property="brand_logo", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="sort", type="string", example="0", description="排序，数字越大越靠前"),
     *                  @SWG\Property( property="templates_id", type="string", example="94", description="运费模板id"),
     *                  @SWG\Property( property="is_default", type="string", example="true", description="是否默认货币"),
     *                  @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                  @SWG\Property( property="default_item_id", type="string", example="5016", description="默认商品ID"),
     *                  @SWG\Property( property="pics", type="array",
     *                      @SWG\Items( type="string", example="http://bbctest.aixue7.com/image/1/2020/06/27/a66fee6b3c2d53a1405b265bcf64e942eM19rY4mi8iIptldOR1aIKPolvnUI3a2", description=""),
     *                  ),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                  @SWG\Property( property="date_type", type="string", example="", description=""),
     *                  @SWG\Property( property="item_category", type="array",
     *                      @SWG\Items( type="string", example="1603", description=""),
     *                  ),
     *                  @SWG\Property( property="rebate_type", type="string", example="default", description="返佣模式"),
     *                  @SWG\Property( property="weight", type="string", example="10", description="商品重量"),
     *                  @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                  @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间"),
     *                  @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                  @SWG\Property( property="tax_rate", type="string", example="0", description="税率"),
     *                  @SWG\Property( property="created", type="string", example="1601018998", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1607327334", description="修改时间"),
     *                  @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                  @SWG\Property( property="videos", type="string", example="", description="视频"),
     *                  @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                  @SWG\Property( property="purchase_agreement", type="string", example="", description="购买协议"),
     *                  @SWG\Property( property="intro", type="string", example="助力测试......", description="图文详情"),
     *                  @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                  @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                  @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                  @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                  @SWG\Property( property="profit_type", type="string", example="0", description=""),
     *                  @SWG\Property( property="profit_fee", type="string", example="0", description="分润金额,单位为分 冗余字段"),
     *                  @SWG\Property( property="is_profit", type="string", example="true", description="是否支持分润"),
     *                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                  @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                  @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                  @SWG\Property( property="taxation_num", type="string", example="0", description="计税单位份数"),
     *                  @SWG\Property( property="type", type="string", example="0", description=""),
     *                  @SWG\Property( property="tdk_content", type="string", example="{'title':'1','mate_description':'2','mate_keywords':'3,3'}", description="tdk详情"),
     *                  @SWG\Property( property="itemId", type="string", example="5016", description=""),
     *                  @SWG\Property( property="consumeType", type="string", example="every", description=""),
     *                  @SWG\Property( property="itemName", type="string", example="助力测试", description=""),
     *                  @SWG\Property( property="itemBn", type="string", example="1345646556", description=""),
     *                  @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                  @SWG\Property( property="item_main_cat_id", type="string", example="5", description=""),
     *                  @SWG\Property( property="type_labels", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="spec_pics", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="item_params", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="item_spec_desc", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="spec_images", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="spec_items", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="attribute_ids", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="attr_values_custom", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="item_category_main", type="array",
     *                      @SWG\Items(ref="#/definitions/ItemCategory"),
     *                  ),
     *                  @SWG\Property( property="item_category_info", type="array",
     *                      @SWG\Items(ref="#/definitions/ItemCategory"),
     *                  ),
     *                  @SWG\Property( property="videos_url", type="string", example="", description=""),
     *                  @SWG\Property( property="distributor_sale_status", type="string", example="true", description=""),
     *                  @SWG\Property( property="item_total_store", type="string", example="986", description=""),
     *                  @SWG\Property( property="distributor_info", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="origincountry_name", type="string", example="", description="产地国名称"),
     *                  @SWG\Property( property="origincountry_img_url", type="string", example="", description="产地国国旗"),
     *                  @SWG\Property( property="cross_border_tax", type="string", example="0", description="商品跨境税费"),
     *                  @SWG\Property( property="activity_type", type="string", example="normal", description="活动类型 full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购,group拼团,seckill秒杀,package打包,limited_time_sale限时特惠"),
     *                  @SWG\Property( property="guide_title_desc", type="string", example="开通vip的引导文本，更多优惠等你来享受哦！", description=""),
     *                  @SWG\Property( property="grade_name", type="string", example="", description="等级名称"),
     *                  @SWG\Property( property="is_vip_grade", type="string", example="false", description=""),
     *                  @SWG\Property( property="member_price", type="string", example="", description=""),
     *                  @SWG\Property( property="vipgrade_guide_title", type="object",
     *                          @SWG\Property( property="vipgrade_desc", type="string", example="一般付费", description=""),
     *                          @SWG\Property( property="gradeDiscount", type="string", example="8", description=""),
     *                          @SWG\Property( property="guide_title_desc", type="string", example="开通vip的引导文本，更多优惠等你来享受哦！", description=""),
     *                          @SWG\Property( property="vipgrade_name", type="string", example="一般付费", description=""),
     *                  ),
     *                  @SWG\Property( property="promotion_activity", type="string", example="null", description=""),
     *                  @SWG\Property( property="promoter_price", type="string", example="0", description=""),
     *                  @SWG\Property( property="cur", type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description=""),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="currency", type="string", example="CNY", description="货币类型"),
     *                          @SWG\Property( property="title", type="string", example="中国人民币", description="货币描述"),
     *                          @SWG\Property( property="symbol", type="string", example="￥", description="货币符号"),
     *                          @SWG\Property( property="rate", type="string", example="1", description="货币汇率(与人民币)"),
     *                          @SWG\Property( property="is_default", type="string", example="true", description="是否默认货币 "),
     *                          @SWG\Property( property="use_platform", type="string", example="normal", description="适用端。可选值为 service,normal"),
     *                  ),
     *                  @SWG\Property( property="kaquan_list", type="object",
     *                          @SWG\Property( property="total_count", type="string", example="6", description=""),
     *                          @SWG\Property( property="pagers", type="object",
     *                                  @SWG\Property( property="total", type="string", example="6", description=""),
     *                          ),
     *                          @SWG\Property( property="list", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="card_id", type="string", example="459", description="卡券id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="card_type", type="string", example="discount", description="卡券类型，可选值有，discount:折扣券;cash:代金券;gift:兑换券"),
     *                                  @SWG\Property( property="brand_name", type="string", example="null", description="商户名称"),
     *                                  @SWG\Property( property="logo_url", type="string", example="null", description="卡券商户 logo"),
     *                                  @SWG\Property( property="title", type="string", example="测试低额度", description="标题"),
     *                                  @SWG\Property( property="color", type="string", example="#000000", description="券颜色值"),
     *                                  @SWG\Property( property="notice", type="string", example="null", description="卡券使用提醒,最大16汉字"),
     *                                  @SWG\Property( property="description", type="string", example="1234", description="描述"),
     *                                  @SWG\Property( property="date_type", type="string", example="DATE_TYPE_FIX_TIME_RANGE", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"),
     *                                  @SWG\Property( property="begin_date", type="string", example="1607961600", description="有效期开始时间"),
     *                                  @SWG\Property( property="end_date", type="string", example="1612022400", description="有效期结束时间 | 会员到期时间"),
     *                                  @SWG\Property( property="fixed_term", type="string", example="null", description="有效期的有效天数"),
     *                                  @SWG\Property( property="service_phone", type="string", example="null", description="客服电话"),
     *                                  @SWG\Property( property="center_title", type="string", example="null", description="卡券顶部居中的按钮，仅在卡券状态正常(可以核销)时显示"),
     *                                  @SWG\Property( property="center_sub_title", type="string", example="null", description="显示在入口下方的提示语"),
     *                                  @SWG\Property( property="center_url", type="string", example="null", description="顶部居中的url"),
     *                                  @SWG\Property( property="custom_url_name", type="string", example="null", description="自定义跳转外链的入口名字"),
     *                                  @SWG\Property( property="custom_url", type="string", example="null", description="自定义跳转的URL"),
     *                                  @SWG\Property( property="custom_url_sub_title", type="string", example="null", description="显示在入口右侧的提示语"),
     *                                  @SWG\Property( property="promotion_url_name", type="string", example="null", description="营销场景的自定义入口名称"),
     *                                  @SWG\Property( property="promotion_url", type="string", example="null", description="营销场景的自定义入口url"),
     *                                  @SWG\Property( property="promotion_url_sub_title", type="string", example="null", description="营销入口右侧的提示语"),
     *                                  @SWG\Property( property="get_limit", type="string", example="1", description="每人可领券的数量限制"),
     *                                  @SWG\Property( property="use_limit", type="string", example="null", description="每人可核销的数量限制"),
     *                                  @SWG\Property( property="can_share", type="string", example="false", description="卡券领取页面是否可分享"),
     *                                  @SWG\Property( property="can_give_friend", type="string", example="false", description="卡券是否可转赠"),
     *                                  @SWG\Property( property="abstract", type="string", example="null", description="封面摘要"),
     *                                  @SWG\Property( property="icon_url_list", type="string", example="null", description="封面图片"),
     *                                  @SWG\Property( property="text_image_list", type="string", example="N;", description="图文列表(DC2Type:array)"),
     *                                  @SWG\Property( property="time_limit", type="string", example="null", description="使用时段限制(DC2Type:array)"),
     *                                  @SWG\Property( property="gift", type="string", example="null", description="兑换券兑换内容名称"),
     *                                  @SWG\Property( property="default_detail", type="string", example="null", description="优惠券优惠详情"),
     *                                  @SWG\Property( property="discount", type="string", example="90", description="折扣值"),
     *                                  @SWG\Property( property="least_cost", type="string", example="0", description="代金券起用金额"),
     *                                  @SWG\Property( property="reduce_cost", type="string", example="0", description="代金券减免金额 or 兑换券起用金额"),
     *                                  @SWG\Property( property="deal_detail", type="string", example="null", description="团购券详情"),
     *                                  @SWG\Property( property="accept_category", type="string", example="null", description="指定可用的商品类目,代金券专用"),
     *                                  @SWG\Property( property="reject_category", type="string", example="null", description="指定不可用的商品类目,代金券专用"),
     *                                  @SWG\Property( property="object_use_for", type="string", example="null", description="购买xx可用类型门槛，仅用于兑换"),
     *                                  @SWG\Property( property="can_use_with_other_discount", type="string", example="false", description="是否可与其他优惠共享"),
     *                                  @SWG\Property( property="quantity", type="string", example="99999", description="卡券总库存"),
     *                                  @SWG\Property( property="use_all_shops", type="string", example="1", description="是否适用所有门店"),
     *                                  @SWG\Property( property="rel_shops_ids", type="string", example=",", description="适用的门店"),
     *                                  @SWG\Property( property="created", type="string", example="1608001062", description=""),
     *                                  @SWG\Property( property="updated", type="string", example="1608001062", description=""),
     *                                  @SWG\Property( property="use_scenes", type="string", example="ONLINE", description="核销场景。可选值有，ONLINE:线上商城(兑换券不可使用);QUICK:快捷买单(兑换券不可使用);SWEEP:门店支付(扫码核销);SELF:到店支付(自助核销)"),
     *                                  @SWG\Property( property="receive", type="string", example="true", description="是否前台直接领取"),
     *                                  @SWG\Property( property="self_consume_code", type="string", example="0", description="自助核销验证码"),
     *                                  @SWG\Property( property="use_platform", type="string", example="mall", description=""),
     *                                  @SWG\Property( property="most_cost", type="string", example="99999900", description="代金券最高消费限额"),
     *                                  @SWG\Property( property="distributor_id", type="string", example=",", description="分销商id"),
     *                                  @SWG\Property( property="use_bound", type="string", example="0", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                                  @SWG\Property( property="tag_ids", type="string", example="null", description="标签id集合"),
     *                                  @SWG\Property( property="brand_ids", type="string", example="null", description="品牌id集合"),
     *                                  @SWG\Property( property="apply_scope", type="string", example="", description="适用范围"),
     *                                  @SWG\Property( property="card_code", type="string", example="null", description="优惠券模板ID-第三方使用"),
     *                                  @SWG\Property( property="card_rule_code", type="string", example="", description="优惠券规则ID-第三方使用"),
     *                                  @SWG\Property( property="get_num", type="string", example="7", description="被领取数量"),
     *                                  @SWG\Property( property="use_num", type="string", example="7", description=""),
     *                               ),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="no_post", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="rate_status", type="string", example="false", description=""),
     *                  @SWG\Property( property="recommend_items", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="tdk_data", type="object",
     *                          @SWG\Property( property="title", type="string", example="hu-测试,52803,dermGO SENSITIVE敏感肌改善抗衰精华30ml,1603,", description="标题"),
     *                          @SWG\Property( property="mate_description", type="string", example="hu-测试,1603,dermGO SENSITIVE敏感肌改善抗衰精华30ml,52803,", description="描述"),
     *                          @SWG\Property( property="mate_keywords", type="string", example="dermGO SENSITIVE敏感肌改善抗衰精华30ml,hu-测试,1603,52803,", description="关键字"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsDetail($item_id, Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $api_from = $authInfo['api_from'];
        $woa_appid = $authInfo['woa_appid'];

        $view_mode = $request->input('view_mode', '');
        $distributorId = $request->input('distributor_id', 0);
        if ($distributorId == 'undefined' || $distributorId == 'null') {
            $distributorId = 0;
        }
        if($view_mode == 'preview'){
            //预览模式，直接读取供应商商品数据
            $itemsService = new SupplierItemsService();
        }else{
            $itemsService = new ItemsService();
        }
        // 如果传入goods_id那么则通过，goods_id获取到item_id
        // 防止链接中的item_id已经失效
        $goodsId = $request->input('goods_id');
        if ($goodsId) {
            $tempItemInfo = $itemsService->getInfo(['goods_id' => $goodsId, 'audit_status' => 'approved', 'is_default' => true, 'company_id' => $company_id]);
            if ($tempItemInfo) {
                $item_id = $tempItemInfo['item_id'];
            } else {
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_not_exist_or_offline'));
            }
        } else {
            $tempItemInfo = $itemsService->getInfo(['item_id' => $item_id, 'audit_status' => 'approved', 'company_id' => $company_id]);
            if (!$tempItemInfo) {
//                return $this->response->array(['item_id'=>0]);
                throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_not_exist_or_offline'));
            }
        }

        $validator = app('validator')->make(['item_id' => $item_id], [
            'item_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->response->array(['item_id' => 0]);
        }

        $distributorId = $request->input('distributor_id', 0);
        if ($distributorId == 'undefined' || $distributorId == 'null') {
            $distributorId = 0;
        }

        $promotionActivityData = $this->getCurrentActivityByItemId($company_id, $item_id, $distributorId);
        // 当前商品在进行活动
        $limitItemIds = array();
        if ($promotionActivityData) {
            $limitItemIds = !in_array($promotionActivityData['activity_type'], ['limited_buy']) ? array_column($promotionActivityData['list'], 'item_id') : [];
        }

        if ($limitItemIds && !in_array($item_id, $limitItemIds)) {
            $item_id = $limitItemIds[0];
        }

        //如果有分销商id。则获取店铺商品详情
        if ($distributorId) {
            $distributorItemsService = new DistributorItemsService();
            $result = $distributorItemsService->getValidDistributorItemInfo($company_id, $item_id, $distributorId, $woa_appid, $limitItemIds);

            $isShopScreen = $request->input('isShopScreen', 0);
            if ($isShopScreen) {
                // 替换商品库存为总部和门店总库存
                $result['store'] += $result['logistics_store'] ?? 0;
                $totalStore = $result['item_total_store'];
                $result['item_total_store'] = $result['store'];
                if (isset($result['spec_items']) && $result['spec_items']) {
                    foreach ($result['spec_items'] as $key => $row) {
                        $result['spec_items'][$key]['store'] += $row['logistics_store'] ?? 0;
                        $totalStore += $row['logistics_store'] ?? 0;
                    }
                    $result['item_total_store'] = $totalStore;
                }
            }
        } else {
            $result = $itemsService->getItemsDetail($item_id, $woa_appid, $limitItemIds, $company_id);
        }
        if (!is_array($result['intro'])) {
            json_decode($result['intro']);

            if (json_last_error() === JSON_ERROR_NONE) {
                $result['intro'] = json_decode($result['intro'], true);
            }
        }

        if (!$result) {
            return $this->response->array(['item_id' => 0]);
        }
        $store = $result['item_total_store'] ?? $result['store'];
        $marketingService = new MarketingActivityService();
        // 如果参加活动则没有推广金额
        if ($promotionActivityData && in_array($promotionActivityData['activity_type'], ['limited_time_sale', 'seckill', 'group'])) {
            //活动商品类型，秒杀或者拼团
            $result['activity_type'] = $promotionActivityData['activity_type'];

            $result = $this->__replaceItemInfo($result, $promotionActivityData['list'][$item_id]);
            $result['item_total_store'] = $result['store'];
            // 替换多规格店铺商品信息
            if (isset($result['spec_items']) && $result['spec_items']) {
                $totalStore = 0;
                foreach ($result['spec_items'] as $key => $row) {
                    $row['activity_type'] = $result['activity_type'];
                    $activityItemInfo = $promotionActivityData['list'][$row['item_id']] ?? [];
                    if (!$activityItemInfo) {
                        unset($result['spec_items'][$key]);
                    } else {
                        $result['spec_items'][$key] = $this->__replaceItemInfo($row, $activityItemInfo);
                        $totalStore += $result['spec_items'][$key]['store'];
                    }
                }
                $sortPrice = array_column($result['spec_items'], 'act_price');
                array_multisort($sortPrice, SORT_ASC, $result['spec_items']);
                $firstIems = reset($result['spec_items']);
                $result = array_merge($result, $firstIems);
                $result['item_total_store'] = $totalStore;
            }

            if ($promotionActivityData['activity_type'] == 'group') {
                $result['groups_list'] = $promotionActivityData['groups_list'] ?? [];
            }
            $userId = $authInfo['user_id'] ?? 0;

            if ($promotionActivityData['activity_type'] == 'limited_time_sale') {
                //获取包含该商品的满折满减营销活动
                $marketingService = new MarketingActivityService();
                $marketingActivity = $marketingService->getValidMarketingActivity($company_id, '', $userId, '', $distributorId, '', $result['goods_id']);
                $result['promotion_activity'] = $marketingActivity;
            }

            $result['activity_info'] = $promotionActivityData['info'];
            $result['promoter_price'] = 0;
        } else {
            //普通商品，不是活动商品
            $result['activity_type'] = 'normal';

            // 计算会员价
            $userId = $authInfo['user_id'] ?? 0;
            $result = $itemsService->getItemsMemberPriceByUserId($result, $userId, $company_id);
            //获取包含该商品的满折满减营销活动
            $marketingActivity = $marketingService->getValidMarketingActivity($company_id, '', $userId, '', $distributorId, '', $result['goods_id']);
            $result['promotion_activity'] = $marketingActivity;

            $settingService = new SettingService();
            $config = $settingService->getConfig($result['company_id']);
            if ($config['popularize_ratio']['type'] == 'profit') {
                $ratio = $config['popularize_ratio']['profit']['first_level']['ratio'];
                $result['promoter_price'] = bcdiv(bcmul(bcsub($result['price'], $result['cost_price']), $ratio), 100, 2);
                $result['promoter_price'] = ($result['promoter_price'] > 0) ? $result['promoter_price'] : 0;
            } else {
                $ratio = $config['popularize_ratio']['order_money']['first_level']['ratio'];
                $result['promoter_price'] = bcdiv(bcmul($result['price'], $ratio), 100);
            }
            $result['promoter_price'] = ($result['promoter_price'] >= 1) ? $result['promoter_price'] : 0;

            //获取系统货币默认配置
            $result['cur'] = $this->getCur($company_id);
        }

        // 会员优先购活动
        $memberpreferenceActivity = $marketingService->getValidMemberpreferenceActivity($company_id, '', $userId, '', $result['goods_id']);
        $memberpreferenceActivity and $result['memberpreference_activity'] = $memberpreferenceActivity[0];

        $kaquanService = new KaquanService(new DiscountCardService());
        $kqfilter = [
            'default_item_id' => $result['default_item_id'],
            'item_main_cat_id' => $result['item_main_cat_id'],
            'brand_id' => $result['brand_id'],
            'company_id' => $company_id,
            'item_id' => $item_id,
            'receive' => 'true',
            'distributor_id' => $distributorId ? $distributorId : ($result['distributor_id'] ?? 0)
        ];

        $result['kaquan_list'] = $kaquanService->getKaquanListByItemId($kqfilter);
        $shippingTemplatesService = new ShippingTemplatesService();
        $express = $shippingTemplatesService->getInfo($result['templates_id'], $company_id);
        $result['no_post'] = [];
        if ($express['nopost_conf'] ?? []) {
            $result['no_post'] = json_decode($express['nopost_conf'], true);
        }
        // 限购活动
        if ($promotionActivityData && in_array($promotionActivityData['activity_type'], ['limited_buy'])) {
          if (in_array($authInfo['grade_id'] ?? 0, $promotionActivityData['info']['valid_grade'])) {
            $result['activity_type'] = $promotionActivityData['activity_type'];
            $result['activity_info'] = $promotionActivityData['info'];
          }
        }

        $result['store'] = $result['item_total_store'] ?? $result['store'];
        $result['sales'] = $result['item_total_sales'] ?? $result['sales'];

        ##团购活动库存更新
        if (!empty($promotionActivityData['activity_type']) && $promotionActivityData['activity_type'] == 'group') {
            if (isset($promotionActivityData['list'][$item_id]['store'])) {
                $result['store'] = min($result['store'], $store);
            }
        }

        $result['rate_status'] = $this->getGoodsRateSettingStatus($result['company_id']);

        //获取库存/销量 显示设置
        $itemSettingService = new ItemSettingService();
        $result['sales_setting'] = $itemSettingService->getItemSalesSetting($company_id)['item_sales_status'];
        $result['store_setting'] = $itemSettingService->getItemStoreSetting($company_id)['item_store_status'];
        // 获取推荐商品 end

        // tdk 信息
        if ($request->input('is_tdk') == 1) {
            $TdkGiven = new TdkGivenService();
            $Tdk_info = $TdkGiven->getInfo('details', $company_id);
            $Tdk_data = $TdkGiven->getData($Tdk_info, $result);

            // 判断商品信息是否带有tdk信息
            if (!empty($result['tdk_content'])) {
                $tdk_content = json_decode($result['tdk_content'], true);
                if (!empty($tdk_content['title'])) {
                    $Tdk_data['title'] = $tdk_content['title'];
                }
                if (!empty($tdk_content['mate_description'])) {
                    $Tdk_data['mate_description'] = $tdk_content['mate_description'];
                }
                if (!empty($tdk_content['mate_keywords'])) {
                    $Tdk_data['mate_keywords'] = $tdk_content['mate_keywords'];
                }
            }
            $result['tdk_data'] = $Tdk_data;
        }

        // 获取药品数据
        $result = (new ItemsMedicineService())->getItemsMedicineData([$result])[0];
        foreach ($result['spec_items'] as $idx => $sp){
            usort($sp['item_spec'], function ($a, $b) {
                return $b['spec_id'] <=> $a['spec_id'];
            });
            $custom_spec_ids = [];
            $custom_spec_names = [];
            foreach ($sp['item_spec'] as $item_spec) {
                $custom_spec_ids[] = $item_spec['spec_value_id'];
                $custom_spec_names[] = $item_spec['spec_value_name'];
            }
            $custom_spec_ids = implode('-', $custom_spec_ids);
            $custom_spec_names = implode('、', $custom_spec_names);
            $sp['custom_spec_id'] = $custom_spec_ids;
            $sp['custom_spec_name'] = $custom_spec_names;
            $result['spec_items'][$idx] = $sp;
        }
        $result['itemName'] = $result['item_name'];


        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/newitems",
     *     summary="获取商品详情(新)",
     *     tags={"商品"},
     *     description="获取商品详情(新)",
     *     operationId="getItemsDetailNew",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="item_id", in="query", description="商品id", required=true, type="integer" ),
     *     @SWG\Parameter( name="goods_id", in="query", description="产品ID，多规格商品不同规格的goods_id是一样的", type="integer" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="门店ID", type="integer" ),
     *     @SWG\Parameter( name="isShopScreen", in="query", description="是否大屏显示", type="integer" ),
     *     @SWG\Parameter( name="is_tdk", in="query", description="是否获取tdk信息，0不获取,1获取", type="number" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="item_id", type="string", example="5016", description="商品id"),
     *                  @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                  @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                  @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                  @SWG\Property( property="store", type="string", example="986", description="库存"),
     *                  @SWG\Property( property="barcode", type="string", example="12312332，fsdaflsdjf，dd", description="商品条形码"),
     *                  @SWG\Property( property="sales", type="string", example="null", description="商品销量"),
     *                  @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                  @SWG\Property( property="rebate", type="string", example="0", description="单个分销金额，以分为单位"),
     *                  @SWG\Property( property="rebate_conf", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="cost_price", type="string", example="0", description="价格,单位为‘分’"),
     *                  @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                  @SWG\Property( property="point", type="string", example="0", description="积分"),
     *                  @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                  @SWG\Property( property="goods_id", type="string", example="5016", description="商品集合ID"),
     *                  @SWG\Property( property="brand_id", type="string", example="1350", description="品牌id"),
     *                  @SWG\Property( property="item_name", type="string", example="助力测试", description="商品名称"),
     *                  @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
     *                  @SWG\Property( property="item_bn", type="string", example="1345646556", description="商品编号"),
     *                  @SWG\Property( property="brief", type="string", example="", description=""),
     *                  @SWG\Property( property="price", type="string", example="7000", description="价格,单位为‘分’"),
     *                  @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
     *                  @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                  @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                  @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                  @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                  @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                  @SWG\Property( property="goods_brand", type="string", example="测试498", description="商品品牌"),
     *                  @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
     *                  @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
     *                  @SWG\Property( property="regions_id", type="string", example="null", description=""),
     *                  @SWG\Property( property="brand_logo", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="sort", type="string", example="0", description="排序，数字越大越靠前"),
     *                  @SWG\Property( property="templates_id", type="string", example="94", description="运费模板id"),
     *                  @SWG\Property( property="is_default", type="string", example="true", description="是否默认货币"),
     *                  @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                  @SWG\Property( property="default_item_id", type="string", example="5016", description="默认商品ID"),
     *                  @SWG\Property( property="pics", type="array",
     *                      @SWG\Items( type="string", example="http://bbctest.aixue7.com/image/1/2020/06/27/a66fee6b3c2d53a1405b265bcf64e942eM19rY4mi8iIptldOR1aIKPolvnUI3a2", description=""),
     *                  ),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                  @SWG\Property( property="date_type", type="string", example="", description=""),
     *                  @SWG\Property( property="item_category", type="array",
     *                      @SWG\Items( type="string", example="1603", description=""),
     *                  ),
     *                  @SWG\Property( property="rebate_type", type="string", example="default", description="返佣模式"),
     *                  @SWG\Property( property="weight", type="string", example="10", description="商品重量"),
     *                  @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                  @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间"),
     *                  @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                  @SWG\Property( property="tax_rate", type="string", example="0", description="税率"),
     *                  @SWG\Property( property="created", type="string", example="1601018998", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1607327334", description="修改时间"),
     *                  @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                  @SWG\Property( property="videos", type="string", example="", description="视频"),
     *                  @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                  @SWG\Property( property="purchase_agreement", type="string", example="", description="购买协议"),
     *                  @SWG\Property( property="intro", type="string", example="助力测试......", description="图文详情"),
     *                  @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                  @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                  @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                  @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                  @SWG\Property( property="profit_type", type="string", example="0", description=""),
     *                  @SWG\Property( property="profit_fee", type="string", example="0", description="分润金额,单位为分 冗余字段"),
     *                  @SWG\Property( property="is_profit", type="string", example="true", description="是否支持分润"),
     *                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                  @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                  @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                  @SWG\Property( property="taxation_num", type="string", example="0", description="计税单位份数"),
     *                  @SWG\Property( property="type", type="string", example="0", description=""),
     *                  @SWG\Property( property="tdk_content", type="string", example="{'title':'1','mate_description':'2','mate_keywords':'3,3'}", description="tdk详情"),
     *                  @SWG\Property( property="itemId", type="string", example="5016", description=""),
     *                  @SWG\Property( property="consumeType", type="string", example="every", description=""),
     *                  @SWG\Property( property="itemName", type="string", example="助力测试", description=""),
     *                  @SWG\Property( property="itemBn", type="string", example="1345646556", description=""),
     *                  @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                  @SWG\Property( property="item_main_cat_id", type="string", example="5", description=""),
     *                  @SWG\Property( property="type_labels", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="spec_pics", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="item_params", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="item_spec_desc", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="spec_images", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="spec_items", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="attribute_ids", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="attr_values_custom", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="item_category_main", type="array",
     *                      @SWG\Items(ref="#/definitions/ItemCategory"),
     *                  ),
     *                  @SWG\Property( property="item_category_info", type="array",
     *                      @SWG\Items(ref="#/definitions/ItemCategory"),
     *                  ),
     *                  @SWG\Property( property="videos_url", type="string", example="", description=""),
     *                  @SWG\Property( property="distributor_sale_status", type="string", example="true", description=""),
     *                  @SWG\Property( property="item_total_store", type="string", example="986", description=""),
     *                  @SWG\Property( property="distributor_info", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="origincountry_name", type="string", example="", description="产地国名称"),
     *                  @SWG\Property( property="origincountry_img_url", type="string", example="", description="产地国国旗"),
     *                  @SWG\Property( property="cross_border_tax", type="string", example="0", description="商品跨境税费"),
     *                  @SWG\Property( property="activity_type", type="string", example="normal", description="活动类型 full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购,group拼团,seckill秒杀,package打包,limited_time_sale限时特惠"),
     *                  @SWG\Property( property="guide_title_desc", type="string", example="开通vip的引导文本，更多优惠等你来享受哦！", description=""),
     *                  @SWG\Property( property="grade_name", type="string", example="", description="等级名称"),
     *                  @SWG\Property( property="is_vip_grade", type="string", example="false", description=""),
     *                  @SWG\Property( property="member_price", type="string", example="", description=""),
     *                  @SWG\Property( property="vipgrade_guide_title", type="object",
     *                          @SWG\Property( property="vipgrade_desc", type="string", example="一般付费", description=""),
     *                          @SWG\Property( property="gradeDiscount", type="string", example="8", description=""),
     *                          @SWG\Property( property="guide_title_desc", type="string", example="开通vip的引导文本，更多优惠等你来享受哦！", description=""),
     *                          @SWG\Property( property="vipgrade_name", type="string", example="一般付费", description=""),
     *                  ),
     *                  @SWG\Property( property="promotion_activity", type="string", example="null", description=""),
     *                  @SWG\Property( property="promoter_price", type="string", example="0", description=""),
     *                  @SWG\Property( property="cur", type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description=""),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="currency", type="string", example="CNY", description="货币类型"),
     *                          @SWG\Property( property="title", type="string", example="中国人民币", description="货币描述"),
     *                          @SWG\Property( property="symbol", type="string", example="￥", description="货币符号"),
     *                          @SWG\Property( property="rate", type="string", example="1", description="货币汇率(与人民币)"),
     *                          @SWG\Property( property="is_default", type="string", example="true", description="是否默认货币 "),
     *                          @SWG\Property( property="use_platform", type="string", example="normal", description="适用端。可选值为 service,normal"),
     *                  ),
     *                  @SWG\Property( property="kaquan_list", type="object",
     *                          @SWG\Property( property="total_count", type="string", example="6", description=""),
     *                          @SWG\Property( property="pagers", type="object",
     *                                  @SWG\Property( property="total", type="string", example="6", description=""),
     *                          ),
     *                          @SWG\Property( property="list", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="card_id", type="string", example="459", description="卡券id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="card_type", type="string", example="discount", description="卡券类型，可选值有，discount:折扣券;cash:代金券;gift:兑换券"),
     *                                  @SWG\Property( property="brand_name", type="string", example="null", description="商户名称"),
     *                                  @SWG\Property( property="logo_url", type="string", example="null", description="卡券商户 logo"),
     *                                  @SWG\Property( property="title", type="string", example="测试低额度", description="标题"),
     *                                  @SWG\Property( property="color", type="string", example="#000000", description="券颜色值"),
     *                                  @SWG\Property( property="notice", type="string", example="null", description="卡券使用提醒,最大16汉字"),
     *                                  @SWG\Property( property="description", type="string", example="1234", description="描述"),
     *                                  @SWG\Property( property="date_type", type="string", example="DATE_TYPE_FIX_TIME_RANGE", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"),
     *                                  @SWG\Property( property="begin_date", type="string", example="1607961600", description="有效期开始时间"),
     *                                  @SWG\Property( property="end_date", type="string", example="1612022400", description="有效期结束时间 | 会员到期时间"),
     *                                  @SWG\Property( property="fixed_term", type="string", example="null", description="有效期的有效天数"),
     *                                  @SWG\Property( property="service_phone", type="string", example="null", description="客服电话"),
     *                                  @SWG\Property( property="center_title", type="string", example="null", description="卡券顶部居中的按钮，仅在卡券状态正常(可以核销)时显示"),
     *                                  @SWG\Property( property="center_sub_title", type="string", example="null", description="显示在入口下方的提示语"),
     *                                  @SWG\Property( property="center_url", type="string", example="null", description="顶部居中的url"),
     *                                  @SWG\Property( property="custom_url_name", type="string", example="null", description="自定义跳转外链的入口名字"),
     *                                  @SWG\Property( property="custom_url", type="string", example="null", description="自定义跳转的URL"),
     *                                  @SWG\Property( property="custom_url_sub_title", type="string", example="null", description="显示在入口右侧的提示语"),
     *                                  @SWG\Property( property="promotion_url_name", type="string", example="null", description="营销场景的自定义入口名称"),
     *                                  @SWG\Property( property="promotion_url", type="string", example="null", description="营销场景的自定义入口url"),
     *                                  @SWG\Property( property="promotion_url_sub_title", type="string", example="null", description="营销入口右侧的提示语"),
     *                                  @SWG\Property( property="get_limit", type="string", example="1", description="每人可领券的数量限制"),
     *                                  @SWG\Property( property="use_limit", type="string", example="null", description="每人可核销的数量限制"),
     *                                  @SWG\Property( property="can_share", type="string", example="false", description="卡券领取页面是否可分享"),
     *                                  @SWG\Property( property="can_give_friend", type="string", example="false", description="卡券是否可转赠"),
     *                                  @SWG\Property( property="abstract", type="string", example="null", description="封面摘要"),
     *                                  @SWG\Property( property="icon_url_list", type="string", example="null", description="封面图片"),
     *                                  @SWG\Property( property="text_image_list", type="string", example="N;", description="图文列表(DC2Type:array)"),
     *                                  @SWG\Property( property="time_limit", type="string", example="null", description="使用时段限制(DC2Type:array)"),
     *                                  @SWG\Property( property="gift", type="string", example="null", description="兑换券兑换内容名称"),
     *                                  @SWG\Property( property="default_detail", type="string", example="null", description="优惠券优惠详情"),
     *                                  @SWG\Property( property="discount", type="string", example="90", description="折扣值"),
     *                                  @SWG\Property( property="least_cost", type="string", example="0", description="代金券起用金额"),
     *                                  @SWG\Property( property="reduce_cost", type="string", example="0", description="代金券减免金额 or 兑换券起用金额"),
     *                                  @SWG\Property( property="deal_detail", type="string", example="null", description="团购券详情"),
     *                                  @SWG\Property( property="accept_category", type="string", example="null", description="指定可用的商品类目,代金券专用"),
     *                                  @SWG\Property( property="reject_category", type="string", example="null", description="指定不可用的商品类目,代金券专用"),
     *                                  @SWG\Property( property="object_use_for", type="string", example="null", description="购买xx可用类型门槛，仅用于兑换"),
     *                                  @SWG\Property( property="can_use_with_other_discount", type="string", example="false", description="是否可与其他优惠共享"),
     *                                  @SWG\Property( property="quantity", type="string", example="99999", description="卡券总库存"),
     *                                  @SWG\Property( property="use_all_shops", type="string", example="1", description="是否适用所有门店"),
     *                                  @SWG\Property( property="rel_shops_ids", type="string", example=",", description="适用的门店"),
     *                                  @SWG\Property( property="created", type="string", example="1608001062", description=""),
     *                                  @SWG\Property( property="updated", type="string", example="1608001062", description=""),
     *                                  @SWG\Property( property="use_scenes", type="string", example="ONLINE", description="核销场景。可选值有，ONLINE:线上商城(兑换券不可使用);QUICK:快捷买单(兑换券不可使用);SWEEP:门店支付(扫码核销);SELF:到店支付(自助核销)"),
     *                                  @SWG\Property( property="receive", type="string", example="true", description="是否前台直接领取"),
     *                                  @SWG\Property( property="self_consume_code", type="string", example="0", description="自助核销验证码"),
     *                                  @SWG\Property( property="use_platform", type="string", example="mall", description=""),
     *                                  @SWG\Property( property="most_cost", type="string", example="99999900", description="代金券最高消费限额"),
     *                                  @SWG\Property( property="distributor_id", type="string", example=",", description="分销商id"),
     *                                  @SWG\Property( property="use_bound", type="string", example="0", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                                  @SWG\Property( property="tag_ids", type="string", example="null", description="标签id集合"),
     *                                  @SWG\Property( property="brand_ids", type="string", example="null", description="品牌id集合"),
     *                                  @SWG\Property( property="apply_scope", type="string", example="", description="适用范围"),
     *                                  @SWG\Property( property="card_code", type="string", example="null", description="优惠券模板ID-第三方使用"),
     *                                  @SWG\Property( property="card_rule_code", type="string", example="", description="优惠券规则ID-第三方使用"),
     *                                  @SWG\Property( property="get_num", type="string", example="7", description="被领取数量"),
     *                                  @SWG\Property( property="use_num", type="string", example="7", description=""),
     *                               ),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="no_post", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="rate_status", type="string", example="false", description=""),
     *                  @SWG\Property( property="recommend_items", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="tdk_data", type="object",
     *                          @SWG\Property( property="title", type="string", example="hu-测试,52803,dermGO SENSITIVE敏感肌改善抗衰精华30ml,1603,", description="标题"),
     *                          @SWG\Property( property="mate_description", type="string", example="hu-测试,1603,dermGO SENSITIVE敏感肌改善抗衰精华30ml,52803,", description="描述"),
     *                          @SWG\Property( property="mate_keywords", type="string", example="dermGO SENSITIVE敏感肌改善抗衰精华30ml,hu-测试,1603,52803,", description="关键字"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsDetailNew(Request $request)
    {
        // 用于商品详情别名接口，兼容老的商品详情接口，用于路由缓存可以找到
        $item_id = $request->input('item_id');
        return $this->getItemsDetail($item_id, $request);
    }

    /**
     * 替换商品基础信息
     *
     * @param array $itemInfo 商品信息
     * @param array $activityItemInfo 活动商品关联自定义的信息
     */
    private function __replaceItemInfo($itemInfo, $activityItemInfo)
    {
        if (!$activityItemInfo) {
            return $itemInfo;
        }

        $itemInfo['limit_num'] = $activityItemInfo['limit_num'];
        $itemInfo['sales_store'] = $activityItemInfo['sales_store'] ?? 0;
        $itemInfo['act_price'] = $activityItemInfo['activity_price'];
        $itemInfo['store'] = $activityItemInfo['store'] ?? $itemInfo['store'];

        if (in_array($itemInfo['activity_type'], ['limited_time_sale', 'seckill'])) {
          $itemInfo['seckill_id'] = $activityItemInfo['seckill_id'];
        }

        return $itemInfo;
    }

    /**
     * @SWG\Post(
     *     path="wxapp/goods/scancodeAddcart",
     *     summary="扫条形码加入购物车",
     *     tags={"商品"},
     *     description="扫条形码加入购物车",
     *     operationId="scanCodeSales",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="barcode", in="query", description="商品条形码", type="integer" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺或门店id", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *                  @SWG\Property( property="msg", type="string", example="加入购物车成功", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function scanCodeSales(Request $request)
    {
        $authInfo = $request->get('auth');
        $filter['barcode'] = $request->get('barcode', 0);
        $filter['company_id'] = $authInfo['company_id'];
        $itemsService = new ItemsService();
        $tempItemInfo = $itemsService->getInfo($filter);
        if (!$tempItemInfo) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.item_not_found'));
        }

        $params['item_id'] = $tempItemInfo['item_id'];
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $params['shop_id'] = $request->get('distributor_id', 0);
        $params['shop_type'] = 'distributor';  //普通商品
        $params['activity_type'] = 'normal';  //普通商品
        $params['num'] = 1;
        $params['source_type'] = 'scancode';  //标示扫码购来源
        $params['isAccumulate'] = true;
        $params['isShopScreen'] = $request->get('isShopScreen', 0);
        $cartService = new CartService();
        $result = $cartService->addCart($params);

        //离线购物车直接返回商品ID在前端处理
        if (!($authInfo['user_id'] ?? 0)) {
            return $this->response->array(['item_id' => $tempItemInfo['item_id']]);
        }

        if ($result['cart_id'] ?? null) {
            return $this->response->array(['status' => true, 'msg' => '加入购物车成功']);
        }
        return $this->response->array(['status' => false, 'msg' => '加入购物车失败']);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/goods/items_price_store/{item_id}",
     *     summary="获取商品价格、库存等参数",
     *     tags={"商品"},
     *     description="获取商品价格、库存等参数",
     *     operationId="getItemsPriceAndStore",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="item_id", in="path", description="商品id", required=true, type="integer" ),
     *     @SWG\Parameter( name="goods_id", in="query", description="产品ID，多规格商品不同规格的goods_id是一样的", type="integer" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="门店ID", type="integer" ),
     *     @SWG\Parameter( name="isShopScreen", in="query", description="是否大屏显示", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="item_id", type="string", example="5016", description="商品id"),
     *                  @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                  @SWG\Property( property="store", type="string", example="986", description="库存"),
     *                  @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                  @SWG\Property( property="cost_price", type="string", example="0", description="价格,单位为‘分’"),
     *                  @SWG\Property( property="goods_id", type="string", example="5016", description="商品集合ID"),
     *                  @SWG\Property( property="brand_id", type="string", example="1350", description="品牌id"),
     *                  @SWG\Property( property="price", type="string", example="7000", description="价格,单位为‘分’"),
     *                  @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
     *                  @SWG\Property( property="regions_id", type="string", example="null", description=""),
     *                  @SWG\Property( property="templates_id", type="string", example="94", description="运费模板id"),
     *                  @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                  @SWG\Property( property="default_item_id", type="string", example="5016", description="默认商品ID"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="tax_rate", type="string", example="0", description="税率"),
     *                  @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                  @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                  @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                  @SWG\Property( property="taxation_num", type="string", example="0", description="计税单位份数"),
     *                  @SWG\Property( property="type", type="string", example="0", description=""),
     *                  @SWG\Property( property="item_main_cat_id", type="string", example="5", description=""),
     *                  @SWG\Property( property="spec_pics", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="item_params", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="item_spec_desc", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="spec_images", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="spec_items", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="attribute_ids", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="distributor_sale_status", type="string", example="true", description=""),
     *                  @SWG\Property( property="item_total_store", type="string", example="986", description=""),
     *                  @SWG\Property( property="cross_border_tax", type="string", example="0", description="商品跨境税费"),
     *                  @SWG\Property( property="activity_type", type="string", example="normal", description="活动类型 full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购,group拼团,seckill秒杀,package打包,limited_time_sale限时特惠"),
     *                  @SWG\Property( property="is_vip_grade", type="string", example="false", description=""),
     *                  @SWG\Property( property="member_price", type="string", example="", description=""),
     *                  @SWG\Property( property="promotion_activity", type="string", example="null", description=""),
     *                  @SWG\Property( property="promoter_price", type="string", example="0", description=""),
     *                  @SWG\Property( property="rate_status", type="string", example="false", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsPriceAndStore($item_id, Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $api_from = $authInfo['api_from'];
        $woa_appid = $authInfo['woa_appid'];

        $validator = app('validator')->make(['item_id' => $item_id], [
            'item_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            return $this->response->array(['item_id' => 0]);
        }

        $distributorId = $request->input('distributor_id', 0);
        if ($distributorId == 'undefined' || $distributorId == 'null') {
            $distributorId = 0;
        }

        $promotionActivityData = $this->getCurrentActivityByItemId($company_id, $item_id, $distributorId);
        // 当前商品在进行活动
        $limitItemIds = array();
        if ($promotionActivityData) {
            $limitItemIds = !in_array($promotionActivityData['activity_type'], ['limited_buy']) ? array_column($promotionActivityData['list'], 'item_id') : [];
        }

        if ($limitItemIds && !in_array($item_id, $limitItemIds)) {
            $item_id = $limitItemIds[0];
        }

        $itemsService = new ItemsService();

        //如果有分销商id。则获取店铺商品详情
        if ($distributorId) {
            $distributorItemsService = new DistributorItemsService();
            $data = $distributorItemsService->getValidDistributorItemInfo($company_id, $item_id, $distributorId, $woa_appid, $limitItemIds);
        } else {
            $data = $itemsService->getItemsDetail($item_id, $woa_appid, $limitItemIds, $company_id);
        }

        if (!$data) {
            return $this->response->array(['item_id' => 0]);
        }
        $store = $data['item_total_store'] ?? $data['store'];
        $marketingService = new MarketingActivityService();
        // 如果参加活动则没有推广金额
        if ($promotionActivityData && in_array($promotionActivityData['activity_type'], ['limited_time_sale', 'seckill', 'group'])) {
            $data = $this->__replaceItemInfo($data, $promotionActivityData['list'][$item_id]);
            $data['item_total_store'] = $data['store'];
            // 替换多规格店铺商品信息
            if (isset($data['spec_items']) && $data['spec_items']) {
                $totalStore = 0;
                foreach ($data['spec_items'] as $key => $row) {
                    $row['activity_type'] = $data['activity_type'];
                    $activityItemInfo = $promotionActivityData['list'][$row['item_id']] ?? [];
                    if (!$activityItemInfo) {
                        unset($data['spec_items'][$key]);
                    } else {
                        $data['spec_items'][$key] = $this->__replaceItemInfo($row, $activityItemInfo);
                        $totalStore += $data['spec_items'][$key]['store'];
                    }
                }
                $sortPrice = array_column($data['spec_items'], 'act_price');
                array_multisort($sortPrice, SORT_ASC, $data['spec_items']);
                $firstIems = reset($data['spec_items']);
                $data = array_merge($data, $firstIems);
                $data['item_total_store'] = $totalStore;
            }
        } else {
            // 计算会员价
            $userId = $authInfo['user_id'] ?? 0;
            $data = $itemsService->getItemsMemberPriceByUserId($data, $userId, $company_id);
        }

        $data['store'] = $data['item_total_store'] ?? $data['store'];

        ##团购活动库存更新
        if (!empty($promotionActivityData['activity_type']) && $promotionActivityData['activity_type'] == 'group') {
            if (isset($promotionActivityData['list'][$item_id]['store'])) {
                $data['store'] = min($data['store'], $store);
            }
        }

        $fields = ['item_id', 'price', 'market_price', 'cost_price', 'member_price', 'act_price', 'activity_price', 'vip_price', 'svip_price', 'store'];
        foreach ($data as $key => $value) {
            if (in_array($key, $fields)) {
                $result[$key] = $value;
                continue;
            }

            if ($key == 'spec_items') {
                foreach ($data['spec_items'] as $i => $val) {
                    foreach ($val as $k => $v) {
                        if (in_array($k, $fields)) {
                            $result['spec_items'][$i][$k] = $v;
                            continue;
                        }
                    }
                }
            }
        }

        return $this->response->array($result);
    }
}
