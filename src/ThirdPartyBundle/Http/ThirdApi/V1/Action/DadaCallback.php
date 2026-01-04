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

use OrdersBundle\Services\LocalDeliveryService;

class DadaCallback extends Controller
{
    /**
     * @SWG\Post(
     *     path="/dada/callback",
     *     summary="达达同城配状态回调",
     *     tags={"order"},
     *     description="达达同城配状态回调",
     *     @SWG\Parameter( in="path", type="string", required=true, name="company_id", description="企业ID" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="client_id", description="返回达达运单号，默认为空" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="order_id", description="订单号" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="order_status", description="订单状态(待接单＝1,待取货＝2,配送中＝3,已完成＝4,已取消＝5, 指派单=8,妥投异常之物品返回中=9, 妥投异常之物品返回完成=10, 骑士到店=100,创建达达运单失败=1000 可参考文末的状态说明）" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="cancel_reason", description="订单取消原因,其他状态下默认值为空字符串" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="cancel_from", description="订单取消原因来源(1:达达配送员取消；2:商家主动取消；3:系统或客服取消；0:默认值)" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="update_time", description="更新时间，时间戳除了创建达达运单失败=1000的精确毫秒，其他时间戳精确到秒" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="signature", description="对client_id, order_id, update_time的值进行字符串升序排列，再连接字符串，取md5值" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="dm_id", description="达达配送员id，接单以后会传" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="dm_name", description="配送员姓名，接单以后会传" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="dm_mobile", description="配送员手机号，接单以后会传" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="finish_code", description="收货码" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="result", type="string", example="success", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="msg", type="string", example="修改成功", description="提示信息"),
     *          @SWG\Property( property="info", type="object",
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function updateOrderStatus($company_id, Request $request)
    {
        $params = $request->all('client_id', 'order_id', 'order_status', 'cancel_reason', 'cancel_from', 'update_time', 'dm_id', 'dm_name', 'dm_mobile');
        app('log')->info('dada updateOrderStatus company_id===>'.var_export($company_id, 1));
        app('log')->info('dada updateOrderStatus params===>'.var_export($params, 1));
        if ($request->input('messageBody', false)) {
            return response()->json(['status' => 'ok']);
        }
        $rules = [
            'order_id' => ['required', '缺少订单号'],
            'order_status' => ['required', '缺少订单状态'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            app('log')->info('dadaCallback request error msg:'.$error);
            return response()->json(['status' => 'fail']);
        }
        try {
            $localDeliveryService = new LocalDeliveryService();
            $result = $localDeliveryService->getOrderService()->callbackUpdateOrderStatus($company_id, $params);
        } catch (\Exception $e) {
            $msg = 'file:'.$e->getFile().',line:'.$e->getLine().',msg:'.$e->getMessage();
            app('log')->info('dadaCallback request error msg:'.$msg);
        }

        return response()->json(['status' => 'ok']);
    }
}
