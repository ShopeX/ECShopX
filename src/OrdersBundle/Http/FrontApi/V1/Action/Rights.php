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

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use Dingo\Api\Exception\ResourceException;

class Rights extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/rights/{rights_id}",
     *     summary="获取权益详情",
     *     tags={"订单"},
     *     description="获取权益详情",
     *     operationId="getRightsDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="rights_id",
     *         in="path",
     *         description="权益id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="rights_id", type="string", example="310", description="权益ID"),
     *               @SWG\Property(property="user_id", type="string", example="20205", description="用户id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *               @SWG\Property(property="can_reservation", type="string", example="", description="是否可预约"),
     *               @SWG\Property(property="rights_name", type="string", example="团购券1", description="权益标题"),
     *               @SWG\Property(property="rights_subname", type="string", example="", description="权益子标题"),
     *               @SWG\Property(property="total_num", type="string", example="1", description="服务商品原始总次数,0标示无限制"),
     *               @SWG\Property(property="total_consum_num", type="string", example="0", description="总消耗次数"),
     *               @SWG\Property(property="is_not_limit_num", type="integer", example="2", description="限制核销次数,1:不限制；2:限制"),
     *               @SWG\Property(property="status", type="string", example="0", description="权益状态; valid:有效的, expire:过期的; invalid:失效的"),
     *               @SWG\Property(property="start_time", type="integer", example="1596556800", description="权益开始时间"),
     *               @SWG\Property(property="end_time", type="integer", example="1596729599", description="权益结束时间"),
     *               @SWG\Property(property="created", type="integer", example="1596594433", description=""),
     *               @SWG\Property(property="updated", type="integer", example="1596594433", description=""),
     *               @SWG\Property(property="order_id", type="string", example="3139418000080205", description="订单号"),
     *               @SWG\Property(property="rights_from", type="string", example="购买获取", description="权益来源"),
     *               @SWG\Property(property="operator_desc", type="string", example="", description="操作员信息"),
     *               @SWG\Property(property="label_infos", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="label_id", type="string", example="18", description=""),
     *                           @SWG\Property(property="label_name", type="string", example="物料2", description=""),
     *                 ),
     *               ),
     *               @SWG\Property(property="is_valid", type="string", example="", description=""),
     *               @SWG\Property(property="server_time", type="integer", example="1611729158", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getRightsDetail($rights_id, Request $request)
    {
        // ShopEx EcShopX Service Component
        $authInfo = $request->get('auth');
        $validator = app('validator')->make(['rights_id' => $rights_id], [
            'rights_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('OrdersBundle/Order.get_rights_detail_error'), $validator->errors());
        }
        $rightsService = new RightsService(new TimesCardService());
        $result = $rightsService->getRightsDetail($rights_id);
        $result['server_time'] = time(); // 增加服务器当前时间用于前端判断
        $company_id = $authInfo['company_id'];
        if ($company_id != $result['company_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.rights_id_invalid'));
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/rightsLogs",
     *     summary="获取权益核销记录列表",
     *     tags={"订单"},
     *     description="获取权益核销记录列表",
     *     operationId="getRightsLogList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取商品列表的初始偏移位置，从1开始计数",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="integer", example="3", description="总记录数"),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="rights_id", type="string", example="231", description="权益ID"),
     *                           @SWG\Property(property="user_id", type="string", example="20133", description="用户id"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="shop_id", type="string", example="26", description="门店id"),
     *                           @SWG\Property(property="rights_name", type="string", example="次卡-3次", description="权益标题"),
     *                           @SWG\Property(property="consum_num", type="string", example="1", description="消耗次数"),
     *                           @SWG\Property(property="rights_subname", type="string", example="物料2", description="权益子标题"),
     *                           @SWG\Property(property="attendant", type="string", example="啦啦啦", description="服务员"),
     *                           @SWG\Property(property="consum_time", type="string", example="1592386130", description="核销时间"),
     *                           @SWG\Property(property="created", type="integer", example="1592386130", description="创建时间"),
     *                           @SWG\Property(property="salesperson_mobile", type="string", example="15618429140", description="核销员手机号"),
     *                           @SWG\Property(property="store_name", type="string", example="断桥残雪", description="门店名称"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getRightsLogList(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('OrdersBundle/Order.get_rights_list_error'), $validator->errors());
        }

        $authUser = $request->get('auth');
        if (!$authUser['user_id']) {
            $result['list'] = [];
            $result['total_count'] = [];
            return $this->response->array($result);
        }
        $params['company_id'] = $authUser['company_id'];
        $params['user_id'] = $authUser['user_id'];

        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];

        $rightsService = new RightsService(new TimesCardService());
        $result = $rightsService->getRightsLogList($params, $page, $pageSize);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/rights",
     *     summary="获取权益列表",
     *     tags={"订单"},
     *     description="获取权益列表",
     *     operationId="getRightsList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取商品列表的初始偏移位置，从1开始计数",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="string", example="3", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(
     *                           @SWG\Property(property="rights_id", type="string", example="303", description="权益ID"),
     *                           @SWG\Property(property="user_id", type="string", example="20205", description="用户id"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司ID"),
     *                           @SWG\Property(property="can_reservation", type="string", example="1", description="是否可预约"),
     *                           @SWG\Property(property="rights_name", type="string", example="次卡-不限次", description="权益标题"),
     *                           @SWG\Property(property="rights_subname", type="string", example="物料1", description="权益子标题"),
     *                           @SWG\Property(property="total_num", type="string", example="0", description="服务商品原始总次数,0标示无限制"),
     *                           @SWG\Property(property="total_consum_num", type="string", example="0", description="总消耗次数"),
     *                           @SWG\Property(property="start_time", type="string", example="1595865600", description="权益开始时间"),
     *                           @SWG\Property(property="end_time", type="string", example="1596383999", description="权益结束时间"),
     *                           @SWG\Property(property="order_id", type="string", example="0", description="订单号"),
     *                           @SWG\Property(property="label_infos", type="array", description="",
     *                             @SWG\Items(
     *                                @SWG\Property(property="label_id", type="string", example="17", description=""),
     *                                @SWG\Property(property="label_name", type="string", example="物料1", description=""),
     *                             ),
     *                           ),
     *                           @SWG\Property(property="created", type="string", example="1595925481", description=""),
     *                           @SWG\Property(property="updated", type="string", example="1595925481", description=""),
     *                           @SWG\Property(property="rights_from", type="string", example="注册赠送", description="权益来源"),
     *                           @SWG\Property(property="mobile", type="string", example="15623503296", description="手机号"),
     *                           @SWG\Property(property="operator_desc", type="string", example="", description="操作员信息"),
     *                           @SWG\Property(property="status", type="string", example="0", description="权益状态; valid:有效的, expire:过期的; invalid:失效的"),
     *                           @SWG\Property(property="is_not_limit_num", type="string", example="1", description="限制核销次数,1:不限制；2:限制"),
     *                           @SWG\Property(property="is_valid", type="string", example="", description=""),
     *                           @SWG\Property(property="total_surplus_num", type="integer", example="-1", description=""),
     *                 ),
     *               ),
     *            ),

     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getRightsList(Request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
            'valid' => 'sometimes|required|in:0,1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('OrdersBundle/Order.get_rights_list_error'), $validator->errors());
        }

        $authUser = $request->get('auth');
        if (!$authUser['user_id']) {
            $result['list'] = [];
            $result['total_count'] = [];
            return $this->response->array($result);
        }

        $params['company_id'] = $authUser['company_id'];
        $params['user_id'] = $authUser['user_id'];

        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];
        if (isset($inputData['valid'])) {
            $params['valid'] = $inputData['valid'];
        }
        //临时加固定条件
        $params['end_time'] = time();

        $rightsService = new RightsService(new TimesCardService());
        $result = $rightsService->getRightsList($params, $page, $pageSize);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/rightscode/{rights_id}",
     *     summary="获取权益核销码",
     *     tags={"订单"},
     *     description="获取权益核销码",
     *     operationId="getRightsCode",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *             @SWG\Property(property="barcode_url", type="string", example="123", description="一维码链接"),
     *             @SWG\Property(property="qrcode_url", type="string", example="123", description="二维码链接"),
     *             @SWG\Property(property="code", type="string", example="2894746322182627", description="code码"),
     *             @SWG\Property(property="_ignore_data", type="string", example="1", description=""),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getRightsCode($rights_id, Request $request)
    {
        $authUser = $request->get('auth');
        $params['company_id'] = $authUser['company_id'];

        $rightsService = new RightsService(new TimesCardService());
        $result = $rightsService->getRightsCode($rights_id);
        $result['_ignore_data'] = true;

        return $result;
    }
}
