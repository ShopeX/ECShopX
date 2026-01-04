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

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\TemplateOrdersServices;

class Template extends Controller
{
    /**
     * @SWG\Get(
     *     path="/templates",
     *     summary="获取模版订单列表",
     *     tags={"订单"},
     *     description="获取模版订单列表",
     *     operationId="getTemplateOrdersList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getTemplateOrdersList(Request $request)
    {
        $templateOrdersServices = new TemplateOrdersServices();

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['order_status'] = 'DONE';

        $orderBy = ['create_time' => 'DESC'];

        $pageSize = $request->input('pageSize', 50);
        $page = $request->input('page', 1);

        $data = $templateOrdersServices->getTemplateOrdersList($filter, $orderBy, $pageSize, $page);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/template/open",
     *     summary="开通小程序模版",
     *     tags={"订单"},
     *     description="开通小程序模版",
     *     operationId="createTemplateOrders",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="template_name", in="query", description="开通模版的名称", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function createTemplateOrders(Request $request)
    {
        $templateOrdersServices = new TemplateOrdersServices();

        $data['company_id'] = app('auth')->user()->get('company_id');
        $data['operator_id'] = app('auth')->user()->get('operator_id');
        $data['template_name'] = $request->input('template_name');

        $data = $templateOrdersServices->createTemplateOrders($data);
        return $this->response->array($data);
    }
}
