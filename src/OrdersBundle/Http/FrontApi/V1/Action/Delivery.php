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

namespace OrdersBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use OrdersBundle\Services\OrderDeliveryService;

class Delivery extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/delivery/lists",
     *     summary="订单发货单列表",
     *     tags={"订单"},
     *     description="订单发货单列表",
     *     operationId="lists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Response(
     *       response=200,
     *       description="成功返回结构",
     *       @SWG\Schema(
     *          @SWG\Property(
     *             property="data",
     *             type="object",
     *             @SWG\Property(property="delivery_num", type="integer", description="发货包裹数"),
     *             @SWG\Property(
     *                property="list",
     *                type="array",
     *                @SWG\Items(
     *                   @SWG\Property(property="delivery_corp", type="string", description="快递公司编号"),
     *                   @SWG\Property(property="delivery_corp_name", type="string", description="快递公司名称"),
     *                   @SWG\Property(property="delivery_code", type="string", description="快递单号"),
     *                   @SWG\Property(
     *                      property="items",
     *                      type="array",
     *                      description="快递单内商品",
     *                      @SWG\Items(
     *                         @SWG\Property(property="pic", type="string", description="商品图片"),
     *                      ),
     *                   ),
     *                   @SWG\Property(property="items_num", type="integer", description="快递单商品数量"),
     *                   @SWG\Property(property="status_msg", type="string", description="快递单状态描述"),
     *                   @SWG\Property(property="delivery_info", type="string", description="物流信息描述"),
     *                   @SWG\Property(property="delivery_id", type="integer", description="发货单id"),
     *                ),
     *             ),
     *          ),
     *       ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function lists(Request $request)
    {
        // CRC: 2367340174
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $order_id = $request->input('order_id');

        $params = [
            'company_id' => $company_id,
            'order_id' => $order_id
        ];
        $service = new OrderDeliveryService();
        $result = $service->deliveryItems($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/delivery/trackerpull",
     *     summary="订单发货单详情",
     *     tags={"订单"},
     *     description="订单发货单详情",
     *     operationId="trackerpull",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="delivery_id", in="query", description="发货单id", required=true, type="string"),
     *     @SWG\Response(
     *       response=200,
     *       description="成功返回结构",
     *       @SWG\Schema(
     *          @SWG\Property(
     *             property="data",
     *             type="array",
     *             @SWG\Items(
     *                @SWG\Property(property="AcceptStation", type="string", description="物流信息描述"),
     *                @SWG\Property(property="AcceptTime", type="string", description="记录更新时间"),
     *             ),
     *          ),
     *       ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function deliveryInfo(Request $request)
    {
        // CRC: 2367340174
        $authInfo = $request->get('auth');
        $user_id = $authInfo['user_id'];
        $orders_delivery_id = $request->input('delivery_id');

        //参数判断
        $params = [
            'orders_delivery_id' => $orders_delivery_id,
        ];
        $rules = [
            'orders_delivery_id' => ['required', '缺少发货单id'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException(trans('OrdersBundle/Order.'.$error));
        }

        $service = new OrderDeliveryService();
        $result = $service->deliveryInfo($orders_delivery_id, $user_id);

        return $this->response->array($result);
    }
}
