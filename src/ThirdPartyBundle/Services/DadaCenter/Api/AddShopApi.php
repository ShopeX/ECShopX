<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\DadaCenter\Api;

use ThirdPartyBundle\Services\DadaCenter\Config\UrlConfig;

class AddShopApi extends BaseApi
{
    // 1e236443e5a30b09910e0d48c994b8e6 core
    public function __construct($params)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        parent::__construct(UrlConfig::SHOP_ADD_URL, $params);
    }
}
