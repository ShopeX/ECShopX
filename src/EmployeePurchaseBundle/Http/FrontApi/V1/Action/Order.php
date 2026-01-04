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

namespace EmployeePurchaseBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as BaseController;
use OrdersBundle\Traits\GetOrderServiceTrait;

class Order extends BaseController
{
    // IDX: 2367340174
    use GetOrderServiceTrait;

    /**
     * @SWG\Put(
     *     path="/wxapp/employeepurchase/order/receiver",
     *     summary="修改订单的收货人信息",
     *     tags={"订单"},
     *     description="修改订单的收货人信息",
     *     operationId="updateOrderReceiver",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function updateOrderReceiver(Request $request)
    {
        // IDX: 2367340174
        $authInfo = $request->get('auth');

        $params = $request->all('order_id', 'receiver_name', 'receiver_mobile', 'receiver_state', 'receiver_city', 'receiver_district', 'receiver_address', 'receiver_zip');

        $rules = [
            'order_id' => ['required', '请填写订单号'],
            'receiver_name' => ['required|zhstring', '请填写正确的收货人姓名'],
            'receiver_mobile' => ['required', '请填写联系方式'],
            'receiver_state' => ['required|zhstring', '请填写正确的省份'],
            'receiver_city' => ['required|zhstring', '请填写正确的城市'],
            'receiver_district' => ['required|zhstring', '请填写正确的地区'],
            'receiver_address' => ['required', '请填写正确的详细地址'],
        ];
        if (!isset($params['receiver_zip']) || !preg_match("/^\d{6}$/", $params['receiver_zip'])) {
            $params['receiver_zip'] = '000000';
        }
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $authInfo = $request->get('auth');
        $params['user_id'] = $authInfo['user_id'];
        $params['company_id'] = $authInfo['company_id'];
        
        $orderService = $this->getOrderService('normal_employee_purchase');
        $result = $orderService->updateOrderReceiver($params);

        return $this->response->array($result);
    }
}