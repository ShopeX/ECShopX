<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\DadaCenter\Api;

use ThirdPartyBundle\Services\DadaCenter\Config\UrlConfig;

class ConfirmGoodsApi extends BaseApi
{
    public function __construct($params)
    {
        // Ver: 1e2364-fe10
        parent::__construct(UrlConfig::CONFIRM_GOODS, $params);
    }
}
