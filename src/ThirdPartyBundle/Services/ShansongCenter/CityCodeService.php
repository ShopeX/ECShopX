<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\ShansongCenter;

use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Services\ShansongCenter\Api\OpenCitiesListsApi;
use ThirdPartyBundle\Services\ShansongCenter\Client\Request;

class CityCodeService
{
    /**
     * 获取城市列表信息
     * @param string $companyId 企业Id
     * @return mixed 城市列表
     */
    public function list($companyId)
    {
        $openCitiesListsApi = new OpenCitiesListsApi([]);
        $client = new Request($companyId, $openCitiesListsApi);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        // 返回字段mapping达达返回字段
        $cityList = [];
        foreach ($resp->result as $row) {
            $cityList = array_merge($cityList, $row['cities']);
        }
        $resp->result = $cityList;

        return $resp->result;
    }
}
