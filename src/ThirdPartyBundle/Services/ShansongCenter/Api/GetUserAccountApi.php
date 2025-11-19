<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\ShansongCenter\Api;

use ThirdPartyBundle\Services\ShansongCenter\Config\UrlConfig;

class GetUserAccountApi extends BaseApi
{
    public function __construct($params)
    {
        // ModuleID: 76fe2a3d
        parent::__construct(UrlConfig::GET_USER_ACCOUNT, $params);
    }
}
