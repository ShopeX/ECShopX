<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Sdk\Request;

use BsPayBundle\Sdk\Enums\FunctionCodeEnum;

/**
 * 用户信息查询
 *
 */
class V2UserBasicdataQueryRequest extends BaseRequest
{

    /**
     * 汇付客户Id
     */
    private $huifuId;
    /**
     * 请求流水号
     */
    private $reqSeqId;
    /**
     * 请求日期
     */
    private $reqDate;

    public function getFunctionCode() {
        return FunctionCodeEnum::$V2_USER_BASICDATA_QUERY;
    }


    public function getHuifuId() {
        return $this->huifuId;
    }

    public function setHuifuId($huifuId) {
        $this->huifuId = $huifuId;
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

}
