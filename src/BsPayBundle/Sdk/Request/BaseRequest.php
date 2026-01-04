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