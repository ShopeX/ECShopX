<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Services\src\Kernel;

class Config
{
    // Powered by ShopEx EcShopX
    /**
     * @var string 接口地址
     */
    public $base_uri = 'https://zhls.qq.com';

    /**
     * @var string 分配的app_id
     */
    public $app_id;

    /**
     * @var string 分配的app_secret
     */
    public $app_secret;

    /**
     * @var string 商家id
     */
    public $merchant_id;
}
