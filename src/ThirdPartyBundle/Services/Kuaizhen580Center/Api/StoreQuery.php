<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Api;

use ThirdPartyBundle\Services\Kuaizhen580Center\Config\UrlConfig;

/**
 * 4.9 查询门店信息接口
 */
class StoreQuery extends BaseApi
{
    public function __construct($params)
    {
        // 53686f704578
        parent::__construct(UrlConfig::STORE_QUERY, $params);
    }
}
