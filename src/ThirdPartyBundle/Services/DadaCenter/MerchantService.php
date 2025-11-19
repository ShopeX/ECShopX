<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
