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

/**
 * Trade 交易单表
 *
 * @ORM\Table(name="trade", options={"comment":"交易单表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_distributor_id", columns={"distributor_id"}),
 *    @ORM\Index(name="ix_order_id", columns={"order_id"}),
 *    @ORM\Index(name="idx_supplier_id", columns={"supplier_id"}),
 *    @ORM\Index(name="ix_user_id", columns={"user_id"}),
 *    @ORM\Index(name="ix_list", columns={"company_id","order_id"}),
 *    @ORM\Index(name="idx_merchant_id", columns={"merchant_id"}),
 * })
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\TradeRepository")
 */
class Trade
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="trade_id", type="string", length=64, options={"comment":"交易单号"})
     */
    private $trade_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=64, nullable=true, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="string", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_id", type="string", nullable=true, options={"comment":"门店ID"})
     */
    private $shop_id;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_id", type="string", nullable=true, options={"comment":"店铺ID"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="dealer_id", type="string", nullable=true, options={"comment":"经销商ID", "default": 0})
     */
    private $dealer_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="trade_source_type", type="string", nullable=true, options={"comment":"交易单来源类型。可选值有 membercard-会员卡购买;normal-实体订单购买;servers-服务订单购买;normal_community-社区订单购买;diposit-预存款购买;order_pay-买单购买;"})
     */
    private $trade_source_type;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", options={"comment":"购买用户"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, nullable=true, options={"comment":"购买用户手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="open_id", type="string", nullable=true, options={"comment":"用户open_id"})
     */
    private $open_id;

    /**
     * @var string
     * @ORM\Column(name="discount_info", type="text", nullable=true, options={"comment":"优惠金额，优惠金额，优惠原因json结构"})
     */
    private $discount_info;

    /**
     * @var string
     *
     * @ORM\Column(name="mch_id", type="string", nullable=true, options={"comment":"商户号，微信支付"})
     */
    private $mch_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_fee", type="integer", options={"unsigned":true, "default": 0,"comment":"应付总金额,以分为单位"})
     */
    private $total_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="discount_fee", type="integer", options={"comment":"订单优惠金额", "default": 0})
     */
    private $discount_fee = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_type", type="string", length=16, options={"comment":"货币类型"})
     */
    private $fee_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="pay_fee", type="integer", options={"comment":"支付金额", "unsigned":true, "default": 0})
     */
    private $pay_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="trade_no", type="string", options={"comment":"每日交易序号", "default": "0"})
     */
    private $trade_no = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="trade_state", type="string", options={"comment":"交易状态。可选值有 SUCCESS—支付成功;REFUND—转入退款;NOTPAY—未支付;CLOSED—已关闭;REVOKED—已撤销;PAYERROR--支付失败(其他原因，如银行返回失败)"})
     */
    private $trade_state;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_type", type="string", options={"comment":"支付方式。wxpay-微信支付;deposit-预存款支付;pos-刷卡;point-积分"})
     */
    private $pay_type;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_channel", nullable=true, type="string", options={ "comment":"adapay支付渠道", "default": ""})
     */
    private $pay_channel;

    /**
     * @var string
     *
     * @ORM\Column(name="transaction_id", type="string", nullable=true, options={"comment":"支付订单号"})
     */
    private $transaction_id;

    /**
     * @var string
     *
     * @ORM\Column(name="authorizer_appid", nullable=true, type="string", length=64, options={"comment":"公众号的appid"})
     */
    private $authorizer_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="wxa_appid", nullable=true, type="string", length=64, options={"comment":"支付小程序的appid"})
     */
    private $wxa_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_type", nullable=true, type="string", options={"comment":"付款银行"})
     */
    private $bank_type;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="string", options={"comment":"交易商品简单描述"})
     */
    private $body;

    /**
     * @var string
     *
     * @ORM\Column(name="detail", type="string", options={"comment":"交易商品详情"})
     */
    private $detail;

    /**
     * @var string
     *
     * @ORM\Column(name="time_start", type="string", options={"comment":"交易起始时间"})
     */
    private $time_start;

    /**
     * @var string
     *
     * @ORM\Column(name="time_expire", nullable=true, type="string", options={"comment":"交易结束时间"})
     */
    private $time_expire;

    /**
     * @var string
     *
     * @ORM\Column(name="div_members", type="string", nullable=true, length=500, options={"comment":"分账对象信息列表，最多仅支持7个分账方，json 数组形式", "default":""})
     */
    private $div_members = '';

    /**
     * @var integer
     *
     * 实际退款金额，以分为单位
     *
     * @ORM\Column(name="refunded_fee", type="integer", nullable=true, options={"unsigned":true, "comment":"实际退款金额，以分为单位","default":0})
     */
    private $refunded_fee = 0;

    /**
     * @var string
     *
     * I 订单内扣除
     * O 预存款账户扣除
     *
     * @ORM\Column(name="adapay_fee_mode", type="string", nullable=true, options={"default":"", "comment":"adapay手续费收取模式"})
     */
    private $adapay_fee_mode = '';

    /**
     * @var integer
     *
     * 分账手续费，以分为单位
     *
     * @ORM\Column(name="adapay_fee", type="integer", nullable=true, options={"unsigned":true, "comment":"分账手续费，以分为单位","default":0})
     */
    private $adapay_fee = 0;

    /**
     * @var integer
     *
     * 分账状态
     *
     * @ORM\Column(name="adapay_div_status", type="string", nullable=true, options={"comment":"分账状态。可选值有NOTDIV—未分账;DIVED-已分账;"})
     */
    private $adapay_div_status = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="cur_fee_type", type="string", length=5, options={"comment":"系统配置货币类型", "default":"CNY"})
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
     * @ORM\Column(name="cur_fee_symbol", type="string", options={"comment":"系统配置货币符号", "default":"￥"})
     */
    private $cur_fee_symbol = '￥';

    /**
     * @var integer
     *
     * @ORM\Column(name="cur_pay_fee", type="integer", options={"unsigned":true, "default": 0, "comment":"系统货币支付金额"})
     */
    private $cur_pay_fee = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="coupon_fee", type="integer", options={"unsigned":true, "comment":"优惠券抵扣金额，以分为单位", "default":0})
     */
    private $coupon_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="coupon_info", nullable=true, type="text", options={"comment":"优惠券信息json结构"})
     */
    private $coupon_info;

    /**
     * @var integer
     *
     * @ORM\Column(name="inital_request", nullable=true, type="text", options={"comment":"统一下单原始请求json结构"})
     */
    private $inital_request;

    /**
     * @var integer
     *
     * @ORM\Column(name="inital_response", nullable=true, type="text", options={"comment":"支付结果通知json结构"})
     */
    private $inital_response;
    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_settled", type="boolean", options={"comment":"是否分账", "default": false})
     */
    private $is_settled = false;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_params", nullable=true, type="text", options={"comment":"支付参数"})
     */
    private $payment_params;


    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="integer", options={"comment":"供应商id", "default":0})
     */
    private $supplier_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="bspay_req_date", nullable=true, type="string", options={"comment":"斗拱支付的请求日期"})
     */
    private $bspay_req_date;

    /**
     * @var string
     *
     * @ORM\Column(name="bspay_div_members", type="string", nullable=true, length=500, options={"comment":"斗拱分账对象信息列表，最多仅支持7个分账方，json 数组形式", "default":""})
     */
    private $bspay_div_members = '';

    /**
     * @var integer
     *
     * 汇付斗拱，分账状态
     *
     * @ORM\Column(name="bspay_div_status", type="string", nullable=true, options={"comment":"斗拱分账状态。可选值有NOTDIV—未分账;DIVED-已分账;"})
     */
    private $bspay_div_status = 0;

    /**
     * @var string
     *
     * 1:外扣 2:内扣
     *
     * @ORM\Column(name="bspay_fee_mode", type="string", nullable=true, options={"default":"", "comment":"斗拱手续费收取模式 1:外扣 2:内扣"})
     */
    private $bspay_fee_mode = null;

    /**
     * @var integer
     *
     * 斗拱分账手续费，以分为单位
     *
     * @ORM\Column(name="bspay_fee", type="integer", nullable=true, options={"unsigned":true, "comment":"斗拱分账手续费，以分为单位","default":0})
     */
    private $bspay_fee = 0;

    /**
     * Set tradeId
     *
     * @param string $tradeId
     *
     * @return Trade
     */
    public function setTradeId($tradeId)
    {
        $this->trade_id = $tradeId;

        return $this;
    }

    /**
     * Get tradeId
     *
     * @return string
     */
    public function getTradeId()
    {
        return $this->trade_id;
    }

    /**
     * Set orderId
     *
     * @param string $orderId
     *
     * @return Trade
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set shopId
     *
     * @param string $shopId
     *
     * @return Trade
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return string
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set userId
     *
     * @param string $userId
     *
     * @return Trade
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return Trade
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
     * Set openId
     *
     * @param string $openId
     *
     * @return Trade
     */
    public function setOpenId($openId)
    {
        $this->open_id = $openId;

        return $this;
    }

    /**
     * Get openId
     *
     * @return string
     */
    public function getOpenId()
    {
        return $this->open_id;
    }

    /**
     * Set discountInfo
     *
     * @param string $discountInfo
     *
     * @return Trade
     */
    public function setDiscountInfo($discountInfo)
    {
        $this->discount_info = $discountInfo;

        return $this;
    }

    /**
     * Get discountInfo
     *
     * @return string
     */
    public function getDiscountInfo()
    {
        return $this->discount_info;
    }

    /**
     * Set mchId
     *
     * @param string $mchId
     *
     * @return Trade
     */
    public function setMchId($mchId)
    {
        $this->mch_id = $mchId;

        return $this;
    }

    /**
     * Get mchId
     *
     * @return string
     */
    public function getMchId()
    {
        return $this->mch_id;
    }

    /**
     * Set totalFee
     *
     * @param string $totalFee
     *
     * @return Trade
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
     * Set discountFee
     *
     * @param string $discountFee
     *
     * @return Trade
     */
    public function setDiscountFee($discountFee)
    {
        $this->discount_fee = $discountFee;

        return $this;
    }

    /**
     * Get discountFee
     *
     * @return string
     */
    public function getDiscountFee()
    {
        return $this->discount_fee;
    }

    /**
     * Set feeType
     *
     * @param string $feeType
     *
     * @return Trade
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
     * Set payFee
     *
     * @param string $payFee
     *
     * @return Trade
     */
    public function setPayFee($payFee)
    {
        $this->pay_fee = $payFee;

        return $this;
    }

    /**
     * Get payFee
     *
     * @return string
     */
    public function getPayFee()
    {
        return $this->pay_fee;
    }

    /**
     * Set tradeNo
     *
     * @param string $tradeNo
     *
     * @return Trade
     */
    public function setTradeNo($tradeNo)
    {
        $this->trade_no = $tradeNo;

        return $this;
    }

    /**
     * Get tradeNo
     *
     * @return string
     */
    public function getTradeNo()
    {
        return $this->trade_no;
    }

    /**
     * Set tradeState
     *
     * @param string $tradeState
     *
     * @return Trade
     */
    public function setTradeState($tradeState)
    {
        $this->trade_state = $tradeState;

        return $this;
    }

    /**
     * Get tradeState
     *
     * @return string
     */
    public function getTradeState()
    {
        return $this->trade_state;
    }

    /**
     * Set payType
     *
     * @param string $payType
     *
     * @return Trade
     */
    public function setPayType($payType)
    {
        $this->pay_type = $payType;

        return $this;
    }

    /**
     * Get payType
     *
     * @return string
     */
    public function getPayType()
    {
        return $this->pay_type;
    }

    /**
     * Set transactionId
     *
     * @param string $transactionId
     *
     * @return Trade
     */
    public function setTransactionId($transactionId)
    {
        $this->transaction_id = $transactionId;

        return $this;
    }

    /**
     * Get transactionId
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * Set wxaAppid
     *
     * @param string $wxaAppid
     *
     * @return Trade
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
     * Set bankType
     *
     * @param string $bankType
     *
     * @return Trade
     */
    public function setBankType($bankType)
    {
        $this->bank_type = $bankType;

        return $this;
    }

    /**
     * Get bankType
     *
     * @return string
     */
    public function getBankType()
    {
        return $this->bank_type;
    }

    /**
     * Set body
     *
     * @param string $body
     *
     * @return Trade
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set detail
     *
     * @param string $detail
     *
     * @return Trade
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * Get detail
     *
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * Set timeStart
     *
     * @param string $timeStart
     *
     * @return Trade
     */
    public function setTimeStart($timeStart)
    {
        $this->time_start = $timeStart;

        return $this;
    }

    /**
     * Get timeStart
     *
     * @return string
     */
    public function getTimeStart()
    {
        return $this->time_start;
    }

    /**
     * Set timeExpire
     *
     * @param string $timeExpire
     *
     * @return Trade
     */
    public function setTimeExpire($timeExpire)
    {
        $this->time_expire = $timeExpire;

        return $this;
    }

    /**
     * Get timeExpire
     *
     * @return string
     */
    public function getTimeExpire()
    {
        return $this->time_expire;
    }

    /**
     * Set companyId
     *
     * @param string $companyId
     *
     * @return Trade
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return string
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set authorizerAppid
     *
     * @param string $authorizerAppid
     *
     * @return Trade
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
     * Set curFeeType
     *
     * @param string $curFeeType
     *
     * @return Trade
     */
    public function setCurFeeType($curFeeType)
    {
        $this->cur_fee_type = $curFeeType;

        return $this;
    }

    /**
     * Get curFeeType
     *
     * @return string
     */
    public function getCurFeeType()
    {
        return $this->cur_fee_type;
    }

    /**
     * Set curFeeRate
     *
     * @param float $curFeeRate
     *
     * @return Trade
     */
    public function setCurFeeRate($curFeeRate)
    {
        $this->cur_fee_rate = $curFeeRate;

        return $this;
    }

    /**
     * Get curFeeRate
     *
     * @return float
     */
    public function getCurFeeRate()
    {
        return $this->cur_fee_rate;
    }

    /**
     * Set curFeeSymbol
     *
     * @param string $curFeeSymbol
     *
     * @return Trade
     */
    public function setCurFeeSymbol($curFeeSymbol)
    {
        $this->cur_fee_symbol = $curFeeSymbol;

        return $this;
    }

    /**
     * Get curFeeSymbol
     *
     * @return string
     */
    public function getCurFeeSymbol()
    {
        return $this->cur_fee_symbol;
    }

    /**
     * Set curPayFee
     *
     * @param string $curPayFee
     *
     * @return Trade
     */
    public function setCurPayFee($curPayFee)
    {
        $this->cur_pay_fee = $curPayFee;

        return $this;
    }

    /**
     * Get curPayFee
     *
     * @return string
     */
    public function getCurPayFee()
    {
        return $this->cur_pay_fee;
    }

    /**
     * Set distributorId
     *
     * @param string $distributorId
     *
     * @return Trade
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return string
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set tradeSourceType
     *
     * @param string $tradeSourceType
     *
     * @return Trade
     */
    public function setTradeSourceType($tradeSourceType)
    {
        $this->trade_source_type = $tradeSourceType;

        return $this;
    }

    /**
     * Get tradeSourceType
     *
     * @return string
     */
    public function getTradeSourceType()
    {
        return $this->trade_source_type;
    }

    /**
     * Set couponFee
     *
     * @param string $couponFee
     *
     * @return Trade
     */
    public function setCouponFee($couponFee)
    {
        $this->coupon_fee = $couponFee;

        return $this;
    }

    /**
     * Get couponFee
     *
     * @return string
     */
    public function getCouponFee()
    {
        return $this->coupon_fee;
    }

    /**
     * Set couponInfo
     *
     * @param string $couponInfo
     *
     * @return Trade
     */
    public function setCouponInfo($couponInfo)
    {
        $this->coupon_info = $couponInfo;

        return $this;
    }

    /**
     * Get couponInfo
     *
     * @return string
     */
    public function getCouponInfo()
    {
        return $this->coupon_info;
    }

    /**
     * Set initalRequest.
     *
     * @param string|null $initalRequest
     *
     * @return Trade
     */
    public function setInitalRequest($initalRequest = null)
    {
        $this->inital_request = $initalRequest;

        return $this;
    }

    /**
     * Get initalRequest.
     *
     * @return string|null
     */
    public function getInitalRequest()
    {
        return $this->inital_request;
    }

    /**
     * Set initalResponse.
     *
     * @param string|null $initalResponse
     *
     * @return Trade
     */
    public function setInitalResponse($initalResponse = null)
    {
        $this->inital_response = $initalResponse;

        return $this;
    }

    /**
     * Get initalResponse.
     *
     * @return string|null
     */
    public function getInitalResponse()
    {
        return $this->inital_response;
    }

    /**
     * Set payChannel.
     *
     * @param string|null $payChannel
     *
     * @return Trade
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
     * Set divMembers.
     *
     * @param string|null $divMembers
     *
     * @return Trade
     */
    public function setDivMembers($divMembers = null)
    {
        $this->div_members = $divMembers;

        return $this;
    }

    /**
     * Get divMembers.
     *
     * @return string|null
     */
    public function getDivMembers()
    {
        return $this->div_members;
    }

    /**
     * Set refundedFee.
     *
     * @param int|null $refundedFee
     *
     * @return Trade
     */
    public function setRefundedFee($refundedFee = null)
    {
        $this->refunded_fee = $refundedFee;

        return $this;
    }

    /**
     * Get refundedFee.
     *
     * @return int|null
     */
    public function getRefundedFee()
    {
        return $this->refunded_fee;
    }

    /**
     * Set adapayFeeMode.
     *
     * @param string|null $adapayFeeMode
     *
     * @return Trade
     */
    public function setAdapayFeeMode($adapayFeeMode = null)
    {
        $this->adapay_fee_mode = $adapayFeeMode;

        return $this;
    }

    /**
     * Get adapayFeeMode.
     *
     * @return string|null
     */
    public function getAdapayFeeMode()
    {
        return $this->adapay_fee_mode;
    }

    /**
     * Set adapayDivStatus.
     *
     * @param string|null $adapayDivStatus
     *
     * @return Trade
     */
    public function setAdapayDivStatus($adapayDivStatus = null)
    {
        $this->adapay_div_status = $adapayDivStatus;

        return $this;
    }

    /**
     * Get adapayDivStatus.
     *
     * @return string|null
     */
    public function getAdapayDivStatus()
    {
        return $this->adapay_div_status;
    }

    /**
     * Set adapayFee.
     *
     * @param int|null $adapayFee
     *
     * @return Trade
     */
    public function setAdapayFee($adapayFee = null)
    {
        $this->adapay_fee = $adapayFee;

        return $this;
    }

    /**
     * Get adapayFee.
     *
     * @return int|null
     */
    public function getAdapayFee()
    {
        return $this->adapay_fee;
    }


    /**
     * Set dealerId.
     *
     * @param string|null $dealerId
     *
     * @return Trade
     */
    public function setDealerId($dealerId = null)
    {
        $this->dealer_id = $dealerId;

        return $this;
    }

    /**
     * Get dealerId.
     *
     * @return string|null
     */
    public function getDealerId()
    {
        return $this->dealer_id;
    }

    /**
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return Trade
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
     * Set isSettled.
     *
     * @param int $isSettled
     *
     * @return Trade
     */
    public function setIsSettled($isSettled)
    {
        $this->is_settled = $isSettled;

        return $this;
    }

    /**
     * Get isSettled.
     *
     * @return int
     */
    public function getIsSettled()
    {
        return $this->is_settled;
    }

    /**
     * Set paymentParams.
     *
     * @param int $paymentParams
     *
     * @return Trade
     */
    public function setPaymentParams($paymentParams)
    {
        $this->payment_params = $paymentParams;

        return $this;
    }

    /**
     * Get paymentParams.
     *
     * @return int
     */
    public function getPaymentParams()
    {
        return $this->payment_params;
    }

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return Trade
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
     * Set bspayDivStatus.
     *
     * @param string|null $bspayDivStatus
     *
     * @return Trade
     */
    public function setBspayDivStatus($bspayDivStatus = null)
    {
        $this->bspay_div_status = $bspayDivStatus;

        return $this;
    }

    /**
     * Get bspayDivStatus.
     *
     * @return string|null
     */
    public function getBspayDivStatus()
    {
        return $this->bspay_div_status;
    }

    /**
     * Set bspayReqDate.
     *
     * @param string $bspayReqDate
     *
     * @return Trade
     */
    public function setBspayReqDate($bspayReqDate)
    {
        $this->bspay_req_date = $bspayReqDate;

        return $this;
    }

    /**
     * Get bspayReqDate.
     *
     * @return string
     */
    public function getBspayReqDate()
    {
        return $this->bspay_req_date;
    }

    /**
     * Set bspayDivMembers.
     *
     * @param string|null $bspayDivMembers
     *
     * @return Trade
     */
    public function setBspayDivMembers($bspayDivMembers = null)
    {
        $this->bspay_div_members = $bspayDivMembers;

        return $this;
    }

    /**
     * Get bspayDivMembers.
     *
     * @return string|null
     */
    public function getBspayDivMembers()
    {
        return $this->bspay_div_members;
    }

    /**
     * Set bspayFeeMode.
     *
     * @param string|null $bspayFeeMode
     *
     * @return Trade
     */
    public function setBspayFeeMode($bspayFeeMode = null)
    {
        $this->bspay_fee_mode = $bspayFeeMode;

        return $this;
    }

    /**
     * Get bspayFeeMode.
     *
     * @return string|null
     */
    public function getBspayFeeMode()
    {
        return $this->bspay_fee_mode;
    }

    /**
     * Set bspayFee.
     *
     * @param int|null $bspayFee
     *
     * @return Trade
     */
    public function setBspayFee($bspayFee = null)
    {
        $this->bspay_fee = $bspayFee;

        return $this;
    }

    /**
     * Get bspayFee.
     *
     * @return int|null
     */
    public function getBspayFee()
    {
        return $this->bspay_fee;
    }
}
