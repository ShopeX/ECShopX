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

namespace HfPayBundle\Services;

use HfPayBundle\Entities\HfpayCompanyStatisticsDay;
use HfPayBundle\Entities\HfpayEnterapply;
use HfPayBundle\Entities\HfpayLedgerConfig;
use HfPayBundle\Entities\HfpayTradeRecord;
use HfPayBundle\Traits\HfpayStatisticsTrait;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Services\OrderProfitSharingService;
use PaymentBundle\Services\Payments\HfPayService;

class HfpayCompanyDayStatisticsService extends HfpayStatisticsBaseService
{
    use HfpayStatisticsTrait;

    public $hfpayEnterapplyRepository;
    public $hfpayTradeRecordRepository;
    public $hfpayCompanyStatisticsDayRepository;
    public $hfpayLedgerConfigRepository;
    public $normalOrdersRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->hfpayEnterapplyRepository = app('registry')->getManager('default')->getRepository(HfpayEnterapply::class);
        $this->hfpayTradeRecordRepository = app('registry')->getManager('default')->getRepository(HfpayTradeRecord::class);
        $this->hfpayCompanyStatisticsDayRepository = app('registry')->getManager('default')->getRepository(HfpayCompanyStatisticsDay::class);
        $this->hfpayLedgerConfigRepository = app('registry')->getManager('default')->getRepository(HfpayLedgerConfig::class);
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
    }

    /**
     * 平台分账统计
     */
    public function count($params)
    {
        $filter['company_id'] = $params['company_id'];
        $filter['type'] = $params['type'];
        // $filter['date|gte']   = strtotime($params['start_date']);
        // $filter['date|lte']   = strtotime($params['end_date']);

        // $cols   = 'sum(income) as income, sum(disburse) as disburse, sum(withdrawal) as withdrawal, sum(refund) as refund, sum(balance) as balance, sum(withdrawal_balance) as withdrawal_balance, sum(unsettled_funds) as unsettled_funds';
        // $result = $this->hfpayCompanyStatisticsDayRepository->sum($filter, $cols);

        $statisticsInfo = $this->hfpayCompanyStatisticsDayRepository->getLists($filter, '*', 1, 1, ['created_at' => 'DESC']);
        $statisticsInfo = $statisticsInfo[0] ?? [];

        $service = new HfPayService();
        $hfpay_setting_info = $service->getPaymentSetting($params['company_id']);

        $withdrawalBalance = 0;
        if ($hfpay_setting_info) {
            //查询汇付账户余额
            $qryParams = [
                'user_cust_id' => $hfpay_setting_info['mer_cust_id'],
                'acct_id' => $hfpay_setting_info['acct_id']
            ];
            $service = new AcouService($params['company_id']);
            $qry_result = $service->qry001($qryParams);

            if ($qry_result['resp_code'] == 'C00000') {
                $withdrawalBalance = bcmul($qry_result['balance'], 100); //余额单位元转换为分
            }
        }

        //今日数据
        //已结算资金
        $orderProfitSharingService = new OrderProfitSharingService();
        $start_date = strtotime(date('Y-m-d 00:00:00', time()));
        $orderProfitSharingFilter = [
            'company_id' => $params['company_id'],
            'pay_type' => 'hfpay',
            'distributor_id' => 0,
            'status' => 1,
            'hf_order_date|gte' => date('Ymd', $start_date),//分账日期
        ];
        $settlement = $orderProfitSharingService->getProfitShareCapital($orderProfitSharingFilter);

        //1、今日收入(包含退款的)
        $orderFilter = [
            'company_id' => $params['company_id'],
            'pay_type' => 'hfpay',
            'is_profitsharing' => 2,
            // 'pay_status' => 'PAYED',
            'trade_state' => 'SUCCESS',
            'time_expire|gte' => $start_date,
        ];
        $todayIncome = $this->income($orderFilter)[0];

        //2、今日退款
        $orderFilter = [
            'company_id' => $params['company_id'],
            'pay_type' => 'hfpay',
            'is_profitsharing' => 2,
            // 'pay_status' => 'PAYED',
            'refund_status' => 'SUCCESS',
            'refund_success_time|gte' => $start_date,
        ];
        $todayRefund = $this->refund($orderFilter)[0];

        $income = ($statisticsInfo['income'] ?? 0) + $todayIncome; //总收入 = 今日之前总收入 + 今日收入
        $refund = ($statisticsInfo['refund'] ?? 0) + $todayRefund; //总退款 = 今日之前退款 + 今日退款
        $todayIncome = $todayIncome - $todayRefund;  //今日收入 = 今日总收入 - 退款
        $unsettled_funds = ($statisticsInfo['unsettled_funds'] ?? 0) + $todayIncome - $settlement; //总未结算资金 = 今日之前未结算资金 + 今日收入 - 今日已结算资金
        $settlement_funds = ($statisticsInfo['settlement_funds'] ?? 0) + $settlement; //总已结算资金 = 今日之前已结算资金 + 今日已结算资金

        $data = [
            'income' => bcdiv($income, 100, 2),
            //'disburse'           => bcdiv($statisticsInfo['disburse'] ?? 0, 100, 2),
            // 'withdrawal'         => bcdiv($statisticsInfo['withdrawal'] ?? 0, 100, 2),
            'refund' => bcdiv($refund, 100, 2),
            //'balance'            => bcdiv($statisticsInfo['balance'] ?? 0, 100, 2),
            'withdrawal_balance' => bcdiv($withdrawalBalance ?? 0, 100, 2),
            'unsettled_funds' => bcdiv($unsettled_funds, 100, 2),
            'settlement_funds' => bcdiv($settlement_funds, 100, 2),
        ];

        return $data;
    }

    /**
     * 平台分账数据跑批
     */
    public function statistics()
    {
        //前一天的时间
        $date = date('Y-m-d', strtotime('-1 days'));

        $filter = [];
        $count = $this->hfpayLedgerConfigRepository->count($filter);
        if ($count < 1) {
            return true;
        }

        $page_size = 500; //单次查询记录数
        //数据大于500条分页处理
        if ($count > $page_size) {
            $page_num = $count / $page_size;
            for ($page = 1; $page < $page_num; $page++) {
                $data = $this->hfpayLedgerConfigRepository->getLists($filter, 'company_id', $page, $page_size);
                // $this->all($data, $date);
                $this->company($data, $date);
                // $this->distributor($data, $date);
            }
        } else {
            $data = $this->hfpayLedgerConfigRepository->getLists($filter, 'company_id', 1, $page_size);
            // $this->all($data, $date);
            $this->company($data, $date);
            // $this->distributor($data, $date);
        }
    }

    /**
     * 全部数据
     */
    private function all($list, $date)
    {
        $start_date = date('Y-m-d 00:00:00', strtotime($date));
        $end_date = date('Y-m-d 23:59:59', strtotime($date));
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);

        if (empty($list)) {
            return true;
        }
        foreach ($list as $k => $v) {
            $company_id = $v['company_id'];

            ////1、总计收入：科目ID100的所有数据
            $income_filter = [
                'company_id' => $company_id,
                'trade_time|gte' => $start_time,
                'trade_time|lte' => $end_time,
                'fin_type' => 100,
                'trade_type' => 1,
            ];
            $income_result = $this->hfpayTradeRecordRepository->sum($income_filter, 'sum(income) as income');
            $income = isset($income_result['income']) ? $income_result['income'] : 0;

            //2、总计提现：科目ID为600，交易类型为0的数据之和
            $withdrawal_filter = [
                'company_id' => $company_id,
                'trade_time|gte' => $start_time,
                'trade_time|lte' => $end_time,
                'fin_type' => 600,
                'trade_type' => 0
            ];
            $withdrawal_result = $this->hfpayTradeRecordRepository->sum($withdrawal_filter, 'sum(outcome) as outcome');
            $withdrawal = isset($withdrawal_result['outcome']) ? $withdrawal_result['outcome'] : 0;

            //3、合计退款：科目ID为610、620，交易类型为0的合计
            $refund_filter = [
                'company_id' => $company_id,
                'trade_time|gte' => $start_time,
                'trade_time|lte' => $end_time,
                'fin_type' => [610, 620],
                'trade_type' => 0
            ];
            $refund_result = $this->hfpayTradeRecordRepository->sum($refund_filter, 'sum(outcome) as outcome');
            $refund = isset($refund_result['outcome']) ? $refund_result['outcome'] : 0;

            //4、余额=总计收入-总计提现-合计退款
            $balance = $income - $withdrawal - $refund;

            //5、可提现余额=科目ID100，600，610、620的余额
            $withdrawal_balance_filter = [
                'company_id' => $company_id,
                'trade_time|gte' => $start_time,
                'trade_time|lte' => $end_time,
                'fin_type' => [100, 600, 610, 620],
                'trade_type' => 1
            ];
            $withdrawal_balance_result = $this->hfpayTradeRecordRepository->sum($withdrawal_balance_filter, 'sum(income) as income');
            $withdrawal_balance = isset($withdrawal_balance_result['income']) ? $withdrawal_balance_result['income'] : 0;

            //6、未结算资金=余额=可提现余额
            $unsettled_funds = $balance - $withdrawal_balance;

            $params = [
                'company_id' => $company_id,
                'type' => 1,
                'date' => strtotime($date),
                'income' => $income,
                'disburse' => 0,
                'withdrawal' => $withdrawal,
                'refund' => $refund,
                'balance' => $balance,
                'withdrawal_balance' => $withdrawal_balance,
                'unsettled_funds' => $unsettled_funds,
            ];
            $this->hfpayCompanyStatisticsDayRepository->create($params);
        }

        return true;
    }

    /**
     * 平台数据
     */
    // private function company($list, $date)
    // {
    //     $start_date = date('Y-m-d 00:00:00', strtotime($date));
    //     $end_date   = date('Y-m-d 23:59:59', strtotime($date));
    //     $start_time = strtotime($start_date);
    //     $end_time   = strtotime($end_date);

    //     if (empty($list)) {
    //         return true;
    //     }

    //     foreach ($list as $k => $v) {
    //         $company_id = $v['company_id'];

    //         //1、总计收入：科目ID100所有数据-科目id200的所有支出再加科目id为300的收入-退款金额
    //         // $company_filter = [
    //         //     'company_id'     => $company_id,
    //         //     'trade_time|gte' => $start_time,
    //         //     'trade_time|lte' => $end_time,
    //         //     'fin_type'       => 100,
    //         // ];
    //         // $company_result = $this->hfpayTradeRecordRepository->sum($company_filter, 'sum(income) as income');
    //         // $company_fee    = isset($company_result['income']) ? $company_result['income'] : 0;

    //         // $distributor_filter = [
    //         //     'company_id'     => $company_id,
    //         //     'trade_time|gte' => $start_time,
    //         //     'trade_time|lte' => $end_time,
    //         //     'fin_type'       => 200,
    //         // ];
    //         // $distributor_result = $this->hfpayTradeRecordRepository->sum($distributor_filter, 'sum(outcome) as outcome');
    //         // $distributor_fee    = isset($distributor_result['outcome']) ? $distributor_result['outcome'] : 0;

    //         // $rate_filter = [
    //         //     'company_id'     => $company_id,
    //         //     'trade_time|gte' => $start_time,
    //         //     'trade_time|lte' => $end_time,
    //         //     'fin_type'       => 300,
    //         // ];
    //         // $rate_result = $this->hfpayTradeRecordRepository->sum($rate_filter, 'sum(income) as income');
    //         // $rate_fee    = isset($rate_result['income']) ? $rate_result['income'] : 0;
    //         // $income      = $company_fee - $distributor_fee + $rate_fee;
    //         $orderFilter = [
    //             'company_id'     => $company_id,
    //             'pay_type'       => 'hfpay',
    //             'is_profitsharing' => 2,
    //             'pay_status' => 'PAYED',
    //             'order_status|notIn' => ['CANCEL'],
    //             'cancel_status|notIn' => ['SUCCESS'],
    //         ];
    //         $orderList = $this->normalOrdersRepository->getList($orderFilter);

    //         $income = 0;
    //         foreach ($orderList as $v) {
    //             $total_fee          = $v['total_fee'];
    //             $profitsharing_rate = $v['profitsharing_rate'];
    //             $fee_amt = bcdiv(bcmul($total_fee, $profitsharing_rate), 100);
    //             if ($fee_amt >= 1) {
    //                 $income = $income + $fee_amt;
    //             }
    //         }

    //         //2、总计支出：科目ID为410-411的支出
    //         $disburse_income_filter = [
    //             'company_id'     => $company_id,
    //             'trade_time|gte' => $start_time,
    //             'trade_time|lte' => $end_time,
    //             'fin_type'       => 410,
    //             'trade_type'     => 1
    //         ];
    //         $disburse_income_result = $this->hfpayTradeRecordRepository->sum($disburse_income_filter, 'sum(income) as income');
    //         $disburse_income        = isset($disburse_income_result['income']) ? $disburse_income_result['income'] : 0;

    //         $disburse_outcome_filter = [
    //             'company_id'     => $company_id,
    //             'trade_time|gte' => $start_time,
    //             'trade_time|lte' => $end_time,
    //             'fin_type'       => 411,
    //             'trade_type'     => 0
    //         ];
    //         $disburse_outcome_result = $this->hfpayTradeRecordRepository->sum($disburse_outcome_filter, 'sum(outcome) as outcome');
    //         $disburse_outcome        = isset($disburse_outcome_result['outcome']) ? $disburse_outcome_result['outcome'] : 0;
    //         $disburse                = $disburse_income - $disburse_outcome;

    //         //3、合计提现，参与分账店铺科目ID为500，交易类型为0的合计支出
    //         //总店铺提现成功金额总和
    //         $withdrawal_filter = [
    //             'company_id'     => $company_id,
    //             'trade_time|gte' => $start_time,
    //             'trade_time|lte' => $end_time,
    //             'fin_type'       => 500,
    //             'trade_type'     => 0,
    //         ];
    //         $withdrawal_result = $this->hfpayTradeRecordRepository->sum($withdrawal_filter, 'sum(outcome) as outcome');
    //         $withdrawal        = isset($withdrawal_result['outcome']) ? $withdrawal_result['outcome'] : 0;


    //         //4、合计退款=科目ID620
    //         // $refund_filter = [
    //         //     'company_id'     => $company_id,
    //         //     'trade_time|gte' => $start_time,
    //         //     'trade_time|lte' => $end_time,
    //         //     'fin_type'       => 620,
    //         //     'trade_type'     => 0
    //         // ];
    //         // $refund_result = $this->hfpayTradeRecordRepository->sum($refund_filter, 'sum(outcome) as outcome');
    //         // $refund        = isset($refund_result['outcome']) ? $refund_result['outcome'] : 0;
    //         //4、总退款金额=总订单退款分账金额
    //         $orderFilter = [
    //             'company_id'     => $company_id,
    //             'pay_type'       => 'hfpay',
    //             'is_profitsharing' => 2,
    //             'pay_status' => 'PAYED',
    //             'order_status' => ['CANCEL'],
    //             'cancel_status' => ['SUCCESS'],
    //         ];
    //         $orderList = $this->normalOrdersRepository->getList($orderFilter);

    //         $refund = 0;
    //         foreach ($orderList as $v) {
    //             $total_fee          = $v['total_fee'];
    //             $profitsharing_rate = $v['profitsharing_rate'];
    //             $fee_amt = bcdiv(bcmul($total_fee, $profitsharing_rate), 100);
    //             if ($fee_amt >= 1) {
    //                 $refund = $refund + $fee_amt;
    //             }
    //         }

    //         // 5、未结算资金=总订单分账金额（未分账）
    //         $orderFilter = [
    //             'company_id'     => $company_id,
    //             'pay_type'       => 'hfpay',
    //             'is_profitsharing' => 2,
    //             'profitsharing_status' => 1,
    //             'pay_status' => 'PAYED',
    //             'order_status|notIn' => ['CANCEL'],
    //             'cancel_status|notIn' => ['SUCCESS'],
    //         ];
    //         $unsettled_funds = $this->normalOrdersRepository->sum($orderFilter, 'total_fee');

    //         $params = [
    //             'company_id'         => $company_id,
    //             'type'               => 2,
    //             'date'               => strtotime($date),
    //             'income'             => $income,
    //             'disburse'           => $disburse,
    //             'refund'             => $refund,
    //             'withdrawal'         => $withdrawal,
    //             'balance'            => 0,
    //             'withdrawal_balance' => 0,
    //             'unsettled_funds'    => $unsettled_funds,
    //         ];
    //         $this->hfpayCompanyStatisticsDayRepository->create($params);
    //     }

    //     return true;
    // }

    private function company($list, $date)
    {
        $orderProfitSharingService = new OrderProfitSharingService();

        $start_date = date('Y-m-d 00:00:00', strtotime($date));
        $end_date = date('Y-m-d 23:59:59', strtotime($date));
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);

        if (empty($list)) {
            return true;
        }

        foreach ($list as $k => $v) {
            $company_id = $v['company_id'];
            //已结算资金
            $orderProfitSharingFilter = [
                'company_id' => $company_id,
                'pay_type' => 'hfpay',
                'distributor_id' => 0,
                'status' => 1,
                'hf_order_date|lte' => date('Ymd', $end_time),//分账日期
            ];
            $settlement = $orderProfitSharingService->getProfitShareCapital($orderProfitSharingFilter);

            //1、总计收入(包含退款的)
            $orderFilter = [
                'company_id' => $company_id,
                'pay_type' => 'hfpay',
                'is_profitsharing' => 2,
                // 'pay_status' => 'PAYED',
                'trade_state' => 'SUCCESS',
                'time_expire|lte' => $end_time,
            ];
            $income = $this->income($orderFilter)[0];

            //2、总退款
            $orderFilter = [
                'company_id' => $company_id,
                'pay_type' => 'hfpay',
                'is_profitsharing' => 2,
                // 'pay_status' => 'PAYED',
                'refund_status' => 'SUCCESS',
                'refund_success_time|lte' => $end_time,
            ];
            $refund = $this->refund($orderFilter)[0];

            //3、合计提现，参与分账店铺科目ID为500，交易类型为0的合计支出
            //总店铺提现成功金额总和
            // $withdrawal_filter = [
            //     'company_id'     => $company_id,
            //     'trade_time|gte' => $start_time,
            //     'trade_time|lte' => $end_time,
            //     'fin_type'       => 500,
            //     'trade_type'     => 0,
            // ];
            // $withdrawal_result = $this->hfpayTradeRecordRepository->sum($withdrawal_filter, 'sum(outcome) as outcome');
            // $withdrawal        = isset($withdrawal_result['outcome']) ? $withdrawal_result['outcome'] : 0;

            // 4、未结算资金=总收入-已结算
            $unsettled_funds = $income - $refund - $settlement;

            $params = [
                'company_id' => $company_id,
                'type' => 2,
                'date' => strtotime($date),
                'income' => $income,
                'disburse' => 0,
                'refund' => $refund,
                'withdrawal' => 0,
                'balance' => 0,
                'withdrawal_balance' => 0,
                'unsettled_funds' => $unsettled_funds,
                'settlement_funds' => $settlement,
            ];
            $this->hfpayCompanyStatisticsDayRepository->create($params);
        }

        return true;
    }

    /**
     * 非平台数据
     */
    private function distributor($list, $date)
    {
        $start_date = date('Y-m-d 00:00:00', strtotime($date));
        $end_date = date('Y-m-d 23:59:59', strtotime($date));
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);

        if (empty($list)) {
            return true;
        }

        foreach ($list as $k => $v) {
            $company_id = $v['company_id'];

            //1、总计收入：参与分账店铺科目ID为200，交易类型为1的收入总计
            $income_filter = [
                'company_id' => $company_id,
                'trade_time|gte' => $start_time,
                'trade_time|lte' => $end_time,
                'fin_type' => 200,
                'trade_type' => 1
            ];
            $income_result = $this->hfpayTradeRecordRepository->sum($income_filter, 'sum(income) as income');
            $income = isset($income_result['income']) ? $income_result['income'] : 0;

            //2、支出总计：参与分账店铺，科目类型为（300、310、400、401）的所有收入支出之和
            $disburse_filter = [
                'company_id' => $company_id,
                'trade_time|gte' => $start_time,
                'trade_time|lte' => $end_time,
                'fin_type' => [300, 310, 400, 401],
            ];
            $disburse_result = $this->hfpayTradeRecordRepository->sum($disburse_filter, 'sum(income) as income, sum(outcome) as outcome');
            $disburse_income = isset($disburse_result['income']) ? $disburse_result['income'] : 0;
            $disburse_outcome = isset($disburse_result['outcome']) ? $disburse_result['outcome'] : 0;
            $disburse = $disburse_income - $disburse_outcome;

            //3、合计提现，参与分账店铺科目ID为500，交易类型为0的合计支出
            $withdrawal_filter = [
                'company_id' => $company_id,
                'trade_time|gte' => $start_time,
                'trade_time|lte' => $end_time,
                'fin_type' => 500,
                'trade_type' => 0,
            ];
            $withdrawal_result = $this->hfpayTradeRecordRepository->sum($withdrawal_filter, 'sum(outcome) as outcome');
            $withdrawal = isset($withdrawal_result['outcome']) ? $withdrawal_result['outcome'] : 0;

            //4、合计退款：参与分账店铺科目id为600，交易类型为0的合计支出
            $refund_filter = [
                'company_id' => $company_id,
                'trade_time|gte' => $start_time,
                'trade_time|lte' => $end_time,
                'fin_type' => 600,
                'trade_type' => 0,
            ];
            $refund_result = $this->hfpayTradeRecordRepository->sum($refund_filter, 'sum(outcome) as outcome');
            $refund = isset($refund_result['outcome']) ? $refund_result['outcome'] : 0;

            //5、余额=合计收入-合计支出-合计提现-合计退款
            $balance = $income - $disburse - $withdrawal - $refund;

            //6、可提现余额=参与分账店铺已经结算的总余额
            $withdrawal_balance_filter = [
                'company_id' => $company_id,
                'trade_time|gte' => $start_time,
                'trade_time|lte' => $end_time,
                'is_clean' => 1,
            ];
            $withdrawal_balance_result = $this->hfpayTradeRecordRepository->sum($withdrawal_balance_filter, 'sum(income) as income, sum(outcome) as outcome');
            $withdrawal_balance_income = isset($withdrawal_balance_result['income']) ? $withdrawal_balance_result['income'] : 0;
            $withdrawal_balance_outcome = isset($withdrawal_balance_result['outcome']) ? $withdrawal_balance_result['outcome'] : 0;
            $withdrawal_balance = $withdrawal_balance_income - $withdrawal_balance_outcome;

            //7、未结算资金=余额-可提现余额
            $unsettled_funds = $balance - $withdrawal_balance;

            $params = [
                'company_id' => $company_id,
                'type' => 3,
                'date' => strtotime($date),
                'income' => $income,
                'disburse' => $disburse,
                'withdrawal' => $withdrawal,
                'refund' => $refund,
                'balance' => $balance,
                'withdrawal_balance' => $withdrawal_balance,
                'unsettled_funds' => $unsettled_funds,
            ];
            $this->hfpayCompanyStatisticsDayRepository->create($params);
        }

        return true;
    }
}
