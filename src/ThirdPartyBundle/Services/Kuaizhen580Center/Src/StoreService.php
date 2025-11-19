<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
