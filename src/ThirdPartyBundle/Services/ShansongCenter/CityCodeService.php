<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
