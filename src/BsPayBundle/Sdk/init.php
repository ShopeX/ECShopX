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

namespace BsPayBundle\Sdk;

// ini_set('date.timezone', 'Asia/Shanghai');
if (!defined("SDK_BASE")) {
    define("SDK_BASE", dirname(__FILE__));
}

# sdk 版本号
if (!defined("SDK_VERSION")) {
    define("SDK_VERSION", "php#v2.0.9");
}

# api 接口版本号
if (!defined("API_VERSION")) {
    define("API_VERSION", "2.0.0");
}

# 设置是否调试模式
if (!defined("DEBUG")) {
    define("DEBUG", false);
}

# 设置调试日志路径
if (!defined("LOG")) {
    define("LOG", dirname(SDK_BASE) . "/log");
}

# 设置生产模式
if (!defined("PROD_MODE")) {
    define("PROD_MODE", true);
}

# 基础 Core 类
require_once SDK_BASE . "/Config/MerConfig.php";
require_once SDK_BASE . "/Core/BsPayRequestV2.php";
require_once SDK_BASE . "/Core/BsPayTools.php";
require_once SDK_BASE . "/Core/BsPay.php";
require_once SDK_BASE . "/Core/BsPayClient.php";
require_once SDK_BASE . "/Request/BaseRequest.php";
require_once SDK_BASE . "/Enums/FunctionCodeEnum.php";


