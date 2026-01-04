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
use OrdersBundle\Services\RefundErrorLogsService;

class RefundErrorLogs extends Controller
{
    /**
     * @SWG\Get(
     *     path="/trade/refunderrorlogs/list",
     *     summary="获取退款错误列表",
     *     tags={"订单"},
     *     description="获取退款错误列表",
     *     operationId="getList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单编号", type="string"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="返回数据",
     *               @SWG\Property(property="total_count", type="integer", example="476", description="总记录条数"),
     *               @SWG\Property(property="list", type="array", description="数据列表",
     *                 @SWG\Items(
     *                           @SWG\Property(property="id", type="string", example="477", description="ID"),
     *                           @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                           @SWG\Property(property="order_id", type="string", example="3131632000240199", description="订单号"),
     *                           @SWG\Property(property="wxa_appid", type="string", example="", description="小程序appid"),
     *                           @SWG\Property(property="data_json", type="string", example="", description="data数据json格式"),
     *                           @SWG\Property(property="status", type="string", example="FAIL", description="错误状态"),
     *                           @SWG\Property(property="error_code", type="string", example="", description="错误码"),
     *                           @SWG\Property(property="error_desc", type="string", example="Undefined variable: return", description="错误描述"),
     *                           @SWG\Property(property="is_resubmit", type="string", example="", description="是否重新提交"),
     *                           @SWG\Property(property="create_time", type="integer", example="1597203117", description="订单创建时间"),
     *                           @SWG\Property(property="update_time", type="integer", example="1597203117", description="订单更新时间"),
     *                 ),
     *               ),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getList(Request $request)
    {
        $filter = [];

        if ($request->get('distributor_id', 0)) {
            $filter['distributor_id'] = $request->get('distributor_id');
        }

        $distributorListSet = app('auth')->user()->get('distributor_ids');
        if (!empty($distributorListSet)) {
            $distributorIdSet = array_column($distributorListSet, 'distributor_id');
            if (isset($filter['distributor_id']) && $filter['distributor_id']) {
                if (!in_array($filter['distributor_id'], $distributorIdSet)) {
                    unset($filter['distributor_id']);
                }
            } else {
                $filter['distributor_id'] = $distributorIdSet;
            }
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        } elseif ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');;
        }
        
        $input = $request->all('status', 'order_id', 'start_time', 'end_time');

        if ($input['status'] == 'waiting') {
            $filter['is_resubmit'] = false;
        } elseif ($input['status'] == 'is_resubmit') {
            $filter['is_resubmit'] = true;
        }
        if ($input['order_id']) {
            $filter['order_id'] = $input['order_id'];
        }
        if (isset($input['start_time'], $input['end_time']) && $input['start_time'] && $input['end_time']) {
            $filter['create_time|gte'] = $input['start_time'];
            $filter['create_time|lte'] = $input['end_time'];
        }

        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);

        $refundErrorLogsService = new RefundErrorLogsService();

        $data = $refundErrorLogsService->getList($filter, $page, $pageSize);

        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="/trade/refunderrorlogs/resubmit/{id}",
     *     summary="重新提交退款",
     *     tags={"订单"},
     *     description="重新提交退款",
     *     operationId="resubmit",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="ID", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="id", type="string", example="477", description="ID"),
     *                    @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                    @SWG\Property(property="order_id", type="string", example="3131632000240199", description="订单号"),
     *                    @SWG\Property(property="wxa_appid", type="string", example="", description="小程序appid"),
     *                    @SWG\Property(property="data_json", type="string", example="", description="data数据json格式"),
     *                    @SWG\Property(property="status", type="string", example="FAIL", description="错误状态"),
     *                    @SWG\Property(property="error_code", type="string", example="", description="错误码"),
     *                    @SWG\Property(property="error_desc", type="string", example="Undefined variable: return", description="错误描述"),
     *                    @SWG\Property(property="is_resubmit", type="string", example="", description="是否重新提交"),
     *                    @SWG\Property(property="create_time", type="integer", example="1597203117", description="订单创建时间"),
     *                    @SWG\Property(property="update_time", type="integer", example="1597203117", description="订单更新时间"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function resubmitRefund($id)
    {
        $refundErrorLogsService = new RefundErrorLogsService();
        return $refundErrorLogsService->resubmit($id);
    }
}
