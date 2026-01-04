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
class OrderItems
{
    /**
     *                                  @SWG\Property( property="id", type="string", example="8336", description="ID"),
     *                                  @SWG\Property( property="order_id", type="string", example="3274628000080264", description="订单编号"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                                  @SWG\Property( property="act_id", type="string", example="154", description="活动ID号"),
     *                                  @SWG\Property( property="item_id", type="string", example="5291", description="商品id"),
     *                                  @SWG\Property( property="item_bn", type="string", example="S5FD1AF7830A63", description="商品编码"),
     *                                  @SWG\Property( property="item_name", type="string", example="社区拼团", description="商品名称"),
     *                                  @SWG\Property( property="pic", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrfOrTwEtYr8iahWh0W6icC572w3E2icsWbdHsbLEqt020mib55P3pJ4Ll53WaaLibSavoicF7z9vB1SSqww/0?wx_fmt=jpeg", description="商品图片"),
     *                                  @SWG\Property( property="num", type="string", example="1", description="数量"),
     *                                  @SWG\Property( property="price", type="string", example="1", description="商品价格"),
     *                                  @SWG\Property( property="total_fee", type="string", example="1", description="订单金额，以分为单位"),
     *                                  @SWG\Property( property="templates_id", type="string", example="1", description="运费模板id"),
     *                                  @SWG\Property( property="rebate", type="string", example="0", description="单个分销金额，以分为单位’"),
     *                                  @SWG\Property( property="total_rebate", type="string", example="0", description="总分销金额，以分为单位"),
     *                                  @SWG\Property( property="item_fee", type="string", example="1", description="商品金额，以分为单位"),
     *                                  @SWG\Property( property="cost_fee", type="string", example="0", description="商品成本价，以分为单位"),
     *                                  @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
     *                                  @SWG\Property( property="member_discount", type="string", example="0", description="会员折扣金额，以分为单位"),
     *                                  @SWG\Property( property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                                  @SWG\Property( property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位"),
     *                                  @SWG\Property( property="discount_info", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="id", type="string", example="14", description="id"),
     *                                          @SWG\Property( property="type", type="string", example="member_tag_targeted_promotion", description="类型"),
     *                                          @SWG\Property( property="rule", type="string", example="员工专属折扣90%", description="分润规则(DC2Type:json_array) | 限购规则"),
     *                                          @SWG\Property( property="info", type="string", example="员工专属折扣", description=""),
     *                                          @SWG\Property( property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位"),
     *                                       ),
     *                                  ),
     *                                  @SWG\Property( property="shop_id", type="string", example="16", description="门店id"),
     *                                  @SWG\Property( property="is_total_store", type="string", example="true", description="是否为总部库存"),
     *                                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                                  @SWG\Property( property="create_time", type="string", example="1608277374", description="创建时间"),
     *                                  @SWG\Property( property="update_time", type="string", example="1608277374", description="更新时间"),
     *                                  @SWG\Property( property="delivery_corp", type="string", example="null", description="快递公司"),
     *                                  @SWG\Property( property="delivery_code", type="string", example="null", description="快递单号"),
     *                                  @SWG\Property( property="delivery_img", type="string", example="null", description="快递发货凭证"),
     *                                  @SWG\Property( property="delivery_time", type="string", example="null", description="发货时间"),
     *                                  @SWG\Property( property="delivery_status", type="string", example="PENDING", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL_DELIVERY-部分发货"),
     *                                  @SWG\Property( property="aftersales_status", type="string", example="null", description="售后状态"),
     *                                  @SWG\Property( property="refunded_fee", type="string", example="0", description="实退金额，以分为单位"),
     *                                  @SWG\Property( property="fee_type", type="string", example="CNY", description="货币类型"),
     *                                  @SWG\Property( property="fee_rate", type="string", example="1", description="货币汇率"),
     *                                  @SWG\Property( property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                                  @SWG\Property( property="cny_fee", type="string", example="1", description=""),
     *                                  @SWG\Property( property="item_point", type="string", example="0", description="商品消费总积分"),
     *                                  @SWG\Property( property="point", type="string", example="0", description="商品总积分"),
     *                                  @SWG\Property( property="item_spec_desc", type="string", example="null", description="商品规格描述"),
     *                                  @SWG\Property( property="order_item_type", type="string", example="normal", description="订单商品类型,normal:正常商品，gift: 赠品, plus_buy: 加价购商品"),
     *                                  @SWG\Property( property="volume", type="string", example="0", description="商品体积"),
     *                                  @SWG\Property( property="weight", type="string", example="0", description="商品重量"),
     *                                  @SWG\Property( property="is_rate", type="string", example="false", description="是否评价"),
     *                                  @SWG\Property( property="auto_close_aftersales_time", type="string", example="null", description="自动关闭售后时间"),
     *                                  @SWG\Property( property="share_points", type="string", example="0", description="积分抵扣时分摊的积分值"),
     *                                  @SWG\Property( property="point_fee", type="string", example="0", description="积分抵扣金额，以分为单位 | 积分抵扣时分摊的积分的金额，以分为单位"),
     *                                  @SWG\Property( property="is_logistics", type="string", example="false", description="门店缺货商品总部快递发货"),
     *                                  @SWG\Property( property="delivery_item_num", type="string", example="null", description="发货单发货数量"),
     *                                  @SWG\Property( property="get_points", type="string", example="0", description="商品获取积分"),
     */
}
