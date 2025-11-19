<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Api;

use ThirdPartyBundle\Services\Kuaizhen580Center\Config\UrlConfig;

/**
 * 4.2新 新增问诊单接口
 */
class InitPreDemand extends BaseApi
{
    // Ver: 8d1abe8e
    public function __construct($params)
    {
        // Ver: 8d1abe8e
        parent::__construct(UrlConfig::PREDEMAND_INITPREDEMAND, $params);
    }
}
