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
 * OrderAssociations 订单主关联表
 *
 * @ORM\Table(name="orders_associations", options={"comment":"订单主关联表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_order_type", columns={"order_type"}),
 *         @ORM\Index(name="idx_order_class", columns={"order_class"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_salesman_id", columns={"salesman_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrderAssociationsRepository")
 */
class OrderAssociations
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
     * @ORM\Column(name="authorizer_appid", type="string", length=64, nullable=true, options={"comment":"公众号的appid"})
     */
    private $authorizer_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="wxa_appid", nullable=true, type="string", length=64, options={"comment":"小程序的appid"})
     */
    private $wxa_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="title", nullable=true, type="string", options={"comment":"订单标题"})
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_fee", type="bigint", nullable=true, options={"comment":"订单金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"店铺id"})
     */
    private $shop_id;

    /**
     * @var string
     *
     * @ORM\Column(name="store_name", type="string", nullable=true, length=100, options={"comment":"店铺名称"})
     */
    private $store_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="promoter_user_id", type="bigint", nullable=true, options={"comment":"推广员user_id"})
     */
    private $promoter_user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="promoter_shop_id", type="bigint", nullable=true, options={"comment":"推广员店铺id，实际为推广员的user_id"})
     */
    private $promoter_shop_id;

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
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, nullable=true, options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="order_class", type="string", options={"default":"normal", "comment":"订单种类。可选值有 normal:普通订单;groups:拼团订单;;community 社区活动订单;bargain:助力订单;seckill:秒杀订单;shopguide:导购订单"})
     */
    private $order_class;

    /**
     * @var string
     *
     * @ORM\Column(name="order_type", nullable=true, type="string", options={"comment":"订单类型。可选值有 service 服务业订单;bargain 砍价订单;distribution 分销订单;normal 普通实体订单"})
     */
    private $order_type;

    /**
     * @var string
     *
     * @ORM\Column(name="order_status", type="string", options={"comment":"订单状态。可选值有 DONE—订单完成;PAYED-已支付;NOTPAY—未支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货"})
     */
    private $order_status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_distribution", type="boolean", options={"default":false, "comment":"是否是分销订单"})
     */
    private $is_distribution = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_rebate", type="integer", options={"unsigned":true, "default":0, "comment":"订单总分销金额，以分为单位"})
     */
    private $total_rebate = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_corp", type="string", nullable=true, options={"comment":"快递公司"})
     */
    private $delivery_corp;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_code", type="string", nullable=true, options={"comment":"快递单号"})
     */
    private $delivery_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="delivery_time", type="integer", nullable=true, options={"comment":"发货时间"})
     */
    private $delivery_time;

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
     * @ORM\Column(name="delivery_status", type="string", options={"default": "PENDING", "comment":"发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL_DELIVERY-部分发货"})
     */
    private $delivery_status = "PENDING";

    /**
     * @var string
     *
     * @ORM\Column(name="cancel_status", type="string", options={"default": "NO_APPLY_CANCEL", "comment":"取消订单状态。可选值有 NO_APPLY_CANCEL 未申请;WAIT_PROCESS 等待审核;REFUND_PROCESS 退款处理;SUCCESS 取消成功;FAILS 取消失败"})
     */
    private $cancel_status = 'NO_APPLY_CANCEL';

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"订单创建时间"})
     */
    private $create_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="bigint", nullable=true, options={"comment":"订单完成时间"})
     */
    private $end_time;

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
     * @var integer
     *
     * @ORM\Column(name="salesman_id", nullable=true, type="bigint", options={"comment":"导购员ID", "default": 0})
     */
    private $salesman_id;

    /**
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return OrderAssociations
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
     * Set authorizerAppid
     *
     * @param string $authorizerAppid
     *
     * @return OrderAssociations
     */
    public function setAuthorizerAppid($authorizerAppid)
    {
        $this->authorizer_appid = $authorizerAppid;

        return $this;
    }

    /**
     * Get authorizerAppid
     *
     * @return string
     */
    public function getAuthorizerAppid()
    {
        return $this->authorizer_appid;
    }

    /**
     * Set wxaAppid
     *
     * @param string $wxaAppid
     *
     * @return OrderAssociations
     */
    public function setWxaAppid($wxaAppid)
    {
        $this->wxa_appid = $wxaAppid;

        return $this;
    }

    /**
     * Get wxaAppid
     *
     * @return string
     */
    public function getWxaAppid()
    {
        return $this->wxa_appid;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return OrderAssociations
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
     * Set totalFee
     *
     * @param integer $totalFee
     *
     * @return OrderAssociations
     */
    public function setTotalFee($totalFee)
    {
        $this->total_fee = $totalFee;

        return $this;
    }

    /**
     * Get totalFee
     *
     * @return integer
     */
    public function getTotalFee()
    {
        return $this->total_fee;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return OrderAssociations
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
     * @return OrderAssociations
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
     * @return OrderAssociations
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
     * @return OrderAssociations
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
     * Set promoterUserId
     *
     * @param integer $promoterUserId
     *
     * @return OrderAssociations
     */
    public function setPromoterUserId($promoterUserId)
    {
        $this->promoter_user_id = $promoterUserId;

        return $this;
    }

    /**
     * Get promoterUserId
     *
     * @return integer
     */
    public function getPromoterUserId()
    {
        return $this->promoter_user_id;
    }

    /**
     * Set sourceId
     *
     * @param integer $sourceId
     *
     * @return OrderAssociations
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
     * Set monitorId
     *
     * @param integer $monitorId
     *
     * @return OrderAssociations
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
     * Set mobile
     *
     * @param string $mobile
     *
     * @return OrderAssociations
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set orderClass
     *
     * @param string $orderClass
     *
     * @return OrderAssociations
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
     * Set orderType
     *
     * @param string $orderType
     *
     * @return OrderAssociations
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
     * Set orderStatus
     *
     * @param string $orderStatus
     *
     * @return OrderAssociations
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
     * Set isDistribution
     *
     * @param boolean $isDistribution
     *
     * @return OrderAssociations
     */
    public function setIsDistribution($isDistribution)
    {
        $this->is_distribution = $isDistribution;

        return $this;
    }

    /**
     * Get isDistribution
     *
     * @return boolean
     */
    public function getIsDistribution()
    {
        return $this->is_distribution;
    }

    /**
     * Set totalRebate
     *
     * @param integer $totalRebate
     *
     * @return OrderAssociations
     */
    public function setTotalRebate($totalRebate)
    {
        $this->total_rebate = $totalRebate;

        return $this;
    }

    /**
     * Get totalRebate
     *
     * @return integer
     */
    public function getTotalRebate()
    {
        return $this->total_rebate;
    }

    /**
     * Set deliveryCorp
     *
     * @param string $deliveryCorp
     *
     * @return OrderAssociations
     */
    public function setDeliveryCorp($deliveryCorp)
    {
        $this->delivery_corp = $deliveryCorp;

        return $this;
    }

    /**
     * Get deliveryCorp
     *
     * @return string
     */
    public function getDeliveryCorp()
    {
        return $this->delivery_corp;
    }

    /**
     * Set deliveryCode
     *
     * @param string $deliveryCode
     *
     * @return OrderAssociations
     */
    public function setDeliveryCode($deliveryCode)
    {
        $this->delivery_code = $deliveryCode;

        return $this;
    }

    /**
     * Get deliveryCode
     *
     * @return string
     */
    public function getDeliveryCode()
    {
        return $this->delivery_code;
    }

    /**
     * Set deliveryTime
     *
     * @param integer $deliveryTime
     *
     * @return OrderAssociations
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->delivery_time = $deliveryTime;

        return $this;
    }

    /**
     * Get deliveryTime
     *
     * @return integer
     */
    public function getDeliveryTime()
    {
        return $this->delivery_time;
    }

    /**
     * Set memberDiscount
     *
     * @param integer $memberDiscount
     *
     * @return OrderAssociations
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
     * @return OrderAssociations
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
     * @return OrderAssociations
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
     * @return OrderAssociations
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
     * Set deliveryStatus
     *
     * @param string $deliveryStatus
     *
     * @return OrderAssociations
     */
    public function setDeliveryStatus($deliveryStatus)
    {
        $this->delivery_status = $deliveryStatus;

        return $this;
    }

    /**
     * Get deliveryStatus
     *
     * @return string
     */
    public function getDeliveryStatus()
    {
        return $this->delivery_status;
    }

    /**
     * Set cancelStatus
     *
     * @param string $cancelStatus
     *
     * @return OrderAssociations
     */
    public function setCancelStatus($cancelStatus)
    {
        $this->cancel_status = $cancelStatus;

        return $this;
    }

    /**
     * Get cancelStatus
     *
     * @return string
     */
    public function getCancelStatus()
    {
        return $this->cancel_status;
    }

    /**
     * Set createTime
     *
     * @param integer $createTime
     *
     * @return OrderAssociations
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
     * Set endTime
     *
     * @param integer $endTime
     *
     * @return OrderAssociations
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set updateTime
     *
     * @param integer $updateTime
     *
     * @return OrderAssociations
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
     * Set feeType
     *
     * @param string $feeType
     *
     * @return OrderAssociations
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
     * @return OrderAssociations
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
     * @return OrderAssociations
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
     * @return OrderAssociations
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
     * Set promoterShopId
     *
     * @param integer $promoterShopId
     *
     * @return OrderAssociations
     */
    public function setPromoterShopId($promoterShopId)
    {
        $this->promoter_shop_id = $promoterShopId;

        return $this;
    }

    /**
     * Get promoterShopId
     *
     * @return integer
     */
    public function getPromoterShopId()
    {
        return $this->promoter_shop_id;
    }
}
