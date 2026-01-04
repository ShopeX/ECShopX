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

namespace SupplierBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SupplierOrder 供应商订单
 *
 * @ORM\Table(name="supplier_order", options={"comment":"供应商订单"},
 *     indexes={
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_order_status", columns={"order_status"}),
 *         @ORM\Index(name="idx_receiver_mobile", columns={"receiver_mobile"}),
 *         @ORM\Index(name="idx_supplier_id", columns={"supplier_id"}),
 *         @ORM\Index(name="idx_create_time", columns={"create_time"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="SupplierBundle\Repositories\SupplierOrderRepository")
 */
class SupplierOrder
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
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
     * @ORM\Column(name="order_source", type="string", nullable=true, options={"comment":"订单来源。可选值有 member-用户自主下单;shop-商家代客下单","default":"member"})
     */
    private $order_source = 'member';

    /**
     * @var string
     *
     * @ORM\Column(name="order_type", type="string", options={"comment":"订单类型。可选值有 normal:普通实体订单","default":"normal"})
     */
    private $order_type = 'normal';

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
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", options={"default":0, "comment":"订单类型，0普通订单,1跨境订单,....其他"})
     */
    private $type = 0;

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
     * @var string
     *
     * @ORM\Column(name="pack", type="string", nullable=true, options={"comment":"包装"})
     */
    private $pack = 0;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", nullable=true, type="integer", options={"comment":"操作者id", "default": 0})
     */
    private $operator_id = 0;

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
     * @var boolean
     *
     * @ORM\Column(name="is_settled", type="boolean", options={"comment":"是否分账(斗拱)", "default": false})
     */
    private $is_settled = false;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
     */
    public function setMobile($mobile = null)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string|null
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set orderClass.
     *
     * @param string $orderClass
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * Set freightType.
     *
     * @param string $freightType
     *
     * @return SupplierOrder
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
     * Set itemFee.
     *
     * @param string $itemFee
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @param string|null $marketFee
     *
     * @return SupplierOrder
     */
    public function setMarketFee($marketFee = null)
    {
        $this->market_fee = $marketFee;

        return $this;
    }

    /**
     * Get marketFee.
     *
     * @return string|null
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * Set receiptType.
     *
     * @param string $receiptType
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * Set isDistribution.
     *
     * @param bool $isDistribution
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * Set deliveryCorp.
     *
     * @param string|null $deliveryCorp
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
     */
    public function setReceiverName($receiverName = null)
    {
        $this->receiver_name = $receiverName;

        return $this;
    }

    /**
     * Get receiverName.
     *
     * @return string|null
     */
    public function getReceiverName()
    {
        return $this->receiver_name;
    }

    /**
     * Set receiverMobile.
     *
     * @param string|null $receiverMobile
     *
     * @return SupplierOrder
     */
    public function setReceiverMobile($receiverMobile = null)
    {
        $this->receiver_mobile = $receiverMobile;

        return $this;
    }

    /**
     * Get receiverMobile.
     *
     * @return string|null
     */
    public function getReceiverMobile()
    {
        return $this->receiver_mobile;
    }

    /**
     * Set receiverZip.
     *
     * @param string|null $receiverZip
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
     */
    public function setReceiverAddress($receiverAddress = null)
    {
        $this->receiver_address = $receiverAddress;

        return $this;
    }

    /**
     * Get receiverAddress.
     *
     * @return string|null
     */
    public function getReceiverAddress()
    {
        return $this->receiver_address;
    }

    /**
     * Set memberDiscount.
     *
     * @param int $memberDiscount
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * Set feeType.
     *
     * @param string $feeType
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * Set itemPoint.
     *
     * @param int|null $itemPoint
     *
     * @return SupplierOrder
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
     * Set point.
     *
     * @param int|null $point
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * Set payChannel.
     *
     * @param string|null $payChannel
     *
     * @return SupplierOrder
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
     * Set remark.
     *
     * @param string|null $remark
     *
     * @return SupplierOrder
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
     * Set invoice.
     *
     * @param array|null $invoice
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * Set type.
     *
     * @param int $type
     *
     * @return SupplierOrder
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
     * Set pointFee.
     *
     * @param int|null $pointFee
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * Set pack.
     *
     * @param string|null $pack
     *
     * @return SupplierOrder
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
     * Set operatorId.
     *
     * @param int|null $operatorId
     *
     * @return SupplierOrder
     */
    public function setOperatorId($operatorId = null)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int|null
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set sourceFrom.
     *
     * @param string|null $sourceFrom
     *
     * @return SupplierOrder
     */
    public function setSourceFrom($sourceFrom = null)
    {
        $this->source_from = $sourceFrom;

        return $this;
    }

    /**
     * Get sourceFrom.
     *
     * @return string|null
     */
    public function getSourceFrom()
    {
        return $this->source_from;
    }

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return SupplierOrder
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
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return SupplierOrder
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
     * @return SupplierOrder
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
     * Set commissionFee.
     *
     * @param int $commissionFee
     *
     * @return SupplierOrder
     */
    public function setCommissionFee($commissionFee)
    {
        $this->commission_fee = $commissionFee;

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
     * Set isSettled.
     *
     * @param bool $isSettled
     *
     * @return SupplierOrder
     */
    public function setIsSettled($isSettled)
    {
        $this->is_settled = $isSettled;

        return $this;
    }

    /**
     * Get isSettled.
     *
     * @return bool
     */
    public function getIsSettled()
    {
        return $this->is_settled;
    }
}
