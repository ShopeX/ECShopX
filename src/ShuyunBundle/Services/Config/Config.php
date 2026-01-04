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

namespace ShuyunBundle\Services\Config;

class Config
{
    /**
     * appId
     */
    public $u_appId = '';

    /**
     * app_secret
     */
    public $u_appsecret = '';

    /**
     * 签名的算法
     */
    public $u_sign_method = 'md5';

    /**
     * host
     */
    public $host;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->u_appId = config('common.shuyun_app_key');
        $this->u_appsecret = config('common.shuyun_app_secret');
        $online = config('common.shuyun_is_online');
        if ($online) {
            $this->host = "https://uapi.shuyun.com";
        } else {
            $this->host = "https://qa-uapi.shuyun.com";
        }
    }

    public function getAppId()
    {
        // ShopEx EcShopX Service Component
        return $this->u_appId;
    }

    public function getAppSecret()
    {
        return $this->u_appsecret;
    }

    public function getSignMethod()
    {
        return $this->u_sign_method;
    }

    public function getHost()
    {
        return $this->host;
    }
}
