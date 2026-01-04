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

namespace OrdersBundle\Http\Api\V1\Swagger\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="OrderData"))
 */
class OrderData
{
    /**
     * @SWG\Property(format="int64", example="3321656000130032", description="订单号")
     * @var int
     */
    public $order_id;

    /**
     * @SWG\Property(example="内蒙古正宗羊肉...", description="订单标题")
     * @var string
     */
    public $title;

    /**
     * @SWG\Property(example="1", description="公司id")
     * @var string
     */
    public $company_id;

    /**
     * @SWG\Property(example="20032", description="用户id")
     * @var string
     */
    public $user_id;

    /**
     * @SWG\Property(example="", description="营销活动ID，团购ID，社区拼团ID，秒杀活动ID等")
     * @var string
     */
    public $act_id;

    /**
     * @SWG\Property(example="15901872216", description="手机号")
     * @var string
     */
    public $mobile;

    /**
     * @SWG\Property(example="0", description="运费价格，以分为单位")
     * @var integer
     */
    public $freight_fee;

    /**
     * @SWG\Property(example="cash", description="运费类型-用于积分商城 cash:现金 point:积分")
     * @var , type=
     */
    public $freight_type;

    /**
     * @SWG\Property(example="1", description="商品金额，以分为单位")
     * @var string
     */
    public $item_fee;

    /**
     * @SWG\Property(example="0", description="商品消费总积分")
     * @var integer
     */
    public $item_point;

    /**
     * @SWG\Property(example="4000", description="商品成本价，以分为单位")
     * @var integer
     */
    public $cost_fee;

    /**
     * @SWG\Property(example="1", description="订单金额，以分为单位")
     * @var string
     */
    public $total_fee;

    /**
     * @SWG\Property(example="0", description="分阶段付款已支付金额，以分为单位")
     * @var integer
     */
    public $step_paid_fee;

    /**
     * @SWG\Property(example="0", description="订单总分销金额，以分为单位")
     * @var integer
     */
    public $total_rebate;

    /**
     * @SWG\Property(example="103", description="分销商id")
     * @var string
     */
    public $distributor_id;

    /**
     * @SWG\Property(example="ziti", description="收货方式。可选值有 logistics:物流;ziti:店铺自提")
     * @var , type=
     */
    public $receipt_type;

    /**
     * @SWG\Property(example="824872", description="店铺自提码")
     * @var string
     */
    public $ziti_code;

    /**
     * @SWG\Property(example="0", description="门店id")
     * @var string
     */
    public $shop_id;

    /**
     * @SWG\Property(example="DONE", description="店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核")
     * @var string
     */
    public $ziti_status;

    /**
     * @SWG\Property(example="DONE", description="订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货")
     * @var string
     */
    public $order_status;

    /**
     * @SWG\Property(example="shop_offline", description="订单来源。可选值有 member-用户自主下单;shop-商家代客下单")
     * @var string
     */
    public $order_source;

    /**
     * @SWG\Property(example="normal", description="订单类型。可选值有 normal:普通实体订单")
     * @var string
     */
    public $order_type;

    /**
     * @SWG\Property(example="shopguide", description="订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城")
     * @var string
     */
    public $order_class;

    /**
     * @SWG\Property(example="1612341546", description="订单自动取消时间")
     * @var string
     */
    public $auto_cancel_time;

    /**
     * @SWG\Property(example="-1460055", description="")
     * @var integer
     */
    public $auto_cancel_seconds;

    /**
     * @SWG\Property(example="", description="订单自动完成时间")
     * @var string
     */
    public $auto_finish_time;

    /**
     * @SWG\Property(example="1", description="是否分销订单")
     * @var string
     */
    public $is_distribution;

    /**
     * @SWG\Property(example="0", description="订单来源id")
     * @var string
     */
    public $source_id;

    /**
     * @SWG\Property(example="0", description="订单监控页面id")
     * @var string
     */
    public $monitor_id;

    /**
     * @SWG\Property(example="78", description="导购员ID")
     * @var string
     */
    public $salesman_id;

    /**
     * @SWG\Property(example="", description="快递公司")
     * @var string
     */
    public $delivery_corp;

    /**
     * @SWG\Property(example="", description="快递代码来源")
     * @var string
     */
    public $delivery_corp_source;

    /**
     * @SWG\Property(example="", description="快递单号")
     * @var string
     */
    public $delivery_code;

    /**
     * @SWG\Property(example="", description="快递发货凭证")
     * @var string
     */
    public $delivery_img;

    /**
     * @SWG\Property(example="DONE", description="发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货")
     * @var string
     */
    public $delivery_status;

    /**
     * @SWG\Property(example="NO_APPLY_CANCEL", description="取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败")
     * @var string
     */
    public $cancel_status;

    /**
     * @SWG\Property(example="1613801601", description="发货时间")
     * @var integer
     */
    public $delivery_time;

    /**
     * @SWG\Property(example="1613801601", description="订单完成时间")
     * @var integer
     */
    public $end_time;

    /**
     * @SWG\Property(example="2021-02-20 14:13:21", description="结束日期")
     * @var string
     */
    public $end_date;

    /**
     * @SWG\Property(example="", description="收货人姓名")
     * @var string
     */
    public $receiver_name;

    /**
     * @SWG\Property(example="", description="收货人手机号")
     * @var string
     */
    public $receiver_mobile;

    /**
     * @SWG\Property(example="", description="收货人邮编")
     * @var string
     */
    public $receiver_zip;

    /**
     * @SWG\Property(example="", description="收货人所在省份")
     * @var string
     */
    public $receiver_state;

    /**
     * @SWG\Property(example="", description="收货人所在城市")
     * @var string
     */
    public $receiver_city;

    /**
     * @SWG\Property(example="", description="收货人所在地区")
     * @var string
     */
    public $receiver_district;

    /**
     * @SWG\Property(example="", description="收货人详细地址")
     * @var string
     */
    public $receiver_address;

    /**
     * @SWG\Property(example="0", description="会员折扣金额，以分为单位")
     * @var integer
     */
    public $member_discount;

    /**
     * @SWG\Property(example="0", description="优惠券抵扣金额，以分为单位")
     * @var integer
     */
    public $coupon_discount;

    /**
     * @SWG\Property(example="0", description="订单优惠金额，以分为单位")
     * @var integer
     */
    public $discount_fee;

    /**
     * @SWG\Property(example="1612340646", description="订单创建时间")
     * @var integer
     */
    public $create_time;

    /**
     * @SWG\Property(example="1613801601", description="订单更新时间")
     * @var integer
     */
    public $update_time;

    /**
     * @SWG\Property(example="CNY", description="货币类型")
     * @var , type=
     */
    public $fee_type;

    /**
     * @SWG\Property(example="1", description="货币汇率")
     * @var integer
     */
    public $fee_rate;

    /**
     * @SWG\Property(example="￥", description="货币符号")
     * @var string
     */
    public $fee_symbol;

    /**
     * @SWG\Property(example="1", description="round(round(floatval($orderEntity->getFeeRate()), 4) * $orderEntity->getTotalFee())")
     * @var integer
     */
    public $cny_fee;

    /**
     * @SWG\Property(example="0", description="消费积分")
     * @var integer
     */
    public $point;

    /**
     * @SWG\Property(example="pos", description="支付方式")
     * @var string
     */
    public $pay_type;

    /**
     * @SWG\Property(example="", description="订单备注")
     * @var string
     */
    public $remark;

    /**
     * @SWG\Property(
     *     type="object",
     *     description="",
     *     @SWG\Property(property="is_liveroom", type="string", example="1", description=""),
     * )
     */
    public $third_params;

    /**
     * @SWG\Property(example="", description="发票信息(DC2Type:json_array)")
     * @var string
     */
    public $invoice;

    /**
     * @SWG\Property(example="0", description="是否分发积分0否 1是")
     * @var integer
     */
    public $send_point;

    /**
     * @SWG\Property(example="", description="是否评价")
     * @var string
     */
    public $is_rate;

    /**
     * @SWG\Property(example="", description="是否已开发票")
     * @var string
     */
    public $is_invoiced;

    /**
     * @SWG\Property(example="", description="发票号")
     * @var string
     */
    public $invoice_number;

    /**
     * @SWG\Property(example="processing", description="跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝")
     * @var string
     */
    public $audit_status;

    /**
     * @SWG\Property(example="正在审核订单", description="审核意见")
     * @var string
     */
    public $audit_msg;

    /**
     * @SWG\Property(example="0", description="积分抵扣金额，以分为单位")
     * @var integer
     */
    public $point_fee;

    /**
     * @SWG\Property(example="0", description="积分抵扣使用的积分数")
     * @var integer
     */
    public $point_use;

    /**
     * @SWG\Property(example="0", description="积分抵扣商家补贴的积分数(基础积分-使用的升值积分)")
     * @var integer
     */
    public $uppoint_use;

    /**
     * @SWG\Property(example="0", description="积分抵扣使用的积分升值数")
     * @var integer
     */
    public $point_up_use;

    /**
     * @SWG\Property(example="PAYED", description="支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中")
     * @var string
     */
    public $pay_status;

    /**
     * @SWG\Property(example="0", description="订单获取积分")
     * @var integer
     */
    public $get_points;

    /**
     * @SWG\Property(example="0", description="购物赠送积分")
     * @var integer
     */
    public $bonus_points;

    /**
     * @SWG\Property(example="1", description="获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送")
     * @var , type=
     */
    public $get_point_type;

    /**
     * @SWG\Property(example="", description="包装")
     * @var string
     */
    public $pack;

    /**
     * @SWG\Property(example="", description="是否门店订单")
     * @var string
     */
    public $is_shopscreen;

    /**
     * @SWG\Property(example="", description="门店缺货商品总部快递发货")
     * @var string
     */
    public $is_logistics;

    /**
     * @SWG\Property(example="1", description="是否分账订单 1不分账 2分账")
     * @var integer
     */
    public $is_profitsharing;

    /**
     * @SWG\Property(example="1", description="分账状态 1未分账 2已分账")
     * @var integer
     */
    public $profitsharing_status;

    /**
     * @SWG\Property(example="", description="自动关闭售后时间")
     * @var string
     */
    public $order_auto_close_aftersales_time;

    /**
     * @SWG\Property(example="0", description="分账费率")
     * @var integer
     */
    public $profitsharing_rate;

    /**
     * @SWG\Property(example="", description="订单订单验证码")
     * @var string
     */
    public $bind_auth_code;

    /**
     * @SWG\Property(example="0", description="订单获取额外积分")
     * @var integer
     */
    public $extra_points;

    /**
     * @SWG\Property(example="0", description="订单类型，0普通订单,1跨境订单,....其他")
     * @var , type=
     */
    public $type;

    /**
     * @SWG\Property(example="0", description="计税总价，以分为单位")
     * @var integer
     */
    public $taxable_fee;

    /**
     * @SWG\Property(example="", description="身份证号码")
     * @var string
     */
    public $identity_id;

    /**
     * @SWG\Property(example="", description="身份证姓名")
     * @var string
     */
    public $identity_name;

    /**
     * @SWG\Property(example="0", description="总税费")
     * @var integer
     */
    public $total_tax;

    /**
     *
     * @SWG\Property(property="discount_info", type="array", description="",
     *   @SWG\Items(
     *             @SWG\Property(property="id", type="integer", example="0", description=""),
     *             @SWG\Property(property="type", type="string", example="member_price", description="订单类型，0普通订单,1跨境订单,....其他"),
     *             @SWG\Property(property="info", type="string", example="会员价", description=""),
     *             @SWG\Property(property="rule", type="string", example="会员折扣优惠2", description=""),
     *             @SWG\Property(property="discount_fee", type="integer", example="0", description="订单优惠金额，以分为单位"),
     *   ),
     * )
     */
    public $discount_info;
}
