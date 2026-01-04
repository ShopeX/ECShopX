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

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Jobs\ExportFileJob;
use HfPayBundle\Services\HfpayCompanyDayStatisticsService;
use HfPayBundle\Services\HfpayDistributorStatisticsDayService;
use HfPayBundle\Services\HfpayDistributorTransactionStatisticsService;
use HfPayBundle\Services\HfpayStatisticsService;
use Illuminate\Http\Request;

class HfpayStatistics extends Controller
{
    /**
     * @SWG\Get(
     *     path="/hfpay/statistics/distributor",
     *     summary="店铺分账交易统计",
     *     tags={"汇付天下"},
     *     description="店铺分账交易统计",
     *     operationId="distributor",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Property(property="distributor_id", type="integer", description="店铺id", required=true),
     *     @SWG\Property(property="order_id", type="integer", description="订单id"),
     *     @SWG\Property(property="start_date", type="string", description="导出数据开始日期", required=true),
     *     @SWG\Property(property="end_date", type="string", description="导出数据结束日期", required=true),
     *     @SWG\Property(property="page", type="integer", description="页码", required=false),
     *     @SWG\Property(property="page_size", type="integer", description="每页记录条数", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="totle", type="object", description="汇总数据",
     *                        @SWG\Property(property="income", type="integer", description="总计收入（元）"),
     *                        @SWG\Property(property="disburse", type="integer", description="总计支出（元）"),
     *                        @SWG\Property(property="withdrawal", type="integer", description="总计提现（元）"),
     *                        @SWG\Property(property="refund", type="integer", description="合计退款（元）"),
     *                        @SWG\Property(property="balance", type="integer", description="余额（元）"),
     *                        @SWG\Property(property="withdrawal_balance", type="integer", description="可提现余额（元）"),
     *                        @SWG\Property(property="unsettled_funds", type="integer", description="未结算资金（元）"),
     *                        @SWG\Property(property="settlement_funds", type="integer", description="已结算资金（元）"),
     *                    ),
     *                    @SWG\Property(property="list", type="object", description="明细数据",
     *                       @SWG\Property(property="total_count", type="integer", description="总记录条数"),
     *                       @SWG\Property(property="data", type="array", description="数据集合",
     *                           @SWG\Items(
     *                              @SWG\Property(property="trade_time", type="string", description="交易时间"),
     *                              @SWG\Property(property="order_id", type="string", description="订单id"),
     *                              @SWG\Property(property="fin_type", type="string", description="交易类型"),
     *                              @SWG\Property(property="income", type="integer", description="收入金额"),
     *                              @SWG\Property(property="outcome", type="integer", description="支出金额"),
     *                           )
     *                       ),
     *                    ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function distributor(Request $request)
    {
        // ShopEx EcShopX Core Module
        $company_id = app('auth')->user()->get('company_id');
        $order_id = $request->input('order_id');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $page = $request->input('page', 1);
        $page_size = $request->input('page_size', 20);
        //明细记录
        $params = [
            'company_id' => $company_id,
            'order_id' => $order_id,
            'start_date' => date('Y-m-d', strtotime($start_date)) . ' 00:00:00',
            'end_date' => date('Y-m-d', strtotime($end_date)) . ' 23:59:59',
            'page' => $page,
            'page_size' => $page_size,
        ];
        $rules = [
            'start_date' => ['required', '请选择对应的日期范围'],
            'end_date' => ['required', '请选择对应的日期范围'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        //汇总记录
        $params = [
            'company_id' => $company_id,
            // 'start_date'     => $start_date,
            // 'end_date'       => $end_date,
        ];
        if ($request->get('distributor_id', 0)) {
            $params['distributor_id'] = $request->get('distributor_id');
        }

        $hfpay_distributor_day_service = new HfpayDistributorStatisticsDayService();
        $totle_result = $hfpay_distributor_day_service->count($params);

        $result = [
            'totle' => $totle_result,
            'list' => [
                'total_count' => 0,
                'data' => []
            ]
        ];
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/hfpay/statistics/company",
     *     summary="交易统计",
     *     tags={"汇付天下"},
     *     description="交易统计",
     *     operationId="company",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="start_date", in="query", type="string", description="数据开始日期", required=true),
     *     @SWG\Parameter(name="end_date", in="query", type="string", description="数据结束日期", required=true),
     *     @SWG\Parameter(name="distributor_id", in="query", type="integer", description="店铺id", required=false),
     *     @SWG\Parameter(name="page", in="query", type="integer", description="页码", required=false),
     *     @SWG\Parameter(name="page_size", in="query", type="integer", description="每页记录条数", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="totle", type="object", description="汇总数据",
     *                        @SWG\Property(property="income", type="integer", description="总计收入（元）"),
     *                        @SWG\Property(property="disburse", type="integer", description="总计支出（元）"),
     *                        @SWG\Property(property="withdrawal", type="integer", description="总计提现（元）"),
     *                        @SWG\Property(property="refund", type="integer", description="合计退款（元）"),
     *                        @SWG\Property(property="balance", type="integer", description="余额（元）"),
     *                        @SWG\Property(property="withdrawal_balance", type="integer", description="可提现余额（元）"),
     *                        @SWG\Property(property="unsettled_funds", type="integer", description="未结算资金（元）"),
     *                        @SWG\Property(property="settlement_funds", type="integer", description="已结算资金（元）"),
     *                    ),
     *                    @SWG\Property(property="list", type="object", description="明细数据",
     *                       @SWG\Property(property="total_count", type="integer", description="总记录条数"),
     *                       @SWG\Property(property="data", type="array", description="数据集合",
     *                           @SWG\Items(
     *                              @SWG\Property(property="distributor_name", type="string", description="店铺名称"),
     *                              @SWG\Property(property="withdrawal_balance", type="string", description="可提现金额（分）"),
     *                              @SWG\Property(property="order_count", type="integer", description="交易总笔数"),
     *                              @SWG\Property(property="order_total_fee", type="integer", description="总计交易金额(含退款)（分）"),
     *                              @SWG\Property(property="order_refund_count", type="integer", description="已退款总笔数"),
     *                              @SWG\Property(property="order_refund_total_fee", type="integer", description="退款总金额(退款成功)（分）"),
     *                              @SWG\Property(property="order_refunding_count", type="integer", description="在退总笔数"),
     *                              @SWG\Property(property="order_refunding_total_fee", type="integer", description="在退总金额(退款中)（分）"),
     *                              @SWG\Property(property="order_profit_sharing_charge", type="integer", description="已结算手续费总额（分）"),
     *                              @SWG\Property(property="order_un_profit_sharing_charge", type="integer", description="未结算手续费总额（分）"),
     *                           )
     *                       ),
     *                    ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function company(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $page = $request->input('page', 1);
        $page_size = $request->input('page_size', 20);

        //明细记录
        $params = [
            'company_id' => $company_id,
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
        if ($request->get('distributor_id', 0)) {
            $params['distributor_id'] = $request->get('distributor_id');
        }

        $params['start_date'] = $params['start_date'] . ' 00:00:00';
        $params['end_date'] = $params['end_date'] . ' 23:59:59';

        $service = new HfpayDistributorTransactionStatisticsService();
        $trade_record_result = $service->transactionList($params, $page, $page_size);

        //汇总记录
        $params = [
            'company_id' => $company_id,
            'type' => 2, //$type,
        ];
        $hfpay_company_day_service = new HfpayCompanyDayStatisticsService();
        $totle_result = $hfpay_company_day_service->count($params);

        $result = [
            'totle' => $totle_result,
            'list' => [
                'total_count' => $trade_record_result['total_count'],
                'data' => $trade_record_result['list']
            ]
        ];
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/hfpay/statistics/exportData",
     *     summary="导出交易报表",
     *     tags={"汇付天下"},
     *     description="导出交易报表",
     *     operationId="exportData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Property(property="distributor_id", type="integer", description="店铺id"),
     *     @SWG\Property(property="start_date", type="string", description="导出数据开始日期", required=true),
     *     @SWG\Property(property="end_date", type="string", description="导出数据结束日期", required=true),
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
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $type = 'hfpay_trade_record';

        $params = [
            'company_id' => $company_id,
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
        if ($request->get('distributor_id', 0)) {
            $params['distributor_id'] = $request->get('distributor_id');
        }

        $params['start_date'] = $params['start_date'] . ' 00:00:00';
        $params['end_date'] = $params['end_date'] . ' 23:59:59';

        $operator_id = app('auth')->user()->get('operator_id');
        $gotoJob = (new ExportFileJob($type, $company_id, $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;

        return response()->json($result);
    }

    /**
     * @SWG\Get(
     *     path="/hfpay/statistics/orderList",
     *     summary="分账数据列表",
     *     tags={"汇付天下"},
     *     description="分账数据列表",
     *     operationId="company",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="distributor_id", in="query", type="string", description="店铺ID", required=false),
     *     @SWG\Parameter(name="start_date", in="query", type="string", description="开始日期 Y-m-d", required=true),
     *     @SWG\Parameter(name="end_date", in="query", type="string", description="结束日期 Y-m-d", required=true),
     *     @SWG\Parameter(name="profitsharing_status", in="query", type="string", description="结算状态 0全部 1未结算 2已结算", required=false),
     *     @SWG\Parameter(name="order_id", in="query", type="integer", description="订单id", required=false),
     *     @SWG\Parameter(name="app_pay_type", in="query", type="string", description="支付类型 支付类型 01：微信正扫 02：支付宝正扫 03：银联正扫 05：微信公众号 06：支付宝小程序/生活号 07：微信小程序 08：微信正扫(直连) 09：微信app支付(直连) 10：银联app支付 11：apple支付 12：微信H5支付(直连) 13：支付宝app支付(直连)", required=false),
     *     @SWG\Parameter(name="order_status", in="query", type="integer", description="订单状态 refunding：退款中， pay：支付成功，refundsuccess：退款成功，refundfail：退款失败", required=false),
     *     @SWG\Parameter(name="page", in="query", type="integer", description="页码", required=false),
     *     @SWG\Parameter(name="page_size", in="query", type="integer", description="每页记录条数", required=false),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="total", type="object", description="汇总数据",
     *                        @SWG\Property(property="order_count", type="integer", description="交易总笔数"),
     *                        @SWG\Property(property="order_total_fee", type="integer", description="总计交易金额(含退款)（元）"),
     *                        @SWG\Property(property="order_refund_count", type="integer", description="已退款总笔数"),
     *                        @SWG\Property(property="order_refund_total_fee", type="integer", description="退款总金额(退款成功)（元）"),
     *                        @SWG\Property(property="order_refunding_count", type="integer", description="在退总笔数"),
     *                        @SWG\Property(property="order_refunding_total_fee", type="integer", description="在退总金额(退款中)（元）"),
     *                        @SWG\Property(property="order_profit_sharing_charge", type="integer", description="已结算手续费总额（元）"),
     *                        @SWG\Property(property="order_un_profit_sharing_charge", type="integer", description="未结算手续费总额（元）"),
     *                    ),
     *                    @SWG\Property(property="list", type="object", description="明细数据",
     *                       @SWG\Property(property="total_count", type="integer", description="总记录条数"),
     *                       @SWG\Property(property="data", type="array", description="数据集合",
     *                           @SWG\Items(
     *                              @SWG\Property(property="create_time", type="string", description="订单创建日期"),
     *                              @SWG\Property(property="trade_id", type="string", description="交易单号"),
     *                              @SWG\Property(property="order_id", type="string", description="订单id"),
     *                              @SWG\Property(property="app_pay_type_desc", type="string", description="支付类型"),
     *                              @SWG\Property(property="profitsharing_status", type="string", description="结算状态 1未结算 2已结算"),
     *                              @SWG\Property(property="total_fee", type="integer", description="交易金额（分）"),
     *                              @SWG\Property(property="charge", type="integer", description="平台手续费（分）"),
     *                              @SWG\Property(property="distributor_name", type="string", description="店铺名称"),
     *                              @SWG\Property(property="refund_fee", type="integer", description="退款金额（分）"),
     *                              @SWG\Property(property="order_status", type="string", description="订单状态 refunding：退款中，pay：支付成功，refundsuccess：退款成功，refundfail：退款失败"),
     *                           )
     *                       ),
     *                    ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function orderList(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $profitsharing_status = $request->input('profitsharing_status', 0);
        $order_status = $request->input('order_status', '');
        $order_id = $request->input('order_id', 0);
        $app_pay_type = $request->input('app_pay_type', '');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $page = $request->input('page', 1);
        $page_size = $request->input('page_size', 20);
        //明细记录
        $params = [
            'company_id' => $company_id,
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

        $params['start_date'] = strtotime($params['start_date'] . ' 00:00:00');
        $params['end_date'] = strtotime($params['end_date'] . ' 23:59:59');

        if ($request->get('distributor_id', 0)) {
            $params['distributor_id'] = $request->get('distributor_id');
        }

        if ($order_id) {
            $params['order_id'] = $order_id;
        }
        if ($app_pay_type) {
            $params['app_pay_type'] = $app_pay_type;
        }
        if ($profitsharing_status) {
            $params['profitsharing_status'] = $profitsharing_status;
        }
        if ($order_status) {
            $params['order_status'] = $order_status;
        }

        $hfpayStatisticsService = new HfpayStatisticsService();
        $totle_result = $hfpayStatisticsService->count($company_id, $params);
        $order_result = $hfpayStatisticsService->getOrderList($company_id, $params, $page, $page_size);

        $result = [
            'total' => $totle_result,
            'list' => [
                'total_count' => $order_result['total_count'],
                'data' => $order_result['list']
            ]
        ];
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/hfpay/statistics/orderDetail/{orderId}",
     *     summary="分账数据详情",
     *     tags={"汇付天下"},
     *     description="分账数据详情",
     *     operationId="company",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="orderId", in="path", description="订单ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                        @SWG\Property(property="create_time", type="string", description="订单创建日期"),
     *                        @SWG\Property(property="trade_id", type="string", description="交易单号"),
     *                        @SWG\Property(property="pay_time", type="string", description="支付日期"),
     *                              @SWG\Property(property="order_id", type="string", description="订单id"),
     *                              @SWG\Property(property="hf_order_id", type="string", description="分账订单id"),
     *                              @SWG\Property(property="hf_order_date", type="string", description="分账日期"),
     *                              @SWG\Property(property="app_pay_type_desc", type="string", description="支付类型"),
     *                              @SWG\Property(property="profitsharing_status", type="string", description="结算状态 1未结算 2已结算"),
     *                              @SWG\Property(property="total_fee", type="integer", description="交易金额（分）"),
     *                              @SWG\Property(property="charge", type="integer", description="平台手续费（分）"),
     *                              @SWG\Property(property="profitsharing_rate", type="integer", description="平台服务费率"),
     *                              @SWG\Property(property="distributor_name", type="string", description="店铺名称"),
     *                              @SWG\Property(property="refund_fee", type="integer", description="退款金额（分）"),
     *                              @SWG\Property(property="balance", type="integer", description="剩余金额（分）"),
     *                              @SWG\Property(property="order_status", type="string", description="订单状态 refunding：退款中，pay：支付成功，refundsuccess：退款成功，refundfail：退款失败"),
     *                              @SWG\Property(property="refund_list", type="array", description="退款列表",
     *                                  @SWG\Items(
     *                                       @SWG\Property(property="refund_bn", type="string", description="退款单号"),
     *                                       @SWG\Property(property="refund_id", type="string", description="汇付退款单号"),
     *                                       @SWG\Property(property="refund_fee", type="string", description="退款金额（分）"),
     *                                       @SWG\Property(property="distributor_name", type="string", description="退款客户名称"),
     *                                       @SWG\Property(property="refund_status", type="string", description="退款状态 READY：未审核 SUCCESS：退款成功  CANCEL：撤销退款 CHANGE：退款异常 REFUNDCLOSE：退款关闭 PROCESSING：退款处理中"),
     *                                  )
     *                              ),
     *                              @SWG\Property(property="profit_share_list", type="array", description="分账信息",
     *                                  @SWG\Items(
     *                                       @SWG\Property(property="created_at", type="string", description="日期"),
     *                                       @SWG\Property(property="distributor_name", type="string", description="分账客户名称"),
     *                                       @SWG\Property(property="total_fee", type="string", description="分账金额（分）"),
     *                                  )
     *                              ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/HfPayErrorRespones") ) )
     * )
     */
    public function orderDetail($orderId)
    {
        $company_id = app('auth')->user()->get('company_id');

        $hfpayStatisticsService = new HfpayStatisticsService();
        $info = $hfpayStatisticsService->getOrderDetail($company_id, $orderId);

        return $this->response->array($info);
    }


    /**
     * @SWG\Get(
     *     path="/hfpay/statistics/orderExportData",
     *     summary="分账数据列表导出",
     *     tags={"汇付天下"},
     *     description="分账数据列表导出",
     *     operationId="company",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="distributor_id", in="query", type="string", description="店铺ID", required=false),
     *     @SWG\Parameter(name="start_date", in="query", type="string", description="开始日期 Y-m-d", required=true),
     *     @SWG\Parameter(name="end_date", in="query", type="string", description="结束日期 Y-m-d", required=true),
     *     @SWG\Parameter(name="profitsharing_status", in="query", type="string", description="结算状态 0全部 1未结算 2已结算", required=false),
     *     @SWG\Parameter(name="order_id", in="query", type="integer", description="订单id", required=false),
     *     @SWG\Parameter(name="app_pay_type", in="query", type="string", description="支付类型 支付类型 01：微信正扫 02：支付宝正扫 03：银联正扫 05：微信公众号 06：支付宝小程序/生活号 07：微信小程序 08：微信正扫(直连) 09：微信app支付(直连) 10：银联app支付 11：apple支付 12：微信H5支付(直连) 13：支付宝app支付(直连)", required=false),
     *     @SWG\Parameter(name="order_status", in="query", type="integer", description="订单状态 refunding：退款中， pay：支付成功，refundsuccess：退款成功，refundfail：退款失败", required=false),
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
    public function orderExportData(Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');
        $profitsharing_status = $request->input('profitsharing_status', 0);
        $order_status = $request->input('order_status', '');
        $order_id = $request->input('order_id', 0);
        $app_pay_type = $request->input('app_pay_type', '');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $type = 'hfpay_order_record';

        //明细记录
        $params = [
            'company_id' => $company_id,
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
        $params['company_id'] = $company_id;

        $params['start_date'] = strtotime($params['start_date'] . ' 00:00:00');
        $params['end_date'] = strtotime($params['end_date'] . ' 23:59:59');

        if ($request->get('distributor_id', 0)) {
            $params['distributor_id'] = $request->get('distributor_id');
        }

        if ($order_id) {
            $params['order_id'] = $order_id;
        }
        if ($app_pay_type) {
            $params['app_pay_type'] = $app_pay_type;
        }
        if ($profitsharing_status) {
            $params['profitsharing_status'] = $profitsharing_status;
        }
        if ($order_status) {
            $params['order_status'] = $order_status;
        }

        $operator_id = app('auth')->user()->get('operator_id');
        $gotoJob = (new ExportFileJob($type, $company_id, $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;

        return response()->json($result);
    }
}
