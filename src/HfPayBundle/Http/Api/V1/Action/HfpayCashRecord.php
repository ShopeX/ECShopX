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

namespace HfPayBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Jobs\ExportFileJob;
use HfPayBundle\Services\HfpayCashRecordService;
use Illuminate\Http\Request;

class HfpayCashRecord extends Controller
{
    /**
     * @SWG\Get(
     *     path="/hfpay/withdraw/getList",
     *     summary="提现记录",
     *     tags={"汇付天下"},
     *     description="提现记录",
     *     operationId="company",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="distributor_id", in="query", type="string", description="店铺ID", required=false),
     *     @SWG\Parameter(name="start_date", in="query", type="string", description="开始日期 Y-m-d", required=true),
     *     @SWG\Parameter(name="end_date", in="query", type="string", description="结束日期 Y-m-d", required=true),
     *     @SWG\Parameter(name="cash_status", in="query", type="string", description="提现状态 1提现中 2提现成功 3提现失败", required=false),
     *     @SWG\Parameter(name="order_id", in="query", type="integer", description="订单id", required=false),
     *     @SWG\Parameter(name="page", in="query", type="integer", description="页码", required=false),
     *     @SWG\Parameter(name="page_size", in="query", type="integer", description="每页记录条数", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             required={"data"},
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    required={"total", "list"},
     *                    @SWG\Property(property="total", type="object", description="汇总数据",
     *                        required={"count", "total_amt", "finish_total_amt", "total_amting", "fail_total_amt"},
     *                        @SWG\Property(property="count", type="integer", description="提现笔数"),
     *                        @SWG\Property(property="total_amt", type="integer", description="提现总金额（分）"),
     *                        @SWG\Property(property="finish_total_amt", type="integer", description="提现成功金额（分）"),
     *                        @SWG\Property(property="total_amting", type="integer", description="提现中金额（分）"),
     *                        @SWG\Property(property="fail_total_amt", type="integer", description="提现失败金额（分）"),
     *                    ),
     *                    @SWG\Property(property="list", type="object", description="明细数据",
     *                       required={"total_count", "data"},
     *                       @SWG\Property(property="total_count", type="integer", description="总记录条数"),
     *                       @SWG\Property(property="data", type="array", description="数据集合",
     *                           @SWG\Items(
     *                              @SWG\Property(property="created_at", type="string", description="日期"),
     *                              @SWG\Property(property="order_id", type="string", description="提现订单号"),
     *                              @SWG\Property(property="bind_card_id", type="string", description="到账银行卡号"),
     *                              @SWG\Property(property="trans_amt", type="integer", description="提现金额（分）"),
     *                              @SWG\Property(property="distributor_name", type="string", description="店铺名称）"),
     *                              @SWG\Property(property="login_name", type="string", description="操作人"),
     *                              @SWG\Property(property="cash_status", type="string", description="取现状态 0未提交 1已提交 2取现成功 3取现失败"),
     *                              @SWG\Property(property="resp_desc", type="string", description="备注"),
     *                           )
     *                       ),
     *                    ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function getList(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $cash_status = $request->input('cash_status', '');
        $order_id = $request->input('order_id', 0);
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $page = $request->input('page', 1);
        $page_size = $request->input('page_size', 20);
        //明细记录
        $params = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $rules = [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ];

        $validator = app('validator')->make($params, $rules, [
            'start_date.required' => '请选择对应的日期范围',
            'start_date.date_format' => '日期格式有误',
            'end_date.required' => '请选择对应的日期范围',
            'end_date.date_format' => '日期格式有误',
        ]);

        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        $filter['company_id'] = $company_id;
        $filter['created_at|gte'] = $params['start_date'] . ' 00:00:00';
        $filter['created_at|lte'] = $params['end_date'] . ' 23:59:59';

        if ($request->get('distributor_id', 0)) {
            $filter['distributor_id'] = $request->get('distributor_id');
        }

        if ($order_id) {
            $filter['order_id'] = $order_id;
        }

        if ($cash_status) {
            switch ($cash_status) {
                case 1:
                    $filter['cash_status'] = [0, 1];
                    break;
                default:
                    $filter['cash_status'] = $cash_status;
                    break;
            }
        }

        $hfpayCashRecordService = new HfpayCashRecordService();
        $totle_result = $hfpayCashRecordService->total($filter);
        $cash_result = $hfpayCashRecordService->lists($filter, $page, $page_size);

        $result = [
            'total' => $totle_result,
            'list' => [
                'total_count' => $cash_result['total_count'],
                'data' => $cash_result['list']
            ]
        ];
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/hfpay/withdraw",
     *     summary="店铺提现",
     *     tags={"汇付天下"},
     *     description="店铺提现",
     *     operationId="company",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="withdrawal_amount", in="query", type="string", description="提现金额", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             required={"data"},
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    required={"trans_amt", "resp_desc"},
     *                    @SWG\Property(property="trans_amt", type="integer", description="提现金额（分）"),
     *                    @SWG\Property(property="resp_desc", type="integer", description="汇付接口返回码描述"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function withdraw(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $withdrawal_amount = $request->input('withdrawal_amount');

        $tenHourLater = strtotime("+ 10 hour", strtotime(date('Y-m-d')));
        if (time() < $tenHourLater) {
            throw new ResourceException('提现操作请在10:00:00-23:59:59进行');
        }

        $distributor_id = null;
        if ($request->get('distributor_id', null)) {
            $distributor_id = $request->get('distributor_id');
        }

        $params = [
            'distributor_id' => $distributor_id,
            'withdrawal_amount' => $withdrawal_amount,
        ];

        $rules = [
            'distributor_id' => 'required',
            'withdrawal_amount' => ['required', 'regex:/^([1-9]\d*(\.\d*[1-9][0-9])?)|(0\.\d*[1-9][0-9])|(0\.\d*[1-9])$/'],
        ];

        $validator = app('validator')->make($params, $rules, [
            'distributor_id.required' => '请选择店铺',
            'withdrawal_amount.required' => '请输入提现金额',
            'withdrawal_amount.regex' => '提现金额不低于0.01元的数字',
        ]);

        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        if (bcmul($withdrawal_amount, 100) < 1) {
            throw new ResourceException('提现金额不低于0.01元的数字');
        }
        $params['company_id'] = $company_id;
        $params['operator_id'] = app('auth')->user()->get('operator_id');
        $hfpayCashRecordService = new HfpayCashRecordService();
        $result = $hfpayCashRecordService->withdraw($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/hfpay/withdraw/exportData",
     *     summary="提现记录导出",
     *     tags={"汇付天下"},
     *     description="提现记录导出",
     *     operationId="company",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="distributor_id", in="query", type="string", description="店铺ID", required=false),
     *     @SWG\Parameter(name="start_date", in="query", type="string", description="开始日期 Y-m-d", required=true),
     *     @SWG\Parameter(name="end_date", in="query", type="string", description="结束日期 Y-m-d", required=true),
     *     @SWG\Parameter(name="cash_status", in="query", type="string", description="提现状态 1提现中 2提现成功 3提现失败", required=false),
     *     @SWG\Parameter(name="order_id", in="query", type="integer", description="订单id", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="status", type="string", description="导出状态"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function exportData(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $cash_status = $request->input('cash_status', '');
        $order_id = $request->input('order_id', 0);
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        //明细记录
        $params = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $rules = [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ];

        $validator = app('validator')->make($params, $rules, [
            'start_date.required' => '请选择对应的日期范围',
            'start_date.date_format' => '日期格式有误',
            'end_date.required' => '请选择对应的日期范围',
            'end_date.date_format' => '日期格式有误',
        ]);

        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        $filter['company_id'] = $company_id;
        $filter['created_at|gte'] = $params['start_date'] . ' 00:00:00';
        $filter['created_at|lte'] = $params['end_date'] . ' 23:59:59';

        if ($request->get('distributor_id', 0)) {
            $filter['distributor_id'] = $request->get('distributor_id');
        }

        if ($order_id) {
            $filter['order_id'] = $order_id;
        }

        if ($cash_status) {
            switch ($cash_status) {
                case 1:
                    $filter['cash_status'] = [0, 1];
                    break;
                default:
                    $filter['cash_status'] = $cash_status;
                    break;
            }
        }
        $type = 'hfpay_withdraw_record';
        $operator_id = app('auth')->user()->get('operator_id');
        $gotoJob = (new ExportFileJob($type, $company_id, $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;

        return response()->json($result);
    }
}
