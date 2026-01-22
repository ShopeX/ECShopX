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

namespace AftersalesBundle\Http\Api\V1\Action;

use EspierBundle\Jobs\ExportFileJob;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use AftersalesBundle\Services\AftersalesRefundService;
use AftersalesBundle\Services\AftersalesOfflineRefundService;
use OrdersBundle\Services\OfflinePaymentService;
use MembersBundle\Services\MemberService;
use Dingo\Api\Exception\ResourceException;

use EspierBundle\Traits\GetExportServiceTraits;

class Refund extends Controller
{
    use GetExportServiceTraits;

    /**
     * @SWG\Definition(
     *     definition="Refund",
     *     type="object",
     *     @SWG\Property(property="refund_bn", type="string", example="202012025055505"),
     *     @SWG\Property(property="aftersales_bn", type="string", example="202012025055505"),
     *     @SWG\Property(property="order_id", type="string", example="3258750000110027"),
     *     @SWG\Property(property="trade_id", type="string", example="3258750000110027"),
     *     @SWG\Property(property="company_id", type="integer", example="1"),
     *     @SWG\Property(property="user_id", type="integer", example="111"),
     *     @SWG\Property(property="shop_id", type="integer", example="1"),
     *     @SWG\Property(property="distributor_id", type="integer", example="1"),
     *     @SWG\Property(property="refund_type", type="string", example="0"),
     *     @SWG\Property(property="refund_channel", type="integer", example="original"),
     *     @SWG\Property(property="refund_status", type="integer", example="AUDIT_SUCCESS"),
     *     @SWG\Property(property="refund_fee", type="integer", example="1"),
     *     @SWG\Property(property="refunded_fee", type="integer", example="1"),
     *     @SWG\Property(property="refunded_point", type="string", example="100"),
     *     @SWG\Property(property="return_point", type="string", example="100"),
     *     @SWG\Property(property="return_freight", type="string", example="0"),
     *     @SWG\Property(property="deposit", type="string", example=""),
     *     @SWG\Property(property="currency", type="string", example="CNY"),
     *     @SWG\Property(property="refund_success_time", type="string", example=""),
     *     @SWG\Property(property="refund_id", type="string", example=""),
     *     @SWG\Property(property="refunds_memo", type="string", example=""),
     *     @SWG\Property(property="create_time", type="string", example="1606916210"),
     *     @SWG\Property(property="update_time", type="string", example="1607936870"),
     *     @SWG\Property(property="cur_pay_fee", type="string", example="10000"),
     *     @SWG\Property(property="cur_fee_symbol", type="string", example="1"),
     *     @SWG\Property(property="cur_fee_rate", type="string", example="￥"),
     *     @SWG\Property(property="cur_fee_type", type="string", example="CNY"),
     *     @SWG\Property(property="hf_order_id", type="string", example=""),
     * )
     */

    /**
     * @SWG\Get(
     *     path="/refund",
     *     summary="获取退款单列表",
     *     tags={"售后"},
     *     description="getRefundList",
     *     operationId="getRefundList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="refund_bn",
     *         in="query",
     *         description="退款单号",
     *         type="string",
     *     ),
     *    @SWG\Parameter(
     *         name="aftersales_bn",
     *         in="query",
     *         description="售后单号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="query",
     *         description="订单号",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="shop_id",
     *         in="query",
     *         description="门店id",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="refund_type",
     *         in="query",
     *         description="退款类型",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="refund_channel",
     *         in="query",
     *         description="退款方式",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="refund_status",
     *         in="query",
     *         description="退款状态",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_begin",
     *         in="query",
     *         description="查询开始时间",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_end",
     *         in="query",
     *         description="查询结束时间",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="total_count", type="integer"),
     *                     @SWG\Property(property="list", type="array", @SWG\Items(
     *                         ref="#/definitions/Refund"
     *                     )),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getRefundList(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        if ($request->input('time_start_begin')) {
            $filter['create_time|gte'] = $request->input('time_start_begin');
            $filter['create_time|lte'] = $request->input('time_start_end');
        }
        $filter['refund_status'] = $request->input('refund_status');
        if (!is_null($request->input('refund_type'))) {
            $filter['refund_type'] = $request->input('refund_type');
        }
        if ($request->input('aftersales_bn')) {
            $filter['aftersales_bn'] = $request->input('aftersales_bn');
        }
        if ($request->input('refund_channel')) {
            $filter['refund_channel'] = $request->input('refund_channel');
        }
        if ($request->input('refund_bn')) {
            $filter['refund_bn'] = $request->input('refund_bn');
        }
        if ($request->input('order_id')) {
            $filter['order_id'] = $request->input('order_id');
        }
        if ($request->input('shop_id')) {
            $filter['shop_id'] = $request->input('shop_id');
        }
        if ($request->input('user_id')) {
            $filter['user_id'] = $request->input('user_id');
        }
        if ($request->get('distributor_id', 0)) {
            $filter['distributor_id'] = $request->get('distributor_id');
        }
        if ($request->input('mobile')) {
            $filter['mobile'] = $request->input('mobile');
        }

        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');;
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

        $companyId = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        $page = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);
        $offset = ($page - 1) * $limit;
        // $filter['need_order'] = true;

        $aftersalesService = new AftersalesRefundService();
        $result = $aftersalesService->getRefundsList($filter, $offset, $limit);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/refunds/{refund_bn}",
     *     summary="获取退款单详情",
     *     tags={"售后"},
     *     description="获取退款单详情",
     *     operationId="getRefundsDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="refund_bn",
     *         in="path",
     *         description="退款单号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Refund"
     *             )
     *         )
     *
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getRefundsDetail($refund_bn)
    {
        if (!$refund_bn) {
            throw new ResourceException('没有填写售后单号');
        }
        $companyId = app('auth')->user()->get('company_id');
        $AftersalesRefundService = new AftersalesRefundService();
        $filter = [
            'company_id' => $companyId,
            'refund_bn' => $refund_bn,
        ];
        $result = $AftersalesRefundService->getRefunds($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/refund/logExport",
     *     summary="导出退款单列表",
     *     tags={"售后"},
     *     description="导出退款单列表",
     *     operationId="logExport",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="refund_bn",
     *         in="query",
     *         description="退款单号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_begin",
     *         in="query",
     *         description="开始时间",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_end",
     *         in="query",
     *         description="结束时间",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="会员手机号",
     *         required=true,
     *         type="integer"
     *     ),
     *    @SWG\Parameter(
     *         name="order_id",
     *         in="query",
     *         description="订单号",
     *         required=true,
     *         type="integer"
     *     ),
     *    @SWG\Parameter(
     *         name="refund_type",
     *         in="query",
     *         description="退款类型",
     *         required=true,
     *         type="integer"
     *     ),
     *    @SWG\Parameter(
     *         name="refund_channel",
     *         in="query",
     *         description="退款方式",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="refund_status",
     *         in="query",
     *         description="退款状态",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function logExport(Request $request)
    {
        $params = $request->all('mobile', 'refund_bn', 'order_id', 'refund_type', 'refund_channel', 'refund_status', 'time_start_begin', 'time_start_end');

        if ($params['mobile']) {
            $memberService = new MemberService();
            $filter['user_id'] = $memberService->getUserIdByMobile($params['mobile'], app('auth')->user()->get('company_id')) ?? 0;
        }
        if ($params['refund_bn']) {
            $filter['refund_bn'] = $params['refund_bn'];
        }
        if ($params['order_id']) {
            $filter['order_id'] = $params['order_id'];
        }
        $filter['refund_type'] = $params['refund_type'];

        if ($params['refund_channel']) {
            $filter['refund_channel'] = $params['refund_channel'];
        }
        if ($params['refund_status']) {
            $filter['refund_status'] = $params['refund_status'];
        }
        if ($params['time_start_begin'] && $params['time_start_end']) {
            $filter['create_time|gte'] = $params['time_start_begin'];
            $filter['create_time|lte'] = $params['time_start_end'];
        }

        if ($request->input('distributor_id')) {
            $filter['distributor_id'] = $request->input('distributor_id');
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        
        // 处理供应商端权限
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');
        }
        
        // 处理店铺端权限
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
        
        // 处理商家端权限
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        $aftersalesRefundService = new AftersalesRefundService();
        $count = $aftersalesRefundService->refundCount($filter);

        if ($count <= 0) {
            throw new resourceexception('导出有误,暂无数据导出');
        }

        if ($count > 15000) {
            throw new resourceexception('导出有误，最高导出15000条数据');
        }

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');

//        if ($count > 500) {
        $gotoJob = (new ExportFileJob('refund_record_count', $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
//         } else {
//             $exportService = $this->getService('refund_record_count');
//             $result = $exportService->exportData($filter);
//             return response()->json($result);
//         }
    }

    /**
     * @SWG\Get(
     *     path="/refund/offline/bank",
     *     summary="获取线下退款银行信息",
     *     tags={"售后"},
     *     description="获取线下退款银行信息",
     *     operationId="getOfflineBank",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="order_id",
     *         in="path",
     *         description="订单编号",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Refund"
     *             )
     *         )
     *
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function getOfflineBank(Request $request)
    {
        $params = $request->all('order_id');
        $rules = [
            'order_id' => ['required', '订单编号必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $companyId = app('auth')->user()->get('company_id');
        $offlinePaymentService = new OfflinePaymentService();
        $filter = [
            'company_id' => $companyId,
            'order_id' => $params['order_id'],
            'check_status' => 1,
        ];
        $result = $offlinePaymentService->getInfo($filter);

        return $this->response->array($result);
    }

     /**
     * @SWG\Post(
     *     path="/refund/offline",
     *     summary="线下退款",
     *     tags={"售后"},
     *     description="线下退款",
     *     operationId="saveOfflineRefund",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="refund_bn",
     *         in="query",
     *         description="退款单号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="bank_account_name",
     *         in="query",
     *         description="收款账户名称",
     *         required=true,
     *         type="string"
     *     ),
     *    @SWG\Parameter(
     *         name="bank_account_no",
     *         in="query",
     *         description="收款银行账号",
     *         required=true,
     *         type="string"
     *     ),
     *    @SWG\Parameter(
     *         name="bank_name",
     *         in="query",
     *         description="收款开户银行",
     *         required=true,
     *         type="string"
     *     ),
     *    @SWG\Parameter(
     *         name="refund_account_name",
     *         in="query",
     *         description="退款账户名",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="refund_account_bank",
     *         in="query",
     *         description="退款银行",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="refund_account_no",
     *         in="query",
     *         description="退款账号",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AftersalesErrorRespones") ) )
     * )
     */
    public function saveOfflineRefund(Request $request)
    {
        $params = $request->all('refund_bn', 'bank_account_name', 'bank_account_no', 'bank_name', 'refund_account_name', 'refund_account_bank', 'refund_account_no');
        $rules = [
            'refund_bn' => ['required', '售后单号必填'],
            'bank_account_name' => ['required', '收款人户名必填'],
            'bank_account_no' => ['required', '收款银行帐号必填'],
            'bank_name' => ['required', '开户银行必填'],
            'refund_account_name' => ['required', '退款人户名必填'],
            'refund_account_bank' => ['required', '退款银行帐号必填'],
            'refund_account_no' => ['required', '退款开户银行必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        
        $aftersalesOfflineRefundService = new AftersalesOfflineRefundService();
        $result = $aftersalesOfflineRefundService->create($params);
        return response()->json($result);
    }
}
