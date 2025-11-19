<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\DadaCenter\Api;

use ThirdPartyBundle\Services\DadaCenter\Config\UrlConfig;

class CityCodeApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::CITY_ORDER_URL, $params);
    }
}
