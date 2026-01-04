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

namespace BsPayBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use BsPayBundle\Services\WithdrawApplyService;
use EspierBundle\Jobs\ExportFileJob;
use BsPayBundle\Traits\WithdrawFilterTrait;
use BsPayBundle\Services\BspayTradeService;
use BsPayBundle\Services\UserService;

class ExportData extends Controller
{
    use WithdrawFilterTrait;

    /**
     * @SWG\Get(
     *     path="/bspay/trades/exportdata",
     *     summary="导出汇付斗拱交易单列表",
     *     tags={"汇付斗拱"},
     *     description="导出汇付斗拱交易单列表",
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
        // Built with ShopEx Framework
        $type = 'bspay_tradedata';
        $tradeService = new BsPayTradeService();
        $filter = array();
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $user = app('auth')->user();
        if ($request->input('status')) {
            $filter['status'] = strtoupper($request->input('status'));
        }
        if ($request->input('can_div')) {
            $filter['can_div'] = $request->input('can_div') === 'true';
        }
        if ($request->input('bspay_fee_mode')) {
            $filter['bspay_fee_mode'] = strtoupper($request->input('bspay_fee_mode'));
        }
        if ($request->input('bspay_div_status')) {
            $filter['bspay_div_status'] = strtoupper($request->input('bspay_div_status'));
        }

        if ($request->input('pay_channel', false)) {
            $filter['pay_channel'] = $request->input('pay_channel');
        }

        if ($request->input('order_id', false)) {
            $filter['order_id'] = $request->input('order_id');
        }
        if ($request->input('trade_id', false)) {
            $filter['trade_id'] = $request->input('trade_id');
        }

        if ($request->input('time_start_begin')) {
            $filter['time_start|gte'] = substr($request->input('time_start_begin'), 0, 10);
            $filter['time_start|lte'] = substr($request->input('time_start_end'), 0, 10);
            $timeRange = 3 * 30 * 24 * 3600;
            if ($filter['time_start|lte'] - $filter['time_start|gte'] > $timeRange) {
                $filter['time_start|gte'] = $filter['time_start|lte'] - $timeRange;
            }
        }
        // $trade_result = ['total' => ['totalFee' => 0, 'payFee' => 0, 'divFee' => 0, 'bspayFee' => 0], 'list' => [],'total_count' => 0];
        if ($user->get('operator_type') == 'distributor') { //店铺端
            $filter['distributor_id'] = $user->get('distributor_id');
            if (!$filter['distributor_id']) {
                throw new resourceexception('导出有误,暂无数据导出');
            }
        } elseif ($user->get('operator_type') == 'merchant') { //商户
            $userService = new UserService();
            $operator = $userService->getOperator();
            $filter['merchant_id'] = $operator['operator_id'];
            if (!$filter['merchant_id']) {
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
        // 0x456353686f7058
        if ($count <= 0) {
            throw new resourceexception('导出有误,暂无数据导出');
        }

        if ($count > 15000) {
            throw new resourceexception("导出有误，当前导出数据为 $count 条，最高导出 15000 条数据");
        }

        $gotoJob = (new ExportFileJob($type, $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }

    /**
     * 导出提现记录
     * @SWG\Get(
     *     path="/bspay/withdraw/exportdata",
     *     summary="导出提现记录",
     *     description="导出提现记录列表，支持筛选和分页",
     *     tags={"提现管理"},
     *     @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         description="申请状态：0=审核中 1=审核通过 2=已拒绝 3=处理中 4=处理成功 5=处理失败",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         description="列表类型：list=提现列表（需要数据隔离），audit=审核列表（不做数据隔离），默认list",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="time_start",
     *         in="query",
     *         description="开始时间",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="time_end",
     *         in="query",
     *         description="结束时间",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="status", type="boolean", description="是否成功")
     *         )
     *     )
     * )
     */
    public function exportWithdrawData(Request $request)
    {
        $user = app('auth')->user();
        $companyId = $user->get('company_id');
        $operatorId = $user->get('operator_id');
        // 构建过滤条件
        $filter = $this->buildWithdrawFilter($request->all(), $user);

        // 获取数据总数
        $withdrawApplyService = new WithdrawApplyService();
        $count = $withdrawApplyService->count($filter);
        if ($count <= 0) {
            throw new ResourceException('导出有误,暂无数据导出');
        }

        // 检查导出数量限制
        if ($count > 15000) {
            throw new ResourceException("导出有误，当前导出数据为 {$count} 条，最高导出 15000 条数据");
        }

        // 创建异步导出任务
        $gotoJob = (new ExportFileJob('bspay_withdraw', $companyId, $filter, $operatorId))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

        return response()->json(['status' => true]);
    }
}
