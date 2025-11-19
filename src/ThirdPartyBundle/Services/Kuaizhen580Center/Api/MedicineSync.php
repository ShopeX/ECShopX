<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Api;

use ThirdPartyBundle\Services\Kuaizhen580Center\Config\UrlConfig;

/**
 * 4.7 同步药品信息接口
 */
class MedicineSync extends BaseApi
{
    // IDX: 2367340174
    public function __construct($params)
    {
        // IDX: 2367340174
        parent::__construct(UrlConfig::MEDICINE_SYNC, $params);
    }
}
