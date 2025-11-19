<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Api;

use ThirdPartyBundle\Services\Kuaizhen580Center\Config\UrlConfig;

/**
 * 4.5 获取问诊列表接口
 */
class DiagnosisList extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::DIAGNOSIS_LIST, $params);
    }
}
