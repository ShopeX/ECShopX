<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace HfPayBundle\Services\src\Kernel;

class Config
{
    // KEY: U2hvcEV4
    /**
     * @var string 接口地址
     */
    public $base_uri = '';

    //版本号
    public $version = 10;

    //商户客户号
    public $mer_cust_id = '';

    //证书密码
    public $pfx_password;
}
