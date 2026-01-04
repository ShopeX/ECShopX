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
class OrderInfo
{
    /**
     *                          @SWG\Property( property="order_id", type="string", example="3274628000080264", description="订单号"),
     *                          @SWG\Property( property="title", type="string", example="社区拼团...", description="标题"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                          @SWG\Property( property="mobile", type="string", example="18321148690", description="手机号"),
     *                          @SWG\Property( property="total_fee", type="string", example="1", description="订单金额，以分为单位"),
     *                          @SWG\Property( property="total_rebate", type="string", example="0", description="总分销金额，以分为单位"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="店铺ID"),
     *                          @SWG\Property( property="order_status", type="string", example="CANCEL", description="订单状态,可选值有 DONE—订单完成;NOTPAY—未支付;CANCEL—已取消"),
     *                          @SWG\Property( property="order_source", type="string", example="member", description="订单来源。可选值有 member 用户自主下单;shop 商家代客下单"),
     *                          @SWG\Property( property="order_type", type="string", example="normal", description="订单类型。可选值有 normal:普通实体订单, pointsmall:积分商城订单"),
     *                          @SWG\Property( property="auto_cancel_time", type="string", example="1608278274", description="订单自动取消时间"),
     *                          @SWG\Property( property="is_distribution", type="string", example="0", description="是否是分销订单"),
     *                          @SWG\Property( property="source_id", type="string", example="0", description="订单来源id"),
     *                          @SWG\Property( property="monitor_id", type="string", example="0", description="订单监控页面id"),
     *                          @SWG\Property( property="delivery_corp", type="string", example="", description="快递公司"),
     *                          @SWG\Property( property="delivery_code", type="string", example="", description="快递单号"),
     *                          @SWG\Property( property="delivery_time", type="string", example="null", description="发货时间"),
     *                          @SWG\Property( property="delivery_status", type="string", example="PENDING", description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *                          @SWG\Property( property="receiver_name", type="string", example="倩儿", description="收货人姓名"),
     *                          @SWG\Property( property="receiver_mobile", type="string", example="18321148690", description="收货人手机号"),
     *                          @SWG\Property( property="receiver_zip", type="string", example="null", description="收货人邮编"),
     *                          @SWG\Property( property="receiver_state", type="string", example="null", description="收货人所在省份"),
     *                          @SWG\Property( property="receiver_city", type="string", example="null", description="收货人所在城市"),
     *                          @SWG\Property( property="receiver_district", type="string", example="null", description="收货人所在地区"),
     *                          @SWG\Property( property="receiver_address", type="string", example="null", description="收货人详细地址"),
     *                          @SWG\Property( property="create_time", type="string", example="1608277374", description="创建时间"),
     *                          @SWG\Property( property="update_time", type="string", example="1608277417", description="最后修改时间"),
     *                          @SWG\Property( property="freight_fee", type="string", example="0", description="运费价格，以分为单位"),
     *                          @SWG\Property( property="item_fee", type="string", example="1", description="商品总金额，以分为单位"),
     *                          @SWG\Property( property="member_discount", type="string", example="0", description="会员折扣金额，以分为单位"),
     *                          @SWG\Property( property="coupon_discount", type="string", example="0", description="优惠券抵扣金额，以分为单位"),
     *                          @SWG\Property( property="coupon_discount_desc", type="string", example="", description="优惠券使用详情"),
     *                          @SWG\Property( property="member_discount_desc", type="string", example="", description="会员折扣使用详情"),
     *                          @SWG\Property( property="shop_id", type="string", example="16", description="门店id"),
     *                          @SWG\Property( property="receipt_type", type="string", example="ziti", description="收货方式。可选值有 logistics:物流;ziti:店铺自提"),
     *                          @SWG\Property( property="ziti_code", type="string", example="142739", description="店铺自提码"),
     *                          @SWG\Property( property="ziti_status", type="string", example="PENDING", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"),
     *                          @SWG\Property( property="end_time", type="string", example="null", description="订单完成时间"),
     *                          @SWG\Property( property="cancel_status", type="string", example="SUCCESS", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"),
     *                          @SWG\Property( property="cost_fee", type="string", example="0", description="商品成本价，以分为单位"),
     *                          @SWG\Property( property="fee_type", type="string", example="CNY", description="货币类型"),
     *                          @SWG\Property( property="fee_rate", type="string", example="1", description="货币汇率"),
     *                          @SWG\Property( property="fee_symbol", type="string", example="￥", description="货币符号"),
     *                          @SWG\Property( property="act_id", type="string", example="154", description="活动ID号"),
     *                          @SWG\Property( property="order_class", type="string", example="community", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"),
     *                          @SWG\Property( property="salesman_id", type="string", example="0", description="导购员id"),
     *                          @SWG\Property( property="auto_finish_time", type="string", example="null", description="订单自动完成时间"),
     *                          @SWG\Property( property="discount_fee", type="string", example="0", description="订单优惠金额，以分为单位"),
     *                          @SWG\Property( property="discount_info", type="string", example="", description="订单优惠详情"),
     *                          @SWG\Property( property="point", type="string", example="0", description="积分"),
     *                          @SWG\Property( property="pay_type", type="string", example="wxpay", description="支付方式"),
     *                          @SWG\Property( property="remark", type="string", example="", description="备注"),
     *                          @SWG\Property( property="third_params", type="string", example="", description="第三方特殊字段存储(DC2Type:json_array)"),
     *                          @SWG\Property( property="invoice", type="string", example="null", description="发票信息"),
     *                          @SWG\Property( property="send_point", type="string", example="0", description="是否分发积分0否 1是"),
     *                          @SWG\Property( property="step_paid_fee", type="string", example="0", description="分阶段付款已支付金额，以分为单位"),
     *                          @SWG\Property( property="delivery_corp_source", type="string", example="null", description="快递代码来源"),
     *                          @SWG\Property( property="is_rate", type="string", example="0", description="是否评价"),
     *                          @SWG\Property( property="invoice_number", type="string", example="null", description="发票号"),
     *                          @SWG\Property( property="is_invoiced", type="string", example="0", description="是否已开发票"),
     *                          @SWG\Property( property="is_online_order", type="string", example="1", description="是否为线上订单"),
     *                          @SWG\Property( property="delivery_img", type="string", example="null", description="快递发货凭证"),
     *                          @SWG\Property( property="pay_status", type="string", example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"),
     *                          @SWG\Property( property="type", type="string", example="0", description="订单类型，0普通订单,1跨境订单,....其他"),
     *                          @SWG\Property( property="identity_id", type="string", example="null", description="身份证号码"),
     *                          @SWG\Property( property="identity_name", type="string", example="null", description="身份证姓名"),
     *                          @SWG\Property( property="total_tax", type="string", example="0", description="总税费"),
     *                          @SWG\Property( property="audit_status", type="string", example="processing", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                          @SWG\Property( property="audit_msg", type="string", example="正在审核订单", description="审核意见"),
     *                          @SWG\Property( property="taxable_fee", type="string", example="0", description="计税总价，以分为单位"),
     *                          @SWG\Property( property="point_fee", type="string", example="0", description="积分抵扣金额，以分为单位"),
     *                          @SWG\Property( property="point_use", type="string", example="0", description="积分抵扣使用的积分数"),
     *                          @SWG\Property( property="get_point_type", type="string", example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"),
     *                          @SWG\Property( property="get_points", type="string", example="1", description="订单获取积分"),
     *                          @SWG\Property( property="bonus_points", type="string", example="0", description="购物赠送积分"),
     *                          @SWG\Property( property="is_shopscreen", type="string", example="0", description="是否门店订单"),
     *                          @SWG\Property( property="is_logistics", type="string", example="0", description="门店缺货商品总部快递发货"),
     *                          @SWG\Property( property="is_profitsharing", type="string", example="1", description="是否分账订单 1不分账 2分账"),
     *                          @SWG\Property( property="profitsharing_status", type="string", example="1", description="分账状态 1未分账 2已分账"),
     *                          @SWG\Property( property="profitsharing_rate", type="string", example="0", description="分账费率"),
     *                          @SWG\Property( property="order_auto_close_aftersales_time", type="string", example="null", description="自动关闭售后时间"),
     *                          @SWG\Property( property="pack", type="string", example="", description="包装"),
     *                          @SWG\Property( property="bind_auth_code", type="string", example="", description="订单订单验证码"),
     *                          @SWG\Property( property="freight_type", type="string", example="cash", description="运费类型-用于积分商城 cash:现金 point:积分"),
     *                          @SWG\Property( property="item_point", type="string", example="0", description="商品消费总积分"),
     *                          @SWG\Property( property="uppoint_use", type="string", example="0", description=""),
     *                          @SWG\Property( property="extra_points", type="string", example="0", description="订单获取额外积分"),
     *                          @SWG\Property( property="order_status_msg", type="string", example="已取消", description=""),
     *                          @SWG\Property( property="order_status_des", type="string", example="CANCEL", description=""),
     *                          @SWG\Property( property="source_name", type="string", example="-", description="来源名称"),
     *                          @SWG\Property( property="distributor_info", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="create_date", type="string", example="2020-12-18 15:42:54", description=""),
     *                          @SWG\Property( property="items", type="array",
     *                              @SWG\Items(
     *                                  ref="#/definitions/OrderItems"
     *                               ),
     *                          ),
     *                          @SWG\Property( property="distributor_name", type="string", example="", description="分销商真实姓名"),
     *                          @SWG\Property( property="delivery_type", type="string", example="new", description=""),
     *                          @SWG\Property( property="orders_delivery_id", type="string", example="", description="订单发货单id"),
     *                          @SWG\Property( property="is_all_delivery", type="string", example="", description=""),
     *                          @SWG\Property( property="delivery_corp_name", type="string", example="", description="快递公司名称"),
     */
}
