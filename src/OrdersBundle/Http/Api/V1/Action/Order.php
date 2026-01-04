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

namespace OrdersBundle\Http\Api\V1\Action;

use DistributionBundle\Events\RefundFreightAutoZyEvent;
use GoodsBundle\Services\ItemsService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use MembersBundle\Services\MemberService;
use OrdersBundle\Constants\OrderReceiptTypeConstant;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Repositories\NormalOrdersRepository;
use OrdersBundle\Services\DeliveryProcessLogServices;
use OrdersBundle\Services\OrderDeliveryService;
use OrdersBundle\Services\OrderProfitService;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Traits\OrderSettingTrait;
use OrdersBundle\Services\LogisticTracker;
use DistributionBundle\Services\DistributorSalesmanService;
use OrdersBundle\Services\UserOrderInvoiceService;
use CompanysBundle\Services\SettingService;
use OrdersBundle\Services\OrderProcessLogService;
use SuperAdminBundle\Services\LogisticsService;
use SupplierBundle\Services\SupplierOrderService;
use SupplierBundle\Services\SupplierService;
use Swagger\Annotations as SWG;
use OrdersBundle\Services\LocalDeliveryService;
use CompanysBundle\Services\OperatorsService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use SalespersonBundle\Services\SalespersonService;
use OrdersBundle\Entities\OrdersDelivery;

class Order extends Controller
{
    use GetOrderServiceTrait;
    use OrderSettingTrait;

    /**
     * @SWG\Definition(
     *     definition="OrderAppInfo",
     *     type="object",
     *         @SWG\Property(property="buttons", type="array", description="展示按钮type: cancel-取消订单, contact-联系客户, mark-备注, delivery-发货, consume-核销, accept-接单",
     *            @SWG\Items(
     *                @SWG\Property(property="type", type="string", example="contact", description="按钮类型"),
     *                @SWG\Property(property="name", type="string", example="联系客户", description="显示名字"),
     *            ),
     *         ),
     *         @SWG\Property(property="delivery_type_msg", type="string", description="快递配送信息"),
     *         @SWG\Property(property="delivery_type_name", type="string", description="快递名"),
     *         @SWG\Property(property="detail_status", type="object", description="订单详情页",
     *             @SWG\Property(property="main_msg", type="string", description="主文案"),
     *             @SWG\Property(property="description", type="string", description="文案描述"),
     *             @SWG\Property(property="type", type="string", description="文案类型 | text | cancel | dada_cancel | not_pay"),
     *         ),
     *         @SWG\Property(property="list_status_mag", type="string", description="订单列表页状态描述"),
     *         @SWG\Property(property="order_class_name", type="string", description="订单类型"),
     *         @SWG\Property(property="delivery_log", type="array", description="物流日志,最后一个信息为最新",
     *            @SWG\Items(
     *                @SWG\Property(property="time", type="string", example="1612150245", description="状态时间"),
     *                @SWG\Property(property="msg", type="string", example="骑士接单", description="状态信息"),
     *            ),
     *         ),
     *         @SWG\Property(property="status_info", type="object", description="主状态与子状态信息",
     *            @SWG\Property(property="main_status", type="string", example="pa", description="主状态 - `cancel`   已取消 - `notpay`   待支付 - `notship`  待发货 - `shipping` 已发货 - `finish`   已完成"),
     *            @SWG\Property(property="child_status", type="string", example="", description="子状态 - `cancel_buyer` 已取消状态下用户取消 - `cancel_shop`  已取消状态下商家取消 - `dada_0`同城配待发货状态下商家接单 - `dada_1`同城配待发货状态下骑士接单 - `dada_2`同城配待发货状态下待取货 - `dada_100`同城配待发货状态下骑士到店 - `dada_5`同城配待发货状态下已取消 - `dada_3`同城配已发货状态下配送中"),
     *         ),
     *         @SWG\Property(property="terminal_info", type="object", description="结束时间",
     *            @SWG\Property(property="msg", type="string", example="pa", description="结束时间文案"),
     *            @SWG\Property(property="time", type="string", example="", description="结束时间"),
     *         ),
     * )
     */

    /**
     * @SWG\Get(
     *     path="/order/{order_id}",
     *     summary="获取订单详情",
     *     tags={"订单"},
     *     description="获取订单详情",
     *     operationId="getOrderDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
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
     *                   @SWG\Property(property="receipt_type", type="string", example="logistics", description="收货方式。可选值有 logistics:物流;ziti:店铺自提;dada:同城配"),
     *                   @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
     *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                   @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *                   @SWG\Property(property="order_status", type="string", example="WAIT_BUYER_CONFIRM", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                   @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
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
     *                   @SWG\Property(property="dada", type="object",
     *                       ref="#/definitions/Dada"
     *                   ),
     *                   @SWG\Property(property="app_info", type="object",
     *                       ref="#/definitions/OrderAppInfo"
     *                   ),
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
    public function getOrderDetail($order_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $order_id);
        if (!$order) {
            return $this->response->error('此订单不存在！', 422);
        }

        $auth = app('auth')->user()->get();

        $orderService = $this->getOrderServiceByOrderInfo($order);
        $result = $orderService->getOrderInfo($companyId, $order_id, true);

        //处理供应商订单商品
        $supplierOrderService = new SupplierOrderService();
        $result = $supplierOrderService->getSupplierOrderItems($auth, $result);

        $orderProfitService = new OrderProfitService();
        $result['profit'] = $orderProfitService->getOrderProfit($order_id);
        $memberService = new MemberService();
        $memberInfo = $memberService->getMemberInfo(['company_id' => $companyId,'user_id' => $result['orderInfo']['user_id']]);
        $result['orderInfo']['user_delete'] = false;
        if (empty($memberInfo)) {
            $result['orderInfo']['user_delete'] = true;
        }
        // 完善店务端附加字段
        if (isset($result['orderInfo']['app_info'])) {
            $deliveryProcessServices = new DeliveryProcessLogServices();
            $result['orderInfo']['app_info']['delivery_log'] = $deliveryProcessServices->getListByOrderData($result);
            // 是否有权限查看加密数据
            $datapassBlock = $request->get('x-datapass-block');
            if ($datapassBlock) {
                if (isset($result['orderInfo']['app_info'])) {
                    $buttons = array_column($result['orderInfo']['app_info']['buttons'], null, 'type');
                    if (isset($buttons['contact'])) {
                        unset($buttons['contact']);
                    }
                    $result['orderInfo']['app_info']['buttons'] = array_values($buttons);
                }
                $result['orderInfo']['mobile'] = data_masking('mobile', (string) $result['orderInfo']['mobile']);
                $result['orderInfo']['receiver_name'] = data_masking('truename', (string) $result['orderInfo']['receiver_name']);
                $result['orderInfo']['receiver_mobile'] = data_masking('mobile', (string) $result['orderInfo']['receiver_mobile']);
                $result['orderInfo']['receiver_address'] = data_masking('address', (string) $result['orderInfo']['receiver_address']);
            }
        }
        if (isset($result['orderInfo']['salesman_id'])) {
            $salesPersonService = new SalespersonService();
            $salespersonInfo = $salesPersonService->salesperson->getInfo(['salesperson_id' => $result['orderInfo']['salesman_id'], 'company_id' => $companyId]);
            $result['orderInfo']['salespersonInfo'] = $salespersonInfo;
        }
        // sale_salesman_distributor_id
        if (isset($result['orderInfo']['sale_salesman_distributor_id'])) {
            $distributorService = new DistributorService();   //  
            $distributorInfo = $distributorService->getInfo(['company_id' => $companyId,'distributor_id' => $result['orderInfo']['sale_salesman_distributor_id']]);
            $result['orderInfo']['sale_salesman_distributor_info'] = $distributorInfo;
        }

        // 积分抵扣运费金额
        $result['orderInfo']['point_freight_fee'] = 0;
        if ($result['orderInfo']['point_fee'] > 0 && $result['orderInfo']['freight_fee'] > 0) {
            $result['orderInfo']['point_freight_fee'] = bcsub($result['orderInfo']['point_fee'], array_sum(array_column($result['orderInfo']['items'], 'point_fee')));
        }
        $result['orderInfo']['item_total_fee'] = bcsub($result['orderInfo']['total_fee'], $result['orderInfo']['freight_fee'] - $result['orderInfo']['point_freight_fee']);


        // 计算促销优惠
        $result['orderInfo']['promotion_discount'] = 0;
        if (isset($result['orderInfo']['discount_info']) && is_array($result['orderInfo']['discount_info'])) {
            foreach ($result['orderInfo']['discount_info'] as $discountInfo) {
                if (in_array($discountInfo['type'], ['full_minus', 'full_discount', 'member_tag_targeted_promotion'])) {
                    $result['orderInfo']['promotion_discount'] += $discountInfo['discount_fee'];
                }
            }
        }
        // 重新计算商品总价，不含价格立减活动及会员价优惠
        $result['orderInfo']['item_fee_new'] = $result['orderInfo']['total_fee']                  //实付金额
                                             - ($result['orderInfo']['freight_fee'] ?? 0)         //减去运费
                                             + ($result['orderInfo']['point_fee'] ?? 0)           //加上积分抵扣
                                             + ($result['orderInfo']['coupon_discount'] ?? 0)     //加上优惠券抵扣
                                             + ($result['orderInfo']['promotion_discount'] ?? 0)  //加上促销优惠
                                             + ($result['orderInfo']['member_discount'] ?? 0); //加上会员折扣，暂时先这么改

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/orders",
     *     summary="获取订单列表",
     *     tags={"订单"},
     *     description="getOrderList",
     *     operationId="getWxShopsList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="用户id",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="query",
     *         description="订单号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="salesman_mobile",
     *         in="query",
     *         description="导购员手机号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_type",
     *         in="query",
     *         description="订单类型",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_begin",
     *         in="query",
     *         description="查询开始时间",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_end",
     *         in="query",
     *         description="查询结束时间",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="店铺id",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="delivery_type",
     *         in="query",
     *         description="配送类型, normal: 普通快递, ziti: 自提, dada： 同城配",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="main_status",
     *         in="query",
     *         description="主状态 - `cancel`   已取消 - `notpay`   待支付 - `notship`  待发货 - `shipping` 已发货 - `finish`   已完成 ",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_by",
     *         in="query",
     *         description="订单时间排序 asc:正序 desc:倒序",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="order_id", type="string", example="3321684000300350", description="订单号"),
     *                           @SWG\Property(property="title", type="string", example="测试多规格1...", description="订单标题"),
     *                           @SWG\Property(property="total_fee", type="string", example="16", description="订单金额，以分为单位"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                           @SWG\Property(property="store_name", type="string", example="", description=""),
     *                           @SWG\Property(property="user_id", type="string", example="20350", description="用户id"),
     *                           @SWG\Property(property="mobile", type="string", example="17638125092", description="手机号"),
     *                           @SWG\Property(property="receipt_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单, ziti: 自提订单, dada: 同城"),
     *                           @SWG\Property(property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                           @SWG\Property(property="create_time", type="string", example="1612343172", description="订单创建时间"),
     *                           @SWG\Property(property="update_time", type="string", example="1612343255", description="订单更新时间"),
     *                           @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                           @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                           @SWG\Property(property="authorizer_appid", type="string", example="", description=""),
     *                           @SWG\Property(property="wxa_appid", type="string", example="", description=""),
     *                           @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                           @SWG\Property(property="total_rebate", type="string", example="0", description="订单总分销金额，以分为单位"),
     *                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                           @SWG\Property(property="delivery_time", type="string", example="1612343255", description="发货时间"),
     *                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *                           @SWG\Property(property="member_discount", type="string", example="4", description="会员折扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount_desc", type="string", example="", description="优惠券使用详情"),
     *                           @SWG\Property(property="member_discount_desc", type="string", example="", description="会员折扣使用详情"),
     *                           @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *                           @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                           @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                           @SWG\Property(property="promoter_user_id", type="string", example="", description=""),
     *                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                           @SWG\Property(property="fee_rate", type="string", example="1", description="货币汇率"),
     *                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                           @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                           @SWG\Property(property="promoter_shop_id", type="string", example="0", description=""),
     *                           @SWG\Property(property="source_name", type="string", example="-", description=""),
     *                           @SWG\Property(property="create_date", type="string", example="2021-02-03 17:06:12", description=""),
     *                           @SWG\Property(property="user_delete", type="boolean", example="true", description="是否注销"),
     *                           @SWG\Property(property="dada", type="object",
     *                               ref="#/definitions/Dada"
     *                           ),
     *                           @SWG\Property(property="app_info", type="object",
     *                               ref="#/definitions/OrderAppInfo"
     *                           ),
     *                 ),
     *               ),
     *              @SWG\Property(property="pager", type="object", description="",
     *                   @SWG\Property(property="count", type="string", example="7999", description="总记录数"),
     *                   @SWG\Property(property="page_no", type="integer", example="1", description="页码"),
     *                   @SWG\Property(property="page_size", type="integer", example="20", description="每页记录条数"),
     *              ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getOrderList(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');
        }
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);

        $this->__getOrderFilter($request, $filter);
        // 处方订单筛选
        $this->__getPrescriptionOrderFilter($request, $filter);
        $orderType = $request->input('order_type') ? $request->input('order_type') : '';
        $orderClass = $request->input('order_class') ? $request->input('order_class') : '';
        if (in_array($orderClass, ['point', 'deposit'])) {
            $filter['pay_type'] = $orderClass;
            $orderClass = '';
	}

        $salespersonname  = $request->input('salespersonname',false);
        if($salespersonname){
            $salespersonService = new SalespersonService();

            $filter_salesperson = array(
                'name' => fixedencrypt($salespersonname)
            );
            $data_name  = $salespersonService->salesperson->lists($filter_salesperson);
            app('log')->debug("\salesperson:".__FUNCTION__."-".__LINE__.":data_name:". json_encode($data_name));
            $promoter_user_id_list_name =  array_column($data_name['list'],'user_id');
            $filter_salesperson = array(
                'mobile' => fixedencrypt($salespersonname)
            );
            $data_mobile = $salespersonService->salesperson->lists($filter_salesperson);
            $promoter_user_id_list_mobile =  array_column($data_mobile['list'],'user_id');
            app('log')->debug("\salesperson:".__FUNCTION__."-".__LINE__.":data_mobile:". json_encode($data_mobile));

	        $promoter_id_merge = array_merge($promoter_user_id_list_name,$promoter_user_id_list_mobile);

            if(!empty($promoter_id_merge) ){
                if ($orderType) {
                    $filter['salesman_id'] = $promoter_id_merge;
                }else{
                    $filter['promoter_user_id'] = $promoter_id_merge;
                }
            }
	}
        app('log')->debug("\salesperson:".__FUNCTION__."-".__LINE__.":filter:". json_encode($filter));

        if ($orderType) {
            if ($orderClass && in_array($orderType, ['normal', 'service']) && $orderClass != $orderType && !in_array($orderClass, ['normal', 'service'])) {
                $orderServiceType = $orderType.'_'.$orderClass;
                $filter['order_type'] = $orderType;
                $filter['order_class'] = $orderClass;
                if ($orderClass == 'crossborder') {
                    unset($filter['order_class']);
                    $orderServiceType = $orderType;
                    $filter['type'] = 1;
                }
            } else {
                $orderServiceType = $orderType;
                $filter['order_type'] = $orderType;
            }
            $orderService = $this->getOrderService($orderServiceType);
            $orderBy = ['create_time' => 'DESC'];
            if ($request->input('order_by') == 'asc') {
                $orderBy = ['create_time' => 'ASC'];
            }
            /**
             * @var \OrdersBundle\Services\Orders\AbstractNormalOrder $orderService
             */
            $result = $orderService->getOrderList($filter, $page, $limit, $orderBy);
        } else {
            $orderAssociationService = new OrderAssociationService();
            $result = $orderAssociationService->getOrderList($cols = '*', $filter, $page, $limit);
        }

        $memberList = [];
        $supplierList = [];
        if ($result['list'] ?? []) {
            $userIds = array_column($result['list'], 'user_id');
            $memberService = new MemberService();
            $memberList = $memberService->getMemberInfoList(['company_id' => $companyId, 'user_id' => $userIds]);
            $memberList = array_column($memberList['list'], null, 'user_id');

            if ($orderType) {
                //处理商品的供应商信息
                $itemIds = [];
                foreach ($result['list'] as $v) {
                    if (isset($v['items'])) {
                        foreach ($v['items'] as $vv) {
                            $itemIds[] = $vv['item_id'];
                        }
                    }
                }
                if (!empty($itemIds)) {
                    $itemIds = array_unique($itemIds);
                    $supplierData = [];
                    $itemsService = new ItemsService();
                    $rs = $itemsService->itemsRepository->getLists(['item_id' => $itemIds], 'supplier_id, distributor_id, item_id');
                    $rsItems = array_column($rs, null, 'item_id');
                    $supplierIds = array_filter(array_column($rs, 'supplier_id'));
                    if ($supplierIds) {
                        $supplierService = new SupplierService();
                        $rs = $supplierService->repository->getLists(['operator_id' => $supplierIds], 'operator_id, supplier_name');
                        $supplierData = array_column($rs, null, 'operator_id');
                    }

                    foreach ($result['list'] as $k => $v) {
                        // $order_holders = [];
                        // $order_holder = 'self';
                        foreach ($v['items'] as $kk => $vv) {
                            $item_holder = 'self';//自营商品
                            $supplier_name = '';
                            if (isset($rsItems[$vv['item_id']])) {
                                if ($rsItems[$vv['item_id']]['supplier_id']) {
                                    $item_holder = 'supplier';//供应商商品
                                    // $order_holder = 'supplier';
                                    $supplier_name = $supplierData[$rsItems[$vv['item_id']]['supplier_id']]['supplier_name'];
                                } elseif ($rsItems[$vv['item_id']]['distributor_id']) {
                                    $item_holder = 'distributor';//商户商品
                                }
                            }
                            // $order_holders[$order_holder] = $order_holder;
                            $result['list'][$k]['items'][$kk]['item_holder'] = $item_holder;
                            $result['list'][$k]['items'][$kk]['supplier_name'] = $supplier_name;
                        }

                        // if (count($order_holders) > 1) {
                        //     $result['list'][$k]['order_holder'] = 'self_supplier';
                        // } else {
                        //     $result['list'][$k]['order_holder'] = $order_holder;
                        // }
                    }
                }
            }
        }

        $ordeIds = array_column($result['list'],'order_id');
        $orderAssociationService = new OrderAssociationService();
        $filter_association = array( 'company_id'=> $companyId, 'order_id|in'=> $ordeIds);

        $result_association = $orderAssociationService->getOrderList($cols = '*', $filter_association, $page, $limit);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":result_association:". json_encode( $result_association));
        $promoter_user_id_list = array_column($result_association['list'],null,'order_id');
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":result_association:". json_encode( $promoter_user_id_list));



        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result['filter'] = $filter;
        $result['datapass_block'] = $datapassBlock;
        foreach ($result['list'] ?? [] as $k => $order) {
            if ($orderType) {
                $list = array();

                if(isset($promoter_user_id_list[$order['order_id']]['promoter_user_id'])
                && $promoter_user_id_list[$order['order_id']]['promoter_user_id']){
                    $salespersonService = new SalespersonService();
                    $saleFilter = [
                        'company_id' => $filter['company_id'],
                        'user_id' => $promoter_user_id_list[$order['order_id']]['promoter_user_id'],
                        'shop_id' => $order['distributor_id'],
                    ];
                    $list = $salespersonService->getSalespersonList($saleFilter, ['created_time' => 'DESC'], 1000, 1);
                    $result['list'][$k]['salesman_info'] = $list ;
                }else{
                    $result['list'][$k]['salesman_info'] = [] ;
                }
                $result['list'][$k]['salesman_mobile'] = $list['list'][0]['mobile'] ?? '' ;
                $result['list'][$k]['salesman_name'] = $list['list'][0]['name'] ?? '' ;
                $result['list'][$k]['salesman_user_id'] = $list['list'][0]['user_id'] ?? '' ;
            }


            if (isset($memberList[$order['user_id']]) && $memberList[$order['user_id']]) {
               if ($datapassBlock) {
                    $result['list'][$k]['username'] = data_masking('truename', (string) $memberList[$order['user_id']]['username']);
               } else {
                    $result['list'][$k]['username'] = $memberList[$order['user_id']]['username'];
               }
            }

            if ($datapassBlock) {
                $result['list'][$k]['receiver_name'] = data_masking('truename', (string) $order['receiver_name']);
            }

            // $result['list'][$k]['supplier_info'] = $supplierList[$order['supplier_id']] ?? [];

            if ($datapassBlock) {
                if (isset($order['app_info'])) {
                    $buttons = array_column($order['app_info']['buttons'], null, 'type');
                    if (isset($buttons['contact'])) {
                        unset($buttons['contact']);
                    }
                    $result['list'][$k]['app_info']['buttons'] = array_values($buttons);
                }
                $result['list'][$k]['mobile'] = data_masking('mobile', (string) $order['mobile']);
                if ($orderType == 'normal') {
                    $result['list'][$k]['receiver_name'] = data_masking('truename', (string) $order['receiver_name']);
                    $result['list'][$k]['receiver_mobile'] = data_masking('mobile', (string) $order['receiver_mobile']);
                    $result['list'][$k]['receiver_address'] = data_masking('address', (string) $order['receiver_address']);
                }
                $order['operator_desc'] = $order['operator_desc'] ?? '';
                $operator_desc = explode(' : ', $order['operator_desc']);
                if (count($operator_desc) == 2) {
                    $operator_mobile = data_masking('mobile', (string) $operator_desc[0]);
                    $operator_name = data_masking('truename', (string) $operator_desc[1]);
                    $result['list'][$k]['operator_desc'] = $operator_mobile . ' : '. $operator_name;
                }
            }
            if (isset($order['app_info'])) {
                // 订单列表页添加备注按钮
                array_unshift($result['list'][$k]['app_info']['buttons'], ['type' => 'mark', 'name' => '备注']);
            }
        }

        // salesman_ids
        $salesman_ids = array_column($result['list'],'salesman_id');
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":salesman_ids:". json_encode( $salesman_ids));
        $salesman_ids = array_unique($salesman_ids);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":salesman_ids:". json_encode( $salesman_ids));
        // get list from salesperson
        $salesmanService = new SalespersonService();
        if(!empty($salesman_ids)){
            $salesmanList = $salesmanService->getSalespersonList(['company_id' => $companyId, 'salesperson_id' => $salesman_ids], ['created_time' => 'DESC'], 1000, 1);
            $salesmanList = array_column($salesmanList['list'], null, 'salesperson_id');
            app('log')->debug("\n".__FUNCTION__."-".__LINE__.":salesmanList:". json_encode( $salesmanList));
            foreach ($result['list'] as $k => $v) {
                $result['list'][$k]['salesman_info'] = $salesmanList[$v['salesman_id']] ?? [];
            }
        }
        
        // sale_salesman_distributor_info 已在 AbstractNormalOrder::getOrderList 中处理，无需重复查询

        return $this->response->array($result);
    }

    /**
     * 处理订单查询条件
     */
    public function __getOrderFilter($request, &$filter)
    {
        if ($request->input('time_start_begin')) {
            $timeStart = $request->input('time_start_begin');
            $timEnd = $request->input('time_start_end');
            if (false !== strpos($timeStart, '-')) {
                $timeStart = strtotime($timeStart.' 00:00:00');
                $timEnd = strtotime($timEnd.' 23:59:59');
            }
            $filter['create_time|gte'] = $timeStart;
            $filter['create_time|lte'] = $timEnd;
        }

        if ($request->input('delivery_time_begin')) {
            $deliveryTimeStart = $request->input('delivery_time_begin');
            $deliveryTimEnd = $request->input('delivery_time_end');
            if (false !== strpos($deliveryTimeStart, '-')) {
                $deliveryTimeStart = strtotime($deliveryTimeStart.' 00:00:00');
                $deliveryTimEnd = strtotime($deliveryTimEnd.' 23:59:59');
            }
            $filter['delivery_time|gte'] = $deliveryTimeStart;
            $filter['delivery_time|lte'] = $deliveryTimEnd;
        }
        // invoice_status
        if ($request->input('invoice_status')) {
            $filter['invoice_status'] = $request->input('invoice_status');
        }
        $filter_bak = $filter;

        $status = $request->input('order_status');
        if ($status) {
            switch ($status) {
                case 'ordercancel':   //已取消待退款
                    $filter['order_status'] = 'CANCEL_WAIT_PROCESS';
                    $filter['cancel_status'] = 'WAIT_PROCESS';
                    break;
                case 'refundprocess':    //已取消待退款
                    $filter['order_status'] = 'CANCEL';
                    $filter['cancel_status'] = 'NO_APPLY_CANCEL';
                    break;
                case 'refundsuccess':    //已取消已退款
                    $filter['order_status'] = 'CANCEL';
                    $filter['cancel_status'] = 'SUCCESS';
                    break;
                case 'notship':  //待发货
                    $filter['order_status'] = 'PAYED';
                    $filter['cancel_status|in'] = ['NO_APPLY_CANCEL', 'FAILS'];
                    $filter['receipt_type'] = 'logistics';
                    break;
                case 'cancelapply':  //待退款
                    $filter['order_status'] = 'PAYED';
                    $filter['cancel_status'] = 'WAIT_PROCESS';
                    break;
                case 'ziti':  //待自提
                    $filter['receipt_type'] = 'ziti';
                    $filter['order_status'] = 'PAYED';
                    $filter['ziti_status'] = 'PENDING';
                    break;
                case 'shipping':  //带收货
                    $filter['order_status'] = 'WAIT_BUYER_CONFIRM';
                    $filter['delivery_status'] = ['DONE', 'PARTAIL'];
                    $filter['receipt_type'] = 'logistics';
                    break;
                case 'finish':  //已完成
                    $filter['order_status'] = 'DONE';
                    break;
                case 'reviewpass':  //待审核
                    $filter['order_status'] = 'REVIEW_PASS';
                    break;
                case 'done_noinvoice':  //已完成未开票
                    $filter['order_status'] = 'DONE';
                    $filter['invoice|neq'] = null;
                    $filter['is_invoiced'] = 0;
                    break;
                case 'done_invoice':  //已完成已开票
                    $filter['order_status'] = 'DONE';
                    $filter['invoice|neq'] = null;
                    $filter['is_invoiced'] = 1;
                    break;
                default:
                    $filter['order_status'] = strtoupper($status);
                    break;
            }
        }
        // 订单主状态
        $main_status = $request->input('main_status');
        if ($main_status) {
            $filter = $filter_bak;
            switch ($main_status) {
                case 'cancel':
                    $filter['order_status|in'] = ['CANCEL_WAIT_PROCESS', 'CANCEL'];
                    break;
                case 'notpay':
                    $filter['order_status'] = 'NOTPAY';
                    break;
                case 'notship':
                    $filter['order_status'] = 'PAYED';
                    $filter['cancel_status|in'] = ['NO_APPLY_CANCEL', 'FAILS'];
                    break;
                case 'shipping':
                    $filter['order_status'] = 'WAIT_BUYER_CONFIRM';
                    break;
                case 'finish':
                    $filter['order_status'] = 'DONE';
                    break;
                case 'ziti':  //待自提
                    $filter['receipt_type'] = 'ziti';
                    $filter['order_status'] = 'PAYED';
                    $filter['ziti_status'] = 'PENDING';
                    break;
                default:
                    break;
            }
        }

        // 支付方式
        if ($pay_type = $request->input('pay_type')) {
            $filter['pay_type'] = $pay_type;
        }

        // 待支付订单,放到_filter方法中了
        // if (isset($filter['order_status']) && $filter['order_status'] == 'NOTPAY') {
        //     // FrontApi:WxappOrder:getOrderList {status: 5} 待支付订单
        //     $filter['auto_cancel_time|gt'] = time();
        // }

        if ($receiver_name = $request->input('receiver_name')) {
            $filter['receiver_name'] = $receiver_name;
        }
        if ($receiver_mobile = $request->input('receiver_mobile')) {
            $filter['receiver_mobile'] = $receiver_mobile;
        }

        if ($item_name = $request->input('item_name')) {
            // 关联查询参数
            $filter['item_name'] = $item_name;
        }

        if ($order_holder = $request->input('order_holder', '')) {
            $filter['order_holder'] = explode(',', $order_holder);
        }

        if ($order_id = $request->input('order_id')) {
            if (strlen($order_id) < 16) {
                $filter['order_id|like'] = '%'.$order_id.'%';
            } else {
                $filter['order_id'] = $order_id;
            }
        }
        if ($request->input('title')) {
            $filter['title|like'] = '%' . $request->input('title') . '%';
        }
        if ($mobile = $request->input('mobile')) {
            $filter['mobile'] = $mobile;
        }
        $companyId = $filter['company_id'];
        if ($request->input('salesman_mobile')) {
            $distributorSalesmanService = new DistributorSalesmanService();
            $salesmanInfo = $distributorSalesmanService->getInfo(['mobile' => trim($request->input('salesman_mobile')), 'company_id' => $companyId]);
            $filter['salesman_id'] = $salesmanInfo ? $salesmanInfo['salesman_id'] : '-1';
        }
        if ($request->input('user_id')) {
            $filter['user_id'] = $request->input('user_id');
        }
        if ($request->input('supplier_name')) {
            $supplier_name = trim($request->input('supplier_name'));
            $supplierService = new SupplierService();
            $rsSupplier = $supplierService->repository->getLists(['company_id' => $companyId, 'supplier_name|contains' => $supplier_name]);
            if ($rsSupplier) {
                $filter['supplier_id'] = array_column($rsSupplier, 'operator_id');
            } else {
                $filter['order_id'] = 0;
            }
        }
        if ($request->input('source_id')) {
            $filter['source_id'] = $request->input('source_id');
        }
        if ($request->input('monitor_id')) {
            $filter['monitor_id'] = $request->input('monitor_id');
        }
        // 活动ID
        if ($request->input('act_id')) {
            $filter['act_id'] = $request->input('act_id');
        }

        // 内购企业ID
        if ($request->input('enterprise_id')) {
            $filter['enterprise_id'] = $request->input('enterprise_id');
        }

        if ($request->input('source_from')) {
            $filter['source_from'] = $request->input('source_from');
        }
        // 推广员身份名称、推广员手机号
        if ($request->input('promoter_identity', '')) {
            $filter['promoter_identity'] = $request->input('promoter_identity');
        }
        if ($request->input('promoter_mobile', '')) {
            $filter['promoter_mobile'] = $request->input('promoter_mobile');
        }
        $orderType = $request->input('order_type') ? $request->input('order_type') : '';
        $orderClass = $request->input('order_class') ? $request->input('order_class') : '';
        if ($orderType == 'service') {
            $shopIds = app('auth')->user()->get('shop_ids');
            if ($shopIds) {
                $filter['shop_id|in'] = array_column($shopIds, 'shop_id');
            }

            if (!is_null($request->input('shop_id'))) {
                $filter['shop_id'] = $request->input('shop_id');
            }
        } elseif ($orderType == 'normal') {
            $distributor_id = $request->get('distributor_id');
            $operator_type = app('auth')->user()->get('operator_type');
            if ($operator_type == 'staff') {
                if (!is_null($distributor_id)) {
                    $filter['distributor_id'] = $distributor_id;
                } else {
                    $distributorIds = app('auth')->user()->get('distributor_ids');
                    if ($distributorIds) {
                        $distributorIds = array_column($distributorIds, 'distributor_id');
                        $filter['distributor_id|in'] = $distributorIds;
                    }
                }
            } else {
                if (!is_null($distributor_id)) {
                    $filter['distributor_id'] = $distributor_id;
                    if ($operator_type == 'distributor' && $order_id) {
                        unset($filter['distributor_id']);
                    }
                }
            }

            $subdistrict_parent_id = $request->get('subdistrict_parent_id');
            $subdistrict_id = $request->get('subdistrict_id');
            if (!is_null($subdistrict_parent_id)) {
                $filter['subdistrict_parent_id'] = $subdistrict_parent_id;
            }
            if (!is_null($subdistrict_id)) {
                $filter['subdistrict_id'] = $subdistrict_id;
            }
        }
        if ($distributor_type = $request->input('distributor_type')) {
            switch ($distributor_type) {
                case 'self':
                    $filter['distributor_id'] = 0;
                    break;
                case 'shop':
                    $filter['distributor_id|neq'] = 0;
                    break;
                case 'all':
            }
        }
        if (!isset($filter['shop_id']) && $request->input('shop_id')) {
            $filter['shop_id'] = $request->input('shop_id');
        }
        //排除指定类型的订单，例如店铺列表需要排除社区订单
        if ($orderType == 'normal' && $request->input('order_class_exclude')) {
            $order_class_exclude = $request->input('order_class_exclude');
            $filter['order_class|notin'] = explode(',', $order_class_exclude);
        }
        if ($request->input('receipt_type')) {
            $filter['receipt_type'] = $request->input('receipt_type');
        }
        if ($request->input('self_delivery_status')) {
            $filter['self_delivery_status'] = $request->input('self_delivery_status');
        }
        if ($request->input('delivery_staff_id')) {
            $filter['self_delivery_operator_id'] = $request->input('delivery_staff_id');
        }
        if ($request->input('is_invoiced')) {
            $filter['is_invoiced'] = intval($request->input('is_invoiced'));
        }
    }

    public function __getPrescriptionOrderFilter($request, &$filter)
    {
        $params = $request->all();
        $prescriptionColumn = ['user_family_name', 'user_family_phone', 'user_family_id_card', 'doctor_name', 'serial_no', 'audit_apothecary_name', 'audit_status'];

        foreach ($prescriptionColumn as $value) {
            if (isset($params[$value]) && $params[$value] !== '') {
                $filter['prescription_order_filter'][$value] = $params[$value];
            }
        }

        // 处方订单类型筛选
        if (isset($params['is_prescription_order'])) {
            if ($params['is_prescription_order'] === '0') { // 不含处方药订单
                $filter['prescription_status'] = 0;
            } else if ($params['is_prescription_order'] === '1') {
                $filter['prescription_status|gt'] = 0;
            }
        }
    }

    /**
     * @SWG\Post(
     *     path="/delivery",
     *     summary="订单发货",
     *     tags={"订单"},
     *     description="订单发货",
     *     operationId="delivery",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_type", in="query", description="发货类型 batch整单发货 sep拆单发货 ", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单id", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_corp", in="query", description="物流公司编码", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_code", in="query", description="物流公司快递号", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="query", description="发货类型 new新发货单 old 旧发货单", required=true, type="string"),
     *     @SWG\Parameter( name="sepInfo", in="query", description="拆单发货json数据", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="order_id", type="string", example="3308712000100353", description="订单号"),
     *               @SWG\Property(property="authorizer_appid", type="string", example="", description="公众号的appid"),
     *               @SWG\Property(property="wxa_appid", type="string", example="", description="小程序的appid"),
     *               @SWG\Property(property="title", type="string", example="大屏测试...", description="订单标题"),
     *               @SWG\Property(property="total_fee", type="string", example="1", description="订单金额，以分为单位"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="shop_id", type="string", example="0", description="店铺id"),
     *               @SWG\Property(property="store_name", type="string", example="", description="店铺名称"),
     *               @SWG\Property(property="user_id", type="string", example="20353", description="用户id"),
     *               @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *               @SWG\Property(property="promoter_user_id", type="string", example="", description="推广员user_id"),
     *               @SWG\Property(property="promoter_shop_id", type="string", example="0", description="推广员店铺id，实际为推广员的user_id"),
     *               @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *               @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *               @SWG\Property(property="mobile", type="string", example="18530870713", description="手机号"),
     *               @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
     *               @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 service 服务业订单;bargain 砍价订单;distribution 分销订单;normal 普通实体订单"),
     *               @SWG\Property(property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;PAYED-已支付;NOTPAY—未支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *               @SWG\Property(property="create_time", type="integer", example="1611222541", description="订单创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1611908346", description="订单更新时间"),
     *               @SWG\Property(property="is_distribution", type="string", example="", description="是否是分销订单"),
     *               @SWG\Property(property="total_rebate", type="integer", example="0", description="订单总分销金额，以分为单位"),
     *               @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *               @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *               @SWG\Property(property="member_discount", type="integer", example="0", description="会员折扣金额，以分为单位"),
     *               @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *               @SWG\Property(property="coupon_discount_desc", type="array", description="",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="member_discount_desc", type="array", description="",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL_DELIVERY-部分发货"),
     *               @SWG\Property(property="delivery_time", type="integer", example="1611908346", description="发货时间"),
     *               @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *               @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *               @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *               @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *               @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function delivery(Request $request)
    {
        $params = $request->all();

        $rules = [
            'order_id' => ['required', '订单号缺少！'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($params['company_id'], $params['order_id']);
        if (!$order) {
            return $this->response->error('此订单不存在！', 422);
        }
        $params['operator_type'] = app('auth')->user()->get('operator_type');
        $params['operator_id'] = app('auth')->user()->get('operator_id');
        $params['supplier_id'] = 0;
        if ($params['operator_type'] == 'supplier') {
            $params['supplier_id'] = $params['operator_id'];
            $supplierOrderService = new SupplierOrderService();
            $params = $supplierOrderService->getDeliveryItems($params);
        }

        $logisticsServices = new LogisticsService();
        $logiInfo = $logisticsServices->getLogisticsFirst(['corp_code' => $params['delivery_corp']]);
        if ($logiInfo) {
            $params['logi_name'] = $logiInfo['corp_name'];
        }

        /**
         * @var \OrdersBundle\Services\Orders\AbstractNormalOrder $orderService
         */
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $result = $orderService->delivery($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/delivery/{orders_delivery_id}",
     *     summary="订单发货信息修改",
     *     tags={"订单"},
     *     description="订单发货信息修改",
     *     operationId="delivery",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_corp", in="query", description="物流公司编码", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_code", in="query", description="物流公司快递号", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *                   @SWG\Property(property="delivery_list", type="array", description="发货单详情",
     *                       @SWG\Items(
     *                          @SWG\Property(property="orders_delivery_id", type="string", description="发货单id"),
     *                          @SWG\Property(property="company_id", type="string", description="公司id"),
     *                          @SWG\Property(property="order_id", type="string", description="订单号"),
     *                          @SWG\Property(property="delivery_corp", type="string", description="快递公司"),
     *                          @SWG\Property(property="delivery_code", type="string", description="快递单号"),
     *                          @SWG\Property(property="delivery_time", type="string", description="发货时间"),
     *                          @SWG\Property(property="created", type="integer", description="创建时间"),
     *                          @SWG\Property(property="updated", type="integer", description="修改时间"),
     *                          @SWG\Property(property="delivery_corp_name", type="string", description="快递公司名称"),
     *                          @SWG\Property(property="delivery_corp_source", type="string", description="快递代码来源"),
     *                          @SWG\Property(property="receiver_mobile", type="string", description="收货人手机号"),
     *                          @SWG\Property(property="user_id", type="integer", description="会员id"),
     *                          @SWG\Property(property="package_type", type="string", description="订单包裹类型 batch 整单发货  sep拆单发货"),
     *                       ),
     *                   ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function updateDelivery($orders_delivery_id, Request $request)
    {
        $params = $request->all();
        $rules = [
            'delivery_corp' => ['required', '物流公司编码必填'],
            'delivery_code' => ['required', '物流公司快递号必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['orders_delivery_id'] = $orders_delivery_id;
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['operator_type'] = 'admin';
        $params['operator_id'] = app('auth')->user()->get('operator_id');
        $orderDeliveryService = new OrderDeliveryService();
        $result = $orderDeliveryService->update($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/old_delivery/{orderId}",
     *     summary="订单发货信息修改（旧)",
     *     tags={"订单"},
     *     description="订单发货信息修改（旧)",
     *     operationId="delivery",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_type", in="query", description="发货类型 batch整单发货 sep拆单发货 ", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单id", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_corp", in="query", description="物流公司编码", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_code", in="query", description="物流公司快递号", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="query", description="发货类型 new新发货单 old 旧发货单", required=true, type="string"),
     *     @SWG\Parameter( name="sepInfo", in="query", description="拆单发货json数据", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="order_id", type="string", example="3308712000100353", description="订单号"),
     *               @SWG\Property(property="authorizer_appid", type="string", example="", description="公众号的appid"),
     *               @SWG\Property(property="wxa_appid", type="string", example="", description="小程序的appid"),
     *               @SWG\Property(property="title", type="string", example="大屏测试...", description="订单标题"),
     *               @SWG\Property(property="total_fee", type="string", example="1", description="订单金额，以分为单位"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="shop_id", type="string", example="0", description="店铺id"),
     *               @SWG\Property(property="store_name", type="string", example="", description="店铺名称"),
     *               @SWG\Property(property="user_id", type="string", example="20353", description="用户id"),
     *               @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *               @SWG\Property(property="promoter_user_id", type="string", example="", description="推广员user_id"),
     *               @SWG\Property(property="promoter_shop_id", type="string", example="0", description="推广员店铺id，实际为推广员的user_id"),
     *               @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *               @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *               @SWG\Property(property="mobile", type="string", example="18530870713", description="手机号"),
     *               @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
     *               @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 service 服务业订单;bargain 砍价订单;distribution 分销订单;normal 普通实体订单"),
     *               @SWG\Property(property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;PAYED-已支付;NOTPAY—未支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *               @SWG\Property(property="create_time", type="integer", example="1611222541", description="订单创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1611908346", description="订单更新时间"),
     *               @SWG\Property(property="is_distribution", type="string", example="", description="是否是分销订单"),
     *               @SWG\Property(property="total_rebate", type="integer", example="0", description="订单总分销金额，以分为单位"),
     *               @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *               @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *               @SWG\Property(property="member_discount", type="integer", example="0", description="会员折扣金额，以分为单位"),
     *               @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *               @SWG\Property(property="coupon_discount_desc", type="array", description="",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="member_discount_desc", type="array", description="",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL_DELIVERY-部分发货"),
     *               @SWG\Property(property="delivery_time", type="integer", example="1611908346", description="发货时间"),
     *               @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *               @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *               @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *               @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *               @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function updateDeliveryOld($orderId, Request $request)
    {
        $params = $request->all();
        $params['order_id'] = $orderId;

        $params['company_id'] = app('auth')->user()->get('company_id');
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($params['company_id'], $params['order_id']);
        if (!$order) {
            return $this->response->error('此订单不存在！', 422);
        }
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $params['operator_type'] = 'admin';
        $params['operator_id'] = app('auth')->user()->get('operator_id');
        $result = $orderService->updateDelivery($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/remarks/{orderId}",
     *     summary="订单备注信息修改",
     *     tags={"订单"},
     *     description="订单备注信息修改",
     *     operationId="updateRemark",
     *     @SWG\Parameter( name="order_id", in="path", description="订单id", required=true, type="string"),
     *     @SWG\Parameter( name="remark", in="query", description="订单备注", type="string"),
     *     @SWG\Parameter( name="is_distribution", in="query", description="是否为商家备注", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *                           @SWG\Property(property="order_id", type="string", example="3321684000300350", description="订单号"),
     *                           @SWG\Property(property="title", type="string", example="测试多规格1...", description="订单标题"),
     *                           @SWG\Property(property="total_fee", type="string", example="16", description="订单金额，以分为单位"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *                           @SWG\Property(property="store_name", type="string", example="", description=""),
     *                           @SWG\Property(property="user_id", type="string", example="20350", description="用户id"),
     *                           @SWG\Property(property="mobile", type="string", example="17638125092", description="手机号"),
     *                           @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *                           @SWG\Property(property="order_status", type="string", example="PAYED", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *                           @SWG\Property(property="create_time", type="string", example="1612343172", description="订单创建时间"),
     *                           @SWG\Property(property="update_time", type="string", example="1612343255", description="订单更新时间"),
     *                           @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *                           @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                           @SWG\Property(property="authorizer_appid", type="string", example="", description=""),
     *                           @SWG\Property(property="wxa_appid", type="string", example="", description=""),
     *                           @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *                           @SWG\Property(property="total_rebate", type="string", example="0", description="订单总分销金额，以分为单位"),
     *                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                           @SWG\Property(property="delivery_time", type="string", example="1612343255", description="发货时间"),
     *                           @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *                           @SWG\Property(property="member_discount", type="string", example="4", description="会员折扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount_desc", type="string", example="", description="优惠券使用详情"),
     *                           @SWG\Property(property="member_discount_desc", type="string", example="", description="会员折扣使用详情"),
     *                           @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *                           @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                           @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
     *                           @SWG\Property(property="promoter_user_id", type="string", example="", description=""),
     *                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                           @SWG\Property(property="fee_rate", type="string", example="1", description="货币汇率"),
     *                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                           @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *                           @SWG\Property(property="promoter_shop_id", type="string", example="0", description=""),
     *                           @SWG\Property(property="source_name", type="string", example="-", description=""),
     *                           @SWG\Property(property="create_date", type="string", example="2021-02-03 17:06:12", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function updateRemarks($orderId, Request $request)
    {
        $params['order_id'] = $orderId;

        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['remark'] = $request->input('remark', '');
        if (mb_strlen($params['remark']) > 150) {
            throw new ResourceException('字数请不要超过150个！');
        }
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($params['company_id'], $params['order_id']);
        if (!$order) {
            return $this->response->error('此订单不存在！', 422);
        }
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $params['operator_type'] = 'admin';
        $params['operator_id'] = app('auth')->user()->get('operator_id');
        $result = $orderService->updateRemark($params, $request->input('is_distribution', false));

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/order/{order_id}/processdrug",
     *     summary="审核药品预约单",
     *     tags={"订单"},
     *     description="审核药品预约单,审核通过或者审核拒绝",
     *     operationId="processDrugOrders",
     *     @SWG\Parameter( name="order_id", in="path", description="订单id", required=true, type="string"),
     *     @SWG\Parameter( name="reject_reason", in="query", description="订单类型", type="string"),
     *     @SWG\Parameter( name="status", in="query", description="审核状态 'true'通过 'false' 拒绝", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *              @SWG\Property(property="status", type="string", description="状态"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function processDrugOrders($order_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $order_id);
        if (!$order || !$order_id) {
            throw new ResourceException('无效的订单号');
        }

        $orderService = $this->getOrderService($order['order_type']);
        if ($request->input('status', 'true') == 'true') {
            if ($order['order_status'] == 'CANCEL') {
                throw new ResourceException('订单已被取消，不要审核');
            }

            $filter = [
                'order_id' => $order_id,
                'company_id' => $companyId,
            ];
            $updateInfo = [
                'ziti_status' => 'APPROVE',
            ];

            if ($request->input('receipt_type', 'ziti') == 'ziti') {
                if ($request->input('shop_id', 0)) {
                    $updateInfo['shop_id'] = $request->input('shop_id', 0);
                    $updateInfo['receipt_type'] = 'ziti';
                } else {
                    throw new ResourceException('请选择自提门店');
                }
            }
            $orderService->update($filter, $updateInfo);
        } else {
            $data = [
                'company_id' => $companyId,
                'order_id' => $order_id,
                'user_id' => $order['user_id'],
                'cancel_reason' => $request->input('reject_reason'),
                'cancel_from' => 'shop',
            ];
            $orderService->cancelOrder($data);
        }
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/order/{order_id}/cancelinfo",
     *     summary="获取订单取消信息",
     *     tags={"订单"},
     *     description="获取订单取消信息",
     *     operationId="cancelinfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单id", required=true, type="string"),
     *     @SWG\Parameter( name="order_type", in="query", description="订单类型", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="cancel_id", type="string", example="2057", description="取消ID"),
     *               @SWG\Property(property="order_id", type="string", example="3316445000300386", description="订单号"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="shop_id", type="integer", example="0", description="门店id"),
     *               @SWG\Property(property="user_id", type="string", example="20386", description="用户id"),
     *               @SWG\Property(property="distributor_id", type="string", example="118", description="分销商id"),
     *               @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 service 服务业订单;bargain 砍价订单;distribution 分销订单;normal 普通实体订单"),
     *               @SWG\Property(property="total_fee", type="string", example="12", description="订单金额，以分为单位"),
     *               @SWG\Property(property="progress", type="integer", example="0", description="处理进度。可选值有 0 待处理;1 已取消;2 处理中;3 已完成; 4 已驳回"),
     *               @SWG\Property(property="cancel_from", type="string", example="shop", description="取消来源。可选值有 buyer 用户取消订单;shop 商家取消订单"),
     *               @SWG\Property(property="cancel_reason", type="string", example="客户商品缺货", description="取消原因"),
     *               @SWG\Property(property="shop_reject_reason", type="string", example="", description="商家拒绝理由"),
     *               @SWG\Property(property="refund_status", type="string", example="WAIT_CHECK", description="退款状态。可选值有 READY 待审核;AUDIT_SUCCESS 审核成功待退款;SUCCESS 退款成功;SHOP_CHECK_FAILS 商家审核不通过;CANCEL 撤销退款;PROCESSING 已发起退款等待到账;FAILS 退款失败;"),
     *               @SWG\Property(property="create_time", type="integer", example="1612165451", description="订单创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1612165451", description="订单更新时间"),
     *               @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *               @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *               @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *               @SWG\Property(property="point", type="integer", example="0", description="消费积分"),
     *               @SWG\Property(property="pay_type", type="string", example="deposit", description="支付方式"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getOrderCancelInfo($order_id, Request $request)
    {
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['order_id'] = $order_id;
        $params['order_type'] = $request->input('order_type') ? $request->input('order_type') : 'normal';
        /** @var \OrdersBundle\Services\Orders\AbstractNormalOrder $orderService */
        $orderService = $this->getOrderService($params['order_type']);
        $result = $orderService->getCancelInfo($params);

        if ($result) {
            $params['user_id'] = $result['user_id'];
            $orderInfo = $orderService->getSimpleOrderInfo($params);
            $result['freight_fee'] = $orderInfo['freight_fee'] ?? 0;
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/order/{order_id}/confirmcancel",
     *     summary="确认订单取消审核",
     *     tags={"订单"},
     *     description="确认订单取消审核",
     *     operationId="confirmcancel",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单id", required=true, type="string"),
     *     @SWG\Parameter( name="check_cancel", in="query", description="是否同意", type="string"),
     *     @SWG\Parameter( name="shop_reject_reason", in="query", description="拒绝退款原因", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="cancel_id", type="string", example="2040", description="取消ID"),
     *               @SWG\Property(property="order_id", type="string", example="3307584000370376", description="订单号"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *               @SWG\Property(property="user_id", type="string", example="20376", description="用户id"),
     *               @SWG\Property(property="distributor_id", type="string", example="104", description="分销商id"),
     *               @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 service 服务业订单;bargain 砍价订单;distribution 分销订单;normal 普通实体订单"),
     *               @SWG\Property(property="total_fee", type="string", example="1", description="订单金额，以分为单位"),
     *               @SWG\Property(property="progress", type="integer", example="2", description="处理进度。可选值有 0 待处理;1 已取消;2 处理中;3 已完成; 4 已驳回"),
     *               @SWG\Property(property="cancel_from", type="string", example="buyer", description="取消来源。可选值有 buyer 用户取消订单;shop 商家取消订单"),
     *               @SWG\Property(property="cancel_reason", type="string", example="不想要了", description="取消原因"),
     *               @SWG\Property(property="shop_reject_reason", type="string", example="", description="商家拒绝理由"),
     *               @SWG\Property(property="refund_status", type="string", example="AUDIT_SUCCESS", description="退款状态。可选值有 READY 待审核;AUDIT_SUCCESS 审核成功待退款;SUCCESS 退款成功;SHOP_CHECK_FAILS 商家审核不通过;CANCEL 撤销退款;PROCESSING 已发起退款等待到账;FAILS 退款失败;"),
     *               @SWG\Property(property="create_time", type="integer", example="1611303164", description="订单创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1612168853", description="订单更新时间"),
     *               @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *               @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *               @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *               @SWG\Property(property="point", type="integer", example="0", description="消费积分"),
     *               @SWG\Property(property="pay_type", type="string", example="wxpay", description="支付方式"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function confirmOrderCancel($order_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $postdata = $request->input();
        $postdata['order_id'] = $order_id;
        $rules = [
            'order_id' => ['required', '订单号必填'],
            'check_cancel' => ['required|in:0,1', '是否同意必填'],
            'shop_reject_reason' => ['required_if:check_cancel,0', '拒绝退款时原因必填'],
        ];
        $errorMessage = validator_params($postdata, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = $companyId;
        $params['order_id'] = $order_id;
        $params['order_type'] = $postdata['order_type'] ?? 'normal';
        $params['check_cancel'] = $postdata['check_cancel'];
        $params['shop_reject_reason'] = $postdata['shop_reject_reason'] ?? '';

        /** @var \OrdersBundle\Services\Orders\AbstractNormalOrder $orderService */
        $orderService = $this->getOrderService($params['order_type']);

        $params['operator_type'] = app('auth')->user()->get('operator_type');
        $params['operator_id'] = app('auth')->user()->get('operator_id');
        $params['supplier_id'] = ($params['operator_type'] == 'supplier') ? $params['operator_id'] : 0;
        $result = $orderService->confirmCancelOrder($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/delivery/details",
     *     summary="物流详情",
     *     tags={"订单"},
     *     description="物流详情",
     *     operationId="details",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_corp", in="query", description="快递公司编码", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_code", in="query", description="快递单号编码", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="array", description="",
     *             @SWG\Items(
     *                @SWG\Property(property="AcceptTime", type="string", description="物流状态时间"),
     *                @SWG\Property(property="AcceptStation", type="string", description="物流状态描述"),
     *             ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function trackerpull(Request $request)
    {
        $postdata = $request->input();
        $rules = [
            'delivery_corp' => ['required', '快递公司编码不能为空'],
            'delivery_code' => ['required', '快递单号编码不能为空']
        ];
        $errorMessage = validator_params($postdata, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $company_id = app('auth')->user()->get('company_id');

        $tracker = new LogisticTracker();

        if ($request->has('delivery_corp_source') && $request->get('delivery_corp_source') == 'kuaidi100') {
            $ordersDeliveryRepository = app('registry')->getManager('default')->getRepository(OrdersDelivery::class);
            $orders_delivery = $ordersDeliveryRepository->getInfo(['company_id' => $company_id, 'delivery_corp' => $postdata['delivery_corp'], 'delivery_code' => $postdata['delivery_code']]);
            if ($orders_delivery) {
                $postdata['receiver_mobile'] = $orders_delivery['receiver_mobile'];
            }else{
                $postdata['receiver_mobile'] = '';
            }
            $receiver_mobile = $postdata['receiver_mobile'];
            $result = $tracker->kuaidi100($postdata['delivery_corp'], $postdata['delivery_code'], $company_id, $receiver_mobile);
        } else {
            //需要根据订单
            $result = $tracker->pullFromHqepay($postdata['delivery_code'], $postdata['delivery_corp'], $company_id);
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/orders/setting/get",
     *     summary="获取订单配置",
     *     tags={"订单"},
     *     description="获取订单配置",
     *     operationId="getOrderSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="order_cancel_time", type="integer", example="15", description="订单取消时间单位分钟"),
     *               @SWG\Property(property="order_finish_time", type="string", example="0.001", description="订单自动收货时间单位天"),
     *               @SWG\Property(property="latest_aftersale_time", type="integer", example="1", description="最后售后时间，确认收货后不可申请售后"),
     *               @SWG\Property(property="auto_refuse_time", type="string", example="0", description="售后驳回时间"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getOrderSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $result = $this->getOrdersSetting($companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/order/setting/set",
     *     summary="订单配置设置",
     *     tags={"订单"},
     *     description="订单配置设置",
     *     operationId="setOrderSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_cancel_time", in="query", description="未付款订单保留时长 (分钟)", required=true, type="string"),
     *     @SWG\Parameter( name="order_finish_time", in="query", description="已发货订单自动完成时长（天）", required=true, type="string"),
     *     @SWG\Parameter( name="latest_aftersale_time", in="query", description="最后售后时间", type="string"),
     *     @SWG\Parameter( name="auto_refuse_time", in="query", description="售后驳回时间", type="string"),
     *     @SWG\Parameter( name="auto_aftersales", in="query", description="未发货售后自动同意", type="string"),
     *     @SWG\Parameter( name="offline_aftersales", in="query", description="到店退货", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="order_cancel_time", type="integer", example="15", description="订单取消时间单位分钟"),
     *               @SWG\Property(property="order_finish_time", type="string", example="0.001", description="订单自动收货时间单位天"),
     *               @SWG\Property(property="latest_aftersale_time", type="integer", example="1", description="最后售后时间，确认收货后不可申请售后"),
     *               @SWG\Property(property="auto_refuse_time", type="string", example="0", description="售后驳回时间"),
     *               @SWG\Property(property="auto_aftersales", type="string", example="0", description="未发货售后自动同意"),
     *               @SWG\Property(property="offline_aftersales", type="string", example="0", description="到店退货"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function setOrderSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $input = $request->input();
        $setting['order_cancel_time'] = $input['order_cancel_time'] ?? 15; //默认15分钟
        $setting['order_finish_time'] = $input['order_finish_time'] ?? 7;//默认发货后7天
        $setting['latest_aftersale_time'] = $input['latest_aftersale_time'] ?? 0; //默认确认收货后不可申请售后
        $setting['auto_refuse_time'] = $input['auto_refuse_time'] ?? 0; //默认确认收货后不可申请售后
        $setting['auto_aftersales'] = isset($input['auto_aftersales']) && $input['auto_aftersales'] && $input['auto_aftersales'] != 'false'; // 未发货售后自动同意
        $setting['offline_aftersales'] = isset($input['offline_aftersales']) && $input['offline_aftersales'] && $input['offline_aftersales'] != 'false'; // 到店退货
        $setting['is_refund_freight'] =  $input['is_refund_freight'] ?? 0; // 退货退款可退运费

        if ($setting['is_refund_freight'] == 1) {
            event(new RefundFreightAutoZyEvent(['is_refund_freight' => $setting['is_refund_freight']]));
        }

        $result = $this->setOrdersSetting($companyId, $setting);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/order/{order_id}/cancel",
     *     summary="订单取消",
     *     tags={"订单"},
     *     description="订单取消",
     *     operationId="cancel",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="cancel_reason", in="query", description="取消原因", required=true, type="integer"),
     *     @SWG\Parameter( name="other_reason", in="query", description="其他原因", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="cancel_id", type="string", example="2057", description="取消ID"),
     *               @SWG\Property(property="order_id", type="string", example="3316445000300386", description="订单号"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="shop_id", type="integer", example="0", description="门店id"),
     *               @SWG\Property(property="user_id", type="string", example="20386", description="用户id"),
     *               @SWG\Property(property="distributor_id", type="string", example="118", description="分销商id"),
     *               @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 service 服务业订单;bargain 砍价订单;distribution 分销订单;normal 普通实体订单"),
     *               @SWG\Property(property="total_fee", type="string", example="12", description="订单金额，以分为单位"),
     *               @SWG\Property(property="progress", type="integer", example="0", description="处理进度。可选值有 0 待处理;1 已取消;2 处理中;3 已完成; 4 已驳回"),
     *               @SWG\Property(property="cancel_from", type="string", example="shop", description="取消来源。可选值有 buyer 用户取消订单;shop 商家取消订单"),
     *               @SWG\Property(property="cancel_reason", type="string", example="客户商品缺货", description="取消原因"),
     *               @SWG\Property(property="shop_reject_reason", type="string", example="", description="商家拒绝理由"),
     *               @SWG\Property(property="refund_status", type="string", example="WAIT_CHECK", description="退款状态。可选值有 READY 待审核;AUDIT_SUCCESS 审核成功待退款;SUCCESS 退款成功;SHOP_CHECK_FAILS 商家审核不通过;CANCEL 撤销退款;PROCESSING 已发起退款等待到账;FAILS 退款失败;"),
     *               @SWG\Property(property="create_time", type="integer", example="1612165451", description="订单创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1612165451", description="订单更新时间"),
     *               @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *               @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *               @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *               @SWG\Property(property="point", type="integer", example="0", description="消费积分"),
     *               @SWG\Property(property="pay_type", type="string", example="deposit", description="支付方式"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
     public function cancelOrder(Request $request)
     {
          $authInfo = app('auth')->user();
          $params = $request->all();
          $params['company_id'] = $authInfo->get('company_id');
          $params['cancel_from'] = 'shop'; //商家取消订单

          $orderAssociationService = new OrderAssociationService();
          $order = $orderAssociationService->getOrder($authInfo->get('company_id'), $params['order_id']);
          if (!$order) {
               throw new \Exception("订单号为{$params['order_id']}的订单不存在");
          }
          if ($order['order_type'] != 'normal') {
               throw new ResourceException("实体类订单才能取消订单！");
          }
          //获取取消订单原因
          $params['cancel_reason'] = config('order.cancelOrderReason')[$params['cancel_reason']];
          //获取订单用户信息
          $params['user_id'] = $order['user_id'];
          $params['mobile'] = $order['mobile'];
          $params['operator_type'] = app('auth')->user()->get('operator_type');
          $params['operator_id'] = app('auth')->user()->get('operator_id');
          $params['supplier_id'] = 0;
          if ($params['operator_type'] == 'supplier') {
              $params['supplier_id'] = $params['operator_id'];
          }

          /** @var \OrdersBundle\Services\Orders\NormalOrderService $orderService */
          $orderService = $this->getOrderServiceByOrderInfo($order);
          if ($order['delivery_status'] == 'PENDING') {
              //未发货订单，整单取消
              $result = $orderService->cancelOrder($params);
          } else {
              //部分发货订单，部分取消
              $result = $orderService->partailCancelOrder($params);
          }

          return $this->response->array($result);
     }

    /**
     * @SWG\Post(
     *     path="/invoice/invoiced",
     *     summary="设置是否已开发票",
     *     tags={"订单"},
     *     description="设置是否已开发票",
     *     operationId="invoiced",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=true, type="integer"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *              @SWG\Property(property="success", type="string", description="状态"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function setInvoiced(Request $request)
    {
        $authInfo = app('auth')->user();
        $params = $request->all();
        $params['company_id'] = $authInfo->get('company_id');
        $operator_type = $authInfo->get('operator_type');
        $rules = [
            'status' => ['required', '状态不能为空'],
            'order_id' => ['required', '订单号不能为空']
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $params['status'] = $params['status'] == 'true' || $params['status'] == '1';

        if ($operator_type == 'supplier') {
            $orderService = new SupplierOrderService();
            $params['supplier_id'] = $authInfo->get('operator_id');
        } else {
            $orderAssociationService = new OrderAssociationService();
            $order = $orderAssociationService->getOrder($params['company_id'], $params['order_id']);
            if (!$order || !$params['order_id']) {
                throw new ResourceException('无效的订单号');
            }
            $orderService = $this->getOrderService($order['order_type']);
        }
        $result = $orderService->setInvoiced($params);
        if ($result) {
            $return = [
                'success' => true
            ];
        } else {
            $return = [
                'success' => false
            ];
        }
        return $this->response->array($return);
    }

    /**
     * @SWG\Post(
     *     path="/invoice/number",
     *     summary="更新发票号",
     *     tags={"订单"},
     *     description="更新发票号",
     *     operationId="number",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="invoice_number", in="query", description="更新发票号", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *              @SWG\Property(property="success", type="string", description="状态"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function updateInvoiceNumber(Request $request)
    {
        $authInfo = app('auth')->user();
        $params = $request->all();
        $params['company_id'] = $authInfo->get('company_id');
        $rules = [
            'invoice_number' => ['required', '发票号不能为空'],
            'order_id' => ['required', '订单号不能为空']
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($params['company_id'], $params['order_id']);
        if (!$order || !$params['order_id']) {
            throw new ResourceException('无效的订单号');
        }

        $orderService = $this->getOrderService($order['order_type']);
        $result = $orderService->updateInvoiceNumber($params);
        if ($result) {
            $return = [
                'success' => true
            ];
        } else {
            $return = [
                'success' => false
            ];
        }
        return $this->response->array($return);
    }


    /**
     * @SWG\Get(
     *     path="/fapiaolist",
     *     summary="获取用户订单发票列表",
     *     tags={"订单"},
     *     description="获取用户订单发票列表",
     *     operationId="/fapiaolist",
     *     @SWG\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="会员id",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="query",
     *         description="订单id",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         description="状态",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
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
    public function getInvoiceList(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = app('auth')->user()->get('company_id');
        $user_id = $request->input('user_id', 0);
        if ($user_id) {
            $filter['user_id'] = $user_id;
        }
        $id = $request->input('id', 0);
        if ($id) {
            $filter['id'] = $id;
        }
        $order_id = $request->input('order_id', 0);
        if ($order_id) {
            $filter['order_id'] = $order_id;
        }
        $status = $request->input('status', 0);
        if ($status) {
            $filter['status'] = $status;
        }


        // $filter['user_id'] = $authInfo['user_id'];
        $filter['company_id'] = $companyId;
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $userOrderInvoiceService = new UserOrderInvoiceService();

        $result = $userOrderInvoiceService->getDataList($filter, $page, $pageSize);
        // app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($filter) );
        // app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($result)  );
        $data = array();
        $data['list'] = array();
        foreach ($result['list'] as $k => $v) {
            $orderinfo = $this->getOrderInfo($companyId, $v['order_id']);

            $v_info = json_decode($v['invoice'], 1);
            $result['list'][$k]['invoice_arr'] = json_decode($v['invoice'], 1);
            $result['list'][$k]['invoice_arr']['orderInfo'] = $orderinfo['orderInfo'];
            $result['list'][$k]['invoice_arr']['id'] = $v['id'];
            $result['list'][$k]['invoice_arr']['order_id'] = $v['order_id'];
            $result['list'][$k]['invoice_arr']['user_id'] = $v['user_id'];
            $result['list'][$k]['invoice_arr']['status'] = $v['status'];
            $result['list'][$k]['invoice_arr']['type_hz'] = "电子发票";
            //todo
            $result['list'][$k]['invoice_arr']['tax_rate'] = "0.13";//税率
            $result['list'][$k]['invoice_arr']['user_name'] = "李健";//开票人
            $amount = $orderinfo['orderInfo']['total_fee'] ?? 0;

            $result['list'][$k]['invoice_arr']['download_url'] = "https://erp-test-1300056727.cos.ap-chengdu.myqcloud.com/pc/JPG/15b9aecdf66b4f4fb40e36784f6eb3db-_MG_9228.JPG";
            $result['list'][$k]['invoice_arr']['amount'] = bcdiv($amount, 100, 2) ;

            if (isset($v_info['fapiaoinfo']) && is_array($v_info['fapiaoinfo'])) {
                $amount = 0;

                foreach ($v_info['fapiaoinfo'] as $kf => $vf) {
                    $amount = $amount + $vf['price'] * $vf['num'];
                }
                $result['list'][$k]['invoice_arr']['amount'] = bcdiv($amount, 1, 2) ;
                app('log')->debug("\n".__FUNCTION__."-".__LINE__."=>".json_encode($amount));
            }
            //         if(isset($v_info['fapiaoinfo_query'])
            //             && isset($v_info['fapiaoinfo_query'][0]['c_status'] )
            //             && $v_info['fapiaoinfo_query'][0]['c_status'] == '20'
            //         ){

            //                 $query_res = $userOrderInvoiceService->queryFapiao($v_info['fapiaoinfo']);
            //                 if(isset($query_res['result'])
            //                     && $query_res['result'] == "success"){
            //                     $filler = array();
            //                     $filler['id'] = $v['id'];
            //                     $v_info['fapiaoinfo_query'] = $query_res['list'];

            //                     $data_up =array();
            //                     $data_up['invoice'] = json_encode($v_info);

            //                     // $update_res = $userOrderInvoiceService->updateDate($filler, $data_up);

            //                 }

            //                 if($query_res['code'] == "E0000"){
            //                     $filler = array();
            //                     $filler['id'] = $id;
            //                     foreach ($query_res['result'] as $kf => $vf) {
            //                         $query_res['result'][$k]['c_jpg_url'] = $vf['invoiceImageUrl'];
            //                         $query_res['result'][$k]['c_url'] = $vf['invoiceFileUrl'];
            //                     }
            //                     $invoice['fapiaoinfo_query'] = $query_res['result'];

            //                     $data_up =array();
            //                     $data_up['invoice'] = json_encode($invoice);
            // app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($data_up)  );
            //                     $update_res = $userOrderInvoiceService->updateDate($filler, $data_up);
            //                 }

            //         }
            $data['list'][] = $result['list'][$k]['invoice_arr'];
        }
        $data['total_count'] = $result['total_count'];
        // $result['']
        return $this->response->array($data);
    }

    public function getOrderInfo($companyId, $order_id)
    {
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $order_id);
        if (!$order) {
//            app('log')->debug("\此订单不存在！422-".__FUNCTION__."-".__LINE__. "=>".json_encode($filter) );
            return false;
        }

        $orderService = $this->getOrderServiceByOrderInfo($order);
        $result = $orderService->getOrderInfo($companyId, $order_id);
        return $result;
    }

    /**
     * @SWG\Get(
     *     path="/setInvoice",
     *     summary="发票操作",
     *     tags={"订单"},
     *     description="获取用户订单发票列表",
     *     operationId="setInvoice",
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         description="ID",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         description="状态",
     *         required=true,
     *         type="string"
     *     ),
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
    public function setInvoice(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = app('auth')->user()->get('company_id');
        $user_id = $request->input('user_id', 0);
        if ($user_id) {
            $filter['user_id'] = $user_id;
        }
        $id = $request->input('id', 0);
        if ($id) {
            $filter['id'] = $id;
        }
        $order_id = $request->input('order_id', 0);
        if ($order_id) {
            $filter['order_id'] = $order_id;
        }
        $status = $request->input('status', 0);

        app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($status));

        $filter['company_id'] = $companyId;
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $userOrderInvoiceService = new UserOrderInvoiceService();

        $result = $userOrderInvoiceService->getDataList($filter, $page, $pageSize);
        $data = array();
        $data['list'] = array();
        foreach ($result['list'] as $k => $v) {
            $result['list'][$k]['invoice_arr'] = json_decode($v['invoice'], 1);
            $invoice = $result['list'][$k]['invoice_arr'];
            $result['list'][$k]['invoice_arr']['id'] = $v['id'];
            $result['list'][$k]['invoice_arr']['order_id'] = $v['order_id'];
            $result['list'][$k]['invoice_arr']['user_id'] = $v['user_id'];
            $result['list'][$k]['invoice_arr']['status'] = $v['status'];
            $result['list'][$k]['invoice_arr']['type_hz'] = "";
            $result['list'][$k]['invoice_arr']['amount'] = "0";
            $data['list'][] = $result['list'][$k]['invoice_arr'];
            app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($v));
            app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($status));

            $kptype = "1";
            //0 开票
            if ($status == 2 && $id == $v['id']) {
                if (!isset($invoice['fapiaoinfo']) && $v['status'] == 1) {
                    //创建发票
                    $params = $result['list'][$k]['invoice_arr'];
                    $params['company_id'] = $v['company_id'] ?? "0";
                    $params['kptype'] = "1";
                    $kptype = $params['kptype'];

                    app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($params));
                    $open_res = $userOrderInvoiceService->createFapiao($params);
                    app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($open_res));
                    $result['list'][$k]['invoice_arr']['fapiaoinfo'] = $open_res;

                    //更新
                    $data_up = array();
                    $data_up['invoice'] = json_encode($result['list'][$k]['invoice_arr']);
                    $filler = array();
                    $filler['id'] = $id;
                    app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($data_up));
                    if (isset($query_res['returnCode']) && $query_res['returnCode'] == '0000') {
                        $data_up['status'] = $status;
                    }
                    $update_res = $userOrderInvoiceService->updateDate($filler, $data_up);
                }
            }

            //2 冲红
            if ($status == 5 && $id == $v['id'] && $v['status'] == 2) {
                $params = $result['list'][$k]['invoice_arr'];
                $params['company_id'] = $v['company_id'] ?? "0";
                $params['kptype'] = "2";

                $kptype = $params['kptype'];

                $invoice = $result['list'][$k]['invoice_arr'];
                $red_res = $userOrderInvoiceService->createFapiao($params);

                $filler = array();
                $filler['id'] = $id;
                $data_up = array();

                if (isset($query_res['returnCode']) && $query_res['returnCode'] == '0000') {
                    $data_up['status'] = $status;
                }

                $invoice['fapiaoinfo_red'] = $red_res;
                $data_up['invoice'] = json_encode($invoice);
                $update_res = $userOrderInvoiceService->updateDate($filler, $data_up);
            }
            //查询发票. 给他们点时间
            sleep(5);

            //1 查询
            if ($status == 5 || $status == 8 || ($status == 2 && $id == $v['id'])) {
                //查询发票
                $params = $result['list'][$k]['invoice_arr'];
                $params['company_id'] = $v['company_id'] ?? "0";
                $params['kptype'] = $kptype ;
                app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($params));
                $query_res = $userOrderInvoiceService->getFapiao($params);
                app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($query_res));

                $data_up = array();

                //更新 状态
                if (isset($query_res['returnCode']) && $query_res['returnCode'] == '0000') {
                    $data_up['status'] = $status;
                }

                $filler = array();
                $filler['id'] = $id;

                //更新 查询记录
                if ($params['kptype'] == 1) {
                    $invoice['fapiaoinfo_query'] = $query_res['result'];
                }
                if ($params['kptype'] == 2) {
                    $invoice['fapiaoinfo_query_red'] = $query_res['result'];
                }

                $data_up['invoice'] = json_encode($invoice);
                app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "filler =>".json_encode($filler));
                app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "data_up=>".json_encode($data_up));
                $update_res = $userOrderInvoiceService->updateDate($filler, $data_up);
                app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "filler =>".json_encode($filler));
            }

            // // 作废
            // if($status == 9){
            //     $params = array();
            //     $params['id'] = 13;
            //     $params['item_id'] = 13;
            //     $res = $userOrderInvoiceService->gettaxspbm($params);
            //     // app('log')->debug("\n"."-".__FUNCTION__."-".__LINE__. "=>".json_encode($res)  );
            // }
        }
        if (isset($query_res)) {
            $data['query_res'] = $query_res;
        }
        if (isset($red_res)) {
            $data['red_res'] = $red_res;
        }
        $data['total_count'] = $result['total_count'];


        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/writeoff/{order_id}",
     *     summary="获取自提订单核销信息",
     *     tags={"订单"},
     *     description="获取自提订单核销信息",
     *     operationId="getOrderWriteoffInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="path",
     *         description="订单号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="数据集合",
     *               @SWG\Property(property="order_id", type="string", example="3313647000010134", description="订单号"),
     *               @SWG\Property(property="items", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="85", description="ID"),
     *                           @SWG\Property(property="order_id", type="string", example="3313647000010134", description="订单号"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
     *                           @SWG\Property(property="user_id", type="string", example="20134", description="购买用户"),
     *                           @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *                           @SWG\Property(property="item_id", type="string", example="5019", description="商品id"),
     *                           @SWG\Property(property="item_bn", type="string", example="hj001", description="商品编码"),
     *                           @SWG\Property(property="item_name", type="string", example="黄金", description="商品名称"),
     *                           @SWG\Property(property="pic", type="string", example="http://bbctest.aixue7.com/image/1/2020/08/03/b22be4303a51a762fa32e226dd40418cnIUa2NLGrtuWGiEt27ZY1npobUQlhLc4", description="商品图片"),
     *                           @SWG\Property(property="num", type="integer", example="1", description="购买商品数量"),
     *                           @SWG\Property(property="price", type="integer", example="2", description="单价，以分为单位"),
     *                           @SWG\Property(property="total_fee", type="integer", example="2", description="应付总金额,以分为单位"),
     *                           @SWG\Property(property="templates_id", type="integer", example="94", description="运费模板id"),
     *                           @SWG\Property(property="rebate", type="integer", example="0", description="单个分销金额，以分为单位"),
     *                           @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
     *                           @SWG\Property(property="item_fee", type="integer", example="2", description="商品总金额，以分为单位"),
     *                           @SWG\Property(property="cost_fee", type="integer", example="10000", description="商品成本价，以分为单位"),
     *                           @SWG\Property(property="item_unit", type="string", example="", description="商品计量单位"),
     *                           @SWG\Property(property="member_discount", type="integer", example="0", description="会员折扣金额，以分为单位"),
     *                           @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *                           @SWG\Property(property="discount_fee", type="integer", example="0", description="订单优惠金额"),
     *                           @SWG\Property(property="discount_info", type="string", description=""),
     *                           @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
     *                           @SWG\Property(property="is_total_store", type="string", example="1", description="是否是总部库存(true:总部库存，false:店铺库存)"),
     *                           @SWG\Property(property="distributor_id", type="string", example="21", description="门店ID"),
     *                           @SWG\Property(property="create_time", type="integer", example="1611648651", description="订单创建时间"),
     *                           @SWG\Property(property="update_time", type="integer", example="1611648651", description="订单更新时间"),
     *                           @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *                           @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *                           @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *                           @SWG\Property(property="delivery_time", type="string", example="", description="发货时间"),
     *                           @SWG\Property(property="delivery_status", type="string", example="PENDING", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                           @SWG\Property(property="aftersales_status", type="string", example="", description="售后状态。可选值有 WAIT_SELLER_AGREE 0 等待商家处理;WAIT_BUYER_RETURN_GOODS 1 商家接受申请，等待消费者回寄;WAIT_SELLER_CONFIRM_GOODS 2 消费者回寄，等待商家收货确认;SELLER_REFUSE_BUYER 3 售后驳回;SELLER_SEND_GOODS 4 卖家重新发货 换货完成;REFUND_SUCCESS 5 退款成功;REFUND_CLOSED 6 退款关闭;CLOSED 7 售后关闭"),
     *                           @SWG\Property(property="refunded_fee", type="integer", example="0", description="退款金额，以分为单位"),
     *                           @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *                           @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *                           @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                           @SWG\Property(property="cny_fee", type="integer", example="2", description=""),
     *                           @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
     *                           @SWG\Property(property="point", type="integer", example="0", description="商品总积分"),
     *                           @SWG\Property(property="item_spec_desc", type="string", example="", description="商品规格描述"),
     *                           @SWG\Property(property="order_item_type", type="string", example="normal", description="订单商品类型,normal:正常商品，gift: 赠品, plus_buy: 加价购商品"),
     *                           @SWG\Property(property="volume", type="integer", example="0", description="商品体积"),
     *                           @SWG\Property(property="weight", type="integer", example="0", description="商品重量"),
     *                           @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *                           @SWG\Property(property="auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *                           @SWG\Property(property="share_points", type="integer", example="0", description="积分抵扣时分摊的积分值"),
     *                           @SWG\Property(property="point_fee", type="integer", example="0", description="积分抵扣时分摊的积分的金额，以分为单位"),
     *                           @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *                           @SWG\Property(property="delivery_item_num", type="string", example="", description="发货单发货数量"),
     *                           @SWG\Property(property="get_points", type="integer", example="0", description="商品获取积分"),
     *                           @SWG\Property(property="after_sales_fee", type="integer", example="0", description=""),
     *                           @SWG\Property(property="remain_fee", type="integer", example="2", description=""),
     *                           @SWG\Property(property="remain_point", type="integer", example="0", description=""),
     *                 ),
     *               ),
     *               @SWG\Property(property="pickupcode_status", type="string", example="", description="提货码的状态"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getOrderWriteoffInfo($order_id)
    {
        $companyId = app('auth')->user()->get('company_id');
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $order_id);
        if (!$order) {
            return $this->response->error('此订单不存在！', 422);
        }

        $orderService = $this->getOrderServiceByOrderInfo($order);
        $detail = $orderService->getOrderInfo($companyId, $order_id);
        // 获取提货码的状态  是否开启
        $settingService = new SettingService();
        $pickupCodeSetting = $settingService->presalePickupcodeGet($companyId);
        $orderInfo = $detail['orderInfo'];
        $result = [
            'order_id' => $orderInfo['order_id'],
            'items' => $orderInfo['items'],
            'pickupcode_status' => $pickupCodeSetting['pickupcode_status'],
        ];

        return $this->response->array($result);
    }

    /**
     * @SWG\Definition(
     *     definition="WriteOffOrderInfo",
     *     type="object",
     *               @SWG\Property(property="order_id", type="string", example="3321656000130032", description="订单号"),
     *               @SWG\Property(property="title", type="string", example="内蒙古正宗羊肉...", description="订单标题"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="user_id", type="string", example="20032", description="用户id"),
     *               @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *               @SWG\Property(property="mobile", type="string", example="15901872216", description="手机号"),
     *               @SWG\Property(property="freight_fee", type="integer", example="0", description="运费价格，以分为单位"),
     *               @SWG\Property(property="freight_type", type="string", example="cash", description="运费类型-用于积分商城 cash:现金 point:积分"),
     *               @SWG\Property(property="item_fee", type="string", example="1", description="商品金额，以分为单位"),
     *               @SWG\Property(property="item_point", type="integer", example="0", description="商品消费总积分"),
     *               @SWG\Property(property="cost_fee", type="integer", example="4000", description="商品成本价，以分为单位"),
     *               @SWG\Property(property="total_fee", type="string", example="1", description="订单金额，以分为单位"),
     *               @SWG\Property(property="step_paid_fee", type="integer", example="0", description="分阶段付款已支付金额，以分为单位"),
     *               @SWG\Property(property="total_rebate", type="integer", example="0", description="订单总分销金额，以分为单位"),
     *               @SWG\Property(property="distributor_id", type="string", example="103", description="分销商id"),
     *               @SWG\Property(property="receipt_type", type="string", example="ziti", description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
     *               @SWG\Property(property="ziti_code", type="string", example="824872", description="店铺自提码"),
     *               @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *               @SWG\Property(property="ziti_status", type="string", example="DONE", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *               @SWG\Property(property="order_status", type="string", example="DONE", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *               @SWG\Property(property="order_source", type="string", example="shop_offline", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
     *               @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *               @SWG\Property(property="order_class", type="string", example="shopguide", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
     *               @SWG\Property(property="auto_cancel_time", type="string", example="1612341546", description="订单自动取消时间"),
     *               @SWG\Property(property="auto_cancel_seconds", type="integer", example="-1460055", description=""),
     *               @SWG\Property(property="auto_finish_time", type="string", example="", description="订单自动完成时间"),
     *               @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *               @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *               @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *               @SWG\Property(property="salesman_id", type="string", example="78", description="导购员ID"),
     *               @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *               @SWG\Property(property="delivery_corp_source", type="string", example="", description="快递代码来源"),
     *               @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *               @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *               @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *               @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *               @SWG\Property(property="delivery_time", type="integer", example="1613801601", description="发货时间"),
     *               @SWG\Property(property="end_time", type="integer", example="1613801601", description="订单完成时间"),
     *               @SWG\Property(property="end_date", type="string", example="2021-02-20 14:13:21", description=""),
     *               @SWG\Property(property="receiver_name", type="string", example="", description="收货人姓名"),
     *               @SWG\Property(property="receiver_mobile", type="string", example="", description="收货人手机号"),
     *               @SWG\Property(property="receiver_zip", type="string", example="", description="收货人邮编"),
     *               @SWG\Property(property="receiver_state", type="string", example="", description="收货人所在省份"),
     *               @SWG\Property(property="receiver_city", type="string", example="", description="收货人所在城市"),
     *               @SWG\Property(property="receiver_district", type="string", example="", description="收货人所在地区"),
     *               @SWG\Property(property="receiver_address", type="string", example="", description="收货人详细地址"),
     *               @SWG\Property(property="member_discount", type="integer", example="0", description="会员折扣金额，以分为单位"),
     *               @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *               @SWG\Property(property="discount_fee", type="integer", example="0", description="订单优惠金额，以分为单位"),
     *               @SWG\Property(property="create_time", type="integer", example="1612340646", description="订单创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1613801601", description="订单更新时间"),
     *               @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *               @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *               @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *               @SWG\Property(property="cny_fee", type="integer", example="1", description=""),
     *               @SWG\Property(property="point", type="integer", example="0", description="消费积分"),
     *               @SWG\Property(property="pay_type", type="string", example="pos", description="支付方式"),
     *               @SWG\Property(property="remark", type="string", example="", description="订单备注"),
     *               @SWG\Property(property="third_params", type="object", description="",
     *                   @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
     *               ),
     *               @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
     *               @SWG\Property(property="send_point", type="integer", example="0", description="是否分发积分0否 1是"),
     *               @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *               @SWG\Property(property="is_invoiced", type="string", example="", description="是否已开发票"),
     *               @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
     *               @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *               @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *               @SWG\Property(property="point_fee", type="integer", example="0", description="积分抵扣金额，以分为单位"),
     *               @SWG\Property(property="point_use", type="integer", example="0", description="积分抵扣使用的积分数"),
     *               @SWG\Property(property="uppoint_use", type="integer", example="0", description="积分抵扣商家补贴的积分数(基础积分-使用的升值积分)"),
     *               @SWG\Property(property="point_up_use", type="integer", example="0", description="积分抵扣使用的积分升值数"),
     *               @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *               @SWG\Property(property="get_points", type="integer", example="0", description="订单获取积分"),
     *               @SWG\Property(property="bonus_points", type="integer", example="0", description="购物赠送积分"),
     *               @SWG\Property(property="get_point_type", type="integer", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *               @SWG\Property(property="pack", type="string", example="", description="包装"),
     *               @SWG\Property(property="is_shopscreen", type="string", example="", description="是否门店订单"),
     *               @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *               @SWG\Property(property="is_profitsharing", type="integer", example="1", description="是否分账订单 1不分账 2分账"),
     *               @SWG\Property(property="profitsharing_status", type="integer", example="1", description="分账状态 1未分账 2已分账"),
     *               @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="", description="自动关闭售后时间"),
     *               @SWG\Property(property="profitsharing_rate", type="integer", example="0", description="分账费率"),
     *               @SWG\Property(property="bind_auth_code", type="string", example="", description="订单订单验证码"),
     *               @SWG\Property(property="extra_points", type="integer", example="0", description="订单获取额外积分"),
     *               @SWG\Property(property="type", type="integer", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *               @SWG\Property(property="taxable_fee", type="integer", example="0", description="计税总价，以分为单位"),
     *               @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
     *               @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
     *               @SWG\Property(property="total_tax", type="integer", example="0", description="总税费"),
     *               @SWG\Property(property="discount_info", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="integer", example="0", description=""),
     *                           @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                           @SWG\Property(property="info", type="string", example="会员价", description=""),
     *                           @SWG\Property(property="rule", type="string", example="会员折扣优惠2", description=""),
     *                           @SWG\Property(property="discount_fee", type="integer", example="0", description="订单优惠金额，以分为单位"),
     *                 ),
     *               ),
     *            ),
     */

    /**
     * @SWG\Post(
     *     path="/writeoff/{order_id}",
     *     summary="自提订单核销",
     *     tags={"订单"},
     *     description="自提订单核销",
     *     operationId="getOrderWriteoffInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="path",
     *         description="订单号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pickupcode",
     *         in="path",
     *         description="提货码",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *                   @SWG\Property(property="data", type="object",
     *                       ref="#/definitions/WriteOffOrderInfo"
     *                   ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function orderWriteoff($order_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operatorId = (int)app('auth')->user()->get('operator_id');

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $order_id);
        if (!$order) {
            return $this->response->error('此订单不存在！', 422);
        }
        $settingService = new SettingService();
        $pickupCodeSetting = $settingService->presalePickupcodeGet($companyId);
        $pickupcode_status = $pickupCodeSetting['pickupcode_status'];

        //社区团购订单不需要提货码
        if ($order['order_class'] == 'community') {
            $pickupcode_status = false;
        }

        // 如果开启了 提货码 提货码必填
        $pickupcode = $request->get('pickupcode');
        if ($pickupcode_status && !$pickupcode) {
            throw new ResourceException('提货码必填!');
        }

        $orderService = $this->getOrderServiceByOrderInfo($order);
        $result = $orderService->orderZitiWriteoff($companyId, $order_id, $pickupcode_status, $pickupcode, "admin", $operatorId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/qr_writeoff",
     *     summary="扫码核销订单",
     *     tags={"订单"},
     *     description="扫码核销订单",
     *     operationId="orderWriteoffQC",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="code",
     *         in="path",
     *         description="核销码",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *                   @SWG\Property(property="data", type="object",
     *                       ref="#/definitions/WriteOffOrderInfo"
     *                   ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function orderWriteoffQR(Request $request)
    {
        $authInfo = $this->auth->user();
        $operatorId = (int)$authInfo->get('operator_id');
        $companyId = $authInfo->get('company_id');

        $orderService = new OrderService(new NormalOrderService());
        // 使用自提码核销
        $code = $request->get('code');

        if (!$code) {
            throw new ResourceException('code参数必填');
        }

        // 从核销码中获取orderId
        $orderId = $orderService->getOrderIdByCode($code);
        if (!$orderId) {
            throw new ResourceException('核销码已过期');
        }

        $orderInfo = $orderService->getOrderInfo($companyId, $orderId);
        if (!$orderInfo) {
            throw new ResourceException('订单不存在');
        }

        if (!$orderInfo['orderInfo']) {
            throw new ResourceException('核销自提订单有误');
        }

        $distributor_ids = array_column($authInfo->get('distributor_ids'), 'distributor_id');

        if ($orderInfo['orderInfo']['shop_id'] && !in_array($orderInfo['orderInfo']['shop_id'], $authInfo->get('shop_ids'))) {
            throw new ResourceException('请确认是否有店铺核销权限！');
        } elseif ($orderInfo['orderInfo']['distributor_id'] && !in_array($orderInfo['orderInfo']['distributor_id'], $distributor_ids)) {
            throw new ResourceException('请确认是否有店铺核销权限！');
        }

        if ($orderInfo['orderInfo']['ziti_code'] != intval(substr($code, 0, 6))) {
            throw new ResourceException('核销自提订单有误');
        }

        if ($orderInfo['orderInfo']['ziti_status'] == 'DONE' && $orderInfo['orderInfo']['order_status'] == 'DONE') {
            throw new ResourceException('该订单已完成自提，请重新确认');
        }

        if ($orderInfo['orderInfo']['cancel_status'] == 'WAIT_PROCESS') {
            throw new ResourceException('订单有未处理的取消申请，不能核销');
        }

        $result = $orderService->orderZitiWriteoff($companyId, $orderId, false, '', "admin", $operatorId);
        return $this->response->array($result);
    }

    /**
     * @SWG\get(
     *     path="/order/process/{orderId}",
     *     summary="查看订单操作记录",
     *     tags={"订单"},
     *     description="查看订单操作记录",
     *     operationId="getOrderProcessLog",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *             @SWG\Property(property="data", type="array", description="",
     *               @SWG\Items(
     *                   @SWG\Property(property="id", type="string", example="6233", description="ID"),
     *                   @SWG\Property(property="order_id", type="string", example="3321684000300350", description="订单id"),
     *                   @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                   @SWG\Property(property="operator_type", type="string", example="user", description="操作类型 用户:user 导购:salesperon 管理员:admin 系统:system"),
     *                   @SWG\Property(property="operator_id", type="string", example="20350", description="操作员id"),
     *                   @SWG\Property(property="operator_name", type="string", example="17638125092", description="操作员名字"),
     *                   @SWG\Property(property="remarks", type="string", example="订单售后", description="订单操作备注"),
     *                   @SWG\Property(property="detail", type="string", example="售后单号：202102039952989 申请售后，申请原因：123", description="订单操作detail"),
     *                   @SWG\Property(property="params", type="string", example="", description="提交参数(DC2Type:json_array)"),
     *                   @SWG\Property(property="create_time", type="string", example="1612343276", description="订单操作时间"),
     *                   @SWG\Property(property="update_time", type="string", example="1612343276", description="订单操作"),
     *               ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
     public function getOrderProcessLog($orderId, Request $request)
     {
          $companyId = app('auth')->user()->get('company_id');
          $operator_type = app('auth')->user()->get('operator_type');
          $operator_id = app('auth')->user()->get('operator_id');
          $isDataMask = $request->get('x-datapass-block', 0);//加密数据权限
          $orderProcessLogService = new OrderProcessLogService();
          $operatorsService = new OperatorsService();
          $filter = [
               'order_id' => $orderId,
               'company_id' => $companyId,
          ];
          if ($operator_type == 'supplier') {
              $filter['supplier_id'] = [0, $operator_id];
          }
          $result = $orderProcessLogService->getLists($filter, '*', 1, -1, ['create_time' => 'desc', 'id' => 'desc']);
          foreach ($result as &$v) {
               if ($v['operator_type'] == 'admin' && $v['operator_id'] > 0) {
                    $operator = $operatorsService->getInfo(['operator_id' => $v['operator_id']]);
                    if ($operator) {
                         $v['operator_name'] = $operator['mobile'];
                    }
               }
               if ($isDataMask) {
                    if (ismobile($v['operator_name'])) {
                         $v['operator_name'] = data_masking('mobile', $v['operator_name']);
                    }
               }
              $v['pics'] = json_decode($v['pics'],true);
          }
          return $result;
     }

    /**
     * @SWG\Post(
     *     path="/businessreceipt/{orderId}",
     *     summary="商家接单",
     *     tags={"订单"},
     *     description="达达同城配，商家接单",
     *     operationId="businessReceipt",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="订单号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="status", type="boolean", example=true),
     *
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function businessReceipt($orderId)
    {
        $companyId = app('auth')->user()->get('company_id');
        $localDeliveryService = new LocalDeliveryService();
        $operator['operator_type'] = 'admin';
        $operator['operator_id'] = app('auth')->user()->get('operator_id');
        $localDeliveryService->getOrderService()->businessReceipt($companyId, $orderId, $operator);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/confirm/goods/{orderId}",
     *     summary="商家确认退回",
     *     tags={"订单"},
     *     description="达达同城配，商家确认退回",
     *     operationId="confirmGoods",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="订单号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="status", type="boolean", example=true),
     *
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function confirmGoods($orderId)
    {
        $companyId = app('auth')->user()->get('company_id');
        $localDeliveryService = new LocalDeliveryService();
        $operator['operator_type'] = 'admin';
        $operator['operator_id'] = app('auth')->user()->get('operator_id');
        $localDeliveryService->getOrderService()->confirmGoods($companyId, $orderId, $operator);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/confirmReceipt",
     *     summary="确认送达",
     *     tags={"订单"},
     *     description="确认送达",
     *     operationId="confirmReceipt",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单id", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="order_id", type="string", example="3309653000180376", description="订单号"),
     *               @SWG\Property(property="title", type="string", example="测试0119-2...", description="订单标题"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="user_id", type="string", example="20376", description="用户id"),
     *               @SWG\Property(property="act_id", type="string", example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
     *               @SWG\Property(property="mobile", type="string", example="13095920688", description="手机号"),
     *               @SWG\Property(property="freight_fee", type="integer", example="1", description="运费价格，以分为单位"),
     *               @SWG\Property(property="freight_type", type="string", example="cash", description=""),
     *               @SWG\Property(property="item_fee", type="string", example="1", description="商品金额，以分为单位"),
     *               @SWG\Property(property="item_point", type="integer", example="0", description=""),
     *               @SWG\Property(property="cost_fee", type="integer", example="10000", description="商品成本价，以分为单位"),
     *               @SWG\Property(property="total_fee", type="string", example="2", description="订单金额，以分为单位"),
     *               @SWG\Property(property="step_paid_fee", type="integer", example="0", description="分阶段付款已支付金额，以分为单位"),
     *               @SWG\Property(property="total_rebate", type="integer", example="0", description="订单总分销金额，以分为单位"),
     *               @SWG\Property(property="distributor_id", type="string", example="104", description="分销商id"),
     *               @SWG\Property(property="receipt_type", type="string", example="logistics", description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
     *               @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
     *               @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *               @SWG\Property(property="ziti_status", type="string", example="NOTZITI", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *               @SWG\Property(property="order_status", type="string", example="DONE", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
     *               @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单"),
     *               @SWG\Property(property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单"),
     *               @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
     *               @SWG\Property(property="auto_cancel_time", type="string", example="1611304246", description="订单自动取消时间"),
     *               @SWG\Property(property="auto_cancel_seconds", type="integer", example="354", description=""),
     *               @SWG\Property(property="auto_finish_time", type="string", example="1611908660", description="订单自动完成时间"),
     *               @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
     *               @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
     *               @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *               @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
     *               @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
     *               @SWG\Property(property="delivery_corp_source", type="string", example="kuaidi100", description="快递代码来源"),
     *               @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
     *               @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
     *               @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"),
     *               @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *               @SWG\Property(property="delivery_time", type="integer", example="1611303860", description="发货时间"),
     *               @SWG\Property(property="end_time", type="integer", example="1611303892", description="订单完成时间"),
     *               @SWG\Property(property="end_date", type="string", example="2021-01-22 16:24:52", description=""),
     *               @SWG\Property(property="receiver_name", type="string", example="张三", description="收货人姓名"),
     *               @SWG\Property(property="receiver_mobile", type="string", example="13095920688", description="收货人手机号"),
     *               @SWG\Property(property="receiver_zip", type="string", example="101001", description="收货人邮编"),
     *               @SWG\Property(property="receiver_state", type="string", example="北京市", description="收货人所在省份"),
     *               @SWG\Property(property="receiver_city", type="string", example="北京市", description="收货人所在城市"),
     *               @SWG\Property(property="receiver_district", type="string", example="东城", description="收货人所在地区"),
     *               @SWG\Property(property="receiver_address", type="string", example="101", description="收货人详细地址"),
     *               @SWG\Property(property="member_discount", type="integer", example="0", description="会员折扣金额，以分为单位"),
     *               @SWG\Property(property="coupon_discount", type="integer", example="0", description="优惠券抵扣金额，以分为单位"),
     *               @SWG\Property(property="discount_fee", type="integer", example="0", description="订单优惠金额，以分为单位"),
     *               @SWG\Property(property="create_time", type="integer", example="1611303646", description="订单创建时间"),
     *               @SWG\Property(property="update_time", type="integer", example="1611303892", description="订单更新时间"),
     *               @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
     *               @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
     *               @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
     *               @SWG\Property(property="cny_fee", type="integer", example="2", description=""),
     *               @SWG\Property(property="point", type="integer", example="0", description="消费积分"),
     *               @SWG\Property(property="pay_type", type="string", example="wxpay", description="支付方式"),
     *               @SWG\Property(property="remark", type="string", example="", description="订单备注"),
     *              @SWG\Property(property="third_params", type="object", description="",
     *                   @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
     *              ),
     *               @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
     *               @SWG\Property(property="send_point", type="integer", example="0", description="是否分发积分0否 1是"),
     *               @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
     *               @SWG\Property(property="is_invoiced", type="string", example="", description="是否已开发票"),
     *               @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
     *               @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *               @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *               @SWG\Property(property="point_fee", type="integer", example="0", description="积分抵扣金额，以分为单位"),
     *               @SWG\Property(property="point_use", type="integer", example="0", description="积分抵扣使用的积分数"),
     *               @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *               @SWG\Property(property="get_points", type="integer", example="2", description="订单获取积分"),
     *               @SWG\Property(property="bonus_points", type="integer", example="0", description="购物赠送积分"),
     *               @SWG\Property(property="get_point_type", type="integer", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *               @SWG\Property(property="pack", type="string", example="", description="包装"),
     *               @SWG\Property(property="is_shopscreen", type="string", example="", description="是否门店订单"),
     *               @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
     *               @SWG\Property(property="is_profitsharing", type="integer", example="1", description="是否分账订单 1不分账 2分账"),
     *               @SWG\Property(property="profitsharing_status", type="integer", example="1", description="分账状态 1未分账 2已分账"),
     *               @SWG\Property(property="order_auto_close_aftersales_time", type="integer", example="1611390292", description="自动关闭售后时间"),
     *               @SWG\Property(property="profitsharing_rate", type="integer", example="0", description="分账费率"),
     *               @SWG\Property(property="bind_auth_code", type="string", example="", description=""),
     *               @SWG\Property(property="extra_points", type="integer", example="0", description=""),
     *               @SWG\Property(property="type", type="integer", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *               @SWG\Property(property="taxable_fee", type="integer", example="0", description="计税总价，以分为单位"),
     *               @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
     *               @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
     *               @SWG\Property(property="total_tax", type="integer", example="0", description="总税费"),
     *               @SWG\Property(property="discount_info", type="string", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function confirmReceipt(Request $request)
    {
        // 获取公司id
        $companyId = (int)app('auth')->user()->get('company_id');
        $adminOperatorId = (int)app('auth')->user()->get('operator_id');

        // 获取订单id
        $orderId = $request->input("order_id");
        if (!$orderId) {
            throw new ResourceException("参数有误！订单号不存在！");
        }

        // 获取订单信息
        $order = (new OrderAssociationService())->getOrder($companyId, $orderId);
        if (!$order) {
            throw new ResourceException("订单号为{$orderId}的订单不存在");
        }
        if (empty($order["order_type"]) || $order["order_type"] !== "normal") {
            throw new ResourceException("实体类订单才能取消订单！");
        }

        // 获取订单服务
        $orderService = $this->getOrderServiceByOrderInfo($order);

        if (!property_exists($orderService, "normalOrdersRepository")
            || !($orderService->normalOrdersRepository instanceof NormalOrdersRepository)
            || !method_exists($orderService, "confirmReceipt")) {
            throw new ResourceException("操作失败！");
        }

        // 获取订单信息
        $orderInfo = $orderService->normalOrdersRepository->getInfo([
            "company_id" => $companyId,
            "order_id" => $orderId,
            "receipt_type" => OrderReceiptTypeConstant::MERCHANT,
            "delivery_status" => "DONE"
        ]);
        if (!$orderInfo) {
            throw new ResourceException("操作失败！该订单不属于商家自配或未发货！");
        }

        // 确认送达,
        $result = $orderService->confirmReceipt([
            "order_id" => $orderId,
            "company_id" => $companyId,
            "user_id" => $orderId["user_id"] ?? null,
        ], [
            "operator_type" => "admin",
            "operator_id" => $adminOperatorId
        ]);

        return $this->response->array($result);
    }

     /**
      * @SWG\Post(
      *     path="/order/markdown",
      *     summary="订单改价",
      *     tags={"订单"},
      *     description="订单改价",
      *     operationId="markDown",
      *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter(name="order_id", in="query", description="订单号", required=true, type="string"),
      *     @SWG\Parameter(name="down_type", in="query", description="改价类型 total=>整单改价,items=>按件改价", required=true, type="string"),
      *     @SWG\Parameter(name="total_fee", in="query", description="整单改价金额，类型为total时必填", required=false, type="string"),
      *     @SWG\Parameter(name="items[][item_id]", in="query", description="按件改价商品ID", required=false, type="string"),
      *     @SWG\Parameter(name="items[][total_fee]", in="query", description="按件改价商品总价", required=false, type="string"),
      *     @SWG\Parameter(name="items[][discount]", in="query", description="按件改价商品折扣", required=false, type="string"),
      *     @SWG\Response(
      *         response="200",
      *         description="响应信息返回",
      *         @SWG\Schema(
      *              @SWG\Property(property="data", type="object", description="",
      *                   @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
      *                   @SWG\Property(property="title", type="string", example="测试商品会员价导入...", description="订单标题"),
      *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
      *                   @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
      *                   @SWG\Property(property="act_id", type="string", example="",  description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
      *                   @SWG\Property(property="mobile", type="string", example="17621502659", description="购买用户手机号"),
      *                   @SWG\Property(property="freight_fee", type="integer", example="0", description="运费价格，以分为单位"),
      *                   @SWG\Property(property="freight_type", type="string", example="cash", description="运费类型-用于积分商城  cash:现金 point:积分"),
      *                   @SWG\Property(property="item_fee", type="string", example="100", description="商品总金额，以分为单位"),
      *                   @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
      *                   @SWG\Property(property="cost_fee", type="integer", example="100", description="商品成本价，以分为单位"),
      *                   @SWG\Property(property="total_fee", type="string", example="0", description="应付总金额,以分为单位"),
      *                   @SWG\Property(property="step_paid_fee", type="integer", example="0",  description="分阶段付款已支付金额，以分为单位"),
      *                   @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
      *                   @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
      *                   @SWG\Property(property="receipt_type", type="string", example="logistics",  description="收货方式。可选值有 logistics:物流;ziti:店铺自提;dada:同城配"),
      *                   @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
      *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
      *                   @SWG\Property(property="ziti_status", type="string", example="NOTZITI",  description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
      *                   @SWG\Property(property="order_status", type="string", example="WAIT_BUYER_CONFIRM",  description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CAN CEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
      *                   @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有  member-用户自主下单;shop-商家代客下单"),
      *                   @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有  normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
      *                   @SWG\Property(property="auto_cancel_time", type="string", example="1612150545",  description="订单自动取消时间"),
      *                   @SWG\Property(property="auto_cancel_seconds", type="integer", example="-12452", description=""),
      *                   @SWG\Property(property="auto_finish_time", type="string", example="1612755464",  description="订单自动完成时间"),
      *                   @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
      *                   @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
      *                   @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
      *                   @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
      *                   @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
      *                   @SWG\Property(property="delivery_corp_source", type="string", example="kuaidi100",  description="快递代码来源"),
      *                   @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
      *                   @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
      *                   @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有  DONE—已发货;PENDING—待发货"),
      *                   @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL",  description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS  取消失败"),
      *                   @SWG\Property(property="delivery_time", type="integer", example="1612150664", description="发货时间"),
      *                   @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
      *                   @SWG\Property(property="end_date", type="string", example="", description=""),
      *                   @SWG\Property(property="receiver_name", type="string", example="1232", description="收货人姓名"),
      *                   @SWG\Property(property="receiver_mobile", type="string", example="17653569856",  description="收货人手机号"),
      *                   @SWG\Property(property="receiver_zip", type="string", example="000000", description="收货人邮编"),
      *                   @SWG\Property(property="receiver_state", type="string", example="北京市", description="收货人所在省份"),
      *                   @SWG\Property(property="receiver_city", type="string", example="北京市", description="收货人所在城市"),
      *                   @SWG\Property(property="receiver_district", type="string", example="东城", description="收货人所在地区"),
      *                   @SWG\Property(property="receiver_address", type="string", example="123123", description="收货人详细地址"),
      *                   @SWG\Property(property="member_discount", type="integer", example="20",  description="会员折扣金额，以分为单位"),
      *                   @SWG\Property(property="coupon_discount", type="integer", example="0",  description="优惠券抵扣金额，以分为单位"),
      *                   @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
      *                   @SWG\Property(property="create_time", type="integer", example="1612150245", description="订单创建时间"),
      *                   @SWG\Property(property="update_time", type="integer", example="1612150664", description="订单更新时间"),
      *                   @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
      *                   @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
      *                   @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
      *                   @SWG\Property(property="cny_fee", type="integer", example="0", description=""),
      *                   @SWG\Property(property="point", type="integer", example="16", description="商品总积分"),
      *                   @SWG\Property(property="pay_type", type="string", example="point",  description="支付方式。wxpay-微信支付;deposit-预存款支付;pos-刷卡;point-积分"),
      *                   @SWG\Property(property="remark", type="string", example="", description="订单备注"),
      *                  @SWG\Property(property="third_params", type="object", description="",
      *                           @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
      *                  ),
      *                   @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
      *                   @SWG\Property(property="send_point", type="integer", example="0", description="是否分发积分0否 1是"),
      *                   @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
      *                   @SWG\Property(property="is_invoiced", type="string", example="", description="是否已开发票"),
      *                   @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
      *                   @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态  approved成功 processing审核中 rejected审核拒绝"),
      *                   @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
      *                   @SWG\Property(property="point_fee", type="integer", example="80",  description="积分抵扣时分摊的积分的金额，以分为单位"),
      *                   @SWG\Property(property="point_use", type="integer", example="16", description="积分抵扣使用的积分数"),
      *                   @SWG\Property(property="uppoint_use", type="integer", example="0", description="积分抵扣使用的积分升值数"),
      *                   @SWG\Property(property="point_up_use", type="integer", example="0", description=""),
      *                   @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有  NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
      *                   @SWG\Property(property="get_points", type="integer", example="0", description="商品获取积分"),
      *                   @SWG\Property(property="bonus_points", type="integer", example="0", description="购物赠送积分"),
      *                   @SWG\Property(property="get_point_type", type="integer", example="1", description="获取积分类型，0  老订单按订单完成时送,1 新订单按下单时计算送"),
      *                   @SWG\Property(property="pack", type="string", example="", description="包装"),
      *                   @SWG\Property(property="is_shopscreen", type="string", example="", description="是否门店订单"),
      *                   @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
      *                   @SWG\Property(property="is_profitsharing", type="integer", example="1", description="是否分账订单 1不分账  2分账"),
      *                   @SWG\Property(property="profitsharing_status", type="integer", example="1", description="分账状态 1未分账  2已分账"),
      *                   @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="",  description="自动关闭售后时间"),
      *                   @SWG\Property(property="profitsharing_rate", type="integer", example="0", description="分账费率"),
      *                   @SWG\Property(property="bind_auth_code", type="string", example="", description="订单订单验证码"),
      *                   @SWG\Property(property="extra_points", type="integer", example="0", description="订单获取额外积分"),
      *                   @SWG\Property(property="type", type="integer", example="0",  description="订单类型，0普通订单,1跨境订单,....其他"),
      *                   @SWG\Property(property="taxable_fee", type="integer", example="0", description="计税总价，以分为单位"),
      *                   @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
      *                   @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
      *                   @SWG\Property(property="total_tax", type="integer", example="0", description="总税费"),
      *                   @SWG\Property(property="discount_info", type="array", description="",
      *                     @SWG\Items(
      *                          @SWG\Property(property="id", type="integer", example="0", description="ID"),
      *                          @SWG\Property(property="type", type="string", example="member_price",  description="订单类型，0普通订单,1跨境订单,....其他"),
      *                          @SWG\Property(property="info", type="string", example="会员价", description=""),
      *                          @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则( DC2Type:json_array)"),
      *                          @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
      *                     ),
      *                   ),
      *                   @SWG\Property(property="can_apply_aftersales", type="integer", example="0", description=""),
      *                   @SWG\Property(property="distributor_name", type="string", example="中关村东路123号院3号楼",  description=""),
      *                   @SWG\Property(property="items", type="array", description="",
      *                     @SWG\Items(
      *                                           @SWG\Property(property="id", type="string", example="8997", description="ID"),
      *                                           @SWG\Property(property="order_id", type="string", example="3319460000470394",  description="订单号"),
      *                                           @SWG\Property(property="company_id", type="string", example="1",  description="企业ID"),
      *                                           @SWG\Property(property="user_id", type="string", example="20394",  description="购买用户"),
      *                                           @SWG\Property(property="act_id", type="string", example="",  description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
      *                                           @SWG\Property(property="item_id", type="string", example="5461",  description="商品id"),
      *                                           @SWG\Property(property="item_bn", type="string", example="gyct2021001",  description="商品编码"),
      *                                           @SWG\Property(property="item_name", type="string", example="测试商品会员价导入",  description="商品名称"),
      *                                           @SWG\Property(property="pic", type="string", example="", description="商品图片"),
      *                                           @SWG\Property(property="num", type="integer", example="1",  description="购买商品数量"),
      *                                           @SWG\Property(property="price", type="integer", example="100",  description="单价，以分为单位"),
      *                                           @SWG\Property(property="total_fee", type="integer", example="0",  description="应付总金额,以分为单位"),
      *                                           @SWG\Property(property="templates_id", type="integer", example="1",  description="运费模板id"),
      *                                           @SWG\Property(property="rebate", type="integer", example="0",  description="单个分销金额，以分为单位"),
      *                                           @SWG\Property(property="total_rebate", type="integer", example="0",  description="总分销金额，以分为单位"),
      *                                           @SWG\Property(property="item_fee", type="integer", example="100",  description="商品总金额，以分为单位"),
      *                                           @SWG\Property(property="cost_fee", type="integer", example="100",  description="商品成本价，以分为单位"),
      *                                           @SWG\Property(property="item_unit", type="string", example="",  description="商品计量单位"),
      *                                           @SWG\Property(property="member_discount", type="integer", example="20",  description="会员折扣金额，以分为单位"),
      *                                           @SWG\Property(property="coupon_discount", type="integer", example="0",  description="优惠券抵扣金额，以分为单位"),
      *                                           @SWG\Property(property="discount_fee", type="integer", example="20",  description="订单优惠金额"),
      *                                           @SWG\Property(property="discount_info", type="array", description="",
      *                                             @SWG\Items(
      *                                                          @SWG\Property(property="id", type="integer", example="0",  description="ID"),
      *                                                          @SWG\Property(property="type", type="string",  example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
      *                                                          @SWG\Property(property="info", type="string", example="会员价",  description=""),
      *                                                          @SWG\Property(property="rule", type="string",  example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
      *                                                          @SWG\Property(property="discount_fee", type="integer",  example="20", description="订单优惠金额"),
      *                                             ),
      *                                           ),
      *                                           @SWG\Property(property="shop_id", type="string", example="0",  description="门店ID"),
      *                                           @SWG\Property(property="is_total_store", type="string", example="1",  description="是否是总部库存(true:总部库存，false:店铺库存)"),
      *                                           @SWG\Property(property="distributor_id", type="string", example="103",  description="门店ID"),
      *                                           @SWG\Property(property="create_time", type="integer", example="1612150245",  description="订单创建时间"),
      *                                           @SWG\Property(property="update_time", type="integer", example="1612150664",  description="订单更新时间"),
      *                                           @SWG\Property(property="delivery_corp", type="string", example="",  description="快递公司"),
      *                                           @SWG\Property(property="delivery_code", type="string", example="",  description="快递单号"),
      *                                           @SWG\Property(property="delivery_img", type="string", example="",  description="快递发货凭证"),
      *                                           @SWG\Property(property="delivery_time", type="string", example="",  description="发货时间"),
      *                                           @SWG\Property(property="delivery_status", type="string", example="DONE",  description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
      *                                           @SWG\Property(property="aftersales_status", type="string", example="",  description="售后状态。可选值有 WAIT_SELLER_AGREE 0 等待商家处理;WAIT_BUYER_RETURN_GOODS 1  商家接受申请，等待消费者回寄;WAIT_SELLER_CONFIRM_GOODS 2 消费者回寄，等待商家收货确认;SELLER_REFUSE_BUYER 3  售后驳回;SELLER_SEND_GOODS 4 卖家重新发货 换货完成;REFUND_SUCCESS 5 退款成功;REFUND_CLOSED 6 退款关闭;CLOSED 7 售后关闭"),
      *                                           @SWG\Property(property="refunded_fee", type="integer", example="0",  description="退款金额，以分为单位"),
      *                                           @SWG\Property(property="fee_type", type="string", example="CNY",  description="货币类型"),
      *                                           @SWG\Property(property="fee_rate", type="integer", example="1",  description="货币汇率"),
      *                                           @SWG\Property(property="fee_symbol", type="string", example="￥",  description="货币符号"),
      *                                           @SWG\Property(property="cny_fee", type="integer", example="0", description=""),
      *                                           @SWG\Property(property="item_point", type="integer", example="0",  description="商品积分"),
      *                                           @SWG\Property(property="point", type="integer", example="16",  description="商品总积分"),
      *                                           @SWG\Property(property="item_spec_desc", type="string",  example="颜色:粉红大格110cm,尺码:20cm", description="商品规格描述"),
      *                                           @SWG\Property(property="order_item_type", type="string", example="normal",  description="订单商品类型,normal:正常商品，gift: 赠品, plus_buy: 加价购商品"),
      *                                           @SWG\Property(property="volume", type="integer", example="0",  description="商品体积"),
      *                                           @SWG\Property(property="weight", type="integer", example="0",  description="商品重量"),
      *                                           @SWG\Property(property="is_rate", type="string", example="",  description="是否评价"),
      *                                           @SWG\Property(property="auto_close_aftersales_time", type="string", example="",  description="自动关闭售后时间"),
      *                                           @SWG\Property(property="share_points", type="integer", example="16",  description="积分抵扣时分摊的积分值"),
      *                                           @SWG\Property(property="point_fee", type="integer", example="80",  description="积分抵扣时分摊的积分的金额，以分为单位"),
      *                                           @SWG\Property(property="is_logistics", type="string", example="",  description="门店缺货商品总部快递发货"),
      *                                           @SWG\Property(property="delivery_item_num", type="integer", example="1",  description="发货单发货数量"),
      *                                           @SWG\Property(property="get_points", type="integer", example="0",  description="商品获取积分"),
      *                     ),
      *                   ),
      *                   @SWG\Property(property="order_status_des", type="string", example="WAIT_BUYER_CONFIRM", description=""),
      *                   @SWG\Property(property="order_status_msg", type="string", example="待收货", description=""),
      *                   @SWG\Property(property="latest_aftersale_time", type="integer", example="0", description=""),
      *                   @SWG\Property(property="estimate_get_points", type="string", example="0", description=""),
      *                   @SWG\Property(property="delivery_type", type="string", example="new", description=""),
      *                   @SWG\Property(property="is_all_delivery", type="string", example="1", description=""),
      *                   @SWG\Property(property="dada", type="object",
      *                       ref="#/definitions/Dada"
      *                   ),
      *                   @SWG\Property(property="app_info", type="object",
      *                       ref="#/definitions/OrderAppInfo"
      *                   ),
      *              ),
      *         ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items( ref="#/definitions/OrdersErrorRespones") ) )
      * )
      */
     public function markDown(Request $request)
     {
          $companyId = app('auth')->user()->get('company_id');
          $params = $request->all();
          $rules = [
               'order_id' => ['required', '订单号必填'],
               'down_type' => ['in:total,items', '确认整单还是按件改价'],
               'total_fee'   => ['required_if:down_type,total', '整单改价金额必填'],
               'items' => ['required_if:down_type,items', '按件改价商品ID必填'],
               'items.*.item_id' => ['required', '按件改价商品ID必填'],
               'items.*.total_fee' => ['required_without:items.*.discount', '按件改价商品总价和折扣必须设置一个'],
               'items.*.discount' => ['required_without:items.*.total_fee', '按件改价商品总价和折扣必须设置一个'],
          ];
          $errorMessage = validator_params($params, $rules);
          if($errorMessage) {
               throw new ResourceException($errorMessage);
          }

          $orderAssociationService = new OrderAssociationService();
          $order = $orderAssociationService->getOrder($companyId, $params['order_id']);
          if(!$order) {
               throw new ResourceException('订单不存在');
          }

          if ($order['order_status'] != 'NOTPAY') {
               throw new ResourceException('只有未支付的订单才能改价');
          }

          $orderService = $this->getOrderServiceByOrderInfo($order);
          $orderInfo = $orderService->getOrderInfo($companyId, $params['order_id']);
          $result = $orderService->markDown($orderInfo['orderInfo'], $params);

          return $this->response->array($result);
     }

     /**
      * @SWG\Post(
      *     path="/order/markdown/confirm",
      *     summary="订单改价确认",
      *     tags={"订单"},
      *     description="订单改价确认",
      *     operationId="confirmMarkDown",
      *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter(name="order_id", in="query", description="订单号", required=true, type="string"),
      *     @SWG\Parameter(name="down_type", in="query", description="改价类型 total=>整单改价,items=>按件改价", required=true, type="string"),
      *     @SWG\Parameter(name="total_fee", in="query", description="整单改价金额，类型为total时必填", required=false, type="string"),
      *     @SWG\Parameter(name="items[][item_id]", in="query", description="按件改价商品ID", required=false, type="string"),
      *     @SWG\Parameter(name="items[][total_fee]", in="query", description="按件改价商品总价", required=false, type="string"),
      *     @SWG\Parameter(name="items[][discount]", in="query", description="按件改价商品折扣", required=false, type="string"),
      *     @SWG\Response(
      *         response="200",
      *         description="响应信息返回",
      *         @SWG\Schema(
      *              @SWG\Property(property="data", type="object", description="",
      *                   @SWG\Property(property="order_id", type="string", example="3319460000470394", description="订单号"),
      *                   @SWG\Property(property="title", type="string", example="测试商品会员价导入...", description="订单标题"),
      *                   @SWG\Property(property="company_id", type="string", example="1", description="企业ID"),
      *                   @SWG\Property(property="user_id", type="string", example="20394", description="购买用户"),
      *                   @SWG\Property(property="act_id", type="string", example="",  description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
      *                   @SWG\Property(property="mobile", type="string", example="17621502659", description="购买用户手机号"),
      *                   @SWG\Property(property="freight_fee", type="integer", example="0", description="运费价格，以分为单位"),
      *                   @SWG\Property(property="freight_type", type="string", example="cash", description="运费类型-用于积分商城  cash:现金 point:积分"),
      *                   @SWG\Property(property="item_fee", type="string", example="100", description="商品总金额，以分为单位"),
      *                   @SWG\Property(property="item_point", type="integer", example="0", description="商品积分"),
      *                   @SWG\Property(property="cost_fee", type="integer", example="100", description="商品成本价，以分为单位"),
      *                   @SWG\Property(property="total_fee", type="string", example="0", description="应付总金额,以分为单位"),
      *                   @SWG\Property(property="step_paid_fee", type="integer", example="0",  description="分阶段付款已支付金额，以分为单位"),
      *                   @SWG\Property(property="total_rebate", type="integer", example="0", description="总分销金额，以分为单位"),
      *                   @SWG\Property(property="distributor_id", type="string", example="103", description="门店ID"),
      *                   @SWG\Property(property="receipt_type", type="string", example="logistics",  description="收货方式。可选值有 logistics:物流;ziti:店铺自提;dada:同城配"),
      *                   @SWG\Property(property="ziti_code", type="string", example="0", description="店铺自提码"),
      *                   @SWG\Property(property="shop_id", type="string", example="0", description="门店ID"),
      *                   @SWG\Property(property="ziti_status", type="string", example="NOTZITI",  description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
      *                   @SWG\Property(property="order_status", type="string", example="WAIT_BUYER_CONFIRM",  description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CAN CEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"),
      *                   @SWG\Property(property="order_source", type="string", example="member", description="订单来源。可选值有  member-用户自主下单;shop-商家代客下单"),
      *                   @SWG\Property(property="order_class", type="string", example="normal", description="订单种类。可选值有  normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城"),
      *                   @SWG\Property(property="auto_cancel_time", type="string", example="1612150545",  description="订单自动取消时间"),
      *                   @SWG\Property(property="auto_cancel_seconds", type="integer", example="-12452", description=""),
      *                   @SWG\Property(property="auto_finish_time", type="string", example="1612755464",  description="订单自动完成时间"),
      *                   @SWG\Property(property="is_distribution", type="string", example="1", description="是否分销订单"),
      *                   @SWG\Property(property="source_id", type="string", example="0", description="订单来源id"),
      *                   @SWG\Property(property="monitor_id", type="string", example="0", description="订单监控页面id"),
      *                   @SWG\Property(property="salesman_id", type="string", example="0", description="导购员ID"),
      *                   @SWG\Property(property="delivery_corp", type="string", example="", description="快递公司"),
      *                   @SWG\Property(property="delivery_corp_source", type="string", example="kuaidi100",  description="快递代码来源"),
      *                   @SWG\Property(property="delivery_code", type="string", example="", description="快递单号"),
      *                   @SWG\Property(property="delivery_img", type="string", example="", description="快递发货凭证"),
      *                   @SWG\Property(property="delivery_status", type="string", example="DONE", description="发货状态。可选值有  DONE—已发货;PENDING—待发货"),
      *                   @SWG\Property(property="cancel_status", type="string", example="NO_APPLY_CANCEL",  description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS  取消失败"),
      *                   @SWG\Property(property="delivery_time", type="integer", example="1612150664", description="发货时间"),
      *                   @SWG\Property(property="end_time", type="string", example="", description="订单完成时间"),
      *                   @SWG\Property(property="end_date", type="string", example="", description=""),
      *                   @SWG\Property(property="receiver_name", type="string", example="1232", description="收货人姓名"),
      *                   @SWG\Property(property="receiver_mobile", type="string", example="17653569856",  description="收货人手机号"),
      *                   @SWG\Property(property="receiver_zip", type="string", example="000000", description="收货人邮编"),
      *                   @SWG\Property(property="receiver_state", type="string", example="北京市", description="收货人所在省份"),
      *                   @SWG\Property(property="receiver_city", type="string", example="北京市", description="收货人所在城市"),
      *                   @SWG\Property(property="receiver_district", type="string", example="东城", description="收货人所在地区"),
      *                   @SWG\Property(property="receiver_address", type="string", example="123123", description="收货人详细地址"),
      *                   @SWG\Property(property="member_discount", type="integer", example="20",  description="会员折扣金额，以分为单位"),
      *                   @SWG\Property(property="coupon_discount", type="integer", example="0",  description="优惠券抵扣金额，以分为单位"),
      *                   @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
      *                   @SWG\Property(property="create_time", type="integer", example="1612150245", description="订单创建时间"),
      *                   @SWG\Property(property="update_time", type="integer", example="1612150664", description="订单更新时间"),
      *                   @SWG\Property(property="fee_type", type="string", example="CNY", description="货币类型"),
      *                   @SWG\Property(property="fee_rate", type="integer", example="1", description="货币汇率"),
      *                   @SWG\Property(property="fee_symbol", type="string", example="￥", description="货币符号"),
      *                   @SWG\Property(property="cny_fee", type="integer", example="0", description=""),
      *                   @SWG\Property(property="point", type="integer", example="16", description="商品总积分"),
      *                   @SWG\Property(property="pay_type", type="string", example="point",  description="支付方式。wxpay-微信支付;deposit-预存款支付;pos-刷卡;point-积分"),
      *                   @SWG\Property(property="remark", type="string", example="", description="订单备注"),
      *                  @SWG\Property(property="third_params", type="object", description="",
      *                           @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
      *                  ),
      *                   @SWG\Property(property="invoice", type="string", example="", description="发票信息(DC2Type:json_array)"),
      *                   @SWG\Property(property="send_point", type="integer", example="0", description="是否分发积分0否 1是"),
      *                   @SWG\Property(property="is_rate", type="string", example="", description="是否评价"),
      *                   @SWG\Property(property="is_invoiced", type="string", example="", description="是否已开发票"),
      *                   @SWG\Property(property="invoice_number", type="string", example="", description="发票号"),
      *                   @SWG\Property(property="audit_status", type="string", example="processing", description="跨境订单审核状态  approved成功 processing审核中 rejected审核拒绝"),
      *                   @SWG\Property(property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
      *                   @SWG\Property(property="point_fee", type="integer", example="80",  description="积分抵扣时分摊的积分的金额，以分为单位"),
      *                   @SWG\Property(property="point_use", type="integer", example="16", description="积分抵扣使用的积分数"),
      *                   @SWG\Property(property="uppoint_use", type="integer", example="0", description="积分抵扣使用的积分升值数"),
      *                   @SWG\Property(property="point_up_use", type="integer", example="0", description=""),
      *                   @SWG\Property(property="pay_status", type="string", example="PAYED", description="支付状态。可选值有  NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
      *                   @SWG\Property(property="get_points", type="integer", example="0", description="商品获取积分"),
      *                   @SWG\Property(property="bonus_points", type="integer", example="0", description="购物赠送积分"),
      *                   @SWG\Property(property="get_point_type", type="integer", example="1", description="获取积分类型，0  老订单按订单完成时送,1 新订单按下单时计算送"),
      *                   @SWG\Property(property="pack", type="string", example="", description="包装"),
      *                   @SWG\Property(property="is_shopscreen", type="string", example="", description="是否门店订单"),
      *                   @SWG\Property(property="is_logistics", type="string", example="", description="门店缺货商品总部快递发货"),
      *                   @SWG\Property(property="is_profitsharing", type="integer", example="1", description="是否分账订单 1不分账  2分账"),
      *                   @SWG\Property(property="profitsharing_status", type="integer", example="1", description="分账状态 1未分账  2已分账"),
      *                   @SWG\Property(property="order_auto_close_aftersales_time", type="string", example="",  description="自动关闭售后时间"),
      *                   @SWG\Property(property="profitsharing_rate", type="integer", example="0", description="分账费率"),
      *                   @SWG\Property(property="bind_auth_code", type="string", example="", description="订单订单验证码"),
      *                   @SWG\Property(property="extra_points", type="integer", example="0", description="订单获取额外积分"),
      *                   @SWG\Property(property="type", type="integer", example="0",  description="订单类型，0普通订单,1跨境订单,....其他"),
      *                   @SWG\Property(property="taxable_fee", type="integer", example="0", description="计税总价，以分为单位"),
      *                   @SWG\Property(property="identity_id", type="string", example="", description="身份证号码"),
      *                   @SWG\Property(property="identity_name", type="string", example="", description="身份证姓名"),
      *                   @SWG\Property(property="total_tax", type="integer", example="0", description="总税费"),
      *                   @SWG\Property(property="discount_info", type="array", description="",
      *                     @SWG\Items(
      *                          @SWG\Property(property="id", type="integer", example="0", description="ID"),
      *                          @SWG\Property(property="type", type="string", example="member_price",  description="订单类型，0普通订单,1跨境订单,....其他"),
      *                          @SWG\Property(property="info", type="string", example="会员价", description=""),
      *                          @SWG\Property(property="rule", type="string", example="会员价直减0.20", description="分润规则( DC2Type:json_array)"),
      *                          @SWG\Property(property="discount_fee", type="integer", example="20", description="订单优惠金额"),
      *                     ),
      *                   ),
      *                   @SWG\Property(property="can_apply_aftersales", type="integer", example="0", description=""),
      *                   @SWG\Property(property="distributor_name", type="string", example="中关村东路123号院3号楼",  description=""),
      *                   @SWG\Property(property="items", type="array", description="",
      *                     @SWG\Items(
      *                                           @SWG\Property(property="id", type="string", example="8997", description="ID"),
      *                                           @SWG\Property(property="order_id", type="string", example="3319460000470394",  description="订单号"),
      *                                           @SWG\Property(property="company_id", type="string", example="1",  description="企业ID"),
      *                                           @SWG\Property(property="user_id", type="string", example="20394",  description="购买用户"),
      *                                           @SWG\Property(property="act_id", type="string", example="",  description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"),
      *                                           @SWG\Property(property="item_id", type="string", example="5461",  description="商品id"),
      *                                           @SWG\Property(property="item_bn", type="string", example="gyct2021001",  description="商品编码"),
      *                                           @SWG\Property(property="item_name", type="string", example="测试商品会员价导入",  description="商品名称"),
      *                                           @SWG\Property(property="pic", type="string", example="", description="商品图片"),
      *                                           @SWG\Property(property="num", type="integer", example="1",  description="购买商品数量"),
      *                                           @SWG\Property(property="price", type="integer", example="100",  description="单价，以分为单位"),
      *                                           @SWG\Property(property="total_fee", type="integer", example="0",  description="应付总金额,以分为单位"),
      *                                           @SWG\Property(property="templates_id", type="integer", example="1",  description="运费模板id"),
      *                                           @SWG\Property(property="rebate", type="integer", example="0",  description="单个分销金额，以分为单位"),
      *                                           @SWG\Property(property="total_rebate", type="integer", example="0",  description="总分销金额，以分为单位"),
      *                                           @SWG\Property(property="item_fee", type="integer", example="100",  description="商品总金额，以分为单位"),
      *                                           @SWG\Property(property="cost_fee", type="integer", example="100",  description="商品成本价，以分为单位"),
      *                                           @SWG\Property(property="item_unit", type="string", example="",  description="商品计量单位"),
      *                                           @SWG\Property(property="member_discount", type="integer", example="20",  description="会员折扣金额，以分为单位"),
      *                                           @SWG\Property(property="coupon_discount", type="integer", example="0",  description="优惠券抵扣金额，以分为单位"),
      *                                           @SWG\Property(property="discount_fee", type="integer", example="20",  description="订单优惠金额"),
      *                                           @SWG\Property(property="discount_info", type="array", description="",
      *                                             @SWG\Items(
      *                                                          @SWG\Property(property="id", type="integer", example="0",  description="ID"),
      *                                                          @SWG\Property(property="type", type="string",  example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
      *                                                          @SWG\Property(property="info", type="string", example="会员价",  description=""),
      *                                                          @SWG\Property(property="rule", type="string",  example="会员价直减0.20", description="分润规则(DC2Type:json_array)"),
      *                                                          @SWG\Property(property="discount_fee", type="integer",  example="20", description="订单优惠金额"),
      *                                             ),
      *                                           ),
      *                                           @SWG\Property(property="shop_id", type="string", example="0",  description="门店ID"),
      *                                           @SWG\Property(property="is_total_store", type="string", example="1",  description="是否是总部库存(true:总部库存，false:店铺库存)"),
      *                                           @SWG\Property(property="distributor_id", type="string", example="103",  description="门店ID"),
      *                                           @SWG\Property(property="create_time", type="integer", example="1612150245",  description="订单创建时间"),
      *                                           @SWG\Property(property="update_time", type="integer", example="1612150664",  description="订单更新时间"),
      *                                           @SWG\Property(property="delivery_corp", type="string", example="",  description="快递公司"),
      *                                           @SWG\Property(property="delivery_code", type="string", example="",  description="快递单号"),
      *                                           @SWG\Property(property="delivery_img", type="string", example="",  description="快递发货凭证"),
      *                                           @SWG\Property(property="delivery_time", type="string", example="",  description="发货时间"),
      *                                           @SWG\Property(property="delivery_status", type="string", example="DONE",  description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
      *                                           @SWG\Property(property="aftersales_status", type="string", example="",  description="售后状态。可选值有 WAIT_SELLER_AGREE 0 等待商家处理;WAIT_BUYER_RETURN_GOODS 1  商家接受申请，等待消费者回寄;WAIT_SELLER_CONFIRM_GOODS 2 消费者回寄，等待商家收货确认;SELLER_REFUSE_BUYER 3  售后驳回;SELLER_SEND_GOODS 4 卖家重新发货 换货完成;REFUND_SUCCESS 5 退款成功;REFUND_CLOSED 6 退款关闭;CLOSED 7 售后关闭"),
      *                                           @SWG\Property(property="refunded_fee", type="integer", example="0",  description="退款金额，以分为单位"),
      *                                           @SWG\Property(property="fee_type", type="string", example="CNY",  description="货币类型"),
      *                                           @SWG\Property(property="fee_rate", type="integer", example="1",  description="货币汇率"),
      *                                           @SWG\Property(property="fee_symbol", type="string", example="￥",  description="货币符号"),
      *                                           @SWG\Property(property="cny_fee", type="integer", example="0", description=""),
      *                                           @SWG\Property(property="item_point", type="integer", example="0",  description="商品积分"),
      *                                           @SWG\Property(property="point", type="integer", example="16",  description="商品总积分"),
      *                                           @SWG\Property(property="item_spec_desc", type="string",  example="颜色:粉红大格110cm,尺码:20cm", description="商品规格描述"),
      *                                           @SWG\Property(property="order_item_type", type="string", example="normal",  description="订单商品类型,normal:正常商品，gift: 赠品, plus_buy: 加价购商品"),
      *                                           @SWG\Property(property="volume", type="integer", example="0",  description="商品体积"),
      *                                           @SWG\Property(property="weight", type="integer", example="0",  description="商品重量"),
      *                                           @SWG\Property(property="is_rate", type="string", example="",  description="是否评价"),
      *                                           @SWG\Property(property="auto_close_aftersales_time", type="string", example="",  description="自动关闭售后时间"),
      *                                           @SWG\Property(property="share_points", type="integer", example="16",  description="积分抵扣时分摊的积分值"),
      *                                           @SWG\Property(property="point_fee", type="integer", example="80",  description="积分抵扣时分摊的积分的金额，以分为单位"),
      *                                           @SWG\Property(property="is_logistics", type="string", example="",  description="门店缺货商品总部快递发货"),
      *                                           @SWG\Property(property="delivery_item_num", type="integer", example="1",  description="发货单发货数量"),
      *                                           @SWG\Property(property="get_points", type="integer", example="0",  description="商品获取积分"),
      *                     ),
      *                   ),
      *                   @SWG\Property(property="order_status_des", type="string", example="WAIT_BUYER_CONFIRM", description=""),
      *                   @SWG\Property(property="order_status_msg", type="string", example="待收货", description=""),
      *                   @SWG\Property(property="latest_aftersale_time", type="integer", example="0", description=""),
      *                   @SWG\Property(property="estimate_get_points", type="string", example="0", description=""),
      *                   @SWG\Property(property="delivery_type", type="string", example="new", description=""),
      *                   @SWG\Property(property="is_all_delivery", type="string", example="1", description=""),
      *                   @SWG\Property(property="dada", type="object",
      *                       ref="#/definitions/Dada"
      *                   ),
      *                   @SWG\Property(property="app_info", type="object",
      *                       ref="#/definitions/OrderAppInfo"
      *                   ),
      *              ),
      *         ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items( ref="#/definitions/OrdersErrorRespones") ) )
      * )
      */
     public function confirmMarkDown(Request $request)
     {
          $companyId = app('auth')->user()->get('company_id');
          $params = $request->all();
          $rules = [
               'order_id' => ['required', '订单号必填'],
               'down_type' => ['in:total,items', '确认整单还是按件改价'],
               'total_fee'   => ['required_if:down_type,total', '整单改价金额必填'],
               'items' => ['required_if:down_type,items', '按件改价商品ID必填'],
               'items.*.item_id' => ['required', '按件改价商品ID必填'],
               'items.*.total_fee' => ['required_without:items.*.discount', '按件改价商品总价和折扣必须设置一个'],
               'items.*.discount' => ['required_without:items.*.total_fee', '按件改价商品总价和折扣必须设置一个'],
          ];
          $errorMessage = validator_params($params, $rules);
          if($errorMessage) {
               throw new ResourceException($errorMessage);
          }

          $orderAssociationService = new OrderAssociationService();
          $order = $orderAssociationService->getOrder($companyId, $params['order_id']);
          if(!$order) {
               throw new ResourceException('订单不存在');
          }

          if ($order['order_status'] != 'NOTPAY') {
               throw new ResourceException('只有未支付的订单才能改价');
          }

          $orderService = $this->getOrderServiceByOrderInfo($order);
          $orderInfo = $orderService->getOrderInfo($companyId, $params['order_id']);

          $params['operator_id'] = app('auth')->user()->get('operator_id');
          $params['operator_type'] = app('auth')->user()->get('operator_type');
          $result = $orderService->saveMarkDown($orderInfo['orderInfo'], $params);

          return $this->response->array($result);
     }

     /**
      * @SWG\Post(
      *     path="/order/deliverystaff/confirm",
      *     summary="订单自配送员确认",
      *
      */
    public function confirmDeliveryStaff(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operator_id = app('auth')->user()->get('operator_id');
        $operator_type = app('auth')->user()->get('operator_type');
        $params = $request->all();
        $rules = [
            'order_id' => ['required', '订单号必填'],
            'self_delivery_operator_id' => ['required', '必须选择一个配送员'],
        ];
        $errorMessage = validator_params($params, $rules);
        if($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $params['order_id']);
        if(!$order) {
            throw new ResourceException('订单不存在');
        }
        $params['company_id'] = $companyId;
        $params['operator_id'] = $operator_id;
        $params['operator_type'] = $operator_type;
        $orderService = $this->getOrderServiceByOrderInfo($order);

        $orderInfo = $orderService->getOrderInfo($companyId, $params['order_id']);
        $order = $orderInfo['orderInfo'];

        if ($order['receipt_type'] != 'merchant') {
            throw new ResourceException('普通快递和到店自提订单不能分配配送员');
        }

        if (in_array($order['order_status'], ['NOTPAY', 'CANCEL', 'DONE'])) {
            throw new ResourceException('订单当前不可以分配配送员');
        }

        if($order['self_delivery_operator_id']){
            throw new ResourceException('订单已经分配配送员，不可重新分配');
        }
        $result = $orderService->confirmDeliveryStaff($params);
        return $this->response->array(['status'=>$result]);
    }

    /**
     * @SWG\Post(
     *     path="/order/cancel/deliverystaff",
     *     summary="订单自配送员确认",
     *
     */
    public function cancelDeliveryStaff(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operator_id = app('auth')->user()->get('operator_id');
        $operator_type = app('auth')->user()->get('operator_type');
        $params = $request->all();
        $rules = [
            'order_id' => ['required', '订单号必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $params['order_id']);
        if(!$order) {
            throw new ResourceException('订单不存在');
        }
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $orderInfo = $orderService->getOrderInfo($companyId, $params['order_id']);
        $order = $orderInfo['orderInfo'];
        if(in_array($order['self_delivery_status'],['CONFIRMING','DONE'])){
            throw new ResourceException('当前状态不可以取消配送');
        }
        $params['self_delivery_status'] = $order['self_delivery_status'];
        if ($order['self_delivery_status'] == 'RECEIVEORDER') {
            $params['self_delivery_status'] = 'CONFIRMING';
        }
        $params['company_id'] = $companyId;
        $params['operator_id'] = $operator_id;
        $params['operator_type'] = $operator_type;

        $result = $orderService->cancelDeliveryStaff($params);
        return $this->response->array(['status'=>$result]);
    }

    /**
     * @SWG\Post(
     *     path="/order/deliverypackag/confirm",
     *     summary="订单打包确认",
     *
     */
    public function confirmDeliveryPackag(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operator_id = app('auth')->user()->get('operator_id');
        $operator_type = app('auth')->user()->get('operator_type');
        $params = $request->all();
        $rules = [
            'order_id' => ['required', '订单号必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $params['order_id']);
        if(!$order) {
            throw new ResourceException('订单不存在');
        }
        $params['company_id'] = $companyId;
        $params['operator_id'] = $operator_id;
        $params['operator_type'] = $operator_type;
        $orderService = $this->getOrderServiceByOrderInfo($order);

        $orderInfo = $orderService->getOrderInfo($companyId, $params['order_id']);
        $order = $orderInfo['orderInfo'];
        if(in_array($order['self_delivery_status'],['DELIVERING','DONE'])){
            throw new ResourceException('当前状态不可以确认打包');
        }
        $result = $orderService->confirmDeliveryPackag($params);
        return $this->response->array(['status'=>$result]);
    }
}
