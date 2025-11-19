<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\ShansongCenter\Api;

use ThirdPartyBundle\Services\ShansongCenter\Config\UrlConfig;

class OpenCitiesListsApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::OPEN_CITIES_LISTS, $params);
    }
}
