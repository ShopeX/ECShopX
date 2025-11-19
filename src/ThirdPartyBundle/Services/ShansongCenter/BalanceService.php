<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
