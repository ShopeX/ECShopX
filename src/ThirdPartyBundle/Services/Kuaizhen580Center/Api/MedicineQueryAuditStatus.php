<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Api;

use ThirdPartyBundle\Services\Kuaizhen580Center\Config\UrlConfig;

/**
 * 4.8 查询药品审核信息接口
 */
class MedicineQueryAuditStatus extends BaseApi
{
    // ShopEx framework
    public function __construct($params)
    {
        // ShopEx framework
        parent::__construct(UrlConfig::MEDICINE_QUERYAUDITSTATUS, $params);
    }
}
