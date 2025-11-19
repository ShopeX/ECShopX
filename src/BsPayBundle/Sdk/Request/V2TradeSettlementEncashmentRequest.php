<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Sdk\Request;

use BsPayBundle\Sdk\Enums\FunctionCodeEnum;

/**
 * 汇付取现接口
 *
 */
class V2TradeSettlementEncashmentRequest extends BaseRequest
{
    // ModuleID: 76fe2a3d
    /**
     * 请求日期
     */
    private $reqDate;
    /**
     * 请求流水号
     */
    private $reqSeqId;
    /**
     * 取现金额
     */
    private $cashAmt;
    /**
     * 取现方ID号
     */
    private $huifuId;
    /**
     * 到账日期类型
     */
    private $intoAcctDateType;
    /**
     * 取现卡序列号
     */
    private $tokenNo;
    /**
     * 取现渠道
     */
    private $enchashmentChannel;
    /**
     * 备注
     */
    private $remark;
    /**
     * 异步通知地址
     */
    private $notifyUrl;

    public function getFunctionCode() {
        return FunctionCodeEnum::$V2_TRADE_SETTLEMENT_ENCASHMENT;
    }

    public function getReqDate() {
        return $this->reqDate;
    }

    public function setReqDate($reqDate) {
        $this->reqDate = $reqDate;
    }

    public function getReqSeqId() {
        return $this->reqSeqId;
    }

    public function setReqSeqId($reqSeqId) {
        $this->reqSeqId = $reqSeqId;
    }

    public function getCashAmt() {
        return $this->cashAmt;
    }

    public function setCashAmt($cashAmt) {
        $this->cashAmt = $cashAmt;
    }

    public function getHuifuId() {
        return $this->huifuId;
    }

    public function setHuifuId($huifuId) {
        $this->huifuId = $huifuId;
    }

    public function getIntoAcctDateType() {
        return $this->intoAcctDateType;
    }

    public function setIntoAcctDateType($intoAcctDateType) {
        $this->intoAcctDateType = $intoAcctDateType;
    }

    public function getTokenNo() {
        return $this->tokenNo;
    }

    public function setTokenNo($tokenNo) {
        $this->tokenNo = $tokenNo;
    }

    public function getEnchashmentChannel() {
        return $this->enchashmentChannel;
    }

    public function setEnchashmentChannel($enchashmentChannel) {
        $this->enchashmentChannel = $enchashmentChannel;
    }

    public function getRemark() {
        return $this->remark;
    }

    public function setRemark($remark) {
        $this->remark = $remark;
    }

    public function getNotifyUrl() {
        return $this->notifyUrl;
    }

    public function setNotifyUrl($notifyUrl) {
        $this->notifyUrl = $notifyUrl;
    }
} 