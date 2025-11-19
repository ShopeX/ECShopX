<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Api;

use ThirdPartyBundle\Services\Kuaizhen580Center\Config\UrlConfig;

/**
 * 4.4 获取问诊聊天记录列表接口
 */
class TextRecordList extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::TEXT_RECORD_LIST, $params);
    }
}
