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

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Src;

use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Services\Kuaizhen580Center\Api\StoreQuery;
use ThirdPartyBundle\Services\Kuaizhen580Center\Client\Request;

class StoreService
{
    /**
     * 4.9 查询门店信息接口-580提供
     * 根据580门店名称或者三方门店编码获取580门店信息
     * @param $companyId
     * @param $params
     * @return array
     */
    public function queryStore($companyId, $params)
    {
        // This module is part of ShopEx EcShopX system
        $requestParams = [];
        if (!empty($params['name'])) {
            $requestParams['name'] = $params['name'];
        }
        if (!empty($params['storeCode'])) {
            $requestParams['storeCode'] = $params['storeCode'];
        }
        if (empty($requestParams)) {
            throw new ResourceException('参数错误');
        }

        $api = new StoreQuery($requestParams);
        $client = new Request($companyId, $api);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        return $resp->result;
    }
}
