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

namespace DepositBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * DepositTrade 预存款交易记录
 *
 * @ORM\Table(name="deposit_trade", options={"comment":"预存款交易记录表"})
 * @ORM\Entity(repositoryClass="DepositBundle\Repositories\DepositTradeRepository")
 */
class DepositTrade
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="deposit_trade_id", type="string", length=64, options={"comment":"储值流水号"})
     */
    private $deposit_trade_id;

    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="string", options={"comment":"企业ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="member_card_code", type="string", options={"comment":"充值会员卡号"})
     */
    private $member_card_code;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_id", nullable=true, type="string", options={"comment":"门店ID"})
     */
    private $shop_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_name", nullable=true, type="string", options={"comment":"门店名称"})
     */
    private $shop_name;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", options={"comment":"购买用户"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", options={"comment":"购买用户手机号"})
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
     *
     * @ORM\Column(name="money", type="string", options={"comment":"充值金额/消费金额。 单位是分"})
     */
    private $money;

    /**
     * @var string
     *
     * @ORM\Column(name="trade_type", type="string", length=16, options={"comment":"交易类型充值或消费。consume:消费，recharge:充值"})
     */
    private $trade_type;

    /**
     * @var string
     *
     * @ORM\Column(name="trade_status", type="string", length=16, options={"comment":"交易状态"})
     */
    private $trade_status;

    /**
     * @var string
     *
     * @ORM\Column(name="transaction_id", type="string", nullable=true, options={"comment":"充值支付订单号"})
     */
    private $transaction_id;

    /**
     * @var string
     *
     * @ORM\Column(name="recharge_rule_id", type="string", nullable=true, options={"comment":"充值满足活动规则ID"})
     */
    private $recharge_rule_id;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_type", nullable=true, type="string", options={"comment":"付款银行"})
     */
    private $bank_type;

    /**
     * @var string
     *
     * @ORM\Column(name="authorizer_appid", nullable=true, type="string", length=64, options={"comment":"公众号的appid"})
     */
    private $authorizer_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_type", nullable=true, type="string", length=64, options={"comment":"充值支付方式"})
     */
    private $pay_type;

    /**
     * @var string
     *
     * @ORM\Column(name="wxa_appid", nullable=true, type="string", length=64, options={"comment":"支付小程序的appid"})
     */
    private $wxa_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="detail", type="string", options={"comment":"交易详情"})
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
     * @ORM\Column(name="fee_type", type="string", length=16, options={"comment":"货币类型", "default":"CNY"})
     */
    private $fee_type = 'CNY';

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
     * @var string
     *
     * @ORM\Column(name="cur_pay_fee", type="string", options={"comment":"系统货币支付金额"})
     */
    private $cur_pay_fee;

    /**
     * Set depositTradeId
     *
     * @param string $depositTradeId
     *
     * @return DepositTrade
     */
    public function setDepositTradeId($depositTradeId)
    {
        $this->deposit_trade_id = $depositTradeId;

        return $this;
    }

    /**
     * Get depositTradeId
     *
     * @return string
     */
    public function getDepositTradeId()
    {
        return $this->deposit_trade_id;
    }

    /**
     * Set companyId
     *
     * @param string $companyId
     *
     * @return DepositTrade
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
     * Set memberCardCode
     *
     * @param string $memberCardCode
     *
     * @return DepositTrade
     */
    public function setMemberCardCode($memberCardCode)
    {
        $this->member_card_code = $memberCardCode;

        return $this;
    }

    /**
     * Get memberCardCode
     *
     * @return string
     */
    public function getMemberCardCode()
    {
        return $this->member_card_code;
    }

    /**
     * Set shopId
     *
     * @param string $shopId
     *
     * @return DepositTrade
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
     * Set shopName
     *
     * @param string $shopName
     *
     * @return DepositTrade
     */
    public function setShopName($shopName)
    {
        $this->shop_name = $shopName;

        return $this;
    }

    /**
     * Get shopName
     *
     * @return string
     */
    public function getShopName()
    {
        return $this->shop_name;
    }

    /**
     * Set userId
     *
     * @param string $userId
     *
     * @return DepositTrade
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
     * @return DepositTrade
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
     * @return DepositTrade
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
     * Set money
     *
     * @param string $money
     *
     * @return DepositTrade
     */
    public function setMoney($money)
    {
        $this->money = $money;

        return $this;
    }

    /**
     * Get money
     *
     * @return string
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * Set tradeType
     *
     * @param string $tradeType
     *
     * @return DepositTrade
     */
    public function setTradeType($tradeType)
    {
        $this->trade_type = $tradeType;

        return $this;
    }

    /**
     * Get tradeType
     *
     * @return string
     */
    public function getTradeType()
    {
        return $this->trade_type;
    }

    /**
     * Set authorizerAppid
     *
     * @param string $authorizerAppid
     *
     * @return DepositTrade
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
     * @return DepositTrade
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
     * Set detail
     *
     * @param string $detail
     *
     * @return DepositTrade
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
     * @return DepositTrade
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
     * @return DepositTrade
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
     * Set tradeStatus
     *
     * @param string $tradeStatus
     *
     * @return DepositTrade
     */
    public function setTradeStatus($tradeStatus)
    {
        $this->trade_status = $tradeStatus;

        return $this;
    }

    /**
     * Get tradeStatus
     *
     * @return string
     */
    public function getTradeStatus()
    {
        return $this->trade_status;
    }

    /**
     * Set transactionId
     *
     * @param string $transactionId
     *
     * @return DepositTrade
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
     * Set bankType
     *
     * @param string $bankType
     *
     * @return DepositTrade
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
     * Set rechargeRuleId
     *
     * @param string $rechargeRuleId
     *
     * @return DepositTrade
     */
    public function setRechargeRuleId($rechargeRuleId)
    {
        $this->recharge_rule_id = $rechargeRuleId;

        return $this;
    }

    /**
     * Get rechargeRuleId
     *
     * @return string
     */
    public function getRechargeRuleId()
    {
        return $this->recharge_rule_id;
    }

    /**
     * Set payType
     *
     * @param string $payType
     *
     * @return DepositTrade
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
     * Set feeType
     *
     * @param string $feeType
     *
     * @return DepositTrade
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
     * Set curFeeType
     *
     * @param string $curFeeType
     *
     * @return DepositTrade
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
     * @return DepositTrade
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
     * @return DepositTrade
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
     * @return DepositTrade
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
}
