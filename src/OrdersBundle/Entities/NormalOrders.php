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

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * NormalOrders 实体订单表
 *
 * @ORM\Table(name="orders_normal_orders", options={"comment":"实体订单表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_order_type", columns={"order_type"}),
 *         @ORM\Index(name="idx_order_class", columns={"order_class"}),
 *         @ORM\Index(name="idx_shop_id", columns={"shop_id"}),
 *         @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_salesman_id", columns={"salesman_id"}),
 *         @ORM\Index(name="idx_order_source", columns={"order_source"}),
 *         @ORM\Index(name="idx_create_time", columns={"create_time"}),
 *         @ORM\Index(name="idx_merchant_id", columns={"merchant_id"}),
 *         @ORM\Index(name="idx_supplier_id", columns={"supplier_id"}),
 *         @ORM\Index(name="idx_order_holder", columns={"order_holder"}),
 *         @ORM\Index(name="idx_66c5819c17fbd9b018f167a7dfd85ba9", columns={"order_status", "pay_type", "is_profitsharing", "profitsharing_status", "order_auto_close_aftersales_time"}),
 *         @ORM\Index(name="idx_prescription_status", columns={"prescription_status"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\NormalOrdersRepository")
 */
class NormalOrders
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", nullable=true, type="string", options={"comment":"订单标题"})
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"门店id", "default": 0})
     */
    private $shop_id = 0;

    /**
     * @var int
     *
     * 支付金额，以分为单位
     *
     * @ORM\Column(name="cost_fee", type="integer", options={"unsigned":true, "comment":"商品成本价，以分为单位"})
     */
    private $cost_fee = 0;

    /**
     * @var integer
     *
     * 佣金，以分为单位
     *
     * @ORM\Column(name="commission_fee", type="integer", options={"comment":"佣金(分)", "default": 0})
     */
    private $commission_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="act_id", type="bigint", nullable=true, options={"comment":"营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"})
     */
    private $act_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, nullable=true, options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="order_class", type="string", options={"default":"normal", "comment":"订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单;pointsmall:积分商城;excard:兑换券"})
     */
    private $order_class;

    /**
     * @var integer
     *
     * @ORM\Column(name="freight_fee", type="integer", nullable=true, options={"default":0, "comment":"运费价格，以分为单位"})
     */
    private $freight_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="freight_point", type="integer", nullable=true, options={"default":0, "comment":"运费使用的积分"})
     */
    private $freight_point = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="freight_point_fee", type="integer", nullable=true, options={"default":0, "comment":"运费使用的积分抵扣的金额，以分为单位"})
     */
    private $freight_point_fee = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="freight_type", type="string", options={"default":"cash", "comment":"运费类型-用于积分商城 cash:现金 point:积分"})
     */
    private $freight_type;

    /**
     * @var string
     *
     * @ORM\Column(name="item_fee", type="string", options={"comment":"商品金额，以分为单位"})
     */
    private $item_fee;

    /**
     * @var string
     *
     * @ORM\Column(name="total_fee", type="string", options={"comment":"订单金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var string
     *
     * @ORM\Column(name="market_fee", type="string", nullable=true, options={"comment":"销售价总金额，以分为单位"})
     */
    private $market_fee;

    /**
     * @var int
     *
     * @ORM\Column(name="step_paid_fee", type="integer", nullable=true, options={"unsigned":true, "comment":"分阶段付款已支付金额，以分为单位", "default": 0})
     */
    private $step_paid_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_rebate", type="integer", options={"unsigned":true, "default":0, "comment":"订单总分销金额，以分为单位"})
     */
    private $total_rebate = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_rate", type="boolean", nullable=true, options={"comment":"是否评价", "default": 0})
     */
    private $is_rate = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="receipt_type", type="string", options={"default":"logistics", "comment":"收货方式。可选值有 【logistics 物流】【ziti 店铺自提】【dada 达达同城配】【merchant 商家自配】"})
     */
    private $receipt_type = 'logistics';

    /**
     * @var integer
     *
     * @ORM\Column(name="ziti_code", type="bigint", options={"default":0, "comment":"店铺自提码"})
     */
    private $ziti_code = 0;

    /**
     * @var integer  自提状态
     *
     * @ORM\Column(name="ziti_status", nullable=true, type="string", options={"default":"NOTZITI", "comment":"店铺自提状态。可选值有 PENDING:等待自提;DONE:自提完成;NOTZITI:自提完成; APPROVE:审核通过,药品自提需要审核"})
     */
    private $ziti_status = 'NOTZITI';
    /**
     * @var integer  自配送状态
     *
     * @ORM\Column(name="self_delivery_status", nullable=true, type="string", options={"default":"NOTMERCHANT", "comment":"店铺自配送状态。可选值有 CONFIRMING:等待确认;RECEIVEORDER:已接单;PACKAGED:已打包;DELIVERING:配送中;DONE:已送达;NOTMERCHANT:不是自配送;"})
     */
    private $self_delivery_status = 'NOTMERCHANT';

    /**
     * @var string
     *
     * @ORM\Column(name="order_status", type="string", options={"comment":"订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"})
     */
    private $order_status;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_status", type="string", options={"default":"NOTPAY","comment":"支付状态。可选值有 NOTPAY—未支付;PAYED-已支付;ADVANCE_PAY-预付款完成;TAIL_PAY-支付尾款中"})
     */
    private $pay_status = 'NOTPAY';

    /**
     * @var string
     *
     * @ORM\Column(name="order_source", type="string", nullable=true, options={"comment":"订单来源。可选值有 member-用户自主下单;shop-商家代客下单 salesperson:业务员导购下单", "default":"member"})
     */
    private $order_source = 'member';

    /**
     * @var string
     *
     * @ORM\Column(name="order_holder", type="string", length=20, nullable=true, options={"comment":"订单归属。可选值 self-自营订单;distributor-商家订单;supplier-供应商订单;self_supplier-自营和供应商订单","default":"self"})
     */
    private $order_holder = 'self';

    /**
     * @var string
     *
     * @ORM\Column(name="order_type", type="string", options={"comment":"订单类型。可选值有 normal:普通实体订单","default":"normal"})
     */
    private $order_type = 'normal';

    /**
     * @var string
     *
     * @ORM\Column(name="auto_cancel_time", type="string", options={"comment":"订单自动取消时间"})
     */
    private $auto_cancel_time;

    /**
     * @var string
     *
     * @ORM\Column(name="auto_finish_time", nullable=true, type="string", options={"comment":"订单自动完成时间"})
     */
    private $auto_finish_time;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_distribution", type="boolean", options={"default":false, "comment":"是否分销订单"})
     */
    private $is_distribution = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="source_id", type="bigint", nullable=true, options={"comment":"订单来源id"})
     */
    private $source_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="monitor_id", type="bigint", nullable=true, options={"comment":"订单监控页面id"})
     */
    private $monitor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesman_id", nullable=true, type="bigint", options={"comment":"导购员ID", "default": 0})
     */
    private $salesman_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_corp", type="string", nullable=true, options={"comment":"快递公司"})
     */
    private $delivery_corp;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_corp_source", type="string", nullable=true, options={"comment":"快递代码来源"})
     */
    private $delivery_corp_source;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_code", type="string", nullable=true, options={"comment":"快递单号"})
     */
    private $delivery_code;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_img", type="string", nullable=true, options={"comment":"快递发货凭证"})
     */
    private $delivery_img;

    /**
     * @var integer
     *
     * @ORM\Column(name="delivery_time", type="integer", nullable=true, options={"comment":"发货时间"})
     */
    private $delivery_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="bigint", nullable=true, options={"comment":"订单完成时间"})
     */
    private $end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_status", type="string", options={"default": "PENDING", "comment":"发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货"})
     */
    private $delivery_status = 'PENDING';

    /**
     * @var string
     *
     * @ORM\Column(name="cancel_status", type="string", options={"default": "NO_APPLY_CANCEL", "comment":"取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"})
     */
    private $cancel_status = 'NO_APPLY_CANCEL';

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_name", type="string", length=500, nullable=true, options={"comment":"收货人姓名"})
     */
    private $receiver_name;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_mobile", type="string", length=255, nullable=true, options={"comment":"收货人手机号"})
     */
    private $receiver_mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_zip", type="string", nullable=true, options={"comment":"收货人邮编"})
     */
    private $receiver_zip;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_state", type="string", nullable=true, options={"comment":"收货人所在省份"})
     */
    private $receiver_state;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_city", type="string", nullable=true, options={"comment":"收货人所在城市"})
     */
    private $receiver_city;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_district", type="string", nullable=true, options={"comment":"收货人所在地区"})
     */
    private $receiver_district;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_address", type="text", nullable=true, options={"comment":"收货人详细地址"})
     */
    private $receiver_address;

    /**
     * @var int
     *
     * @ORM\Column(name="member_discount", type="integer", options={"unsigned":true, "comment":"会员折扣金额，以分为单位"})
     */
    private $member_discount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="coupon_discount", type="integer", options={"unsigned":true, "comment":"优惠券抵扣金额，以分为单位"})
     */
    private $coupon_discount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="discount_fee", type="integer", options={"comment":"订单优惠金额，以分为单位", "default":0})
     */
    private $discount_fee = 0;

    /**
    * @var int
    *
    * @ORM\Column(name="discount_info", type="text", nullable=true, options={"comment":"订单优惠详情"})
    */
    private $discount_info = 0;


    /**
     * @var string
     *
     * @ORM\Column(name="coupon_discount_desc", type="text", nullable=true, options={"comment":"优惠券使用详情"})
     */
    private $coupon_discount_desc = "";

    /**
     * @var string
     *
     * @ORM\Column(name="member_discount_desc", type="text", nullable=true, options={"comment":"会员折扣使用详情"})
     */
    private $member_discount_desc = "";

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"订单创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"订单更新时间"})
     */
    private $update_time;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_type", type="string", length=5, options={"comment":"货币类型", "default":"CNY"})
     */
    private $fee_type = 'CNY';

    /**
     * @var string
     *
     * @ORM\Column(name="fee_rate", type="float", precision=15, scale=4, options={"comment":"货币汇率", "default":1})
     */
    private $fee_rate = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_symbol", type="string", options={"comment":"货币符号", "default":"￥"})
     */
    private $fee_symbol = '￥';

    /**
     * @var int
     *
     * @ORM\Column(name="item_point", nullable=true, type="integer", options={"unsigned":true, "comment":"商品消费总积分", "default": 0})
     */
    private $item_point = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="point", nullable=true, type="integer", options={"unsigned":true, "comment":"消费积分", "default": 0})
     */
    private $point = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_type", nullable=true, type="string", options={ "comment":"支付方式", "default": ""})
     */
    private $pay_type = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_channel", nullable=true, type="string", options={ "comment":"adapay支付渠道", "default": ""})
     */
    private $pay_channel;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", nullable=true, options={"comment":"订单备注"})
     */
    private $remark;

    /**
     * @var json_array
     *
     * @ORM\Column(name="third_params", type="json_array", nullable=true, options={"comment":"第三方特殊字段存储"})
     */
    private $third_params;

    /**
     * @var json_array
     *
     * @ORM\Column(name="invoice", type="json_array", nullable=true, options={"comment":"发票信息"})
     */
    private $invoice;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_number", type="string", nullable=true, options={"default":"", "comment":"发票号"})
     */
    private $invoice_number;

    /**
     * @var string
     *
     * @ORM\Column(name="is_invoiced", type="boolean", nullable=true, options={"default":0, "comment":"是否已开发票"})
     */
    private $is_invoiced = 0;

    /**
    * @var integer
    *
    * @ORM\Column(name="send_point", type="integer", options={"default":0, "comment":"是否分发积分0否 1是"})
    */
    private $send_point = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="is_online_order", type="boolean", options={"comment":"是否为线上订单", "default": true})
     */
    private $is_online_order = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_profitsharing", type="integer", nullable=true, options={"comment":"是否分账订单 1不分账 2分账", "default": 1})
     */
    private $is_profitsharing = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="profitsharing_status", type="integer", nullable=true, options={"comment":"分账状态 1未分账 2已分账", "default": 1})
     */
    private $profitsharing_status = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="profitsharing_rate", type="integer", nullable=true, options={"comment":"分账费率"})
     */
    private $profitsharing_rate;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_auto_close_aftersales_time", type="integer", nullable=true, options={"comment":"自动关闭售后时间", "default": 0})
     */
    private $order_auto_close_aftersales_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", options={"default":0, "comment":"订单类型，0普通订单,1跨境订单,....其他"})
     */
    private $type = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="taxable_fee", type="integer", options={"unsigned":true, "comment":"计税总价，以分为单位", "default":0})
     */
    private $taxable_fee = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_id", type="string", length=18, nullable=true, options={"default":"", "comment":"身份证号码"})
     */
    private $identity_id;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_name", type="string",  length=20, nullable=true, options={"default":"", "comment":"身份证姓名"})
     */
    private $identity_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_tax", type="integer", options={"default":0, "comment":"总税费"})
     */
    private $total_tax = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_status", nullable=true, type="string", options={"comment":"跨境订单审核状态 approved成功 processing审核中 rejected审核拒绝", "default":"processing"})
     */
    private $audit_status = 'processing';

    /**
     * @var string
     *
     * @ORM\Column(name="audit_msg", nullable=true, type="string", options={"comment":"审核意见", "default":"正在审核订单"})
     */
    private $audit_msg = '正在审核订单';

    /**
     * @var integer
     *
     * @ORM\Column(name="point_fee", type="integer", nullable=true, options={"default":0, "comment":"积分抵扣金额，以分为单位"})
     */
    private $point_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="point_use", type="integer", nullable=true, options={"default":0, "comment":"积分抵扣使用的积分数"})
     */
    private $point_use = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="uppoint_use", nullable=true, type="integer", options={"unsigned":true, "comment":"积分抵扣商家补贴的积分数(基础积分-使用的升值积分)", "default": 0})
     */
    private $uppoint_use = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="point_up_use", nullable=true, type="integer", options={"unsigned":true, "comment":"积分抵扣使用的积分升值数", "default": 0})
     */
    private $point_up_use = 0;
    /**
     * @var integer
     *
     * @ORM\Column(name="get_point_type", type="integer", options={"default":0, "comment":"获取积分类型，0 老订单按订单完成时送,1 新订单按下单时计算送"})
     */
    private $get_point_type = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="pack", type="string", nullable=true, options={"comment":"包装"})
     */
    private $pack = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_shopscreen", type="boolean", nullable=true, options={"comment":"是否门店订单", "default": false})
     */
    private $is_shopscreen = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_logistics", type="boolean", nullable=true, options={"comment":"门店缺货商品总部快递发货", "default": false})
     */
    private $is_logistics = false;

    /**
     * @var int
     *
     * @ORM\Column(name="get_points", nullable=true, type="integer", options={"unsigned":true, "comment":"订单获取积分", "default": 0})
     */
    private $get_points = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="extra_points", nullable=true, type="integer", options={"unsigned":true, "comment":"订单获取额外积分", "default": 0})
     */
    private $extra_points = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="bonus_points", nullable=true, type="integer", options={"unsigned":true, "comment":"购物赠送积分", "default": 0})
     */
    private $bonus_points = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="bind_auth_code", type="string",  length=10, nullable=true, options={"default":"", "comment":"订单订单验证码"})
     */
    private $bind_auth_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="sale_salesman_distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"销售导购店铺id"})
     */
    private $sale_salesman_distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="bind_salesman_id", nullable=true, type="bigint", options={"comment":"绑定导购员ID", "default": 0})
     */
    private $bind_salesman_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="bind_salesman_distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"绑定导购店铺id"})
     */
    private $bind_salesman_distributor_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="chat_id", type="string",  length=32, nullable=true, options={"default":"", "comment":"客户群ID"})
     */
    private $chat_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_consumption", type="integer", options={"default":0, "comment":"是否处理了增加消费金额，0未处理,1已处理"})
     */
    private $is_consumption = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="app_pay_type", type="string", nullable=true, options={"comment":"支付类型 01：微信正扫 02：支付宝正扫 03：银联正扫 05：微信公众号 06：支付宝小程序/生活号 07：微信小程序 08：微信正扫(直连) 09：微信app支付(直连) 10：银联app支付 11：apple支付 12：微信H5支付(直连) 13：支付宝app支付(直连)", "default": "07"})
     */
    private $app_pay_type = '07';

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_remark", type="string", length=255, nullable=false, options={"comment"="商家备注"})
     */
    private $distributor_remark = '';
    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="self_delivery_operator_id", type="bigint", nullable=true, options={"comment":"自配送员id", "default": 0})
     */
    private $self_delivery_operator_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="self_delivery_fee", nullable=true, type="integer", options={"comment":"操作者id", "default": 0})
     */
    private $self_delivery_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="self_delivery_time", type="integer", nullable=true, options={"comment":"自配送预约时间", "default": 0})
     */
    private $self_delivery_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="self_delivery_end_time", type="bigint", nullable=true, options={"comment":"自配送送达时间"})
     */
    private $self_delivery_end_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="subdistrict_parent_id", nullable=false, type="bigint", options={"comment":"街道id"})
     */
    private $subdistrict_parent_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="subdistrict_id", nullable=false, type="bigint", options={"comment":"社区id"})
     */
    private $subdistrict_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="building_number", nullable=false, type="string", length=20, options={"comment":"楼栋号"})
     */
    private $building_number = '';

    /**
     * @var string
     *
     * @ORM\Column(name="house_number", nullable=false, type="string", length=20, options={"comment":"门牌号"})
     */
    private $house_number = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", nullable=true, type="integer", options={"comment":"操作者id", "default": 0})
     */
    private $operator_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="left_aftersales_num", type="integer", options={"unsigned":true, "comment":"剩余可申请售后的数量", "default":0})
     */
    private $left_aftersales_num = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="source_from", nullable=true, type="string", length=10, options={"comment":"订单来源 pc,h5,wxapp,aliapp,unknow,dianwu"})
     */
    private $source_from;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="integer", options={"comment":"供应商id", "default":0})
     */
    private $supplier_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="original_order_id", nullable=true, type="bigint", length=64, options={"comment":"拆单前原始订单号"})
     */
    private $original_order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="offline_payment_status", type="smallint", nullable=true, options={"comment":"线下支付状态：-1-未上传转账凭证；0-待处理；1-已审核；2-已拒绝；9-已取消", "default":-1})
     */
    private $offline_payment_status = -1;

    /**
     * @var integer
     *
     * @ORM\Column(name="prescription_status", type="integer", options={"comment":"开方状态，0不需要开方，1未开方，2已开方，处方药开方后才能继续支付", "default":0})
     */
    private $prescription_status = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_status", type="string", options={"default": "PENDING", "comment":"开票状态。可选值有 DONE—已开票;PENDING—待开票;PARTAIL-部分开票"})
     */
    private $invoice_status = 'PENDING';

     /**
     * @var string
     *
     * @ORM\Column(name="dm_point_preid", type="string", nullable=true, length=64, options={"comment":"达摩积分预扣id"})
     */
    private $dm_point_preid;
    
    public function setDmPointPreid($dmPointPreid)
    {
        $this->dm_point_preid = $dmPointPreid;
        return $this;
    }

    public function getDmPointPreid()
    {
        return $this->dm_point_preid;
    }

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return NormalOrders
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set title.
     *
     * @param string|null $title
     *
     * @return NormalOrders
     */
    public function setTitle($title = null)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return NormalOrders
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set shopId.
     *
     * @param int|null $shopId
     *
     * @return NormalOrders
     */
    public function setShopId($shopId = null)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId.
     *
     * @return int|null
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set costFee.
     *
     * @param int $costFee
     *
     * @return NormalOrders
     */
    public function setCostFee($costFee)
    {
        $this->cost_fee = $costFee;

        return $this;
    }

    /**
     * Get costFee.
     *
     * @return int
     */
    public function getCostFee()
    {
        return $this->cost_fee;
    }

    /**
     * Set userId.
     *
     * @param int|null $userId
     *
     * @return NormalOrders
     */
    public function setUserId($userId = null)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int|null
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set actId.
     *
     * @param int|null $actId
     *
     * @return NormalOrders
     */
    public function setActId($actId = null)
    {
        $this->act_id = $actId;

        return $this;
    }

    /**
     * Get actId.
     *
     * @return int|null
     */
    public function getActId()
    {
        return $this->act_id;
    }

    /**
     * Set mobile.
     *
     * @param string|null $mobile
     *
     * @return NormalOrders
     */
    public function setMobile($mobile = null)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string|null
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set orderClass.
     *
     * @param string $orderClass
     *
     * @return NormalOrders
     */
    public function setOrderClass($orderClass)
    {
        $this->order_class = $orderClass;

        return $this;
    }

    /**
     * Get orderClass.
     *
     * @return string
     */
    public function getOrderClass()
    {
        return $this->order_class;
    }

    /**
     * Set freightFee.
     *
     * @param int|null $freightFee
     *
     * @return NormalOrders
     */
    public function setFreightFee($freightFee = null)
    {
        $this->freight_fee = $freightFee;

        return $this;
    }

    /**
     * Get freightFee.
     *
     * @return int|null
     */
    public function getFreightFee()
    {
        return $this->freight_fee;
    }

    /**
     * Set freightPoint.
     *
     * @param int|null $freightPoint
     *
     * @return NormalOrders
     */
    public function setFreightPoint($freightPoint = null)
    {
        $this->freight_point = $freightPoint;

        return $this;
    }

    /**
     * Get freightPoint.
     *
     * @return int|null
     */
    public function getFreightPoint()
    {
        return $this->freight_point;
    }

    /**
     * Set freightPointFee.
     *
     * @param int|null $freightPointFee
     *
     * @return NormalOrders
     */
    public function setFreightPointFee($freightPointFee = null)
    {
        $this->freight_point_fee = $freightPointFee;

        return $this;
    }

    /**
     * Get freightPointFee.
     *
     * @return int|null
     */
    public function getFreightPointFee()
    {
        return $this->freight_point_fee;
    }

    /**
     * Set itemFee.
     *
     * @param string $itemFee
     *
     * @return NormalOrders
     */
    public function setItemFee($itemFee)
    {
        $this->item_fee = $itemFee;

        return $this;
    }

    /**
     * Get itemFee.
     *
     * @return string
     */
    public function getItemFee()
    {
        return $this->item_fee;
    }

    /**
     * Set totalFee.
     *
     * @param string $totalFee
     *
     * @return NormalOrders
     */
    public function setTotalFee($totalFee)
    {
        $this->total_fee = $totalFee;

        return $this;
    }

    /**
     * Get totalFee.
     *
     * @return string
     */
    public function getTotalFee()
    {
        return $this->total_fee;
    }

    /**
     * Set marketFee.
     *
     * @param string $marketFee
     *
     * @return NormalOrders
     */
    public function setMarketFee($marketFee)
    {
        $this->market_fee = $marketFee;

        return $this;
    }

    /**
     * Get marketFee.
     *
     * @return string
     */
    public function getMarketFee()
    {
        return $this->market_fee;
    }

    /**
     * Set stepPaidFee.
     *
     * @param int|null $stepPaidFee
     *
     * @return NormalOrders
     */
    public function setStepPaidFee($stepPaidFee = null)
    {
        $this->step_paid_fee = $stepPaidFee;

        return $this;
    }

    /**
     * Get stepPaidFee.
     *
     * @return int|null
     */
    public function getStepPaidFee()
    {
        return $this->step_paid_fee;
    }

    /**
     * Set totalRebate.
     *
     * @param int $totalRebate
     *
     * @return NormalOrders
     */
    public function setTotalRebate($totalRebate)
    {
        $this->total_rebate = $totalRebate;

        return $this;
    }

    /**
     * Get totalRebate.
     *
     * @return int
     */
    public function getTotalRebate()
    {
        return $this->total_rebate;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return NormalOrders
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set isRate.
     *
     * @param bool|null $isRate
     *
     * @return NormalOrders
     */
    public function setIsRate($isRate = null)
    {
        $this->is_rate = $isRate;

        return $this;
    }

    /**
     * Get isRate.
     *
     * @return bool|null
     */
    public function getIsRate()
    {
        return $this->is_rate;
    }

    /**
     * Set receiptType.
     *
     * @param string $receiptType
     *
     * @return NormalOrders
     */
    public function setReceiptType($receiptType)
    {
        $this->receipt_type = $receiptType;

        return $this;
    }

    /**
     * Get receiptType.
     *
     * @return string
     */
    public function getReceiptType()
    {
        return $this->receipt_type;
    }

    /**
     * Set zitiCode.
     *
     * @param int $zitiCode
     *
     * @return NormalOrders
     */
    public function setZitiCode($zitiCode)
    {
        $this->ziti_code = $zitiCode;

        return $this;
    }

    /**
     * Get zitiCode.
     *
     * @return int
     */
    public function getZitiCode()
    {
        return $this->ziti_code;
    }

    /**
     * Set zitiStatus.
     *
     * @param string|null $zitiStatus
     *
     * @return NormalOrders
     */
    public function setZitiStatus($zitiStatus = null)
    {
        $this->ziti_status = $zitiStatus;

        return $this;
    }

    /**
     * Get zitiStatus.
     *
     * @return string|null
     */
    public function getZitiStatus()
    {
        return $this->ziti_status;
    }

    /**
     * Set orderStatus.
     *
     * @param string $orderStatus
     *
     * @return NormalOrders
     */
    public function setOrderStatus($orderStatus)
    {
        $this->order_status = $orderStatus;

        return $this;
    }

    /**
     * Get orderStatus.
     *
     * @return string
     */
    public function getOrderStatus()
    {
        return $this->order_status;
    }

    /**
     * Set payStatus.
     *
     * @param string $payStatus
     *
     * @return NormalOrders
     */
    public function setPayStatus($payStatus)
    {
        $this->pay_status = $payStatus;

        return $this;
    }

    /**
     * Get payStatus.
     *
     * @return string
     */
    public function getPayStatus()
    {
        return $this->pay_status;
    }

    /**
     * Set orderSource.
     *
     * @param string|null $orderSource
     *
     * @return NormalOrders
     */
    public function setOrderSource($orderSource = null)
    {
        $this->order_source = $orderSource;

        return $this;
    }

    /**
     * Get orderSource.
     *
     * @return string|null
     */
    public function getOrderSource()
    {
        return $this->order_source;
    }

    /**
     * Set orderType.
     *
     * @param string $orderType
     *
     * @return NormalOrders
     */
    public function setOrderType($orderType)
    {
        $this->order_type = $orderType;

        return $this;
    }

    /**
     * Get orderType.
     *
     * @return string
     */
    public function getOrderType()
    {
        return $this->order_type;
    }

    /**
     * Set autoCancelTime.
     *
     * @param string $autoCancelTime
     *
     * @return NormalOrders
     */
    public function setAutoCancelTime($autoCancelTime)
    {
        $this->auto_cancel_time = $autoCancelTime;

        return $this;
    }

    /**
     * Get autoCancelTime.
     *
     * @return string
     */
    public function getAutoCancelTime()
    {
        return $this->auto_cancel_time;
    }

    /**
     * Set autoFinishTime.
     *
     * @param string|null $autoFinishTime
     *
     * @return NormalOrders
     */
    public function setAutoFinishTime($autoFinishTime = null)
    {
        $this->auto_finish_time = $autoFinishTime;

        return $this;
    }

    /**
     * Get autoFinishTime.
     *
     * @return string|null
     */
    public function getAutoFinishTime()
    {
        return $this->auto_finish_time;
    }

    /**
     * Set isDistribution.
     *
     * @param bool $isDistribution
     *
     * @return NormalOrders
     */
    public function setIsDistribution($isDistribution)
    {
        $this->is_distribution = $isDistribution;

        return $this;
    }

    /**
     * Get isDistribution.
     *
     * @return bool
     */
    public function getIsDistribution()
    {
        return $this->is_distribution;
    }

    /**
     * Set sourceId.
     *
     * @param int|null $sourceId
     *
     * @return NormalOrders
     */
    public function setSourceId($sourceId = null)
    {
        $this->source_id = $sourceId;

        return $this;
    }

    /**
     * Get sourceId.
     *
     * @return int|null
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * Set monitorId.
     *
     * @param int|null $monitorId
     *
     * @return NormalOrders
     */
    public function setMonitorId($monitorId = null)
    {
        $this->monitor_id = $monitorId;

        return $this;
    }

    /**
     * Get monitorId.
     *
     * @return int|null
     */
    public function getMonitorId()
    {
        return $this->monitor_id;
    }

    /**
     * Set salesmanId.
     *
     * @param int|null $salesmanId
     *
     * @return NormalOrders
     */
    public function setSalesmanId($salesmanId = null)
    {
        $this->salesman_id = $salesmanId;

        return $this;
    }

    /**
     * Get salesmanId.
     *
     * @return int|null
     */
    public function getSalesmanId()
    {
        return $this->salesman_id;
    }

    /**
     * Set deliveryCorp.
     *
     * @param string|null $deliveryCorp
     *
     * @return NormalOrders
     */
    public function setDeliveryCorp($deliveryCorp = null)
    {
        $this->delivery_corp = $deliveryCorp;

        return $this;
    }

    /**
     * Get deliveryCorp.
     *
     * @return string|null
     */
    public function getDeliveryCorp()
    {
        return $this->delivery_corp;
    }

    /**
     * Set deliveryCorpSource.
     *
     * @param string|null $deliveryCorpSource
     *
     * @return NormalOrders
     */
    public function setDeliveryCorpSource($deliveryCorpSource = null)
    {
        $this->delivery_corp_source = $deliveryCorpSource;

        return $this;
    }

    /**
     * Get deliveryCorpSource.
     *
     * @return string|null
     */
    public function getDeliveryCorpSource()
    {
        return $this->delivery_corp_source;
    }

    /**
     * Set deliveryCode.
     *
     * @param string|null $deliveryCode
     *
     * @return NormalOrders
     */
    public function setDeliveryCode($deliveryCode = null)
    {
        $this->delivery_code = $deliveryCode;

        return $this;
    }

    /**
     * Get deliveryCode.
     *
     * @return string|null
     */
    public function getDeliveryCode()
    {
        return $this->delivery_code;
    }

    /**
     * Set deliveryImg.
     *
     * @param string|null $deliveryImg
     *
     * @return NormalOrders
     */
    public function setDeliveryImg($deliveryImg = null)
    {
        $this->delivery_img = $deliveryImg;

        return $this;
    }

    /**
     * Get deliveryImg.
     *
     * @return string|null
     */
    public function getDeliveryImg()
    {
        return $this->delivery_img;
    }

    /**
     * Set deliveryTime.
     *
     * @param int|null $deliveryTime
     *
     * @return NormalOrders
     */
    public function setDeliveryTime($deliveryTime = null)
    {
        $this->delivery_time = $deliveryTime;

        return $this;
    }

    /**
     * Get deliveryTime.
     *
     * @return int|null
     */
    public function getDeliveryTime()
    {
        return $this->delivery_time;
    }

    /**
     * Set endTime.
     *
     * @param int|null $endTime
     *
     * @return NormalOrders
     */
    public function setEndTime($endTime = null)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime.
     *
     * @return int|null
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set deliveryStatus.
     *
     * @param string $deliveryStatus
     *
     * @return NormalOrders
     */
    public function setDeliveryStatus($deliveryStatus)
    {
        $this->delivery_status = $deliveryStatus;

        return $this;
    }

    /**
     * Get deliveryStatus.
     *
     * @return string
     */
    public function getDeliveryStatus()
    {
        return $this->delivery_status;
    }

    /**
     * Set cancelStatus.
     *
     * @param string $cancelStatus
     *
     * @return NormalOrders
     */
    public function setCancelStatus($cancelStatus)
    {
        $this->cancel_status = $cancelStatus;

        return $this;
    }

    /**
     * Get cancelStatus.
     *
     * @return string
     */
    public function getCancelStatus()
    {
        return $this->cancel_status;
    }

    /**
     * Set receiverName.
     *
     * @param string|null $receiverName
     *
     * @return NormalOrders
     */
    public function setReceiverName($receiverName = null)
    {
        $this->receiver_name = fixedencrypt($receiverName);

        return $this;
    }

    /**
     * Get receiverName.
     *
     * @return string|null
     */
    public function getReceiverName()
    {
        return fixeddecrypt($this->receiver_name);
    }

    /**
     * Set receiverMobile.
     *
     * @param string|null $receiverMobile
     *
     * @return NormalOrders
     */
    public function setReceiverMobile($receiverMobile = null)
    {
        $this->receiver_mobile = fixedencrypt($receiverMobile);

        return $this;
    }

    /**
     * Get receiverMobile.
     *
     * @return string|null
     */
    public function getReceiverMobile()
    {
        return fixeddecrypt($this->receiver_mobile);
    }

    /**
     * Set receiverZip.
     *
     * @param string|null $receiverZip
     *
     * @return NormalOrders
     */
    public function setReceiverZip($receiverZip = null)
    {
        $this->receiver_zip = $receiverZip;

        return $this;
    }

    /**
     * Get receiverZip.
     *
     * @return string|null
     */
    public function getReceiverZip()
    {
        return $this->receiver_zip;
    }

    /**
     * Set receiverState.
     *
     * @param string|null $receiverState
     *
     * @return NormalOrders
     */
    public function setReceiverState($receiverState = null)
    {
        $this->receiver_state = $receiverState;

        return $this;
    }

    /**
     * Get receiverState.
     *
     * @return string|null
     */
    public function getReceiverState()
    {
        return $this->receiver_state;
    }

    /**
     * Set receiverCity.
     *
     * @param string|null $receiverCity
     *
     * @return NormalOrders
     */
    public function setReceiverCity($receiverCity = null)
    {
        $this->receiver_city = $receiverCity;

        return $this;
    }

    /**
     * Get receiverCity.
     *
     * @return string|null
     */
    public function getReceiverCity()
    {
        return $this->receiver_city;
    }

    /**
     * Set receiverDistrict.
     *
     * @param string|null $receiverDistrict
     *
     * @return NormalOrders
     */
    public function setReceiverDistrict($receiverDistrict = null)
    {
        $this->receiver_district = $receiverDistrict;

        return $this;
    }

    /**
     * Get receiverDistrict.
     *
     * @return string|null
     */
    public function getReceiverDistrict()
    {
        return $this->receiver_district;
    }

    /**
     * Set receiverAddress.
     *
     * @param string|null $receiverAddress
     *
     * @return NormalOrders
     */
    public function setReceiverAddress($receiverAddress = null)
    {
        $this->receiver_address = fixedencrypt($receiverAddress);

        return $this;
    }

    /**
     * Get receiverAddress.
     *
     * @return string|null
     */
    public function getReceiverAddress()
    {
        return fixeddecrypt($this->receiver_address);
    }

    /**
     * Set memberDiscount.
     *
     * @param int $memberDiscount
     *
     * @return NormalOrders
     */
    public function setMemberDiscount($memberDiscount)
    {
        $this->member_discount = $memberDiscount;

        return $this;
    }

    /**
     * Get memberDiscount.
     *
     * @return int
     */
    public function getMemberDiscount()
    {
        return $this->member_discount;
    }

    /**
     * Set couponDiscount.
     *
     * @param int $couponDiscount
     *
     * @return NormalOrders
     */
    public function setCouponDiscount($couponDiscount)
    {
        $this->coupon_discount = $couponDiscount;

        return $this;
    }

    /**
     * Get couponDiscount.
     *
     * @return int
     */
    public function getCouponDiscount()
    {
        return $this->coupon_discount;
    }

    /**
     * Set discountFee.
     *
     * @param int $discountFee
     *
     * @return NormalOrders
     */
    public function setDiscountFee($discountFee)
    {
        $this->discount_fee = $discountFee;

        return $this;
    }

    /**
     * Get discountFee.
     *
     * @return int
     */
    public function getDiscountFee()
    {
        return $this->discount_fee;
    }

    /**
     * Set discountInfo.
     *
     * @param string|null $discountInfo
     *
     * @return NormalOrders
     */
    public function setDiscountInfo($discountInfo = null)
    {
        $this->discount_info = $discountInfo;

        return $this;
    }

    /**
     * Get discountInfo.
     *
     * @return string|null
     */
    public function getDiscountInfo()
    {
        return $this->discount_info;
    }

    /**
     * Set couponDiscountDesc.
     *
     * @param string|null $couponDiscountDesc
     *
     * @return NormalOrders
     */
    public function setCouponDiscountDesc($couponDiscountDesc = null)
    {
        $this->coupon_discount_desc = $couponDiscountDesc;

        return $this;
    }

    /**
     * Get couponDiscountDesc.
     *
     * @return string|null
     */
    public function getCouponDiscountDesc()
    {
        return $this->coupon_discount_desc;
    }

    /**
     * Set memberDiscountDesc.
     *
     * @param string|null $memberDiscountDesc
     *
     * @return NormalOrders
     */
    public function setMemberDiscountDesc($memberDiscountDesc = null)
    {
        $this->member_discount_desc = $memberDiscountDesc;

        return $this;
    }

    /**
     * Get memberDiscountDesc.
     *
     * @return string|null
     */
    public function getMemberDiscountDesc()
    {
        return $this->member_discount_desc;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return NormalOrders
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime.
     *
     * @return int
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime.
     *
     * @param int|null $updateTime
     *
     * @return NormalOrders
     */
    public function setUpdateTime($updateTime = null)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime.
     *
     * @return int|null
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Set feeType.
     *
     * @param string $feeType
     *
     * @return NormalOrders
     */
    public function setFeeType($feeType)
    {
        $this->fee_type = $feeType;

        return $this;
    }

    /**
     * Get feeType.
     *
     * @return string
     */
    public function getFeeType()
    {
        return $this->fee_type;
    }

    /**
     * Set feeRate.
     *
     * @param float $feeRate
     *
     * @return NormalOrders
     */
    public function setFeeRate($feeRate)
    {
        $this->fee_rate = $feeRate;

        return $this;
    }

    /**
     * Get feeRate.
     *
     * @return float
     */
    public function getFeeRate()
    {
        return $this->fee_rate;
    }

    /**
     * Set feeSymbol.
     *
     * @param string $feeSymbol
     *
     * @return NormalOrders
     */
    public function setFeeSymbol($feeSymbol)
    {
        $this->fee_symbol = $feeSymbol;

        return $this;
    }

    /**
     * Get feeSymbol.
     *
     * @return string
     */
    public function getFeeSymbol()
    {
        return $this->fee_symbol;
    }

    /**
     * Set point.
     *
     * @param int|null $point
     *
     * @return NormalOrders
     */
    public function setPoint($point = null)
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Get point.
     *
     * @return int|null
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * Set payType.
     *
     * @param string|null $payType
     *
     * @return NormalOrders
     */
    public function setPayType($payType = null)
    {
        $this->pay_type = $payType;

        return $this;
    }

    /**
     * Get payType.
     *
     * @return string|null
     */
    public function getPayType()
    {
        return $this->pay_type;
    }

    /**
     * Set remark.
     *
     * @param string|null $remark
     *
     * @return NormalOrders
     */
    public function setRemark($remark = null)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark.
     *
     * @return string|null
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set thirdParams.
     *
     * @param array|null $thirdParams
     *
     * @return NormalOrders
     */
    public function setThirdParams($thirdParams = null)
    {
        $this->third_params = $thirdParams;

        return $this;
    }

    /**
     * Get thirdParams.
     *
     * @return array|null
     */
    public function getThirdParams()
    {
        return $this->third_params;
    }

    /**
     * Set invoice.
     *
     * @param array|null $invoice
     *
     * @return NormalOrders
     */
    public function setInvoice($invoice = null)
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * Get invoice.
     *
     * @return array|null
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * Set invoiceNumber.
     *
     * @param string|null $invoiceNumber
     *
     * @return NormalOrders
     */
    public function setInvoiceNumber($invoiceNumber = null)
    {
        $this->invoice_number = $invoiceNumber;

        return $this;
    }

    /**
     * Get invoiceNumber.
     *
     * @return string|null
     */
    public function getInvoiceNumber()
    {
        return $this->invoice_number;
    }

    /**
     * Set isInvoiced.
     *
     * @param bool|null $isInvoiced
     *
     * @return NormalOrders
     */
    public function setIsInvoiced($isInvoiced = null)
    {
        $this->is_invoiced = $isInvoiced;

        return $this;
    }

    /**
     * Get isInvoiced.
     *
     * @return bool|null
     */
    public function getIsInvoiced()
    {
        return $this->is_invoiced;
    }

    /**
     * Set sendPoint.
     *
     * @param int $sendPoint
     *
     * @return NormalOrders
     */
    public function setSendPoint($sendPoint)
    {
        $this->send_point = $sendPoint;

        return $this;
    }

    /**
     * Get sendPoint.
     *
     * @return int
     */
    public function getSendPoint()
    {
        return $this->send_point;
    }

    /**
     * Set isOnlineOrder.
     *
     * @param bool $isOnlineOrder
     *
     * @return NormalOrders
     */
    public function setIsOnlineOrder($isOnlineOrder)
    {
        $this->is_online_order = $isOnlineOrder;

        return $this;
    }

    /**
     * Get isOnlineOrder.
     *
     * @return bool
     */
    public function getIsOnlineOrder()
    {
        return $this->is_online_order;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return NormalOrders
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set taxableFee.
     *
     * @param int $taxableFee
     *
     * @return NormalOrders
     */
    public function setTaxableFee($taxableFee)
    {
        $this->taxable_fee = $taxableFee;

        return $this;
    }

    /**
     * Get taxableFee.
     *
     * @return int
     */
    public function getTaxableFee()
    {
        return $this->taxable_fee;
    }

    /**
     * Set identityId.
     *
     * @param string|null $identityId
     *
     * @return NormalOrders
     */
    public function setIdentityId($identityId = null)
    {
        $this->identity_id = $identityId;

        return $this;
    }

    /**
     * Get identityId.
     *
     * @return string|null
     */
    public function getIdentityId()
    {
        return $this->identity_id;
    }

    /**
     * Set identityName.
     *
     * @param string|null $identityName
     *
     * @return NormalOrders
     */
    public function setIdentityName($identityName = null)
    {
        $this->identity_name = $identityName;

        return $this;
    }

    /**
     * Get identityName.
     *
     * @return string|null
     */
    public function getIdentityName()
    {
        return $this->identity_name;
    }

    /**
     * Set totalTax.
     *
     * @param int $totalTax
     *
     * @return NormalOrders
     */
    public function setTotalTax($totalTax)
    {
        $this->total_tax = $totalTax;

        return $this;
    }

    /**
     * Get totalTax.
     *
     * @return int
     */
    public function getTotalTax()
    {
        return $this->total_tax;
    }

    /**
     * Set auditStatus.
     *
     * @param string|null $auditStatus
     *
     * @return NormalOrders
     */
    public function setAuditStatus($auditStatus = null)
    {
        $this->audit_status = $auditStatus;

        return $this;
    }

    /**
     * Get auditStatus.
     *
     * @return string|null
     */
    public function getAuditStatus()
    {
        return $this->audit_status;
    }

    /**
     * Set auditMsg.
     *
     * @param string|null $auditMsg
     *
     * @return NormalOrders
     */
    public function setAuditMsg($auditMsg = null)
    {
        $this->audit_msg = $auditMsg;

        return $this;
    }

    /**
     * Get auditMsg.
     *
     * @return string|null
     */
    public function getAuditMsg()
    {
        return $this->audit_msg;
    }

    /**
     * Set pointFee.
     *
     * @param int|null $pointFee
     *
     * @return NormalOrders
     */
    public function setPointFee($pointFee = null)
    {
        $this->point_fee = $pointFee;

        return $this;
    }

    /**
     * Get pointFee.
     *
     * @return int|null
     */
    public function getPointFee()
    {
        return $this->point_fee;
    }

    /**
     * Set pointUse.
     *
     * @param int|null $pointUse
     *
     * @return NormalOrders
     */
    public function setPointUse($pointUse = null)
    {
        $this->point_use = $pointUse;

        return $this;
    }

    /**
     * Get pointUse.
     *
     * @return int|null
     */
    public function getPointUse()
    {
        return $this->point_use;
    }

    /**
     * Set getPointType.
     *
     * @param int $getPointType
     *
     * @return NormalOrders
     */
    public function setGetPointType($getPointType)
    {
        $this->get_point_type = $getPointType;

        return $this;
    }

    /**
     * Get getPointType.
     *
     * @return int
     */
    public function getGetPointType()
    {
        return $this->get_point_type;
    }

    /**
     * Set getPoints.
     *
     * @param int|null $getPoints
     *
     * @return NormalOrders
     */
    public function setGetPoints($getPoints = null)
    {
        $this->get_points = $getPoints;

        return $this;
    }

    /**
     * Get getPoints.
     *
     * @return int|null
     */
    public function getGetPoints()
    {
        return $this->get_points;
    }

    /**
     * Set bonusPoints.
     *
     * @param int|null $bonusPoints
     *
     * @return NormalOrders
     */
    public function setBonusPoints($bonusPoints = null)
    {
        $this->bonus_points = $bonusPoints;

        return $this;
    }

    /**
     * Get bonusPoints.
     *
     * @return int|null
     */
    public function getBonusPoints()
    {
        return $this->bonus_points;
    }

    /**
     * Set isShopScreen.
     *
     * @param bool $isShopScreen
     *
     * @return NormalOrders
     */
    public function setIsShopScreen($isShopScreen)
    {
        $this->is_shopscreen = $isShopScreen;

        return $this;
    }

    /**
     * Get isShopScreen.
     *
     * @return bool
     */
    public function getIsShopScreen()
    {
        return $this->is_shopscreen;
    }

    /**
     * Set isLogistics.
     *
     * @param bool $isLogistics
     *
     * @return NormalOrdersItems
     */
    public function setIsLogistics($isLogistics)
    {
        $this->is_logistics = $isLogistics;

        return $this;
    }

    /**
     * Get isLogistics.
     *
     * @return bool
     */
    public function getIsLogistics()
    {
        return $this->is_logistics;
    }

    /**
     * Set isProfitsharing.
     *
     * @param int $isProfitsharing
     *
     * @return NormalOrders
     */
    public function setIsProfitsharing($isProfitsharing)
    {
        $this->is_profitsharing = $isProfitsharing;

        return $this;
    }

    /**
     * Get isProfitsharing.
     *
     * @return int
     */
    public function getIsProfitsharing()
    {
        return $this->is_profitsharing;
    }

    /**
     * Set profitsharingStatus.
     *
     * @param int $profitsharingStatus
     *
     * @return NormalOrders
     */
    public function setProfitsharingStatus($profitsharingStatus)
    {
        $this->profitsharing_status = $profitsharingStatus;

        return $this;
    }

    /**
     * Get profitsharingStatus.
     *
     * @return int
     */
    public function getProfitsharingStatus()
    {
        return $this->profitsharing_status;
    }

    /**
     * Set orderAutoCloseAftersalesTime.
     *
     * @param int|null $orderAutoCloseAftersalesTime
     *
     * @return NormalOrders
     */
    public function setOrderAutoCloseAftersalesTime($orderAutoCloseAftersalesTime = null)
    {
        $this->order_auto_close_aftersales_time = $orderAutoCloseAftersalesTime;

        return $this;
    }

    /**
     * Get orderAutoCloseAftersalesTime.
     *
     * @return int|null
     */
    public function getOrderAutoCloseAftersalesTime()
    {
        return $this->order_auto_close_aftersales_time;
    }

    /**
     * Set profitsharingRate.
     *
     * @param int|null $profitsharingRate
     *
     * @return NormalOrders
     */
    public function setProfitsharingRate($profitsharingRate = null)
    {
        $this->profitsharing_rate = $profitsharingRate;

        return $this;
    }

    /**
     * Get profitsharingRate.
     *
     * @return int|null
     */
    public function getProfitsharingRate()
    {
        return $this->profitsharing_rate;
    }

    /**
     * Set pack.
     *
     * @param string|null $pack
     *
     * @return NormalOrders
     */
    public function setPack($pack = null)
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack.
     *
     * @return string|null
     */
    public function getPack()
    {
        return $this->pack;
    }

    /**
     * Set freightType.
     *
     * @param string $freightType
     *
     * @return NormalOrders
     */
    public function setFreightType($freightType)
    {
        $this->freight_type = $freightType;

        return $this;
    }

    /**
     * Get freightType.
     *
     * @return string
     */
    public function getFreightType()
    {
        return $this->freight_type;
    }

    /**
     * Set itemPoint.
     *
     * @param int|null $itemPoint
     *
     * @return NormalOrders
     */
    public function setItemPoint($itemPoint = null)
    {
        $this->item_point = $itemPoint;

        return $this;
    }

    /**
     * Get itemPoint.
     *
     * @return int|null
     */
    public function getItemPoint()
    {
        return $this->item_point;
    }

    /**
     * Set bindAuthCode.
     *
     * @param string|null $bindAuthCode
     *
     * @return NormalOrders
     */
    public function setBindAuthCode($bindAuthCode = null)
    {
        $this->bind_auth_code = $bindAuthCode;

        return $this;
    }

    /**
     * Get bindAuthCode.
     *
     * @return string|null
     */
    public function getBindAuthCode()
    {
        return $this->bind_auth_code;
    }

    /**
     * Set extra_points.
     *
     * @param int|null $point
     *
     * @return NormalOrders
     */
    public function setExtraPoints($extra_points = null)
    {
        $this->extra_points = $extra_points;

        return $this;
    }

    /**
     * Get extra_points.
     *
     * @return int|null
     */
    public function getExtraPoints()
    {
        return $this->extra_points;
    }


    /**
     * Set uppointUse.
     *
     * @param int|null $uppointUse
     *
     * @return NormalOrders
     */
    public function setUppointUse($uppointUse = null)
    {
        $this->uppoint_use = $uppointUse;

        return $this;
    }

    /**
     * Get uppointUse.
     *
     * @return int|null
     */
    public function getUppointUse()
    {
        return $this->uppoint_use;
    }


    /**
     * Set pointUpUse.
     *
     * @param int|null $pointUpUse
     *
     * @return NormalOrders
     */
    public function setPointUpUse($pointUpUse = null)
    {
        $this->point_up_use = $pointUpUse;

        return $this;
    }

    /**
     * Get pointUpUse.
     *
     * @return int|null
     */
    public function getPointUpUse()
    {
        return $this->point_up_use;
    }


    /**
     * Set saleSalesmanDistributorId.
     *
     * @param int $saleSalesmanDistributorId
     *
     * @return NormalOrders
     */
    public function setSaleSalesmanDistributorId($saleSalesmanDistributorId)
    {
        $this->sale_salesman_distributor_id = $saleSalesmanDistributorId;

        return $this;
    }

    /**
     * Get saleSalesmanDistributorId.
     *
     * @return int
     */
    public function getSaleSalesmanDistributorId()
    {
        return $this->sale_salesman_distributor_id;
    }

    /**
     * Set bindSalesmanId.
     *
     * @param int|null $bindSalesmanId
     *
     * @return NormalOrders
     */
    public function setBindSalesmanId($bindSalesmanId = null)
    {
        $this->bind_salesman_id = $bindSalesmanId;

        return $this;
    }

    /**
     * Get bindSalesmanId.
     *
     * @return int|null
     */
    public function getBindSalesmanId()
    {
        return $this->bind_salesman_id;
    }

    /**
     * Set bindSalesmanDistributorId.
     *
     * @param int $bindSalesmanDistributorId
     *
     * @return NormalOrders
     */
    public function setBindSalesmanDistributorId($bindSalesmanDistributorId)
    {
        $this->bind_salesman_distributor_id = $bindSalesmanDistributorId;

        return $this;
    }

    /**
     * Get bindSalesmanDistributorId.
     *
     * @return int
     */
    public function getBindSalesmanDistributorId()
    {
        return $this->bind_salesman_distributor_id;
    }

    /**
     * Set chatId.
     *
     * @param string|null $chatId
     *
     * @return NormalOrders
     */
    public function setChatId($chatId = null)
    {
        $this->chat_id = $chatId;

        return $this;
    }

    /**
     * Get chatId.
     *
     * @return string|null
     */
    public function getChatId()
    {
        return $this->chat_id;
    }


    /**
     * Set isConsumption.
     *
     * @param int $isConsumption
     *
     * @return NormalOrders
     */
    public function setIsConsumption($isConsumption)
    {
        $this->is_consumption = $isConsumption;

        return $this;
    }

    /**
     * Get isConsumption.
     *
     * @return int
     */
    public function getIsConsumption()
    {
        return $this->is_consumption;
    }

    /**
     * Set appPayType.
     *
     * @param string|null $appPayType
     *
     * @return NormalOrders
     */
    public function setAppPayType($appPayType = null)
    {
        $this->app_pay_type = $appPayType;

        return $this;
    }

    /**
     * Set distributorRemark.
     *
     * @param string $distributorRemark
     *
     * @return NormalOrders
     */
    public function setDistributorRemark($distributorRemark)
    {
        $this->distributor_remark = $distributorRemark;

        return $this;
    }

    /**
     * Get appPayType.
     *
     * @return string|null
     */
    public function getAppPayType()
    {
        return $this->app_pay_type;
    }

    /**
     * Get distributorRemark.
     *
     * @return string
     */
    public function getDistributorRemark()
    {
        return $this->distributor_remark;
    }

    /**
     * Set payChannel.
     *
     * @param string|null $payChannel
     *
     * @return NormalOrders
     */
    public function setPayChannel($payChannel = null)
    {
        $this->pay_channel = $payChannel;

        return $this;
    }

    /**
     * Get payChannel.
     *
     * @return string|null
     */
    public function getPayChannel()
    {
        return $this->pay_channel;
    }

    /**
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return NormalOrders
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set subdistrictParentId
     *
     * @param integer $subdistrictParentId
     *
     * @return NormalOrders
     */
    public function setSubdistrictParentId($subdistrictParentId)
    {
        $this->subdistrict_parent_id = $subdistrictParentId;

        return $this;
    }

    /**
     * Get subdistrictParentId
     *
     * @return integer
     */
    public function getSubdistrictParentId()
    {
        return $this->subdistrict_parent_id;
    }

    /**
     * Set subdistrictId
     *
     * @param integer $subdistrictId
     *
     * @return NormalOrders
     */
    public function setSubdistrictId($subdistrictId)
    {
        $this->subdistrict_id = $subdistrictId;

        return $this;
    }

    /**
     * Get subdistrictId
     *
     * @return integer
     */
    public function getSubdistrictId()
    {
        return $this->subdistrict_id;
    }

    /**
     * Set buildingNumber
     *
     * @param string $buildingNumber
     *
     * @return NormalOrders
     */
    public function setBuildingNumber($buildingNumber)
    {
        $this->building_number = $buildingNumber;

        return $this;
    }

    /**
     * Get buildingNumber
     *
     * @return string
     */
    public function getBuildingNumber()
    {
        return $this->building_number;
    }

    /**
     * Set houseNumber
     *
     * @param string $houseNumber
     *
     * @return NormalOrders
     */
    public function setHouseNumber($houseNumber)
    {
        $this->house_number = $houseNumber;

        return $this;
    }

    /**
     * Get houseNumber
     *
     * @return string
     */
    public function getHouseNumber()
    {
        return $this->house_number;
    }

    /**
     * Set operatorId
     *
     * @param string $operatorId
     *
     * @return NormalOrders
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId
     *
     * @return string
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set leftAftersalesNum
     *
     * @param string $leftAftersalesNum
     *
     * @return NormalOrders
     */
    public function setLeftAftersalesNum($leftAftersalesNum)
    {
        $this->left_aftersales_num = $leftAftersalesNum;

        return $this;
    }

    /**
     * Get leftAftersalesNum
     *
     * @return string
     */
    public function getLeftAftersalesNum()
    {
        return $this->left_aftersales_num;
    }

    /**
     * Set sourceFrom
     *
     * @param string $sourceFrom
     *
     * @return NormalOrders
     */
    public function setSourceFrom($sourceFrom)
    {
        $this->source_from = $sourceFrom;

        return $this;
    }

    /**
     * Get sourceFrom
     *
     * @return string
     */
    public function getSourceFrom()
    {
        return $this->source_from;
    }

    /**
     * Set selfDeliveryStatus.
     *
     * @param string|null $selfDeliveryStatus
     *
     * @return NormalOrders
     */
    public function setSelfDeliveryStatus($selfDeliveryStatus = null)
    {
        $this->self_delivery_status = $selfDeliveryStatus;

        return $this;
    }

    /**
     * Get selfDeliveryStatus.
     *
     * @return string|null
     */
    public function getSelfDeliveryStatus()
    {
        return $this->self_delivery_status;
    }

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return NormalOrders
     */
    public function setSupplierId($supplierId)
    {
        $this->supplier_id = $supplierId;

        return $this;
    }

    /**
     * Get supplierId.
     *
     * @return int
     */
    public function getSupplierId()
    {
        return $this->supplier_id;
    }

    /**
     * Set selfDeliveryOperatorId.
     *
     * @param int $selfDeliveryOperatorId
     *
     * @return NormalOrders
     */
    public function setSelfDeliveryOperatorId($selfDeliveryOperatorId)
    {
        $this->self_delivery_operator_id = $selfDeliveryOperatorId;

        return $this;
    }

    /**
     * Get selfDeliveryOperatorId.
     *
     * @return int
     */
    public function getSelfDeliveryOperatorId()
    {
        return $this->self_delivery_operator_id;
    }

    /**
     * Set originalOrderId.
     *
     * @param int $originalOrderId
     *
     * @return NormalOrders
     */
    public function setOriginalOrderId($originalOrderId)
    {
        $this->original_order_id = $originalOrderId;

        return $this;
    }

    /**
     * Get originalOrderId.
     *
     * @return int
     */
    public function getOriginalOrderId()
    {
        return $this->original_order_id;
    }

    /**
     * Set selfDeliveryFee.
     *
     * @param int|null $selfDeliveryFee
     *
     * @return NormalOrders
     */
    public function setSelfDeliveryFee($selfDeliveryFee = null)
    {
        $this->self_delivery_fee = $selfDeliveryFee;

        return $this;
    }

    /**
     * Get selfDeliveryFee.
     *
     * @return int|null
     */
    public function getSelfDeliveryFee()
    {
        return $this->self_delivery_fee;
    }

    /**
     * Set selfDeliveryTime.
     *
     * @param int|null $selfDeliveryTime
     *
     * @return NormalOrders
     */
    public function setSelfDeliveryTime($selfDeliveryTime = null)
    {
        $this->self_delivery_time = $selfDeliveryTime;

        return $this;
    }

    /**
     * Get selfDeliveryTime.
     *
     * @return int|null
     */
    public function getSelfDeliveryTime()
    {
        return $this->self_delivery_time;
    }

    /**
     * Set commissionFee.
     *
     * @param int $commissionFee
     *
     * @return NormalOrders
     */
    public function setCommissionFee($commissionFee)
    {
        $this->commission_fee = $commissionFee;
        return $this;
    }

     /**
     * Set selfDeliveryEndTime.
     *
     * @param int|null $selfDeliveryEndTime
     *
     * @return NormalOrders
     */
    public function setSelfDeliveryEndTime($selfDeliveryEndTime = null)
    {
        $this->self_delivery_end_time = $selfDeliveryEndTime;

        return $this;
    }

    /**
     * Get commissionFee.
     *
     * @return int
     */
    public function getCommissionFee()
    {
        return $this->commission_fee;
    }


     /**
     * Get selfDeliveryEndTime.
     *
     * @return int|null
     */
    public function getSelfDeliveryEndTime()
    {
        return $this->self_delivery_end_time;
    }

    /**
     * Set orderHolder.
     *
     * @param string|null $orderHolder
     *
     * @return NormalOrders
     */
    public function setOrderHolder($orderHolder = null)
    {
        $this->order_holder = $orderHolder;

        return $this;
    }

    /**
     * Get orderHolder.
     *
     * @return string|null
     */
    public function getOrderHolder()
    {
        return $this->order_holder;
    }

    /**
     * Set offlinePaymentStatus.
     *
     * @param int|null $offlinePaymentStatus
     *
     * @return NormalOrders
     */
    public function setOfflinePaymentStatus($offlinePaymentStatus = null)
    {
        $this->offline_payment_status = $offlinePaymentStatus;

        return $this;
    }

    /**
     * Get offlinePaymentStatus.
     *
     * @return int|null
     */
    public function getOfflinePaymentStatus()
    {
        return $this->offline_payment_status;
    }

    /**
     * Set prescriptionStatus.
     *
     * @param int|null $prescriptionStatus
     *
     * @return NormalOrders
     */
    public function setPrescriptionStatus($prescriptionStatus = null)
    {
        $this->prescription_status = $prescriptionStatus;

        return $this;
    }

    /**
     * Get prescriptionStatus.
     *
     * @return int|null
     */
    public function getPrescriptionStatus()
    {
        return $this->prescription_status;
    }

    /**
     * Set invoiceStatus.
     *
     * @param string $invoiceStatus
     *
     * @return NormalOrders
     */
    public function setInvoiceStatus($invoiceStatus)
    {
        $this->invoice_status = $invoiceStatus;

        return $this;
    }

    /**
     * Get invoiceStatus.
     *
     * @return string
     */
    public function getInvoiceStatus()
    {
        return $this->invoice_status;
    }
}
