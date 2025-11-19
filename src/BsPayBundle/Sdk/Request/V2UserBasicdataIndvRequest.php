<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Sdk\Request;

use BsPayBundle\Sdk\Enums\FunctionCodeEnum;

/**
 * 个人用户基本信息开户
 *
 */
class V2UserBasicdataIndvRequest extends BaseRequest
{

    /**
     * 请求流水号
     */
    private $reqSeqId;
    /**
     * 请求日期
     */
    private $reqDate;
    /**
     * 个人姓名
     */
    private $name;
    /**
     * 个人证件类型
     */
    private $certType;
    /**
     * 个人证件号码
     */
    private $certNo;
    /**
     * 个人证件有效期类型
     */
    private $certValidityType;
    /**
     * 个人证件有效期开始日期
     */
    private $certBeginDate;
    /**
     * 手机号
     */
    private $mobileNo;

    public function getFunctionCode() {
        return FunctionCodeEnum::$V2_USER_BASICDATA_INDV;
    }


    public function getReqSeqId() {
        return $this->reqSeqId;
    }

    public function setReqSeqId($reqSeqId) {
        $this->reqSeqId = $reqSeqId;
    }

    public function getReqDate() {
        return $this->reqDate;
    }

    public function setReqDate($reqDate) {
        $this->reqDate = $reqDate;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getCertType() {
        return $this->certType;
    }

    public function setCertType($certType) {
        $this->certType = $certType;
    }

    public function getCertNo() {
        return $this->certNo;
    }

    public function setCertNo($certNo) {
        $this->certNo = $certNo;
    }

    public function getCertValidityType() {
        return $this->certValidityType;
    }

    public function setCertValidityType($certValidityType) {
        $this->certValidityType = $certValidityType;
    }

    public function getCertBeginDate() {
        return $this->certBeginDate;
    }

    public function setCertBeginDate($certBeginDate) {
        $this->certBeginDate = $certBeginDate;
    }

    public function getMobileNo() {
        return $this->mobileNo;
    }

    public function setMobileNo($mobileNo) {
        $this->mobileNo = $mobileNo;
    }

}
