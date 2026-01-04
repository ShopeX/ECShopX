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
 * BargainOrders 砍价订单表
 *
 * @ORM\Table(name="orders_bargain", options={"comment":"砍价订单表"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\BargainOrdersRepository")
 */
class BargainOrders
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
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="bargain_id", type="bigint", options={"comment":"活动id"})
     */
    private $bargain_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", nullable=true, type="string", length=255, options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="item_price", type="string", options={"comment":"商品价格"})
     */
    private $item_price;

    /**
     * @var string
     *
     * @ORM\Column(name="item_pics", type="string", options={"comment":"商品图片"})
     */
    private $item_pics;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_num", type="bigint", options={"comment":"购买商品数量"})
     */
    private $item_num;

    /**
     * @var integer
     *
     * 运费模板id     *
     * @ORM\Column(name="templates_id", type="integer", options={"default":0, "comment":"运费模板id"})
     */
    private $templates_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", nullable=true, options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var integer
     *
     * 运费价格
     *
     * @ORM\Column(name="freight_fee", type="integer", nullable=true, options={"default":0, "comment":"运费价格，以分为单位"})
     */
    private $freight_fee = 0;

    /**
     * @var string
     *
     * 商品金额
     *
     * @ORM\Column(name="item_fee", type="string", options={"comment":"商品金额，以分为单位"})
     */
    private $item_fee;

    /**
     * @var integer
     *
     * 订单金额
     *
     * @ORM\Column(name="total_fee", type="bigint", options={"comment":"订单金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var string
     *
     * DONE—订单完成
     * NOTPAY—未支付
     * CANCEL—已取消
     *
     * @ORM\Column(name="order_status", type="string", options={"comment":"订单状态"})
     */
    private $order_status;

    /**
     * @var string
     *
     * @ORM\Column(name="order_type", type="string", options={"comment":"订单类型","default":"bargain"})
     */
    private $order_type = 'bargain';

    /**
     * @var string
     *
     * @ORM\Column(name="trade_source", type="string", nullable=true, options={"comment":"订单来源"})
     */
    private $order_source;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_name", type="string", nullable=true, options={"comment":"收货人姓名"})
     */
    private $receiver_name;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_mobile", type="string", nullable=true, options={"comment":"收货人手机号"})
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
     * @ORM\Column(name="receiver_address", type="string", nullable=true, options={"comment":"收货人详细地址"})
     */
    private $receiver_address;

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
     * @ORM\Column(name="remark", type="string", options={"comment":"订单备注"})
     */
    private $remark;

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
     * @var json_array
     *
     * @ORM\Column(name="third_params", type="json_array", nullable=true, options={"comment":"第三方特殊字段存储"})
     */
    private $third_params;

    /**
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return BargainOrders
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
     * @return BargainOrders
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
     * @return BargainOrders
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return BargainOrders
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
     * Set bargainId
     *
     * @param integer $bargainId
     *
     * @return BargainOrders
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
     * Set itemName
     *
     * @param string $itemName
     *
     * @return BargainOrders
     */
    public function setItemName($itemName)
    {
        $this->item_name = $itemName;

        return $this;
    }

    /**
     * Get itemName
     *
     * @return string
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set itemPrice
     *
     * @param string $itemPrice
     *
     * @return BargainOrders
     */
    public function setItemPrice($itemPrice)
    {
        $this->item_price = $itemPrice;

        return $this;
    }

    /**
     * Get itemPrice
     *
     * @return string
     */
    public function getItemPrice()
    {
        return $this->item_price;
    }

    /**
     * Set itemPics
     *
     * @param string $itemPics
     *
     * @return BargainOrders
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
     * Set itemNum
     *
     * @param integer $itemNum
     *
     * @return BargainOrders
     */
    public function setItemNum($itemNum)
    {
        $this->item_num = $itemNum;

        return $this;
    }

    /**
     * Get itemNum
     *
     * @return integer
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
     * @return BargainOrders
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
     * @param integer $totalFee
     *
     * @return BargainOrders
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
     * Set orderStatus
     *
     * @param string $orderStatus
     *
     * @return BargainOrders
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
     * Set orderType
     *
     * @param string $orderType
     *
     * @return BargainOrders
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
     * Set orderSource
     *
     * @param string $orderSource
     *
     * @return BargainOrders
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
     * Set receiverName
     *
     * @param string $receiverName
     *
     * @return BargainOrders
     */
    public function setReceiverName($receiverName)
    {
        $this->receiver_name = $receiverName;

        return $this;
    }

    /**
     * Get receiverName
     *
     * @return string
     */
    public function getReceiverName()
    {
        return $this->receiver_name;
    }

    /**
     * Set receiverMobile
     *
     * @param string $receiverMobile
     *
     * @return BargainOrders
     */
    public function setReceiverMobile($receiverMobile)
    {
        $this->receiver_mobile = $receiverMobile;

        return $this;
    }

    /**
     * Get receiverMobile
     *
     * @return string
     */
    public function getReceiverMobile()
    {
        return $this->receiver_mobile;
    }

    /**
     * Set receiverZip
     *
     * @param string $receiverZip
     *
     * @return BargainOrders
     */
    public function setReceiverZip($receiverZip)
    {
        $this->receiver_zip = $receiverZip;

        return $this;
    }

    /**
     * Get receiverZip
     *
     * @return string
     */
    public function getReceiverZip()
    {
        return $this->receiver_zip;
    }

    /**
     * Set receiverState
     *
     * @param string $receiverState
     *
     * @return BargainOrders
     */
    public function setReceiverState($receiverState)
    {
        $this->receiver_state = $receiverState;

        return $this;
    }

    /**
     * Get receiverState
     *
     * @return string
     */
    public function getReceiverState()
    {
        return $this->receiver_state;
    }

    /**
     * Set receiverCity
     *
     * @param string $receiverCity
     *
     * @return BargainOrders
     */
    public function setReceiverCity($receiverCity)
    {
        $this->receiver_city = $receiverCity;

        return $this;
    }

    /**
     * Get receiverCity
     *
     * @return string
     */
    public function getReceiverCity()
    {
        return $this->receiver_city;
    }

    /**
     * Set receiverDistrict
     *
     * @param string $receiverDistrict
     *
     * @return BargainOrders
     */
    public function setReceiverDistrict($receiverDistrict)
    {
        $this->receiver_district = $receiverDistrict;

        return $this;
    }

    /**
     * Get receiverDistrict
     *
     * @return string
     */
    public function getReceiverDistrict()
    {
        return $this->receiver_district;
    }

    /**
     * Set receiverAddress
     *
     * @param string $receiverAddress
     *
     * @return BargainOrders
     */
    public function setReceiverAddress($receiverAddress)
    {
        $this->receiver_address = $receiverAddress;

        return $this;
    }

    /**
     * Get receiverAddress
     *
     * @return string
     */
    public function getReceiverAddress()
    {
        return $this->receiver_address;
    }

    /**
     * Set createTime
     *
     * @param integer $createTime
     *
     * @return BargainOrders
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
     * @return BargainOrders
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
     * @return BargainOrders
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
     * Set sourceId
     *
     * @param integer $sourceId
     *
     * @return BargainOrders
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
     * @return BargainOrders
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
     * Set remark
     *
     * @param string $remark
     *
     * @return BargainOrders
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set templatesId
     *
     * @param integer $templatesId
     *
     * @return BargainOrders
     */
    public function setTemplatesId($templatesId)
    {
        $this->templates_id = $templatesId;

        return $this;
    }

    /**
     * Get templatesId
     *
     * @return integer
     */
    public function getTemplatesId()
    {
        return $this->templates_id;
    }

    /**
     * Set freightFee
     *
     * @param integer $freightFee
     *
     * @return BargainOrders
     */
    public function setFreightFee($freightFee)
    {
        $this->freight_fee = $freightFee;

        return $this;
    }

    /**
     * Get freightFee
     *
     * @return integer
     */
    public function getFreightFee()
    {
        return $this->freight_fee;
    }

    /**
     * Set itemFee
     *
     * @param string $itemFee
     *
     * @return BargainOrders
     */
    public function setItemFee($itemFee)
    {
        $this->item_fee = $itemFee;

        return $this;
    }

    /**
     * Get itemFee
     *
     * @return string
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
     * @return BargainOrders
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
     * @return BargainOrders
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
     * @return BargainOrders
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
     * @return BargainOrders
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
     * @return BargainOrders
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
     * @return BargainOrders
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
     * @return BargainOrders
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
     * Set thirdParams
     *
     * @param array $thirdParams
     *
     * @return BargainOrders
     */
    public function setThirdParams($thirdParams)
    {
        $this->third_params = $thirdParams;

        return $this;
    }

    /**
     * Get thirdParams
     *
     * @return array
     */
    public function getThirdParams()
    {
        return $this->third_params;
    }
}
