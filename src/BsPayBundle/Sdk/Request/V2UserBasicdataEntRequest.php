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

namespace BsPayBundle\Sdk\Request;

use BsPayBundle\Sdk\Enums\FunctionCodeEnum;

/**
 * 企业用户基本信息开户
 *
 */
class V2UserBasicdataEntRequest extends BaseRequest
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
     * 企业用户名称
     */
    private $regName;
    /**
     * 营业执照编号
     */
    private $licenseCode;
    /**
     * 证照有效期类型
     */
    private $licenseValidityType;
    /**
     * 证照有效期起始日期
     */
    private $licenseBeginDate;
    /**
     * 证照有效期结束日期日期格式：yyyyMMdd; 非长期有效时必填；&lt;font color&#x3D;&quot;green&quot;&gt;示例值：20320905&lt;/font&gt;
     */
    private $licenseEndDate;
    /**
     * 注册地址(省)
     */
    private $regProvId;
    /**
     * 注册地址(市)
     */
    private $regAreaId;
    /**
     * 注册地址(区)
     */
    private $regDistrictId;
    /**
     * 注册地址(详细信息)
     */
    private $regDetail;
    /**
     * 法人姓名
     */
    private $legalName;
    /**
     * 法人证件类型
     */
    private $legalCertType;
    /**
     * 法人证件号码
     */
    private $legalCertNo;
    /**
     * 法人证件有效期类型
     */
    private $legalCertValidityType;
    /**
     * 法人证件有效期开始日期
     */
    private $legalCertBeginDate;
    /**
     * 法人证件有效期截止日期日期格式：yyyyMMdd; 非长期有效时必填，长期有效为空；&lt;font color&#x3D;&quot;green&quot;&gt;示例值：20320905&lt;/font&gt;
     */
    private $legalCertEndDate;
    /**
     * 联系人姓名
     */
    private $contactName;
    /**
     * 联系人手机号
     */
    private $contactMobile;

    public function getFunctionCode() {
        return FunctionCodeEnum::$V2_USER_BASICDATA_ENT;
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

    public function getRegName() {
        return $this->regName;
    }

    public function setRegName($regName) {
        $this->regName = $regName;
    }

    public function getLicenseCode() {
        return $this->licenseCode;
    }

    public function setLicenseCode($licenseCode) {
        $this->licenseCode = $licenseCode;
    }

    public function getLicenseValidityType() {
        return $this->licenseValidityType;
    }

    public function setLicenseValidityType($licenseValidityType) {
        $this->licenseValidityType = $licenseValidityType;
    }

    public function getLicenseBeginDate() {
        return $this->licenseBeginDate;
    }

    public function setLicenseBeginDate($licenseBeginDate) {
        $this->licenseBeginDate = $licenseBeginDate;
    }

    public function getLicenseEndDate() {
        return $this->licenseEndDate;
    }

    public function setLicenseEndDate($licenseEndDate) {
        $this->licenseEndDate = $licenseEndDate;
    }

    public function getRegProvId() {
        return $this->regProvId;
    }

    public function setRegProvId($regProvId) {
        $this->regProvId = $regProvId;
    }

    public function getRegAreaId() {
        return $this->regAreaId;
    }

    public function setRegAreaId($regAreaId) {
        $this->regAreaId = $regAreaId;
    }

    public function getRegDistrictId() {
        return $this->regDistrictId;
    }

    public function setRegDistrictId($regDistrictId) {
        $this->regDistrictId = $regDistrictId;
    }

    public function getRegDetail() {
        return $this->regDetail;
    }

    public function setRegDetail($regDetail) {
        $this->regDetail = $regDetail;
    }

    public function getLegalName() {
        return $this->legalName;
    }

    public function setLegalName($legalName) {
        $this->legalName = $legalName;
    }

    public function getLegalCertType() {
        return $this->legalCertType;
    }

    public function setLegalCertType($legalCertType) {
        $this->legalCertType = $legalCertType;
    }

    public function getLegalCertNo() {
        return $this->legalCertNo;
    }

    public function setLegalCertNo($legalCertNo) {
        $this->legalCertNo = $legalCertNo;
    }

    public function getLegalCertValidityType() {
        return $this->legalCertValidityType;
    }

    public function setLegalCertValidityType($legalCertValidityType) {
        $this->legalCertValidityType = $legalCertValidityType;
    }

    public function getLegalCertBeginDate() {
        return $this->legalCertBeginDate;
    }

    public function setLegalCertBeginDate($legalCertBeginDate) {
        $this->legalCertBeginDate = $legalCertBeginDate;
    }

    public function getLegalCertEndDate() {
        return $this->legalCertEndDate;
    }

    public function setLegalCertEndDate($legalCertEndDate) {
        $this->legalCertEndDate = $legalCertEndDate;
    }

    public function getContactName() {
        return $this->contactName;
    }

    public function setContactName($contactName) {
        $this->contactName = $contactName;
    }

    public function getContactMobile() {
        return $this->contactMobile;
    }

    public function setContactMobile($contactMobile) {
        $this->contactMobile = $contactMobile;
    }

}
