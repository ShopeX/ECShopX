<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\DadaCenter\Api;

use ThirdPartyBundle\Services\DadaCenter\Config\UrlConfig;

class UpdateShopApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::SHOP_UPDATE_URL, $params);
    }
}
