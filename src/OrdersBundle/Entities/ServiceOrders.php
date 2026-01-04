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
 * ServiceOrders 服务订单表
 *
 * @ORM\Table(name="service_orders", options={"comment":"服务订单表"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\ServiceOrdersRepository")
 */
class ServiceOrders
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
     * @ORM\Column(name="shop_id", type="bigint", options={"comment":"店铺id", "default": 0})
     */
    private $shop_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="store_name", type="string", nullable=true, options={"comment":"店铺名称"})
     */
    private $store_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="consume_type", type="string", length=15, options={"comment":"核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"})
     */
    private $consume_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_brief", type="string", nullable=true, length=255, options={"comment":"商品描述"})
     */
    private $item_brief;

    /**
     * @var string
     *
     * @ORM\Column(name="item_pics", type="string", nullable=true,  options={"comment":"商品图片"})
     */
    private $item_pics;

    /**
     * @var integer
     *
     * @ORM\Column(name="source_id", type="bigint", nullable=true, options={"comment":"订单来源id"})
     */
    private $source_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="bargain_id", type="bigint", nullable=true, options={"comment":"活动id"})
     */
    private $bargain_id;

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
    private $salesman_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_num", type="string", options={"comment":"购买商品数量"})
     */
    private $item_num;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", nullable=true, options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * 订单金额
     *
     * @ORM\Column(name="total_fee", type="string", options={"comment":"订单金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var int
     *
     * @ORM\Column(name="step_paid_fee", type="integer", nullable=true, options={"unsigned":true, "comment":"分阶段付款已支付金额，以分为单位", "default": 0})
     */
    private $step_paid_fee = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="order_class", type="string", options={"default":"normal", "comment":"订单种类。可选值有 normal 普通订单;groups 拼团订单"})
     */
    private $order_class;

    /**
     * @var string
     *
     * @ORM\Column(name="order_status", type="string", options={"comment":"订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消"})
     */
    private $order_status;

    /**
     * @var string
     *
     * @ORM\Column(name="order_source", type="string", nullable=true, options={"comment":"订单来源。可选值有 member 用户自主下单;shop 商家代客下单","default":"member"})
     */
    private $order_source = 'member';

    /**
     * @var string
     *
     * @ORM\Column(name="operator_desc", type="string", length=100, nullable=true, options={"comment":"操作员信息"})
     */
    private $operator_desc;

    /**
     * @var string
     *
     * @ORM\Column(name="order_type", nullable=true, type="string", options={"comment":"订单类型"})
     */
    private $order_type;

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
     * @ORM\Column(name="auto_cancel_time", type="string", options={"comment":"订单自动取消时间"})
     */
    private $auto_cancel_time;

    /**
     * @var string
     *
     * @ORM\Column(name="date_type", nullable=true, type="string", options={"comment":"有效期的类型, DATE_TYPE_FIX_TIME_RANGE: 指定日期范围内，DATE_TYPE_FIX_TERM:固定天数后"})
     */
    private $date_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="begin_date", nullable=true, type="integer", options={"comment":"有效期开始时间"})
     */
    private $begin_date;

    /**
     * @var datetime
     *
     * @ORM\Column(name="end_date", nullable=true, type="integer", options={"comment":"有效期结束时间"})
     */
    private $end_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="fixed_term", nullable=true, type="integer", options={"comment":"有效期的有效天数"})
     */
    private $fixed_term;

    /**
     * @var int
     *
     * @ORM\Column(name="cost_fee", type="integer", options={"unsigned":true, "comment":"商品成本价，以分为单位"})
     */
    private $cost_fee = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="item_fee", type="integer", options={"unsigned":true, "comment":"商品总金额，以分为单位"})
     */
    private $item_fee = 0;

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
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return ServiceOrders
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return ServiceOrders
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ServiceOrders
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return ServiceOrders
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set storeName
     *
     * @param string $storeName
     *
     * @return ServiceOrders
     */
    public function setStoreName($storeName)
    {
        $this->store_name = $storeName;

        return $this;
    }

    /**
     * Get storeName
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->store_name;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return ServiceOrders
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set consumeType
     *
     * @param string $consumeType
     *
     * @return ServiceOrders
     */
    public function setConsumeType($consumeType)
    {
        $this->consume_type = $consumeType;

        return $this;
    }

    /**
     * Get consumeType
     *
     * @return string
     */
    public function getConsumeType()
    {
        return $this->consume_type;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return ServiceOrders
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set itemBrief
     *
     * @param string $itemBrief
     *
     * @return ServiceOrders
     */
    public function setItemBrief($itemBrief)
    {
        $this->item_brief = $itemBrief;

        return $this;
    }

    /**
     * Get itemBrief
     *
     * @return string
     */
    public function getItemBrief()
    {
        return $this->item_brief;
    }

    /**
     * Set itemPics
     *
     * @param string $itemPics
     *
     * @return ServiceOrders
     */
    public function setItemPics($itemPics)
    {
        $this->item_pics = $itemPics;

        return $this;
    }

    /**
     * Get itemPics
     *
     * @return string
     */
    public function getItemPics()
    {
        return $this->item_pics;
    }

    /**
     * Set sourceId
     *
     * @param integer $sourceId
     *
     * @return ServiceOrders
     */
    public function setSourceId($sourceId)
    {
        $this->source_id = $sourceId;

        return $this;
    }

    /**
     * Get sourceId
     *
     * @return integer
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * Set bargainId
     *
     * @param integer $bargainId
     *
     * @return ServiceOrders
     */
    public function setBargainId($bargainId)
    {
        $this->bargain_id = $bargainId;

        return $this;
    }

    /**
     * Get bargainId
     *
     * @return integer
     */
    public function getBargainId()
    {
        return $this->bargain_id;
    }

    /**
     * Set monitorId
     *
     * @param integer $monitorId
     *
     * @return ServiceOrders
     */
    public function setMonitorId($monitorId)
    {
        $this->monitor_id = $monitorId;

        return $this;
    }

    /**
     * Get monitorId
     *
     * @return integer
     */
    public function getMonitorId()
    {
        return $this->monitor_id;
    }

    /**
     * Set itemNum
     *
     * @param string $itemNum
     *
     * @return ServiceOrders
     */
    public function setItemNum($itemNum)
    {
        $this->item_num = $itemNum;

        return $this;
    }

    /**
     * Get itemNum
     *
     * @return string
     */
    public function getItemNum()
    {
        return $this->item_num;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return ServiceOrders
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set totalFee
     *
     * @param string $totalFee
     *
     * @return ServiceOrders
     */
    public function setTotalFee($totalFee)
    {
        $this->total_fee = $totalFee;

        return $this;
    }

    /**
     * Get totalFee
     *
     * @return string
     */
    public function getTotalFee()
    {
        return $this->total_fee;
    }

    /**
     * Set orderClass
     *
     * @param string $orderClass
     *
     * @return ServiceOrders
     */
    public function setOrderClass($orderClass)
    {
        $this->order_class = $orderClass;

        return $this;
    }

    /**
     * Get orderClass
     *
     * @return string
     */
    public function getOrderClass()
    {
        return $this->order_class;
    }

    /**
     * Set orderStatus
     *
     * @param string $orderStatus
     *
     * @return ServiceOrders
     */
    public function setOrderStatus($orderStatus)
    {
        $this->order_status = $orderStatus;

        return $this;
    }

    /**
     * Get orderStatus
     *
     * @return string
     */
    public function getOrderStatus()
    {
        return $this->order_status;
    }

    /**
     * Set orderSource
     *
     * @param string $orderSource
     *
     * @return ServiceOrders
     */
    public function setOrderSource($orderSource)
    {
        $this->order_source = $orderSource;

        return $this;
    }

    /**
     * Get orderSource
     *
     * @return string
     */
    public function getOrderSource()
    {
        return $this->order_source;
    }

    /**
     * Set operatorDesc
     *
     * @param string $operatorDesc
     *
     * @return ServiceOrders
     */
    public function setOperatorDesc($operatorDesc)
    {
        $this->operator_desc = $operatorDesc;

        return $this;
    }

    /**
     * Get operatorDesc
     *
     * @return string
     */
    public function getOperatorDesc()
    {
        return $this->operator_desc;
    }

    /**
     * Set orderType
     *
     * @param string $orderType
     *
     * @return ServiceOrders
     */
    public function setOrderType($orderType)
    {
        $this->order_type = $orderType;

        return $this;
    }

    /**
     * Get orderType
     *
     * @return string
     */
    public function getOrderType()
    {
        return $this->order_type;
    }

    /**
     * Set createTime
     *
     * @param integer $createTime
     *
     * @return ServiceOrders
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime
     *
     * @return integer
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime
     *
     * @param integer $updateTime
     *
     * @return ServiceOrders
     */
    public function setUpdateTime($updateTime)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime
     *
     * @return integer
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Set autoCancelTime
     *
     * @param string $autoCancelTime
     *
     * @return ServiceOrders
     */
    public function setAutoCancelTime($autoCancelTime)
    {
        $this->auto_cancel_time = $autoCancelTime;

        return $this;
    }

    /**
     * Get autoCancelTime
     *
     * @return string
     */
    public function getAutoCancelTime()
    {
        return $this->auto_cancel_time;
    }

    /**
     * Set dateType
     *
     * @param string $dateType
     *
     * @return ServiceOrders
     */
    public function setDateType($dateType)
    {
        $this->date_type = $dateType;

        return $this;
    }

    /**
     * Get dateType
     *
     * @return string
     */
    public function getDateType()
    {
        return $this->date_type;
    }

    /**
     * Set beginDate
     *
     * @param integer $beginDate
     *
     * @return ServiceOrders
     */
    public function setBeginDate($beginDate)
    {
        $this->begin_date = $beginDate;

        return $this;
    }

    /**
     * Get beginDate
     *
     * @return integer
     */
    public function getBeginDate()
    {
        return $this->begin_date;
    }

    /**
     * Set endDate
     *
     * @param integer $endDate
     *
     * @return ServiceOrders
     */
    public function setEndDate($endDate)
    {
        $this->end_date = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return integer
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * Set fixedTerm
     *
     * @param integer $fixedTerm
     *
     * @return ServiceOrders
     */
    public function setFixedTerm($fixedTerm)
    {
        $this->fixed_term = $fixedTerm;

        return $this;
    }

    /**
     * Get fixedTerm
     *
     * @return integer
     */
    public function getFixedTerm()
    {
        return $this->fixed_term;
    }

    /**
     * Set costFee
     *
     * @param integer $costFee
     *
     * @return ServiceOrders
     */
    public function setCostFee($costFee)
    {
        $this->cost_fee = $costFee;

        return $this;
    }

    /**
     * Get costFee
     *
     * @return integer
     */
    public function getCostFee()
    {
        return $this->cost_fee;
    }

    /**
     * Set itemFee
     *
     * @param integer $itemFee
     *
     * @return ServiceOrders
     */
    public function setItemFee($itemFee)
    {
        $this->item_fee = $itemFee;

        return $this;
    }

    /**
     * Get itemFee
     *
     * @return integer
     */
    public function getItemFee()
    {
        return $this->item_fee;
    }

    /**
     * Set memberDiscount
     *
     * @param integer $memberDiscount
     *
     * @return ServiceOrders
     */
    public function setMemberDiscount($memberDiscount)
    {
        $this->member_discount = $memberDiscount;

        return $this;
    }

    /**
     * Get memberDiscount
     *
     * @return integer
     */
    public function getMemberDiscount()
    {
        return $this->member_discount;
    }

    /**
     * Set couponDiscount
     *
     * @param integer $couponDiscount
     *
     * @return ServiceOrders
     */
    public function setCouponDiscount($couponDiscount)
    {
        $this->coupon_discount = $couponDiscount;

        return $this;
    }

    /**
     * Get couponDiscount
     *
     * @return integer
     */
    public function getCouponDiscount()
    {
        return $this->coupon_discount;
    }

    /**
     * Set couponDiscountDesc
     *
     * @param string $couponDiscountDesc
     *
     * @return ServiceOrders
     */
    public function setCouponDiscountDesc($couponDiscountDesc)
    {
        $this->coupon_discount_desc = $couponDiscountDesc;

        return $this;
    }

    /**
     * Get couponDiscountDesc
     *
     * @return string
     */
    public function getCouponDiscountDesc()
    {
        return $this->coupon_discount_desc;
    }

    /**
     * Set memberDiscountDesc
     *
     * @param string $memberDiscountDesc
     *
     * @return ServiceOrders
     */
    public function setMemberDiscountDesc($memberDiscountDesc)
    {
        $this->member_discount_desc = $memberDiscountDesc;

        return $this;
    }

    /**
     * Get memberDiscountDesc
     *
     * @return string
     */
    public function getMemberDiscountDesc()
    {
        return $this->member_discount_desc;
    }

    /**
     * Set feeType
     *
     * @param string $feeType
     *
     * @return ServiceOrders
     */
    public function setFeeType($feeType)
    {
        $this->fee_type = $feeType;

        return $this;
    }

    /**
     * Get feeType
     *
     * @return string
     */
    public function getFeeType()
    {
        return $this->fee_type;
    }

    /**
     * Set feeRate
     *
     * @param float $feeRate
     *
     * @return ServiceOrders
     */
    public function setFeeRate($feeRate)
    {
        $this->fee_rate = $feeRate;

        return $this;
    }

    /**
     * Get feeRate
     *
     * @return float
     */
    public function getFeeRate()
    {
        return $this->fee_rate;
    }

    /**
     * Set feeSymbol
     *
     * @param string $feeSymbol
     *
     * @return ServiceOrders
     */
    public function setFeeSymbol($feeSymbol)
    {
        $this->fee_symbol = $feeSymbol;

        return $this;
    }

    /**
     * Get feeSymbol
     *
     * @return string
     */
    public function getFeeSymbol()
    {
        return $this->fee_symbol;
    }

    /**
     * Set salesmanId
     *
     * @param integer $salesmanId
     *
     * @return ServiceOrders
     */
    public function setSalesmanId($salesmanId)
    {
        $this->salesman_id = $salesmanId;

        return $this;
    }

    /**
     * Get salesmanId
     *
     * @return integer
     */
    public function getSalesmanId()
    {
        return $this->salesman_id;
    }

    /**
     * Set stepPaidFee
     *
     * @param integer $stepPaidFee
     *
     * @return ServiceOrders
     */
    public function setStepPaidFee($stepPaidFee)
    {
        $this->step_paid_fee = $stepPaidFee;

        return $this;
    }

    /**
     * Get stepPaidFee
     *
     * @return integer
     */
    public function getStepPaidFee()
    {
        return $this->step_paid_fee;
    }
}
