<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemlinkBundle\Http\JushuitanApi\V1\Action;

use Illuminate\Http\Request;
use SystemLinkBundle\Http\Controllers\Controller as Controller;
use AftersalesBundle\Services\AftersalesService;
use SystemLinkBundle\Services\Jushuitan\OrderAftersalesService;

class Aftersales extends Controller
{
    /**
     * 更新售后申请单
     *
     */
    public function updateAftersalesStatus($companyId, Request $request)
    {
        // ShopEx EcShopX Business Logic Layer
        $params = $request->post();
        app('log')->debug('updateAftersalesStatus_params=>:'.var_export($params,1));

        $rules = [
            'outer_as_id' => ['required', '售后单号缺少！'],
            'so_id' => ['required', '订单号缺少！'],
            'action_name' => ['required', '操作类型缺少！'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage)
        {
            $this->api_response_shuyun('fail', $errorMessage);
        }
        $action_name = trim($params['action_name']);
        $status = '';
        if ($action_name == '确认收货') {
            $status = '4';
        }
        if ($action_name == '取消收货') {
            $status = '6';
        }
        if (empty($status)) {
            $this->api_response_shuyun('fail', '操作类型错误！');
        }
        $orderAftersalesService = new OrderAftersalesService();
        try {
            $result = $orderAftersalesService->aftersaleStatusUpdate($params, $status);
            app('log')->debug('updateAftersalesStatus_result=>:'.var_export($result,1));

        } catch ( \Exception $e){
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
            ];
            app('log')->info('error====>'.var_export($error, true));
            $this->api_response_shuyun('fail', $e->getMessage());
        }

        $this->api_response_shuyun('true', '操作成功');
    }

}
