<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
