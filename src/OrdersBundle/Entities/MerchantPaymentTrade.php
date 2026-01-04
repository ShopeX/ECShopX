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
 * MerchantPaymentTrade 企业付款账单
 *
 * @ORM\Table(name="orders_merchant_trade", options={"comment":"企业付款账单"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\MerchantPaymentTradeRepositories")
 */
class MerchantPaymentTrade
{
    /**
     * @var string
     *
     * @ORM\Column(name="merchant_trade_id", type="string", length=32, options={"comment":"商家支付交易单号"})
     * @ORM\Id
     */
    private $merchant_trade_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     *
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="rel_scene_id", nullable=true, type="string", options={"comment":"关联支付场景ID"})
     */
    private $rel_scene_id;

    /**
     * @var string
     *
     * @ORM\Column(name="rel_scene_name", nullable=true, type="string", options={"comment":"关联支付场景名称"})
     */
    private $rel_scene_name;

    /**
     * @var string
     *
     * @ORM\Column(name="mch_appid", nullable=true, type="string", length=32, options={"comment":"商户APPId"})
     *
     */
    private $mch_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="mchid", type="string", nullable=true, length=32, options={"comment":"商户号"})
     *
     */
    private $mchid;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_action", type="string", length=32, options={"comment":"商户支付方式"})
     *
     */
    private $payment_action = "WECHAT";
    /**
     * @var string
     *
     * NO_CHECK：不校验真实姓名
     * FORCE_CHECK：强校验真实姓名
     *
     * @ORM\Column(name="check_name", type="string", length=11, options={"comment":"是否强验用户姓名"})
     *
     */
    private $check_name = 'NO_CHECK';

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", nullable=true, type="string", length=32, options={"comment":"支付手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="re_user_name", nullable=true, type="string", options={"comment":"收款用户姓名"})
     *
     */
    private $re_user_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     *
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="open_id", nullable=true, type="string", options={"comment":"用户openId"})
     *
     */
    private $open_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="bigint", options={"comment":"付款金额(分)"})
     *
     */
    private $amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="payment_desc", type="string", options={"comment":"付款备注"})
     *
     */
    private $payment_desc;

    /**
     * @var integer
     *
     * @ORM\Column(name="spbill_create_ip", type="string", options={"comment":"ip地址"})
     *
     */
    private $spbill_create_ip;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="string", options={"comment":"支付状态。可选值有 NOT_PAY:未付款;PAYING:付款中;SUCCESS:付款成功;FAIL:付款失败"})
     *
     */
    private $status = "NOT_PAY";

    /**
     * @var integer
     *
     * @ORM\Column(name="payment_no", nullable=true, type="string", length=64, options={"comment":"微信支付订单号"})
     *
     */
    private $payment_no;

    /**
     * @var integer
     *
     * @ORM\Column(name="payment_time", nullable=true, type="string", options={"comment":"微信支付成功时间"})
     *
     */
    private $payment_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="error_code", type="string", nullable=true, options={"comment":"支付错误code码"})
     *
     */
    private $error_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="error_desc", type="string", nullable=true, options={"comment":"支付错误描述"})
     *
     */
    private $error_desc;

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
     * @var string
     *
     * @ORM\Column(name="hf_order_id", type="string", nullable=true, options={"comment":"汇付请求订单号"})
     */
    private $hf_order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_order_date", type="string", nullable=true, options={"comment":"汇付请求日期"})
     */
    private $hf_order_date;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_cash_type", type="string", nullable=true, options={"comment":"汇付取现方式 T0：T0取现; T1：T1取现 D1：D1取现"})
     */
    private $hf_cash_type;

    /**
     * @var string
     *
     * @ORM\Column(name="user_cust_id", type="string", nullable=true, options={"comment":"汇付商户客户号"})
     */
    private $user_cust_id;

    /**
     * @var string
     *
     * @ORM\Column(name="bind_card_id", type="string", nullable=true, options={"comment":"汇付取现银行卡id"})
     */
    private $bind_card_id;


    /**
     * Set merchantTradeId
     *
     * @param string $merchantTradeId
     *
     * @return MerchantPaymentTrade
     */
    public function setMerchantTradeId($merchantTradeId)
    {
        $this->merchant_trade_id = $merchantTradeId;

        return $this;
    }

    /**
     * Get merchantTradeId
     *
     * @return string
     */
    public function getMerchantTradeId()
    {
        return $this->merchant_trade_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MerchantPaymentTrade
     */
    public function setCompanyId($companyId)
    {
        // ShopEx EcShopX Service Component
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
     * Set relSceneId
     *
     * @param string $relSceneId
     *
     * @return MerchantPaymentTrade
     */
    public function setRelSceneId($relSceneId)
    {
        $this->rel_scene_id = $relSceneId;

        return $this;
    }

    /**
     * Get relSceneId
     *
     * @return string
     */
    public function getRelSceneId()
    {
        return $this->rel_scene_id;
    }

    /**
     * Set relSceneName
     *
     * @param string $relSceneName
     *
     * @return MerchantPaymentTrade
     */
    public function setRelSceneName($relSceneName)
    {
        $this->rel_scene_name = $relSceneName;

        return $this;
    }

    /**
     * Get relSceneName
     *
     * @return string
     */
    public function getRelSceneName()
    {
        return $this->rel_scene_name;
    }

    /**
     * Set mchAppid
     *
     * @param string $mchAppid
     *
     * @return MerchantPaymentTrade
     */
    public function setMchAppid($mchAppid)
    {
        $this->mch_appid = $mchAppid;

        return $this;
    }

    /**
     * Get mchAppid
     *
     * @return string
     */
    public function getMchAppid()
    {
        return $this->mch_appid;
    }

    /**
     * Set mchid
     *
     * @param string $mchid
     *
     * @return MerchantPaymentTrade
     */
    public function setMchid($mchid)
    {
        $this->mchid = $mchid;

        return $this;
    }

    /**
     * Get mchid
     *
     * @return string
     */
    public function getMchid()
    {
        return $this->mchid;
    }

    /**
     * Set paymentAction
     *
     * @param string $paymentAction
     *
     * @return MerchantPaymentTrade
     */
    public function setPaymentAction($paymentAction)
    {
        $this->payment_action = $paymentAction;

        return $this;
    }

    /**
     * Get paymentAction
     *
     * @return string
     */
    public function getPaymentAction()
    {
        return $this->payment_action;
    }

    /**
     * Set checkName
     *
     * @param string $checkName
     *
     * @return MerchantPaymentTrade
     */
    public function setCheckName($checkName)
    {
        $this->check_name = $checkName;

        return $this;
    }

    /**
     * Get checkName
     *
     * @return string
     */
    public function getCheckName()
    {
        return $this->check_name;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return MerchantPaymentTrade
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
     * Set reUserName
     *
     * @param string $reUserName
     *
     * @return MerchantPaymentTrade
     */
    public function setReUserName($reUserName)
    {
        $this->re_user_name = $reUserName;

        return $this;
    }

    /**
     * Get reUserName
     *
     * @return string
     */
    public function getReUserName()
    {
        return $this->re_user_name;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return MerchantPaymentTrade
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
     * Set openId
     *
     * @param string $openId
     *
     * @return MerchantPaymentTrade
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
     * Set amount
     *
     * @param integer $amount
     *
     * @return MerchantPaymentTrade
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set paymentDesc
     *
     * @param string $paymentDesc
     *
     * @return MerchantPaymentTrade
     */
    public function setPaymentDesc($paymentDesc)
    {
        $this->payment_desc = $paymentDesc;

        return $this;
    }

    /**
     * Get paymentDesc
     *
     * @return string
     */
    public function getPaymentDesc()
    {
        return $this->payment_desc;
    }

    /**
     * Set spbillCreateIp
     *
     * @param string $spbillCreateIp
     *
     * @return MerchantPaymentTrade
     */
    public function setSpbillCreateIp($spbillCreateIp)
    {
        $this->spbill_create_ip = $spbillCreateIp;

        return $this;
    }

    /**
     * Get spbillCreateIp
     *
     * @return string
     */
    public function getSpbillCreateIp()
    {
        return $this->spbill_create_ip;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return MerchantPaymentTrade
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set paymentNo
     *
     * @param string $paymentNo
     *
     * @return MerchantPaymentTrade
     */
    public function setPaymentNo($paymentNo)
    {
        $this->payment_no = $paymentNo;

        return $this;
    }

    /**
     * Get paymentNo
     *
     * @return string
     */
    public function getPaymentNo()
    {
        return $this->payment_no;
    }

    /**
     * Set paymentTime
     *
     * @param string $paymentTime
     *
     * @return MerchantPaymentTrade
     */
    public function setPaymentTime($paymentTime)
    {
        $this->payment_time = $paymentTime;

        return $this;
    }

    /**
     * Get paymentTime
     *
     * @return string
     */
    public function getPaymentTime()
    {
        return $this->payment_time;
    }

    /**
     * Set errorCode
     *
     * @param string $errorCode
     *
     * @return MerchantPaymentTrade
     */
    public function setErrorCode($errorCode)
    {
        $this->error_code = $errorCode;

        return $this;
    }

    /**
     * Get errorCode
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * Set errorDesc
     *
     * @param string $errorDesc
     *
     * @return MerchantPaymentTrade
     */
    public function setErrorDesc($errorDesc)
    {
        $this->error_desc = $errorDesc;

        return $this;
    }

    /**
     * Get errorDesc
     *
     * @return string
     */
    public function getErrorDesc()
    {
        return $this->error_desc;
    }

    /**
     * Set createTime
     *
     * @param integer $createTime
     *
     * @return MerchantPaymentTrade
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
     * @return MerchantPaymentTrade
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
     * @return MerchantPaymentTrade
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
     * @return MerchantPaymentTrade
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
     * @return MerchantPaymentTrade
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
     * @return MerchantPaymentTrade
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
     * @return MerchantPaymentTrade
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
     * Set hfOrderId.
     *
     * @param string|null $hfOrderId
     *
     * @return MerchantPaymentTrade
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
     * Set hfOrderDate.
     *
     * @param string|null $hfOrderDate
     *
     * @return MerchantPaymentTrade
     */
    public function setHfOrderDate($hfOrderDate = null)
    {
        $this->hf_order_date = $hfOrderDate;

        return $this;
    }

    /**
     * Get hfOrderDate.
     *
     * @return string|null
     */
    public function getHfOrderDate()
    {
        return $this->hf_order_date;
    }

    /**
     * Set hfCashType.
     *
     * @param string|null $hfCashType
     *
     * @return MerchantPaymentTrade
     */
    public function setHfCashType($hfCashType = null)
    {
        $this->hf_cash_type = $hfCashType;

        return $this;
    }

    /**
     * Get hfCashType.
     *
     * @return string|null
     */
    public function getHfCashType()
    {
        return $this->hf_cash_type;
    }

    /**
     * Set userCustId.
     *
     * @param string|null $userCustId
     *
     * @return MerchantPaymentTrade
     */
    public function setUserCustId($userCustId = null)
    {
        $this->user_cust_id = $userCustId;

        return $this;
    }

    /**
     * Get userCustId.
     *
     * @return string|null
     */
    public function getUserCustId()
    {
        return $this->user_cust_id;
    }

    /**
     * Set bindCardId.
     *
     * @param string|null $bindCardId
     *
     * @return MerchantPaymentTrade
     */
    public function setBindCardId($bindCardId = null)
    {
        $this->bind_card_id = $bindCardId;

        return $this;
    }

    /**
     * Get bindCardId.
     *
     * @return string|null
     */
    public function getBindCardId()
    {
        return $this->bind_card_id;
    }
}
