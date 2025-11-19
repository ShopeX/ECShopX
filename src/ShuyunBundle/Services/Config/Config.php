<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
