<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Api;

use ThirdPartyBundle\Services\Kuaizhen580Center\Config\UrlConfig;

/**
 * 4.6 获取问诊详情信息接口
 */
class CheckSign extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::CHECK_SIGN, $params);
    }
}
