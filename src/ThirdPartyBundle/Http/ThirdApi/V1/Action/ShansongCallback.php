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

class ShansongCallback extends Controller
{
    /**
     * @SWG\Post(
     *     path="/shansong/callback",
     *     summary="闪送同城配状态回调",
     *     tags={"order"},
     *     description="闪送同城配状态回调",
     *     @SWG\Parameter( in="path", type="string", required=true, name="company_id", description="企业ID" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="issOrderNo", description="闪送订单号" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="orderNo", description="订单号" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="status", description="订单状态(20:派单中（转单改派中），30:待取货（已就位），40:闪送中（申请取消中、物品送回中、取消单客服介入中），50:已完成（已退款），60:已取消）" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="statusDesc", description="订单状态描述" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="subStatus", description="订单子状态" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="subStatusDesc", description="订单子状态描述" ),
     *     @SWG\Parameter( in="formData", type="string", required=true, name="deductAmount", description="扣款金额，单位：分" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="abortType", description="取消发起人（1:客户发起取消，3:闪送员发起取消，10:系统自动发起取消）" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="punishType", description="取消责任人（1:因客户，2:因服务，3:因闪送员，10:因系统自动取消，99:因其它）" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="abortReason", description="取消原因" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="courier", description="闪送员信息" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="sendBackFee", description="送回费，单位：分" ),
     *     @SWG\Parameter( in="formData", type="string", required=false, name="drawback", description="退款金额，单位：分" ),
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
        $params = $request->all('issOrderNo', 'orderNo', 'status', 'statusDesc', 'subStatus', 'subStatusDesc', 'deductAmount', 'abortType', 'punishType', 'abortReason', 'courier', 'sendBackFee', 'drawback');
        app('log')->info('shansong updateOrderStatus company_id===>'.var_export($company_id, 1));
        app('log')->info('shansong updateOrderStatus params===>'.var_export($params, 1));
        $rules = [
            'orderNo' => ['required', '缺少订单号'],
            'status' => ['required', '缺少订单状态'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            app('log')->info('shansongCallback request error msg:'.$error);
            $this->api_response('fail', '操作失败');
        }
        try {
            $localDeliveryService = new LocalDeliveryService();
            $result = $localDeliveryService->getOrderService()->callbackUpdateOrderStatus($company_id, $params);
        } catch (\Exception $e) {
            $msg = 'file:'.$e->getFile().',line:'.$e->getLine().',msg:'.$e->getMessage();
            app('log')->info('shansongCallback request error msg:'.$msg);
        }

        $this->api_response('true', '操作成功');
    }
}
