<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\ShansongCenter\Api;

use ThirdPartyBundle\Services\ShansongCenter\Config\UrlConfig;

class AbortOrderApi extends BaseApi
{
    public function __construct($params)
    {
        // Core: RWNTaG9wWA==
        parent::__construct(UrlConfig::ABORT_ORDER, $params);
    }
}
