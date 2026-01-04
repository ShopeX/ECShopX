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

namespace AftersalesBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * AftersalesRefund 退款表
 *
 * @ORM\Table(name="aftersales_refund", options={"comment":"退款表"},
 *     indexes={
 *        @ORM\Index(name="idx_aftersales_bn", columns={"aftersales_bn"}),
 *        @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *        @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *        @ORM\Index(name="idx_merchant_id", columns={"merchant_id"}),
 *        @ORM\Index(name="idx_supplier_id", columns={"supplier_id"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="AftersalesBundle\Repositories\AftersalesRefundRepository")
 */
class AftersalesRefund
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="refund_bn", type="bigint", options={"comment":"申请退款单号"})
     */
    private $refund_bn;

    /**
     * @var integer
     *
     * @ORM\Column(name="aftersales_bn", type="bigint", nullable=true, options={"comment":"售后单号"})
     */
    private $aftersales_bn;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="trade_id", type="string", length=64, options={"comment":"支付单号"})
     */
    private $trade_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", nullable=true, options={"comment":"商品id"})
     */
    // private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="bigint", options={"comment":"供应商id", "default": 0})
     */
    private $supplier_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"门店id", "default": 0})
     */
    private $shop_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var string
     *
     * 0 售后申请退款
     * 1 取消订单退款
     * 2 拒收订单退款
     *
     * @ORM\Column(name="refund_type", type="string", options={"comment":"退款类型","default": 0})
     */
    private $refund_type = 0;

    /**
     * @var string
     *
     * offline 线下退款
     * original 原路返回
     *
     * @ORM\Column(name="refund_channel", type="string", options={"comment":"退款渠道"})
     */
    private $refund_channel;

    /**
     * @var string
     *
     * READY 未审核
     * AUDIT_SUCCESS 审核成功待退款
     * SUCCESS 退款成功
     * REFUSE 退款驳回
     * CANCEL 撤销退款
     * REFUNDCLOSE 退款关闭。
     * PROCESSING 退款处理中
     * PROCESSING 已发起退款等待到账
     * CHANGE 退款异常
     *
     * @ORM\Column(name="refund_status", type="string", options={"default": "READY", "comment":"退款状态"})
     */
    private $refund_status = 'PROCESSING';

    /**
     * @var integer
     *
     * 订单金额
     *
     * @ORM\Column(name="order_fee", type="integer", options={"unsigned":true, "comment":"订单金额，以分为单位"})
     */
    // private $order_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_fee", type="integer", options={"unsigned":true, "comment":"应退金额，以分为单位，非积分支付"})
     */
    private $refund_fee;

    /**
     * @var integer
     *
     * 实退金额，以分为单位
     *
     * @ORM\Column(name="refunded_fee", type="integer", options={"unsigned":true, "comment":"实退金额，以分为单位","default":0})
     */
    private $refunded_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_point", type="integer", options={"unsigned":true, "comment":"应退积分，以分为单位"})
     */
    private $refund_point;

    /**
     * @var integer
     *
     * @ORM\Column(name="refunded_point", type="integer", options={"unsigned":true, "comment":"实退积分","default":0})
     */
    private $refunded_point = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="return_point", type="integer", options={"unsigned":true, "comment":"退还(下单获得的)积分，以分为单位"})
     */
    private $return_point = 0;

    /**
     * @var integer
     *
     * 是否退运费
     * 0 不退运费
     * 1 退运费
     *
     * @ORM\Column(name="return_freight", type="integer", options={"default":0, "comment":"是否退运费"})
     */
    private $return_freight = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_type", type="string", nullable=true, options={"comment":"支付方式，同对应支付单", "default": ""})
     */
    private $pay_type = '';

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", nullable=true, length=16, options={"comment":"货币类型"})
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="refunds_memo", type="string", nullable=true, options={ "comment":"退款备注"})
     */
    private $refunds_memo;

    /**
     * @var \DateTime $refund_time
     *
     * @ORM\Column(name="refund_time", type="bigint", nullable=true, options={"comment":"申请退款时间"})
     */
    // private $refund_time;

    /**
     * @var \DateTime $refund_success_time
     *
     * @ORM\Column(name="refund_success_time", type="bigint", nullable=true, options={"comment":"退款成功时间"})
     */
    private $refund_success_time;

    /**
     * @var string $refund_id
     *
     * @ORM\Column(name="refund_id", type="string", length=50, nullable=true, options={"comment":"商户返回退款单号"})
     */
    private $refund_id;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $update_time;

    /**
     * @var string
     *
     * @ORM\Column(name="cur_fee_type", type="string", nullable=true, length=5, options={"comment":"系统配置货币类型", "default":"CNY"})
     */
    private $cur_fee_type = 'CNY';

    /**
     * @var string
     *
     * @ORM\Column(name="cur_fee_rate", type="float", precision=15, scale=4, options={"comment":"系统配置货币汇率", "default":1})
     */
    private $cur_fee_rate = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="cur_fee_symbol", type="string", nullable=true,  options={"comment":"系统配置货币符号", "default":"￥"})
     */
    private $cur_fee_symbol = '￥';

    /**
     * @var string
     *
     * @ORM\Column(name="cur_pay_fee", type="string", options={"comment":"系统货币支付金额"})
     */
    private $cur_pay_fee;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_order_id", type="string", nullable=true, options={"comment":"hf_order_id"})
     */
    private $hf_order_id;
    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="freight", type="integer",  nullable=true, options={"unsigned":true, "comment":"退款运费，根据freight_type存储现金（分）或积分值","default":0})
     */
    private $freight = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="freight_type", type="string", length=10, options={"default":"cash", "comment":"运费类型-用于积分商城 cash:现金 point:积分"})
     */
    private $freight_type = 'cash';

    /**
     * Set freight.
     *
     * @param int $freight
     *
     * @return AftersalesRefund
     */
    public function setFreight($freight)
    {
        $this->freight = $freight;

        return $this;
    }

    /**
     * Get freight.
     *
     * @return int
     */
    public function getFreight()
    {
        return $this->freight;
    }

    /**
     * Set freightType.
     *
     * @param string $freightType
     *
     * @return AftersalesRefund
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
     * Set refundBn.
     *
     * @param int $refundBn
     *
     * @return AftersalesRefund
     */
    public function setRefundBn($refundBn)
    {
        $this->refund_bn = $refundBn;

        return $this;
    }

    /**
     * Get refundBn.
     *
     * @return int
     */
    public function getRefundBn()
    {
        return $this->refund_bn;
    }

    /**
     * Set aftersalesBn.
     *
     * @param int|null $aftersalesBn
     *
     * @return AftersalesRefund
     */
    public function setAftersalesBn($aftersalesBn = null)
    {
        $this->aftersales_bn = $aftersalesBn;

        return $this;
    }

    /**
     * Get aftersalesBn.
     *
     * @return int|null
     */
    public function getAftersalesBn()
    {
        return $this->aftersales_bn;
    }

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return AftersalesRefund
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
     * Set tradeId.
     *
     * @param string $tradeId
     *
     * @return AftersalesRefund
     */
    public function setTradeId($tradeId)
    {
        $this->trade_id = $tradeId;

        return $this;
    }

    /**
     * Get tradeId.
     *
     * @return string
     */
    public function getTradeId()
    {
        return $this->trade_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return AftersalesRefund
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
     * Set userId.
     *
     * @param int $userId
     *
     * @return AftersalesRefund
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set shopId.
     *
     * @param int|null $shopId
     *
     * @return AftersalesRefund
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return AftersalesRefund
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
     * Set refundType.
     *
     * @param string $refundType
     *
     * @return AftersalesRefund
     */
    public function setRefundType($refundType)
    {
        $this->refund_type = $refundType;

        return $this;
    }

    /**
     * Get refundType.
     *
     * @return string
     */
    public function getRefundType()
    {
        return $this->refund_type;
    }

    /**
     * Set refundChannel.
     *
     * @param string $refundChannel
     *
     * @return AftersalesRefund
     */
    public function setRefundChannel($refundChannel)
    {
        $this->refund_channel = $refundChannel;

        return $this;
    }

    /**
     * Get refundChannel.
     *
     * @return string
     */
    public function getRefundChannel()
    {
        return $this->refund_channel;
    }

    /**
     * Set refundStatus.
     *
     * @param string $refundStatus
     *
     * @return AftersalesRefund
     */
    public function setRefundStatus($refundStatus)
    {
        $this->refund_status = $refundStatus;

        return $this;
    }

    /**
     * Get refundStatus.
     *
     * @return string
     */
    public function getRefundStatus()
    {
        return $this->refund_status;
    }

    /**
     * Set refundFee.
     *
     * @param int $refundFee
     *
     * @return AftersalesRefund
     */
    public function setRefundFee($refundFee)
    {
        $this->refund_fee = $refundFee;

        return $this;
    }

    /**
     * Get refundFee.
     *
     * @return int
     */
    public function getRefundFee()
    {
        return $this->refund_fee;
    }

    /**
     * Set refundedFee.
     *
     * @param int $refundedFee
     *
     * @return AftersalesRefund
     */
    public function setRefundedFee($refundedFee)
    {
        $this->refunded_fee = $refundedFee;

        return $this;
    }

    /**
     * Get refundedFee.
     *
     * @return int
     */
    public function getRefundedFee()
    {
        return $this->refunded_fee;
    }

    /**
     * Set refundPoint.
     *
     * @param int $refundPoint
     *
     * @return AftersalesRefund
     */
    public function setRefundPoint($refundPoint)
    {
        $this->refund_point = $refundPoint;

        return $this;
    }

    /**
     * Get refundPoint.
     *
     * @return int
     */
    public function getRefundPoint()
    {
        return $this->refund_point;
    }

    /**
     * Set refundedPoint.
     *
     * @param int $refundedPoint
     *
     * @return AftersalesRefund
     */
    public function setRefundedPoint($refundedPoint)
    {
        $this->refunded_point = $refundedPoint;

        return $this;
    }

    /**
     * Get refundedPoint.
     *
     * @return int
     */
    public function getRefundedPoint()
    {
        return $this->refunded_point;
    }

    /**
     * Set returnFreight.
     *
     * @param int $returnFreight
     *
     * @return AftersalesRefund
     */
    public function setReturnFreight($returnFreight)
    {
        $this->return_freight = $returnFreight;

        return $this;
    }

    /**
     * Get returnFreight.
     *
     * @return int
     */
    public function getReturnFreight()
    {
        return $this->return_freight;
    }

    /**
     * Set payType.
     *
     * @param string|null $payType
     *
     * @return AftersalesRefund
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
     * Set currency.
     *
     * @param string $currency
     *
     * @return AftersalesRefund
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set refundsMemo.
     *
     * @param string|null $refundsMemo
     *
     * @return AftersalesRefund
     */
    public function setRefundsMemo($refundsMemo = null)
    {
        $this->refunds_memo = $refundsMemo;

        return $this;
    }

    /**
     * Get refundsMemo.
     *
     * @return string|null
     */
    public function getRefundsMemo()
    {
        return $this->refunds_memo;
    }

    /**
     * Set refundSuccessTime.
     *
     * @param int|null $refundSuccessTime
     *
     * @return AftersalesRefund
     */
    public function setRefundSuccessTime($refundSuccessTime = null)
    {
        $this->refund_success_time = $refundSuccessTime;

        return $this;
    }

    /**
     * Get refundSuccessTime.
     *
     * @return int|null
     */
    public function getRefundSuccessTime()
    {
        return $this->refund_success_time;
    }

    /**
     * Set refundId.
     *
     * @param string|null $refundId
     *
     * @return AftersalesRefund
     */
    public function setRefundId($refundId = null)
    {
        $this->refund_id = $refundId;

        return $this;
    }

    /**
     * Get refundId.
     *
     * @return string|null
     */
    public function getRefundId()
    {
        return $this->refund_id;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AftersalesRefund
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
     * @return AftersalesRefund
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
     * Set curFeeType.
     *
     * @param string $curFeeType
     *
     * @return AftersalesRefund
     */
    public function setCurFeeType($curFeeType)
    {
        $this->cur_fee_type = $curFeeType;

        return $this;
    }

    /**
     * Get curFeeType.
     *
     * @return string
     */
    public function getCurFeeType()
    {
        return $this->cur_fee_type;
    }

    /**
     * Set curFeeRate.
     *
     * @param float $curFeeRate
     *
     * @return AftersalesRefund
     */
    public function setCurFeeRate($curFeeRate)
    {
        $this->cur_fee_rate = $curFeeRate;

        return $this;
    }

    /**
     * Get curFeeRate.
     *
     * @return float
     */
    public function getCurFeeRate()
    {
        return $this->cur_fee_rate;
    }

    /**
     * Set curFeeSymbol.
     *
     * @param string $curFeeSymbol
     *
     * @return AftersalesRefund
     */
    public function setCurFeeSymbol($curFeeSymbol)
    {
        $this->cur_fee_symbol = $curFeeSymbol;

        return $this;
    }

    /**
     * Get curFeeSymbol.
     *
     * @return string
     */
    public function getCurFeeSymbol()
    {
        return $this->cur_fee_symbol;
    }

    /**
     * Set curPayFee.
     *
     * @param string $curPayFee
     *
     * @return AftersalesRefund
     */
    public function setCurPayFee($curPayFee)
    {
        $this->cur_pay_fee = $curPayFee;

        return $this;
    }

    /**
     * Get curPayFee.
     *
     * @return string
     */
    public function getCurPayFee()
    {
        return $this->cur_pay_fee;
    }

    /**
     * Set hfOrderId.
     *
     * @param string|null $hfOrderId
     *
     * @return AftersalesRefund
     */
    public function setHfOrderId($hfOrderId = null)
    {
        $this->hf_order_id = $hfOrderId;

        return $this;
    }

    /**
     * Get hfOrderId.
     *
     * @return string|null
     */
    public function getHfOrderId()
    {
        return $this->hf_order_id;
    }

    /**
     * Set returnPoint.
     *
     * @param int $returnPoint
     *
     * @return AftersalesRefund
     */
    public function setReturnPoint($returnPoint)
    {
        $this->return_point = $returnPoint;

        return $this;
    }

    /**
     * Get returnPoint.
     *
     * @return int
     */
    public function getReturnPoint()
    {
        return $this->return_point;
    }

    /**
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return AftersalesRefund
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
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return AftersalesRefund
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
}
