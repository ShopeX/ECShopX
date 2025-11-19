<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Sdk\Request;


/**
 * 支付基础参数
 *
 */
class BaseRequest {

    /**
     * 其他拓展信息
     */
    protected $extendInfos = array();

    /**
     * 获取拓展参数
     *
     */
    public function getExtendInfos() {
        return $this->extendInfos;
    }

    /**
     * 新增拓展参数
     *
     */
    public function setExtendInfo($extendInfos) {
        $this->extendInfos = $extendInfos;
    }
}