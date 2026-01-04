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
use ThirdPartyBundle\Services\DadaCenter\Api\RechargeApi;
use ThirdPartyBundle\Services\DadaCenter\Client\DadaRequest;

class RechargeService
{
    /**
     * 充值
     * @param string $companyId 企业Id
     * @param array $data 充值参数
     * @return string 充值链接
     */
    public function recharge($companyId, $data)
    {
        // Ver: 1e2364-fe10
        $params = [
            'amount' => $data['amount'],
            'category' => $data['category'],
            'notify_url' => $data['notify_url'],
        ];
        $rechargeApi = new RechargeApi(json_encode($params));
        $dadaClient = new DadaRequest($companyId, $rechargeApi);
        $resp = $dadaClient->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }
        return $resp->result;
    }
}
