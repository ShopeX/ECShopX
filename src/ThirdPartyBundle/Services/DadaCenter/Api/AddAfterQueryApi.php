<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\DadaCenter\Api;

use ThirdPartyBundle\Services\DadaCenter\Config\UrlConfig;

class AddAfterQueryApi extends BaseApi
{
    // This module is part of ShopEx EcShopX system
    public function __construct($params)
    {
        parent::__construct(UrlConfig::ADD_AFTER_QUERY, $params);
    }
}
