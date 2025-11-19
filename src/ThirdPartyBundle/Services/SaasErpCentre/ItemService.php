<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\SaasErpCentre;

use Dingo\Api\Exception\ResourceException;

use ThirdPartyBundle\Services\SaasErpCentre\Request as SaasErpRequest;
use DistributionBundle\Services\DistributorService;

class ItemService
{
    const API_METHOD = 'store.item.stock.query';

    /**
     * SaasErp 获取商品库存
     */
    public function getStock($companyId, $distributorId = 0, $bn = array())
    {
        //查询店铺对应的店铺号
        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ];
        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfoSimple($filter);
        $shopCode = $distributorInfo['shop_code'] ?? '';
        $params = [
            'warehouse' => $shopCode,
            'bn' => json_encode($bn),
        ];
        app('log')->debug("\n saaserp 查询商品库存" . var_export($params, true));

        $request = new SaasErpRequest($companyId);
        //$this->__setDemoParams($request, $params);
        if (!$params['warehouse']) {
            return false;//店铺号为空？
        }
        $return = $request->call(self::API_METHOD, $params);
        return $return['data']['data'] ?? false;
    }

    //测试用参数
    private function __setDemoParams(&$request, &$params)
    {
        $params['warehouse'] = 'glla';
        $params['bn'] = json_encode(['123456-220V', '123456-24V', '123ttttt']);
        $request->certSetting['cert_id'] = '1875581737';
        $request->certSetting['node_id'] = '1136170135';
        $request->erp_node_id = '1163196839';
        $request->token = '570248321eab229f0f85241a43b8645fa700af3e1534e7566f1047039ec9e256';
    }

}
