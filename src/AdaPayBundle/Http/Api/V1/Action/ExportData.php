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

namespace AdaPayBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\AdapayLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;

use App\Http\Controllers\Controller as Controller;
use EspierBundle\Jobs\ExportFileJob;
use AdaPayBundle\Services\AdapayTradeService;
use AdaPayBundle\Services\MemberService;

class ExportData extends Controller
{
    /**
     * @SWG\Get(
     *     path="/adapay/trades/exportdata",
     *     summary="导出汇付交易单列表",
     *     tags={"Adapay"},
     *     description="导出汇付交易单列表",
     *     operationId="exportTradeData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="order_id", in="query", description="订单号", required=false, type="string"),
     *     @SWG\Parameter( name="trade_id", in="query", description="交易单号", required=false, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="根据店铺筛选", type="string"),
     *     @SWG\Parameter( name="distributor_name", in="query", description="店铺名称", type="string"),
     *     @SWG\Parameter( name="pay_channel", in="query", description="支付方式:wx_lite微信小程序支付", type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="开始时间", type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="结束时间", type="string"),
     *     @SWG\Parameter( name="status", in="query", description="交易状态: SUCCESS—支付完成;PARTIAL_REFUND—部分退款;FULL_REFUND—全额退款", type="string"),
     *     @SWG\Parameter( name="adapay_div_status", in="query", description="分账状态:NOTDIV — 未分账;DIVED - 已分账", type="string"),
     *     @SWG\Parameter( name="adapay_fee_mode", in="query", description="手续费扣费方式", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/AdaPayErrorResponse") ) )
     * )
     */
    public function exportTradeData(Request $request)
    {
        $type = 'adapay_tradedata';
        $tradeService = new AdapayTradeService();
        $filter = array();
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $params = $request->all();
        $user = app('auth')->user();
        if ($request->input('status')) {
            $filter['status'] = strtoupper($request->input('status'));
        }
        if ($request->input('can_div')) {
            $filter['can_div'] = $request->input('can_div') === 'true';
        }
        if ($request->input('adapay_fee_mode')) {
            $filter['adapay_fee_mode'] = strtoupper($request->input('adapay_fee_mode'));
        }
        if ($request->input('adapay_div_status')) {
            $filter['adapay_div_status'] = strtoupper($request->input('adapay_div_status'));
        }

        if ($request->input('pay_channel', false)) {
            $filter['pay_channel'] = $request->input('pay_channel');
        }

        if ($request->input('trade_id', false)) {
            $filter['trade_id'] = $request->input('trade_id');
        }

        if ($request->input('time_start_begin')) {
            $filter['time_start|gte'] = $request->input('time_start_begin');
            $filter['time_start|lte'] = $request->input('time_start_end');
        }

        // if($user->get('distributor_id', 0)) { //店铺端
        //     $filter['distributor_id'] = $user->get('distributor_id');
        // } else if($user->get('distributor_ids', 0)) { //经销商端
        //     $filter['distributor_id'] = array_column($user->get('distributor_ids'), 'distributor_id');
        // }

        if ($user->get('operator_type') == 'distributor') { //店铺端
            $filter['distributor_id'] = $user->get('distributor_id');
            if (!$filter['distributor_id']) {
                throw new resourceexception('导出有误,暂无数据导出');
            }
        } elseif ($user->get('operator_type') == 'dealer') { //经销商端
            $memberService = new MemberService();
            $operator = $memberService->getOperator();
            $filter['dealer_id'] = $operator['operator_id'];
            if (!$filter['dealer_id']) {
                throw new resourceexception('导出有误,暂无数据导出');
            }
        }

        if ($request->get('distributor_name', 0)) { //主商户端/经销商端 根据店铺字段筛选
            $distributorFilter = ['name|contains' => $request->get('distributor_name')];
            $distributorFilter['company_id'] = $filter['company_id'];
            $distributors = $tradeService->getDistributors($distributorFilter);
            if (!$distributors) {
                throw new resourceexception('导出有误,暂无数据导出');
            }
            $filter['distributor_id'] = array_column($distributors, 'distributor_id'); //覆盖distributor_id条件
//            unset($filter['distributor_name']);
        }
        $filter['operator_type'] = $user->get('operator_type');
        $res = $tradeService->getTradeList($filter, 1, 1);
        $count = $res['total_count'] ?? 0;
        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');
        return $this->exportData($count, $type, $filter, $operator_id);
    }

    private function exportData($count, $type, $filter, $operator_id = 0)
    {
        if ($count <= 0) {
            throw new resourceexception('导出有误,暂无数据导出');
        }

        if ($count > 15000) {
            throw new resourceexception("导出有误，当前导出数据为 $count 条，最高导出 15000 条数据");
        }

        (new AdapayLogService())->recordLogByType($filter['company_id'], 'trade/exportdata');

        // if ($count > 500) {
        $gotoJob = (new ExportFileJob($type, $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
        // } else {
        //     $exportService = $this->getService($type);
        //     $result = $exportService->exportData($filter);
        //     return response()->json($result);
        // }
    }
}
