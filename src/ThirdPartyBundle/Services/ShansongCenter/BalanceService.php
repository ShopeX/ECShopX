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
use ThirdPartyBundle\Services\ShansongCenter\Api\GetUserAccountApi;
use ThirdPartyBundle\Services\ShansongCenter\Client\Request;

class BalanceService
{
    /**
     * 查询账户余额
     * @param string $companyId 企业Id
     * @return mixed 账户余额信息
     */
    public function query($companyId)
    {
        $getUserAccountApi = new GetUserAccountApi([]);
        $client = new Request($companyId, $getUserAccountApi);
        $resp = $client->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }

        // 返回字段mapping达达返回字段
        $resp->result['deliverBalance'] = bcdiv($resp->result['balance'], 100, 2);

        return $resp->result;
    }
}
