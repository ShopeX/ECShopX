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

namespace SystemLinkBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;
// use App\Http\Controllers\Controller as Controller;
use SystemLinkBundle\Http\Controllers\Controller as Controller;

use AftersalesBundle\Services\AftersalesService;


use SystemLinkBundle\Services\ShopexErp\OrderAftersalesService;

use OrdersBundle\Services\Orders\NormalOrderService;

class Aftersales extends Controller
{
    public function getAftersalesDetail($aftersales_bn)
    {
        $companyId = app('auth')->user()->get('company_id');
        $aftersalesService = new AftersalesService();
        $result = $aftersalesService->getAftersalesInfo($companyId, $aftersales_bn);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/updateAftersalesStatus",
     *     summary="OMS更新售后单",
     *     tags={"omeapi"},
     *     description="OMS更新售后申请单",
     *     operationId="updateAftersalesStatus",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="store.trade.aftersale.status.update", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="aftersale_id", in="query", description="售后单号", required=true, type="integer"),
     *     @SWG\Parameter( name="tid", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="售后状态", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function updateAftersalesStatus(Request $request)
    {
        $params = $request->all();
        app('log')->debug('updateAftersalesStatus_params=>:'.var_export($params, 1));

        $rules = [
            'aftersale_id' => ['required', '售后单号缺少！'],
            'tid' => ['required', '订单号缺少！'],
            'status' => ['required', '售后状态缺少！'],
            // 'memo'        => ['required', '备注缺少！'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $orderAftersalesService = new OrderAftersalesService();

        try {
            $result = $orderAftersalesService->aftersaleStatusUpdate($params, trim($params['status']));
            app('log')->debug('updateAftersalesStatus_result=>:'.var_export($result, 1));
        } catch (\Exception $e) {
            $this->api_response('fail', $e->getMessage());
        }

        $this->api_response('true', '操作成功', $result);
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/omsAddAftersale",
     *     summary="OMS创建售后单",
     *     tags={"omeapi"},
     *     description="OMS创建售后单并回传bbc",
     *     operationId="omsAddAftersale",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="store.trade.aftersale.add", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="detail", in="query", description="售后商品明细(json)", required=true, type="string"),
     *     @SWG\Parameter( name="aftersales_type", in="query", description="售后类型", required=true, type="string"),
     *     @SWG\Parameter( name="reason", in="query", description="售后原因", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="操作成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function omsAddAftersale(Request $request)
    {
        $params = $request->all();
        app('log')->debug('omsAddAftersale_params=>:'.var_export($params, 1));
        $omsAuth = $request->get('oms_auth');
        $params['company_id'] = $omsAuth['company_id'];
        $validator = app('validator')->make($params, [
            'order_id' => 'required',
            'detail' => 'required',
            'aftersales_type' => 'required',
            'reason' => 'required',
        ], [
            'order_id.*' => '订单号必填,必须为整数',
            'detail.*' => '售后商品明细必填',
            'aftersales_type.*' => '售后类型必选',
            'reason.*' => '售后原因必选',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            $this->api_response('fail', trim($errmsg, '，'));
        }

        $filter = [
            'company_id' => $params['company_id'],
            'order_id' => $params['order_id'],
        ];
        $normalOrderService = new NormalOrderService();
        $orderInfo = $normalOrderService->normalOrdersRepository->getInfo($filter);
        if (!$orderInfo) {
            $this->api_response('fail', '未查询到订单');
        }
        $params['user_id'] = $orderInfo['user_id'];
        $params['detail'] = json_decode($params['detail'], 1);

        $orderAftersalesService = new OrderAftersalesService();
        $result = $orderAftersalesService->omsSendAftersalesCreate($params);
        if ($result) {
            $this->api_response('true', '操作成功', $result);
        } else {
            $this->api_response('fail', '操作失败');
        }
    }

    //oms回传售后单客户回寄物流信息
    public function omsSendBackDeliveryInfo(Request $request)
    {
        // Ver: 1e2364-fe10
        $params = $request->all();
        app('log')->debug('omsSendBackDeliveryInfo_params=>:'.var_export($params, 1));
        $omsAuth = $request->get('oms_auth');
        $companyId = $omsAuth['company_id'];
        $rules = [
            'order_bn' => ['required', '售后单号缺少！'],
            'deliverNo' => ['required', '物流号缺少！'],
            'company' => ['required', '物流公司缺少！'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $aftersalesService = new AftersalesService();
        $filter = [
            'company_id' => $companyId,
            'aftersales_bn' => $params['order_bn'],
        ];
        $aftersalesInfo = $aftersalesService->aftersalesRepository->get($filter);
        if (!$aftersalesInfo) {
            $this->api_response('fail', '售后单号不存在');
        }
        $data = [
            'company_id' => $companyId,
            'aftersales_bn' => $params['order_bn'],
            'user_id' => $aftersalesInfo['user_id'],
            'corp_code' => $params['company'],
            'logi_no' => $params['deliverNo'],
        ];

        $result = $aftersalesService->sendBack($data, true);
        $this->api_response('true', '操作成功', $result);
    }
}
