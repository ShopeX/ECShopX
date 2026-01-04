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

namespace OrdersBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use OrdersBundle\Services\DeliveryProcessLogServices;
use OrdersBundle\Services\OrderDeliveryService;
use SupplierBundle\Services\SupplierService;

class Delivery extends Controller
{
    /**
     * @SWG\Get(
     *     path="/delivery/lists",
     *     summary="发货单列表",
     *     tags={"订单"},
     *     description="发货单列表",
     *     operationId="lists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="array", description="",
     *             @SWG\Items(
     *                @SWG\Property(property="orders_delivery_id", type="string", description="发货单id"),
     *                @SWG\Property(property="company_id", type="string", description="公司id"),
     *                @SWG\Property(property="order_id", type="string", description="订单号"),
     *                @SWG\Property(property="delivery_corp", type="string", description="快递公司"),
     *                @SWG\Property(property="delivery_code", type="string", description="快递单号"),
     *                @SWG\Property(property="delivery_time", type="string", description="发货时间"),
     *                @SWG\Property(property="created", type="integer", description="创建时间"),
     *                @SWG\Property(property="updated", type="integer", description="修改时间"),
     *                @SWG\Property(property="delivery_corp_name", type="string", description="快递公司名称"),
     *                @SWG\Property(property="delivery_corp_source", type="string", description="快递代码来源"),
     *                @SWG\Property(property="receiver_mobile", type="string", description="收货人手机号"),
     *                @SWG\Property(property="user_id", type="integer", description="会员id"),
     *                @SWG\Property(property="package_type", type="string", description="订单包裹类型 batch 整单发货  sep拆单发货"),
     *             ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function lists(Request $request)
    {
        // ModuleID: 76fe2a3d
        $company_id = app('auth')->user()->get('company_id');
        $operator_type = app('auth')->user()->get('operator_type');
        $order_id = $request->input('order_id');

        $filter = [
            'company_id' => $company_id,
            'order_id' => $order_id
        ];
        if ($operator_type == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');
        }
        $service = new OrderDeliveryService();
        $result = $service->lists($filter);
        if ($result) {
            $supplierIds = array_filter(array_column($result, 'supplier_id'));
            if ($supplierIds) {
                $supplierService = new SupplierService();
                $rs = $supplierService->repository->getLists(['operator_id' => $supplierIds], 'operator_id, supplier_name');
                if ($rs) {
                    $supplierData = array_column($rs, null, 'operator_id');
                    foreach ($result as $k => $v) {
                        if (!$v['supplier_id']) continue;
                        $result[$k]['supplier_name'] = $supplierData[$v['supplier_id']]['supplier_name'] ?? '';
                    }
                }
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/delivery/process/list",
     *     summary="物流状态日志",
     *     tags={"orders"},
     *     description="物流状态日志",
     *     operationId="deliveryProcessLog",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *                   @SWG\Property(property="delivery_list", type="array", description="发货单详情",
     *                       @SWG\Items(
     *                          @SWG\Property(property="orders_delivery_id", type="string", description="发货单id"),
     *                          @SWG\Property(property="company_id", type="string", description="公司id"),
     *                          @SWG\Property(property="order_id", type="string", description="订单号"),
     *                          @SWG\Property(property="delivery_corp", type="string", description="快递公司"),
     *                          @SWG\Property(property="delivery_code", type="string", description="快递单号"),
     *                          @SWG\Property(property="delivery_time", type="string", description="发货时间"),
     *                          @SWG\Property(property="created", type="integer", description="创建时间"),
     *                          @SWG\Property(property="updated", type="integer", description="修改时间"),
     *                          @SWG\Property(property="delivery_corp_name", type="string", description="快递公司名称"),
     *                          @SWG\Property(property="delivery_corp_source", type="string", description="快递代码来源"),
     *                          @SWG\Property(property="receiver_mobile", type="string", description="收货人手机号"),
     *                          @SWG\Property(property="user_id", type="integer", description="会员id"),
     *                          @SWG\Property(property="package_type", type="string", description="订单包裹类型 batch 整单发货  sep拆单发货"),
     *                       ),
     *                   ),
     *                   @SWG\Property(property="logs", type="array", description="物流日志,最后一个信息为最新",
     *                      @SWG\Items(
     *                          @SWG\Property(property="time", type="string", example="1612150245", description="状态时间"),
     *                          @SWG\Property(property="msg", type="string", example="骑士接单", description="状态信息"),
     *                          @SWG\Property(property="level", type="integer", example="1", description="状态等级, 当前 0: 主状态， 1: 子状态"),
     *                      ),
     *                   ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function processLogList(Request $request)
    {
        $filter = [
            'company_id' => app('auth')->user()->get('company_id'),
            'order_id' => $request->query('order_id'),
        ];
        $service = new OrderDeliveryService();
        $delivery_list = $service->lists($filter);

        $deliveryProcessServices = new DeliveryProcessLogServices();
        $result = $deliveryProcessServices->getList($filter);

        return $this->response->array([
            'delivery_list' => $delivery_list,
            'logs' => $result,
        ]);
    }
}
