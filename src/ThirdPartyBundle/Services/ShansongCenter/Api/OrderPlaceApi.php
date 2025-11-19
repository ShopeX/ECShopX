<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\ShansongCenter\Api;

use ThirdPartyBundle\Services\ShansongCenter\Config\UrlConfig;

class OrderPlaceApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::ORDER_PLACE, $params);
    }
}
