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

use SystemLinkBundle\Http\Controllers\Controller as Controller;

use OrdersBundle\Services\OrderAssociationService;

use OrdersBundle\Services\Orders\NormalOrderService;

use OrdersBundle\Traits\GetOrderServiceTrait;

//use OrdersBundle\Jobs\OrderDeliverySendMsg;

class Delivery extends Controller
{
    use GetOrderServiceTrait;

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/createDelivery",
     *     summary="OMS发货",
     *     tags={"omeapi"},
     *     description="OMS创建发货单",
     *     operationId="createDelivery",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="store.logistics.offline.send", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="tid", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Parameter( name="company_code", in="query", description="物流公司编码", required=true, type="string"),
     *     @SWG\Parameter( name="logistics_no", in="query", description="物流单号", required=true, type="string"),
     *     @SWG\Parameter( name="oid_list", in="query", description="拆单发货信息(json)", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="发货成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function createDelivery(Request $request)
    {
        // ShopEx EcShopX Core Module
        $params = $request->all();

        $rules = [
            'tid' => ['required', '订单号缺少！'],
            'company_code' => ['required', '缺少物流编码'],
            'logistics_no' => ['required', '缺少物流单号'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }

        $normalOrderService = new NormalOrderService();

        //2565607000050583
        $filter = ['order_id' => $params['tid']];

        $tradeInfo = $normalOrderService->getList($filter);

        if (!$tradeInfo) {
            $this->api_response('fail', '此订单不存在');
        }

        if ($tradeInfo[0]['delivery_status'] == 'DONE') {
            $this->api_response('fail', '订单已发货，请勿重复发货');
        }

        $orderAssociationService = new OrderAssociationService();

        $order = $orderAssociationService->getOrder($tradeInfo[0]['company_id'], $tradeInfo[0]['order_id']);

        if (!$order) {
            $this->api_response('fail', '获取订单信息失败');
        }

        //组装发货数据
        $delivery_type = 'batch';
        $sep_info = [];
        if (isset($params['oid_list']) && !empty($params['oid_list'])) {
            $delivery_type = 'sep';
            $oid_list = json_decode($params['oid_list'], true);
            if (json_last_error() != JSON_ERROR_NONE) {
                $this->api_response('fail', '拆单发货，oid_list数据格式错误');
            }
            if (!is_array($oid_list)) {
                $this->api_response('fail', '拆单发货，oid_list数据格式错误');
            }
            foreach ($oid_list as $_val) {
                $sep_info[] = [
                    'id' => $_val['oid'],
                    'delivery_num' => $_val['nums']
                ];
            }

            $sep_info = json_encode($sep_info);
        }

        $orderService = $this->getOrderServiceByOrderInfo($order);
        $deliveryData = [
            'order_id' => $tradeInfo[0]['order_id'],
            'delivery_type' => $delivery_type,
            'company_id' => $tradeInfo[0]['company_id'],
            'delivery_corp' => $params['company_code'],
            'delivery_code' => $params['logistics_no'],
            'sepInfo' => $sep_info,
            'type' => 'new'
        ];

        try {
            $result = $orderService->delivery($deliveryData);

            //小程序发货通知已经有了，这里不需要
            //发货成功 发送小程序消息通知
            //$gotoJob = (new OrderDeliverySendMsg($tradeInfo[0]['company_id'], $tradeInfo[0]['order_id']))->onQueue('slow');
            //app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        } catch (\Exception $e) {
            app('log')->debug('OME发货请求失败:'. $e->getMessage()."==>deliveryData==>".json_encode($deliveryData));
            $this->api_response('fail', $e->getMessage());
        }

        $this->api_response('true', '发货成功', $result);
    }

    /**
     * @SWG\Post(
     *     path="/systemlink/ome/returnDelivery",
     *     summary="OMS商家确认收货(暂时不用)",
     *     tags={"omeapi"},
     *     description="商家确认收货  暂时不需要此接口 写上只是为了防止报错",
     *     operationId="returnDelivery",
     *     @SWG\Parameter( name="method", in="query", description="接口方法名", default="store.logistics.offline.send", required=true, type="string"),
     *     @SWG\Parameter( name="sign", in="query", description="参数签名", required=true, type="string"),
     *     @SWG\Parameter( name="tid", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="rsp", type="string", example="succ", description="操作结果"),
     *          @SWG\Property( property="code", type="string", example="0", description="code"),
     *          @SWG\Property( property="err_msg", type="string", example="确认收货成功", description="提示信息"),
     *          @SWG\Property( property="data", type="string", example="null", description="返回数据"),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SystemLinkErrorResponse") ) )
     * )
     */
    public function returnDelivery(Request $request)
    {
        $params = $request->all();
        $this->api_response('true', '确认收货成功', $params);
    }
}
