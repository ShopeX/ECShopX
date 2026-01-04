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

namespace ThirdPartyBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;
use ThirdPartyBundle\Http\Controllers\Controller as Controller;

use AftersalesBundle\Services\AftersalesService;

// use Dingo\Api\Exception\ResourceException;

use ThirdPartyBundle\Services\SaasErpCentre\OrderAftersalesService;

class Aftersales extends Controller
{
    /**
     * @SWG\Get(
     *     path="/aftersales/{aftersales_bn}",
     *     summary="获取售后单详情",
     *     tags={"aftersales"},
     *     description="获取售后单详情",
     *     operationId="getAftersalesDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="aftersales_bn",
     *         in="path",
     *         description="售后单号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     * )
     */
    public function getAftersalesDetail($aftersales_bn)
    {
        $companyId = app('auth')->user()->get('company_id');
        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->getAftersalesInfo($companyId, $aftersales_bn);

        return $this->response->array($result);
    }

    /**
     * SaasErp 更新售后申请单
     * 操作审核中 [status] => 4
     * 接收申请 [status] => 4
     * 拒绝申请 [status] => 2
     * 售后完成 [status] => 1
     */
    public function updateAftersalesStatus(Request $request)
    {
        $params = $request->all();
        //$companyId = $params['company_id'];
        app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.",params=>:".var_export($params, 1));

        $rules = [
            'aftersale_id' => ['required', '售后单号缺少！'],
            'order_id' => ['required', '订单号缺少！'],
            'status' => ['required', '售后状态缺少！'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $orderAftersalesService = new OrderAftersalesService();

        try {
            app('log')->debug("\nsaaserp 去更新售后状态 ".__FUNCTION__.",".__LINE__.", status=>".$params['status']);
            $result = $orderAftersalesService->aftersaleStatusUpdate($params, trim($params['status']));
            app('log')->debug("\nsaaserp updateAftersalesStatus_result=>:".var_export($result, 1));
        } catch (\Exception $e) {
            $errorMsg = "saaserp updateAftersalesStatus Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage()."\n";
            app('log')->debug("\n saaserp updateAftersalesStatus 请求失败:". $errorMsg);
            $this->api_response('fail', $e->getMessage());
        }

        $this->api_response('true', '操作成功', $result);
    }
}
