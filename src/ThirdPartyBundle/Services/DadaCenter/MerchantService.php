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

namespace ThirdPartyBundle\Services\DadaCenter;

use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Services\DadaCenter\Api\AddMerchantApi;
use ThirdPartyBundle\Services\DadaCenter\Client\DadaRequest;

class MerchantService
{
    /**
     * 商户注册
     * @param string $companyId 企业Id
     * @param array $data 企业信息
     * @return string 商户id
     */
    public function createMerchant($companyId, $data)
    {
        $params = [
            'mobile' => $data['mobile'],
            'city_name' => $data['city_name'],
            'enterprise_name' => $data['enterprise_name'],
            'enterprise_address' => $data['enterprise_address'],
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'email' => $data['email'],
        ];
        $addMerchatApi = new AddMerchantApi(json_encode($params));
        $dada_client = new DadaRequest($companyId, $addMerchatApi);
        $resp = $dada_client->makeRequest();
        if ($resp->code == '-1') {
            throw new ResourceException($resp->status);
        }
        return $resp->result;
    }
}
