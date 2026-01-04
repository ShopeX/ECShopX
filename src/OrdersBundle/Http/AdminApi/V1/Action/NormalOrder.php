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

namespace OrdersBundle\Http\AdminApi\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;

use App\Http\Controllers\Controller as Controller;

use OrdersBundle\Entities\OrderAssociations;
use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\OrderProfitService;
use OrdersBundle\Services\Orders\NormalOrderService;
use PopularizeBundle\Services\BrokerageService;

use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Traits\GetPaymentServiceTrait;
use MembersBundle\Services\MemberService;
use OrdersBundle\Traits\OrderSettingTrait;

class NormalOrder extends Controller
{
    use GetOrderServiceTrait;
    use GetPaymentServiceTrait;
    use OrderSettingTrait;
    /**
     * @SWG\Get(
     *     path="/admin/wxapp/order/detail",
     *     summary="获取订单详情",
     *     tags={"订单"},
     *     description="获取订单详情",
     *     operationId="getOrderDetail",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="code", in="query", description="根据状态筛选", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *              @SWG\Property(property="orderInfo", type="object", description="",
     *                   @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
     *                   @SWG\Property(property="title", type="string", example="测试商品会员价导入...", description="订单标题"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
     *                   @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                   @SWG\Property(property="mobile", type="string", example="17621502659", description="购买用户手机号"),
     *                   @SWG\Property(property="freight_fee", type="integer", example="0", description="运费价格，以分为单位"),
     *                   @SWG\Property(property="freight_type", type="string", example="cash", description="运费类型-用于积分商城 cash:现金 point:积分"),
     *                   @SWG\Property(property="item_fee", type="string", example="100", description="商品总金额，以分为单位"),
     *                   @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
     *                   @SWG\Property(property="cost_fee", type="integer", example="100", description="商品成本价，以分为单位"),
     *                   @SWG\Property(property="total_fee", type="string", example="0", description="应付总金额,以分为单位"),
     *                   @SWG\Property(property="step_paid_fee", type="integer", example="0", description="分阶段付款已支付金额，以分为单位"),
     *                   @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
     *                   @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
     *                   @SWG\Property(property="receipt_type", type="string", example="logistics", description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
     *                   @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
     *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *                   @SWG\Property(property="order_status", type="string", example="WAIT_BUYER_CONFIRM", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                   @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
     *                   @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *                   @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *                   @SWG\Property(property="auto_cancel_time", type="string", example="1612150545", description="订单自动取消时间"),
     *                   @SWG\Property(property="auto_cancel_seconds", type="integer", example="-12452", description=""),
     *                   @SWG\Property(property="auto_finish_time", type="string", example="1612755464", description="订单自动完成时间"),
     *                   @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                   @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                   @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                   @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                   @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                   @SWG\Property(property="delivery_corp_source", type="string", example="kuaidi100", description="快递代码来源"),
     *                   @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                   @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                   @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                   @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                   @SWG\Property(property="delivery_time", type="integer", example="1612150664", description="发货时间"),
     *                   @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                   @SWG\Property(property="end_date", type="string", example="", description=""),
     *                   @SWG\Property(property="receiver_name", type="string", example="1232", description="收货人姓名"),
     *                   @SWG\Property(property="receiver_mobile", type="string", example="17653569856", description="收货人手机号"),
     *                   @SWG\Property(property="receiver_zip", type="string", example="000000", description="收货人邮编"),
     *                   @SWG\Property(property="receiver_state", type="string", example="北京市", description="收货人所在省份"),
     *                   @SWG\Property(property="receiver_city", type="string", example="北京市", description="收货人所在城市"),
     *                   @SWG\Property(property="receiver_district", type="string", example="东城", description="收货人所在地区"),
     *                   @SWG\Property(property="receiver_address", type="string", example="123123", description="收货人详细地址"),
     *                   @SWG\Property(property="member_discount", type="integer", example="20", description="会员折扣金额，以分为单位"),
     *                   @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                   @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                   @SWG\Property(property="create_time", type="integer", example="1612150245", description="订单创建时间"),
     *                   @SWG\Property(property="update_time", type="integer", example="1612150664", description="订单更新时间"),
     *                   @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                   @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                   @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                   @SWG\Property(property="cny_fee", type="integer", example="0", description=""),
     *                   @SWG\Property(property="point", type="integer", example="16", description="商品总积分"),
     *                   @SWG\Property(property="pay_type", type="string", example="point", description="支付方式。wxpay-微信支付;deposit-预存款支付;pos-刷卡;point-积分"),
     *                   @SWG\Property(property="remark", type="string", example="", description="订单备注"),
     *                  @SWG\Property(property="third_params", type="object", description="",
     *                           @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
     *                  ),
     *                   @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
     *                   @SWG\Property(property="send_point", type="integer", example="0", description="是否分发积分0否 1是"),
     *                   @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *                   @SWG\Property(property="is_invoiced", type="string", example="", description="是否已开发票"),
     *                   @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
     *                   @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                   @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *                   @SWG\Property(property="point_fee", type="integer", example="80", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                   @SWG\Property(property="point_use", type="integer", example="16", description="积分抵扣使用的积分数"),
     *                   @SWG\Property(property="uppoint_use", type="integer", example="0", description="积分抵扣使用的积分升值数"),
     *                   @SWG\Property(property="point_up_use", type="integer", example="0", description=""),
     *                   @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *                   @SWG\Property(property="get_points", type="integer", example="0", description="商品获取积分"),
     *                   @SWG\Property(property="bonus_points", type="integer", example="0", description="购物赠送积分"),
     *                   @SWG\Property(property="get_point_type", type="integer", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *                   @SWG\Property(property="pack", type="string", example="", description="包装"),
     *                   @SWG\Property(property="is_shopscreen", type="string", example="", description="是否门店订单"),
     *                   @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                   @SWG\Property(property="is_profitsharing", type="integer", example="1", description="是否分账订单 1不分账 2分账"),
     *                   @SWG\Property(property="profitsharing_status", type="integer", example="1", description="分账状态 1未分账 2已分账"),
     *                   @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                   @SWG\Property(property="profitsharing_rate", type="integer", example="0", description="分账费率"),
     *                   @SWG\Property(property="bind_auth_code", type="string", example="", description="订单订单验证码"),
     *                   @SWG\Property(property="extra_points", type="integer", example="0", description="订单获取额外积分"),
     *                   @SWG\Property(property="type", type="integer", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                   @SWG\Property(property="taxable_fee", type="integer", example="0", description="计税总价，以分为单位"),
     *                   @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
     *                   @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
     *                   @SWG\Property(property="total_tax", type="integer", example="0", description="总税费"),
     *                   @SWG\Property(property="discount_info", type="array", description="",
     *                     @SWG\Items(
     *                          @SWG\Property(property="id", type="integer", example="0", description="ID"),
     *                          @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                          @SWG\Property(property="info", type="string", example="会员价", description=""),
     *                          @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
     *                          @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                     ),
     *                   ),
     *                   @SWG\Property(property="can_apply_aftersales", type="integer", example="0", description=""),
     *                   @SWG\Property(property="distributor_name", type="string", example="中关村东路123号院3号楼", description=""),
     *                   @SWG\Property(property="items", type="array", description="",
     *                     @SWG\Items(
     *                                           @SWG\Property(property="id", type="string", example="8997", description="ID"),
     *                                           @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
     *                                           @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                                           @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
     *                                           @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                                           @SWG\Property(property="item_id", type="string", example="5461", description="商品id"),
     *                                           @SWG\Property(property="item_bn", type="string", example="gyct2021001", description="商品编码"),
     *                                           @SWG\Property(property="item_name", type="string", example="测试商品会员价导入", description="商品名称"),
     *                                           @SWG\Property(property="pic", type="string", example="", description="商品图片"),
     *                                           @SWG\Property(property="num", type="integer", example="1", description="购买商品数量"),
     *                                           @SWG\Property(property="price", type="integer", example="100", description="单价，以分为单位"),
     *                                           @SWG\Property(property="total_fee", type="integer", example="0", description="应付总金额,以分为单位"),
     *                                           @SWG\Property(property="templates_id", type="integer", example="1", description="运费模板id"),
     *                                           @SWG\Property(property="rebate", type="integer", example="0", description="单个分销金额，以分为单位"),
     *                                           @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
     *                                           @SWG\Property(property="item_fee", type="integer", example="100", description="商品总金额，以分为单位"),
     *                                           @SWG\Property(property="cost_fee", type="integer", example="100", description="商品成本价，以分为单位"),
     *                                           @SWG\Property(property="item_unit", type="string", example="", description="商品计量单位"),
     *                                           @SWG\Property(property="member_discount", type="integer", example="20", description="会员折扣金额，以分为单位"),
     *                                           @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                                           @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                                           @SWG\Property(property="discount_info", type="array", description="",
     *                                             @SWG\Items(
     *                                                          @SWG\Property(property="id", type="integer", example="0", description="ID"),
     *                                                          @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                                                          @SWG\Property(property="info", type="string", example="会员价", description=""),
     *                                                          @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
     *                                                          @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                                             ),
     *                                           ),
     *                                           @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                                           @SWG\Property(property="is_total_store", type="string", example="1", description="是否是总部库存(true:总部库存，false:店铺库存)"),
     *                                           @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
     *                                           @SWG\Property(property="create_time", type="integer", example="1612150245", description="订单创建时间"),
     *                                           @SWG\Property(property="update_time", type="integer", example="1612150664", description="订单更新时间"),
     *                                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                                           @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                                           @SWG\Property(property="delivery_time", type="string", example="", description="发货时间"),
     *                                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                                           @SWG\Property(property="aftersales_status", type="string", example="", description="售后状态。可选值有 WAIT_SELLER_AGREE 0 等待商家处理;WAIT_BUYER_RETURN_GOODS 1 商家接受申请，等待消费者回寄;WAIT_SELLER_CONFIRM_GOODS 2 消费者回寄，等待商家收货确认;SELLER_REFUSE_BUYER 3 售后驳回;SELLER_SEND_GOODS 4 卖家重新发货 换货完成;REFUND_SUCCESS 5 退款成功;REFUND_CLOSED 6 退款关闭;CLOSED 7 售后关闭"),
     *                                           @SWG\Property(property="refunded_fee", type="integer", example="0", description="退款金额，以分为单位"),
     *                                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                                           @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                                           @SWG\Property(property="cny_fee", type="integer", example="0", description=""),
     *                                           @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
     *                                           @SWG\Property(property="point", type="integer", example="16", description="商品总积分"),
     *                                           @SWG\Property(property="item_spec_desc", type="string", example="颜色:粉红大格110cm,尺码:20cm", description="商品规格描述"),
     *                                           @SWG\Property(property="order_item_type", type="string", example="normal", description="订单商品类型,normal:正常商品，gift: 赠品, plus_buy: 加价购商品"),
     *                                           @SWG\Property(property="volume", type="integer", example="0", description="商品体积"),
     *                                           @SWG\Property(property="weight", type="integer", example="0", description="商品重量"),
     *                                           @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *                                           @SWG\Property(property="auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                                           @SWG\Property(property="share_points", type="integer", example="16", description="积分抵扣时分摊的积分值"),
     *                                           @SWG\Property(property="point_fee", type="integer", example="80", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                                           @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                                           @SWG\Property(property="delivery_item_num", type="integer", example="1", description="发货单发货数量"),
     *                                           @SWG\Property(property="get_points", type="integer", example="0", description="商品获取积分"),
     *                     ),
     *                   ),
     *                   @SWG\Property(property="order_status_des", type="string", example="WAIT_BUYER_CONFIRM", description=""),
     *                   @SWG\Property(property="order_status_msg", type="string", example="待收货", description=""),
     *                   @SWG\Property(property="latest_aftersale_time", type="integer", example="0", description=""),
     *                   @SWG\Property(property="estimate_get_points", type="string", example="0", description=""),
     *                   @SWG\Property(property="delivery_type", type="string", example="new", description=""),
     *                   @SWG\Property(property="is_all_delivery", type="string", example="1", description=""),
     *              ),
     *              @SWG\Property(property="tradeInfo", type="object", description="",
     *                   @SWG\Property(property="tradeId", type="string", example="12345673319460000520394", description=""),
     *                   @SWG\Property(property="orderId", type="string", example="3319460000470394", description=""),
     *                   @SWG\Property(property="shopId", type="string", example="0", description=""),
     *                   @SWG\Property(property="userId", type="string", example="20394", description=""),
     *                   @SWG\Property(property="mobile", type="string", example="17621502659", description="购买用户手机号"),
     *                   @SWG\Property(property="openId", type="string", example="", description=""),
     *                  @SWG\Property(property="discountInfo", type="object", description="",
     *                          @SWG\Property(property="member_price0", type="object", description="",
     *                                           @SWG\Property(property="id", type="integer", example="0", description="ID"),
     *                                           @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                                           @SWG\Property(property="info", type="string", example="会员价", description=""),
     *                                           @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
     *                                           @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                          ),
     *                  ),
     *                   @SWG\Property(property="mchId", type="string", example="", description=""),
     *                   @SWG\Property(property="totalFee", type="integer", example="0", description=""),
     *                   @SWG\Property(property="discountFee", type="integer", example="20", description=""),
     *                   @SWG\Property(property="feeType", type="string", example="CNY", description=""),
     *                   @SWG\Property(property="payFee", type="integer", example="16", description=""),
     *                   @SWG\Property(property="tradeState", type="string", example="SUCCESS", description=""),
     *                   @SWG\Property(property="payType", type="string", example="point", description=""),
     *                   @SWG\Property(property="transactionId", type="string", example="", description=""),
     *                   @SWG\Property(property="wxaAppid", type="string", example="", description=""),
     *                   @SWG\Property(property="bankType", type="string", example="积分", description=""),
     *                   @SWG\Property(property="body", type="string", example="测试商品会员价导入...", description="交易商品简单描述"),
     *                   @SWG\Property(property="detail", type="string", example="测试商品会员价导入...", description="交易商品详情"),
     *                   @SWG\Property(property="timeStart", type="string", example="1612150245", description=""),
     *                   @SWG\Property(property="timeExpire", type="string", example="1612150245", description=""),
     *                   @SWG\Property(property="companyId", type="string", example="1", description=""),
     *                   @SWG\Property(property="authorizerAppid", type="string", example="", description=""),
     *                   @SWG\Property(property="curFeeType", type="string", example="CNY", description=""),
     *                   @SWG\Property(property="curFeeRate", type="integer", example="1", description=""),
     *                   @SWG\Property(property="curFeeSymbol", type="string", example="￥", description=""),
     *                   @SWG\Property(property="curPayFee", type="integer", example="16", description=""),
     *                   @SWG\Property(property="distributorId", type="string", example="103", description=""),
     *                   @SWG\Property(property="tradeSourceType", type="string", example="normal", description=""),
     *                   @SWG\Property(property="couponFee", type="integer", example="0", description=""),
     *                   @SWG\Property(property="couponInfo", type="string", example="", description=""),
     *                   @SWG\Property(property="initalRequest", type="string", example="", description=""),
     *                   @SWG\Property(property="initalResponse", type="string", example="", description=""),
     *                   @SWG\Property(property="payDate", type="string", example="2021-02-01 11:30:45", description=""),
     *              ),
     *              @SWG\Property(property="distributor", type="object", description="",
     *                   @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
     *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="is_distributor", type="string", example="1", description=""),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="mobile", type="string", example="17638125092", description="购买用户手机号"),
     *                   @SWG\Property(property="address", type="string", example="淀区中关村东路123号院", description=""),
     *                   @SWG\Property(property="name", type="string", example="中关村东路123号院3号楼", description=""),
     *                   @SWG\Property(property="auto_sync_goods", type="string", example="1", description=""),
     *                   @SWG\Property(property="logo", type="string", example="https://wemall-media-dev.s3.cn-northwest-1.amazonaws.com.cn/1606288539555.maomi_laoshi-003.jpg", description=""),
     *                   @SWG\Property(property="contract_phone", type="string", example="17638125092", description=""),
     *                   @SWG\Property(property="banner", type="string", example="", description=""),
     *                   @SWG\Property(property="contact", type="string", example="孙帅帅", description=""),
     *                   @SWG\Property(property="is_valid", type="string", example="true", description=""),
     *                   @SWG\Property(property="lng", type="string", example="116.333545", description=""),
     *                   @SWG\Property(property="lat", type="string", example="39.969303", description=""),
     *                   @SWG\Property(property="child_count", type="integer", example="0", description=""),
     *                   @SWG\Property(property="is_default", type="integer", example="0", description=""),
     *                   @SWG\Property(property="is_audit_goods", type="string", example="1", description=""),
     *                   @SWG\Property(property="is_ziti", type="string", example="1", description=""),
     *                   @SWG\Property(property="regions_id", type="array", description="",
     *                      @SWG\Items(
     *                         type="string", example="110000", description=""
     *                      ),
     *                   ),
     *                   @SWG\Property(property="regions", type="array", description="",
     *                      @SWG\Items(
     *                         type="string", example="北京市", description=""
     *                      ),
     *                   ),
     *                   @SWG\Property(property="is_domestic", type="integer", example="1", description=""),
     *                   @SWG\Property(property="is_direct_store", type="integer", example="1", description=""),
     *                   @SWG\Property(property="province", type="string", example="北京市", description=""),
     *                   @SWG\Property(property="is_delivery", type="string", example="1", description=""),
     *                   @SWG\Property(property="city", type="string", example="北京市", description=""),
     *                   @SWG\Property(property="area", type="string", example="东城区", description=""),
     *                   @SWG\Property(property="hour", type="string", example="08:00-21:00", description=""),
     *                   @SWG\Property(property="created", type="integer", example="1606288943", description=""),
     *                   @SWG\Property(property="updated", type="integer", example="1611123611", description=""),
     *                   @SWG\Property(property="shop_code", type="string", example="1234567", description=""),
     *                   @SWG\Property(property="wechat_work_department_id", type="integer", example="0", description=""),
     *                   @SWG\Property(property="distributor_self", type="integer", example="0", description=""),
     *                   @SWG\Property(property="regionauth_id", type="string", example="2", description=""),
     *                   @SWG\Property(property="is_open", type="string", example="true", description=""),
     *                   @SWG\Property(property="rate", type="string", example="1.00", description=""),
     *                   @SWG\Property(property="store_address", type="string", example="北京市东城区淀区中关村东路123号院", description=""),
     *                   @SWG\Property(property="store_name", type="string", example="中关村东路123号院3号楼", description=""),
     *                   @SWG\Property(property="phone", type="string", example="17638125092", description=""),
     *              ),
     *               @SWG\Property(property="cancelData", type="string", description=""),
     *              @SWG\Property(property="profit", type="object", description="",
     *                   @SWG\Property(property="id", type="string", example="3960", description="ID"),
     *                   @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
     *                   @SWG\Property(property="order_profit_status", type="string", example="1", description="0 无效分润 1 冻结分润 2 分润成功"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="total_fee", type="string", example="0", description="应付总金额,以分为单位"),
     *                   @SWG\Property(property="pay_fee", type="string", example="0", description="支付金额"),
     *                   @SWG\Property(property="profit_type", type="integer", example="2", description="分润类型 1 总部分润 2 自营门店分润 3 加盟门店分润"),
     *                   @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
     *                   @SWG\Property(property="dealer_id", type="string", example="0", description="区域经销商id"),
     *                   @SWG\Property(property="distributor_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="order_distributor_id", type="string", example="103", description="下单当前所在门店id"),
     *                   @SWG\Property(property="distributor_nid", type="string", example="0", description="拉新导购当前所在门店id"),
     *                   @SWG\Property(property="seller_id", type="string", example="0", description="拉新导购id"),
     *                   @SWG\Property(property="popularize_distributor_id", type="string", example="0", description="推广门店id"),
     *                   @SWG\Property(property="popularize_seller_id", type="string", example="0", description="推广导购id"),
     *                   @SWG\Property(property="proprietary", type="string", example="2", description="判断拉新门店 0 无门店 1 自营门店 2 加盟门店"),
     *                   @SWG\Property(property="popularize_proprietary", type="string", example="2", description="判断推广门店 0 无门店 1 自营门店 2 加盟门店"),
     *                   @SWG\Property(property="dealers", type="string", example="0", description="区域经销商分成"),
     *                   @SWG\Property(property="distributor", type="string", example="0", description="拉新门店分成"),
     *                   @SWG\Property(property="seller", type="string", example="0", description="拉新导购分成（分给门店）"),
     *                   @SWG\Property(property="popularize_distributor", type="string", example="0", description="推广门店分成"),
     *                   @SWG\Property(property="popularize_seller", type="string", example="0", description="推广导购分成（分给门店）"),
     *                   @SWG\Property(property="commission", type="string", example="0", description="总部手续费"),
     *                  @SWG\Property(property="rule", type="object", description="",
     *                           @SWG\Property(property="show", type="string", example="1", description=""),
     *                           @SWG\Property(property="seller", type="string", example="50", description="拉新导购分成（分给门店）"),
     *                           @SWG\Property(property="distributor", type="string", example="50", description="拉新门店分成"),
     *                           @SWG\Property(property="plan_limit_time", type="string", example="0", description=""),
     *                           @SWG\Property(property="popularize_seller", type="string", example="50", description="推广导购分成（分给门店）"),
     *                           @SWG\Property(property="distributor_seller", type="string", example="50", description=""),
     *                  ),
     *                   @SWG\Property(property="created", type="integer", example="1612150245", description=""),
     *                   @SWG\Property(property="updated", type="integer", example="1612150245", description=""),
     *                   @SWG\Property(property="plan_close_time", type="string", example="1927510245", description="计划结算时间"),
     *                   @SWG\Property(property="distributor_info", type="string", description=""),
     *                   @SWG\Property(property="seller_info", type="string", description=""),
     *                   @SWG\Property(property="popularize_seller_info", type="string", description=""),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getOrderDetail(Request $request)
    {
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];

        $code = $request->input('code');
        $orderService = new OrderService(new NormalOrderService());

        if ($code) {
            $orderId = $orderService->getOrderIdByCode($code);
        } else {
            throw new ResourceException(trans('OrdersBundle/Order.verification_code_required'));
        }

        if (!$orderId) {
            throw new ResourceException(trans('OrdersBundle/Order.pickup_verification_expired'));
        }

        $orderInfo = $orderService->getOrderInfo($companyId, $orderId);

        if (!$orderInfo['orderInfo']) {
            throw new ResourceException(trans('OrdersBundle/Order.pickup_order_error'));
        }

        if ($orderInfo['orderInfo']['ziti_code'] != intval(substr($code, 0, 6))) {
            throw new ResourceException(trans('OrdersBundle/Order.pickup_order_error'));
        }
        $orderInfo['orderInfo']['ziti_code'] = $code;

        //获取会员信息
        $userId = $orderInfo['orderInfo']['user_id'];
        $memberService = new MemberService();
        $uf = [
            'user_id' => $orderInfo['orderInfo']['user_id'],
        ];
        $userinfo = $memberService->getMemberInfo($uf);
        $orderInfo['orderInfo']['username'] = $userinfo['username'] ?? '';
        return $this->response->array($orderInfo);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/order/ziti",
     *     summary="订单自提核销",
     *     tags={"订单"},
     *     description="订单自提核销",
     *     operationId="finishOrderZiti",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="order_id", in="formData", description="需要核销的权益ID", required=true, type="string"),
     *     @SWG\Parameter( name="ziti_code", in="formData", description="核销的权益次数", required=true, type="string"),
     *     @SWG\Parameter( name="attendant", in="formData", description="服务员姓名", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *                   @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
     *                   @SWG\Property(property="title", type="string", example="测试商品会员价导入...", description="订单标题"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                   @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
     *                   @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                   @SWG\Property(property="mobile", type="string", example="17621502659", description="购买用户手机号"),
     *                   @SWG\Property(property="freight_fee", type="integer", example="0", description="运费价格，以分为单位"),
     *                   @SWG\Property(property="freight_type", type="string", example="cash", description="运费类型-用于积分商城 cash:现金 point:积分"),
     *                   @SWG\Property(property="item_fee", type="string", example="100", description="商品总金额，以分为单位"),
     *                   @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
     *                   @SWG\Property(property="cost_fee", type="integer", example="100", description="商品成本价，以分为单位"),
     *                   @SWG\Property(property="total_fee", type="string", example="0", description="应付总金额,以分为单位"),
     *                   @SWG\Property(property="step_paid_fee", type="integer", example="0", description="分阶段付款已支付金额，以分为单位"),
     *                   @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
     *                   @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
     *                   @SWG\Property(property="receipt_type", type="string", example="logistics", description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
     *                   @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
     *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *                   @SWG\Property(property="order_status", type="string", example="WAIT_BUYER_CONFIRM", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                   @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
     *                   @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *                   @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *                   @SWG\Property(property="auto_cancel_time", type="string", example="1612150545", description="订单自动取消时间"),
     *                   @SWG\Property(property="auto_cancel_seconds", type="integer", example="-12452", description=""),
     *                   @SWG\Property(property="auto_finish_time", type="string", example="1612755464", description="订单自动完成时间"),
     *                   @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                   @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                   @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                   @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                   @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                   @SWG\Property(property="delivery_corp_source", type="string", example="kuaidi100", description="快递代码来源"),
     *                   @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                   @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                   @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                   @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                   @SWG\Property(property="delivery_time", type="integer", example="1612150664", description="发货时间"),
     *                   @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                   @SWG\Property(property="end_date", type="string", example="", description=""),
     *                   @SWG\Property(property="receiver_name", type="string", example="1232", description="收货人姓名"),
     *                   @SWG\Property(property="receiver_mobile", type="string", example="17653569856", description="收货人手机号"),
     *                   @SWG\Property(property="receiver_zip", type="string", example="000000", description="收货人邮编"),
     *                   @SWG\Property(property="receiver_state", type="string", example="北京市", description="收货人所在省份"),
     *                   @SWG\Property(property="receiver_city", type="string", example="北京市", description="收货人所在城市"),
     *                   @SWG\Property(property="receiver_district", type="string", example="东城", description="收货人所在地区"),
     *                   @SWG\Property(property="receiver_address", type="string", example="123123", description="收货人详细地址"),
     *                   @SWG\Property(property="member_discount", type="integer", example="20", description="会员折扣金额，以分为单位"),
     *                   @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                   @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                   @SWG\Property(property="create_time", type="integer", example="1612150245", description="订单创建时间"),
     *                   @SWG\Property(property="update_time", type="integer", example="1612150664", description="订单更新时间"),
     *                   @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                   @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                   @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                   @SWG\Property(property="cny_fee", type="integer", example="0", description=""),
     *                   @SWG\Property(property="point", type="integer", example="16", description="商品总积分"),
     *                   @SWG\Property(property="pay_type", type="string", example="point", description="支付方式。wxpay-微信支付;deposit-预存款支付;pos-刷卡;point-积分"),
     *                   @SWG\Property(property="remark", type="string", example="", description="订单备注"),
     *                  @SWG\Property(property="third_params", type="object", description="",
     *                           @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
     *                  ),
     *                   @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
     *                   @SWG\Property(property="send_point", type="integer", example="0", description="是否分发积分0否 1是"),
     *                   @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *                   @SWG\Property(property="is_invoiced", type="string", example="", description="是否已开发票"),
     *                   @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
     *                   @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                   @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *                   @SWG\Property(property="point_fee", type="integer", example="80", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                   @SWG\Property(property="point_use", type="integer", example="16", description="积分抵扣使用的积分数"),
     *                   @SWG\Property(property="uppoint_use", type="integer", example="0", description="积分抵扣使用的积分升值数"),
     *                   @SWG\Property(property="point_up_use", type="integer", example="0", description=""),
     *                   @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *                   @SWG\Property(property="get_points", type="integer", example="0", description="商品获取积分"),
     *                   @SWG\Property(property="bonus_points", type="integer", example="0", description="购物赠送积分"),
     *                   @SWG\Property(property="get_point_type", type="integer", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *                   @SWG\Property(property="pack", type="string", example="", description="包装"),
     *                   @SWG\Property(property="is_shopscreen", type="string", example="", description="是否门店订单"),
     *                   @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                   @SWG\Property(property="is_profitsharing", type="integer", example="1", description="是否分账订单 1不分账 2分账"),
     *                   @SWG\Property(property="profitsharing_status", type="integer", example="1", description="分账状态 1未分账 2已分账"),
     *                   @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                   @SWG\Property(property="profitsharing_rate", type="integer", example="0", description="分账费率"),
     *                   @SWG\Property(property="bind_auth_code", type="string", example="", description="订单订单验证码"),
     *                   @SWG\Property(property="extra_points", type="integer", example="0", description="订单获取额外积分"),
     *                   @SWG\Property(property="type", type="integer", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                   @SWG\Property(property="taxable_fee", type="integer", example="0", description="计税总价，以分为单位"),
     *                   @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
     *                   @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
     *                   @SWG\Property(property="total_tax", type="integer", example="0", description="总税费"),
     *                   @SWG\Property(property="discount_info", type="array", description="",
     *                     @SWG\Items(
     *                          @SWG\Property(property="id", type="integer", example="0", description="ID"),
     *                          @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                          @SWG\Property(property="info", type="string", example="会员价", description=""),
     *                          @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
     *                          @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
     *                     ),
     *                   ),
     *                   @SWG\Property(property="can_apply_aftersales", type="integer", example="0", description=""),
     *                   @SWG\Property(property="distributor_name", type="string", example="中关村东路123号院3号楼", description=""),
     *                   @SWG\Property(property="order_status_des", type="string", example="WAIT_BUYER_CONFIRM", description=""),
     *                   @SWG\Property(property="order_status_msg", type="string", example="待收货", description=""),
     *                   @SWG\Property(property="latest_aftersale_time", type="integer", example="0", description=""),
     *                   @SWG\Property(property="estimate_get_points", type="string", example="0", description=""),
     *                   @SWG\Property(property="delivery_type", type="string", example="new", description=""),
     *                   @SWG\Property(property="is_all_delivery", type="string", example="1", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */

    public function finishOrderZiti(Request $request)
    {
        $orderId = $request->input('order_id');
        $code = $request->input('ziti_code');

        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];

        $orderService = new OrderService(new NormalOrderService());
        $orderInfo = $orderService->getOrderInfo($companyId, $orderId);
        if (!$orderInfo['orderInfo']) {
            throw new ResourceException(trans('OrdersBundle/Order.pickup_order_error'));
        }

        if ($orderInfo['orderInfo']['shop_id'] && !in_array($orderInfo['orderInfo']['shop_id'], $authInfo['shop_ids'])) {
            throw new ResourceException(trans('OrdersBundle/Order.no_store_verification_permission'));
        } elseif ($orderInfo['orderInfo']['distributor_id'] && !in_array($orderInfo['orderInfo']['distributor_id'], $authInfo['distributor_ids'])) {
            throw new ResourceException(trans('OrdersBundle/Order.no_store_verification_permission'));
        }

        if ($orderInfo['orderInfo']['ziti_code'] != intval(substr($code, 0, 6))) {
            throw new ResourceException(trans('OrdersBundle/Order.pickup_order_error'));
        }

        if ($orderInfo['orderInfo']['ziti_status'] == 'DONE' && $orderInfo['orderInfo']['order_status'] == 'DONE') {
            throw new ResourceException(trans('OrdersBundle/Order.pickup_order_completed'));
        }

        if ($orderInfo['orderInfo']['cancel_status'] == 'WAIT_PROCESS') {
            throw new ResourceException(trans('OrdersBundle/Order.order_has_cancel_request'));
        }

        //更新售后时效时间
        $aftersalesTime = intval($this->getOrdersSetting($companyId, 'latest_aftersale_time'));
        $auto_close_aftersales_time = strtotime("+$aftersalesTime day", time());

        $filter['order_id'] = $orderId;
        $filter['company_id'] = $companyId;
        $updateInfo = [
            'ziti_status' => 'DONE',
            'order_status' => 'DONE',
            'delivery_status' => 'DONE',
            'delivery_time' => time(),
            'end_time' => time(),
            'order_auto_close_aftersales_time' => $auto_close_aftersales_time,
        ];
        $result = $orderService->update($filter, $updateInfo);

        $brokerageService = new BrokerageService();
        $brokerageService->updatePlanCloseTime($companyId, $orderId);


        //更新会员等级- 积分支付订单不需要
        // $orderService->orderUpdateMemberGrade($companyId, $orderInfo);

        $orderProfitService = new OrderProfitService();
        $orderProfitService->orderProfitPlanCloseTime($companyId, $orderId);
        if ($result) {
            try {
                $data = [
                  'status' => 'success',
                  'order_id' => $result['order_id'],
                  'user_id' => $result['user_id'],
                  'ziti_status' => $updateInfo['ziti_status'],
                  'order_status' => $updateInfo['order_status'],
              ];
                app('websocket_client')->driver('orderzitimsg')->send($data);
            } catch (\Exception $e) {
                app('log')->debug('websocket orderzitimsg service Error:'.$e->getMessage());
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/admin/wxapp/normalcreate",
     *     summary="代客下单",
     *     tags={"订单"},
     *     description="代客下单",
     *     operationId="createUserOrder",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="pay_type", in="formData", description="支付类型 wxpay 微信支付", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData", description="用户id", required=true, type="string"),
     *     @SWG\Parameter( name="coupon_discount", in="formData", description="优惠券优惠码", type="string"),
     *     @SWG\Parameter( name="distributor_id", in="formData", description="店铺id", type="string"),
     *     @SWG\Parameter( name="point", in="formData", description="积分", type="string"),
     *     @SWG\Parameter( name="point_use", in="formData", description="使用的积分数", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="appId", type="string", description="应用ID"),
     *                 @SWG\Property(property="timeStamp", type="string", description="时间戳"),
     *                 @SWG\Property(property="nonceStr", type="string", description="随机字符串"),
     *                 @SWG\Property(property="package", type="string", description="订单详情扩展字符串"),
     *                 @SWG\Property(property="signType", type="string", description="签名方式"),
     *                 @SWG\Property(property="paySign", type="string", description="签名"),
     *                 @SWG\Property(property="team_id", type="string", description=""),
     *             )
     *         )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */

    public function createUserOrder(Request $request)
    {
        $input = $request->all();
        $authInfo = $this->auth->user();
        $input['distributor_id'] = $request->input('distributor_id') ?: $authInfo['distributor_id'];
        $input['company_id'] = $request->get('company_id') ?: $authInfo['company_id'];

        if ($input['company_id'] != $authInfo['company_id']) {
            $input['distributor_id'] = 0;
        }
        $params = $this->_getOrderParams($input, $authInfo);
        $orderService = $this->getOrderService($params['order_type']);
        $result = $orderService->create($params);

        $data = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'total_fee' => $result['total_fee'],
            'detail' => $result['title'],
            'order_id' => $result['order_id'],
            'body' => $result['title'],
            'open_id' => $params['open_id'] ?? '',
            'wxa_appid' => $params['wxapp_appid'] ?? '',
            'mobile' => $params['mobile'],
            'pay_type' => $params['pay_type'] ,
            'pay_fee' => $result['total_fee'],
            'discount_fee' => $result['discount_fee'],
            'discount_info' => $result['discount_info'],
            'fee_rate' => $result['fee_rate'],
            'fee_type' => $result['fee_type'],
            'fee_symbol' => $result['fee_symbol'],
            'shop_id' => $result['shop_id'] ?? 0,
            'distributor_id' => isset($result['distributor_id']) ? $result['distributor_id'] : '',
            'trade_source_type' => $params['order_type'],
        ];
        $authorizerAppId = $authInfo['woa_appid'] ?? '';
        $wxaAppId = $authInfo['wxapp_appid'] ?? '';
        $service = $this->getGuidePaymentService($params['pay_type'], $data['distributor_id']);
        $payResult = $service->doPayment($authorizerAppId, $wxaAppId, $data, false);

        return $this->response->array($payResult);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/cartcheckout",
     *     summary="购物车结算列表",
     *     tags={"订单"},
     *     description="购物车结算列表",
     *     operationId="cartCheckout",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="pay_type", in="formData", description="支付类型 wxpay 微信支付", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="formData", description="用户id", required=true, type="string"),
     *     @SWG\Parameter( name="coupon_discount", in="formData", description="优惠券优惠码", type="string"),
     *     @SWG\Parameter( name="distributor_id", in="formData", description="店铺id", type="string"),
     *     @SWG\Parameter( name="point", in="formData", description="积分", type="string"),
     *     @SWG\Parameter( name="point_use", in="formData", description="使用的积分数", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function cartCheckout(Request $request)
    {
        $input = $request->all();
        $authInfo = $this->auth->user();
        $input['distributor_id'] = $request->input('distributor_id') ?: $authInfo['distributor_id'];
        $input['company_id'] = $request->get('company_id') ?: $authInfo['company_id'];

        if ($input['company_id'] != $authInfo['company_id']) {
            $input['distributor_id'] = 0;
        }
        $params = $this->_getOrderParams($input, $authInfo);
        $orderService = $this->getOrderService($params['order_type']);
        $result = $orderService->getOrderTempInfo($params);

        return $this->response->array($result);
    }

    private function _getOrderParams($input, $authInfo)
    {
        if (!($input['user_id'] ?? null)) {
            throw new ResourceException('必须指定会员');
        }

        $memberService = new MemberService();
        $uf = [
            'user_id' => $input['user_id'],
        ];
        $userinfo = $memberService->getMemberInfo($uf);
        if (!$userinfo) {
            throw new ResourceException('会员信息有误');
        }

        $params['promotion'] = 'normal';
        $params['order_source'] = 'shop_offline';
        $params['is_online_order'] = false;
        $params['receipt_type'] = 'ziti';
        $params['order_type'] = 'normal_shopguide';
        $params['pay_type'] = $input['pay_type'] ?? 'wxpaypc';
        $params['mobile'] = $userinfo['mobile'];
        $params['authorizer_appid'] = $userinfo['woa_appid'] ?? '';
        $params['wxa_appid'] = $userinfo['wxapp_appid'] ?? '';
        $params['user_id'] = $input['user_id'];
        $params['coupon_discount'] = $input['coupon_discount'] ?? 0;
        $params['salesman_id'] = $authInfo['salesperson_id'];
        $params['company_id'] = $input['company_id'];
        $params['distributor_id'] = $input['distributor_id'];
        $params['is_order'] = $input['is_order'] ?? false;
        $params['point_use'] = $input['point_use'] ?? 0;
        $params['not_use_coupon'] = $input['not_use_coupon'] ?? 0;
        return $params;
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/orderstatus",
     *     summary="获取订单支付状态",
     *     tags={"订单"},
     *     description="获取订单支付状态",
     *     operationId="cartCheckout",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="orderId", in="query", description="订单id", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="order_status", type="string", description="订单状态"),
     *                   @SWG\Property(property="order_id", type="string", description="订单编号"),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getPayStatus(Request $request)
    {
        $authInfo = $this->auth->user();
        $orderId = trim($request->input('orderId', 0));

        if (!$orderId) {
            throw new ResourceException('无效订单');
        }

        $companyId = $authInfo['company_id'] ?? 0;
        if (!$companyId) {
            throw new ResourceException('企业id缺失');
        }

        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId
        ];

        $orderAssociations = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
        $result = $orderAssociations->getList('order_status, order_id', $filter, 0, 1);
        if ($result) {
            return $this->response->array(array_shift($result));
        } else {
            return $this->response->array([]);
        }
    }
}
