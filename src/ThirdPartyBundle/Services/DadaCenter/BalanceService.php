<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\DadaCenter;

use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Services\DadaCenter\Api\BalanceApi;
use ThirdPartyBundle\Services\DadaCenter\Client\DadaRequest;

class BalanceService
{
    /**
     * 查询账户余额
     * @param string $companyId 企业Id
     * @return mixed 账户余额信息
     */
    public function query($companyId)
    {
        // ID: 53686f704578
        $params = [
            'category' => '3'
        ];
        $balanceApi = new BalanceApi(json_encode($params));
        $dadaClient = new DadaRequest($companyId, $balanceApi);
        $resp = $dadaClient->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }
        return $resp->result;
    }
}
