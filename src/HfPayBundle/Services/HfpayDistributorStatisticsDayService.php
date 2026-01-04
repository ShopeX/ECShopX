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

use HfPayBundle\Entities\HfpayDistributorStatisticsDay;
use HfPayBundle\Entities\HfpayDistributorTransactionStatistics;
use HfPayBundle\Entities\HfpayEnterapply;
use HfPayBundle\Entities\HfpayTradeRecord;
use HfPayBundle\Traits\HfpayStatisticsTrait;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Services\OrderProfitSharingService;

class HfpayDistributorStatisticsDayService extends HfpayStatisticsBaseService
{
    use HfpayStatisticsTrait;

    public $hfpayEnterapplyRepository;
    public $hfpayTradeRecordRepository;
    public $hfpayDistributorStatisticsDayRepository;
    public $normalOrdersRepository;
    private $hfpayDistributorTransactionStatisticsRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->hfpayEnterapplyRepository = app('registry')->getManager('default')->getRepository(HfpayEnterapply::class);
        $this->hfpayTradeRecordRepository = app('registry')->getManager('default')->getRepository(HfpayTradeRecord::class);
        $this->hfpayDistributorStatisticsDayRepository = app('registry')->getManager('default')->getRepository(HfpayDistributorStatisticsDay::class);
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->hfpayDistributorTransactionStatisticsRepository = app('registry')->getManager('default')->getRepository(HfpayDistributorTransactionStatistics::class);
    }

    /**
     * 店铺分账统计汇总
     */
    public function count($params)
    {
        $filter['company_id'] = $params['company_id'];

        if (!($params['distributor_id'] ?? 0)) {
            $data = [
                'income' => bcdiv(0, 100, 2),
                'refund' => bcdiv(0, 100, 2),
                'withdrawal_balance' => bcdiv(0, 100, 2),
                'unsettled_funds' => bcdiv(0, 100, 2),
                'settlement_funds' => bcdiv(0, 100, 2),
            ];

            return $data;
        }
        $filter['distributor_id'] = $params['distributor_id'];
        // $filter['date|gte']       = strtotime($params['start_date']);
        // $filter['date|lte']       = strtotime($params['end_date']);
        // $cols   = 'sum(income) as income, sum(disburse) as disburse, sum(withdrawal) as withdrawal, sum(refund) as refund, sum(balance) as balance, sum(withdrawal_balance) as withdrawal_balance, sum(unsettled_funds) as unsettled_funds';
        // $result = $this->hfpayDistributorStatisticsDayRepository->sum($filter, $cols);

        $enterapplyFilter = [
            'company_id' => $params['company_id'],
            'distributor_id' => $params['distributor_id'],
            'apply_type' => [1, 2],
            'status' => 3
        ];
        $enterapplyInfo = $this->hfpayEnterapplyRepository->getInfo($enterapplyFilter);

        $withdrawalBalance = 0;
        if ($enterapplyInfo) {
            //查询汇付账户余额
            $qryParams = [
                'user_cust_id' => $enterapplyInfo['user_cust_id'],
                'acct_id' => $enterapplyInfo['acct_id']
            ];
            $service = new AcouService($enterapplyInfo['company_id']);
            $qry_result = $service->qry001($qryParams);
            if ($qry_result['resp_code'] == 'C00000') {
                $withdrawalBalance = bcmul($qry_result['balance'], 100); //余额单位元转换为分
            }
        }
        // 未结算资金=余额-可提现余额
        //$unsettled_funds = $result['income'] - $withdrawalBalance; //总收入-可提现余额
        $statisticsInfo = $this->hfpayDistributorStatisticsDayRepository->getLists($filter, '*', 1, 1, ['created_at' => 'DESC']);
        $statisticsInfo = $statisticsInfo[0] ?? [];

        //今日数据
        //已结算资金
        $orderProfitSharingService = new OrderProfitSharingService();
        $start_date = strtotime(date('Y-m-d 00:00:00', time()));
        $orderProfitSharingFilter = [
            'company_id' => $params['company_id'],
            'pay_type' => 'hfpay',
            'distributor_id' => $params['distributor_id'],
            'status' => 1,
            'hf_order_date|gte' => date('Ymd', $start_date),//分账日期
        ];
        $settlement = $orderProfitSharingService->getProfitShareCapital($orderProfitSharingFilter);

        //1、今日收入(包含退款的)
        $orderFilter = [
            'company_id' => $params['company_id'],
            'distributor_id' => $params['distributor_id'],
            'pay_type' => 'hfpay',
            'is_profitsharing' => 2,
            // 'pay_status' => 'PAYED',
            'trade_state' => 'SUCCESS',
            'time_expire|gte' => $start_date,
        ];
        $todayIncome = $this->income($orderFilter)[1];

        //2、今日退款
        $orderFilter = [
            'company_id' => $params['company_id'],
            'distributor_id' => $params['distributor_id'],
            'pay_type' => 'hfpay',
            'is_profitsharing' => 2,
            // 'pay_status' => 'PAYED',
            'refund_status' => 'SUCCESS',
            'refund_success_time|gte' => $start_date,
        ];
        $todayRefund = $this->refund($orderFilter)[1];

        //3、今日提现，本店铺客户ID为500，交易类型为0的合计支出
        // $withdrawal_filter = [
        //     'company_id'     => $params['company_id'],
        //     'distributor_id' => $params['distributor_id'],
        //     'trade_time|gte' => $start_date,
        //     'fin_type'       => 500,
        //     'trade_type'     => 0,
        // ];
        // $withdrawal_result = $this->hfpayTradeRecordRepository->sum($withdrawal_filter, 'sum(outcome) as outcome');
        // $withdrawal        = isset($withdrawal_result['outcome']) ? $withdrawal_result['outcome'] : 0;

        //4、退款中
        $orderFilter = [
            'company_id' => $params['company_id'],
            'distributor_id' => $params['distributor_id'],
            'pay_type' => 'hfpay',
            'is_profitsharing' => 2,
            // 'pay_status' => 'PAYED',
            'refund_status' => ['READY', 'AUDIT_SUCCESS', 'PROCESSING'],
        ];
        $refunding = $this->refund($orderFilter)[1];

        $todayIncome = $todayIncome - $todayRefund;  //今日收入 = 今日总收入 - 退款
        $income = ($statisticsInfo['income'] ?? 0) + $todayIncome - $refunding; //总收入 = 今日之前总收入 + 今日收入 - 退款中
        //$withdrawal = $statisticsInfo['withdrawal'] ?? 0 + $withdrawal; //总提现 = 今日之前提现 + 今日提现
        $refund = ($statisticsInfo['refund'] ?? 0) + $todayRefund; //总退款 = 今日之前退款 + 今日退款
        $unsettled_funds = ($statisticsInfo['unsettled_funds'] ?? 0) + $todayIncome - $settlement; //总未结算资金 = 今日之前未结算资金 + 今日收入 - 今日已结算资金
        $settlement_funds = ($statisticsInfo['settlement_funds'] ?? 0) + $settlement; //总已结算资金 = 今日之前已结算资金 + 今日已结算资金

        $data = [
            'income' => bcdiv($income, 100, 2),
            // 'disburse'           => bcdiv($statisticsInfo['disburse'] ?? 0, 100, 2),
            //'withdrawal'         => bcdiv($withdrawal, 100, 2),
            'refund' => bcdiv($refund, 100, 2),
            // 'balance'            => bcdiv($statisticsInfo['balance'] ?? 0, 100, 2),
            'withdrawal_balance' => bcdiv($withdrawalBalance ?? 0, 100, 2),
            'unsettled_funds' => bcdiv($unsettled_funds, 100, 2),
            'settlement_funds' => bcdiv($settlement_funds, 100, 2),
        ];

        return $data;
    }

    /**
     * 店铺分账数据跑批
     */
    public function statistics()
    {
        //前一天的时间
        $date = date('Y-m-d', strtotime('-1 days'));

        //查询入驻店铺数据
        $filter = [
            'apply_type' => [1, 2],
            'status' => 3
        ];
        $count = $this->hfpayEnterapplyRepository->count($filter);
        $page_size = 500; //单次查询记录数
        //数据大于500条分页处理
        if ($count > $page_size) {
            $page_num = $count / $page_size;
            for ($page = 1; $page < $page_num; $page++) {
                $data = $this->hfpayEnterapplyRepository->getLists($filter, 'company_id, distributor_id', $page, $page_size);
                $this->distributorDay($data, $date);
                $this->distributorTransactionDay($data, $date);
            }
        } else {
            $data = $this->hfpayEnterapplyRepository->getLists($filter, 'company_id, distributor_id', 1, $page_size);
            $this->distributorDay($data, $date);
            $this->distributorTransactionDay($data, $date);
        }
    }

    /**
     * 店铺分账初始化
     */
    public function initStatistics($companyId, $date)
    {
        //查询入驻店铺数据
        $filter = [
            'apply_type' => [1, 2],
            'status' => 3,
            'company_id' => $companyId,
        ];
        $count = $this->hfpayEnterapplyRepository->count($filter);
        $page_size = 500; //单次查询记录数
        //数据大于500条分页处理
        if ($count > $page_size) {
            $page_num = $count / $page_size;
            for ($page = 1; $page < $page_num; $page++) {
                $data = $this->hfpayEnterapplyRepository->getLists($filter, 'company_id, distributor_id', $page, $page_size);
                $this->distributorTransactionDay($data, $date);
            }
        } else {
            $data = $this->hfpayEnterapplyRepository->getLists($filter, 'company_id, distributor_id', 1, $page_size);
            $this->distributorTransactionDay($data, $date);
        }
    }

    /**
     * 店铺分账数据日汇总
     */
    // private function distributorDay($data, $date)
    // {
    //     $start_date = date('Y-m-d 00:00:00', strtotime($date));
    //     $end_date   = date('Y-m-d 23:59:59', strtotime($date));
    //     $start_time = strtotime($start_date);
    //     $end_time   = strtotime($end_date);

    //     foreach ($data as $key => $val) {
    //         $company_id     = $val['company_id'];
    //         $distributor_id = $val['distributor_id'];

    //         //1、总计收入：本店铺科目ID为200，交易类型为1的收入总计
    //         // $income_filter = [
    //         //     'company_id'     => $company_id,
    //         //     'distributor_id' => $distributor_id,
    //         //     'trade_time|gte' => $start_time,
    //         //     'trade_time|lte' => $end_time,
    //         //     'fin_type'       => '200',
    //         //     'trade_type'     => 1,
    //         // ];
    //         // $income_result = $this->hfpayTradeRecordRepository->sum($income_filter, 'sum(income) as income');
    //         // $income        = isset($income_result['income']) ? $income_result['income'] : 0;
    //         $orderFilter = [
    //             'company_id'     => $company_id,
    //             'distributor_id' => $distributor_id,
    //             'pay_type'       => 'hfpay',
    //             'is_profitsharing' => 2,
    //             'pay_status' => 'PAYED',
    //             'order_status|notIn' => ['CANCEL'],
    //             'cancel_status|notIn' => ['SUCCESS'],
    //         ];
    //         $orderList = $this->normalOrdersRepository->getList($orderFilter);

    //         $income = 0;
    //         $total_fee = 0;
    //         $fee_amt = 0;
    //         foreach ($orderList as $v) {
    //             $order_total_fee          = $v['total_fee'];
    //             $profitsharing_rate = $v['profitsharing_rate'];
    //             $order_fee_amt = bcdiv(bcmul($order_total_fee, $profitsharing_rate), 100);
    //             if ($order_fee_amt >= 1) {
    //                 $fee_amt += $order_fee_amt;
    //             }
    //             $total_fee += $order_total_fee;
    //         }
    //         $income = $total_fee - $fee_amt;

    //         //2、支出总计：本店铺，科目类型为（300、310、400、401）的所有收入支出之和
    //         $income_filter    = [
    //             'company_id'     => $company_id,
    //             'distributor_id' => $distributor_id,
    //             'fin_type'       => [300, 310, 400, 401],
    //         ];
    //         $disburse_result  = $this->hfpayTradeRecordRepository->sum($income_filter, 'sum(income) as income, sum(outcome) as outcome');
    //         $disburse_income  = isset($disburse_result['income']) ? $disburse_result['income'] : 0;
    //         $disburse_outcome = isset($disburse_result['outcome']) ? $disburse_result['outcome'] : 0;
    //         $disburse         = $disburse_income - $disburse_outcome;

    //         //3、合计提现，本店铺客户ID为500，交易类型为0的合计支出
    //         $withdrawal_filter = [
    //             'company_id'     => $company_id,
    //             'distributor_id' => $distributor_id,
    //             'trade_time|gte' => $start_time,
    //             'trade_time|lte' => $end_time,
    //             'fin_type'       => 500,
    //             'trade_type'     => 0,
    //         ];
    //         $withdrawal_result = $this->hfpayTradeRecordRepository->sum($withdrawal_filter, 'sum(outcome) as outcome');
    //         $withdrawal        = isset($withdrawal_result['outcome']) ? $withdrawal_result['outcome'] : 0;

    //         //4、合计退款：本店铺科目id为600，交易类型为0的合计支出
    //         // $refund_filter = [
    //         //     'company_id'     => $company_id,
    //         //     'distributor_id' => $distributor_id,
    //         //     'trade_time|gte' => $start_time,
    //         //     'trade_time|lte' => $end_time,
    //         //     'fin_type'       => 600,
    //         //     'trade_type'     => 0,
    //         // ];
    //         // $refund_result = $this->hfpayTradeRecordRepository->sum($refund_filter, 'sum(outcome) as outcome');
    //         // $refund        = isset($refund_result['outcome']) ? $refund_result['outcome'] : 0;
    //         $orderFilter = [
    //             'company_id'     => $company_id,
    //             'distributor_id' => $distributor_id,
    //             'pay_type'       => 'hfpay',
    //             'is_profitsharing' => 2,
    //             'pay_status' => 'PAYED',
    //             'order_status' => ['CANCEL'],
    //             'cancel_status' => ['SUCCESS'],
    //         ];
    //         $orderList = $this->normalOrdersRepository->getList($orderFilter);

    //         $refund = 0;
    //         $refund_total_fee = 0;
    //         $refund_fee_amt = 0;
    //         foreach ($orderList as $v) {
    //             $order_total_fee          = $v['total_fee'];
    //             $profitsharing_rate = $v['profitsharing_rate'];
    //             $order_fee_amt = bcdiv(bcmul($order_total_fee, $profitsharing_rate), 100);
    //             if ($order_fee_amt >= 1) {
    //                 $refund_fee_amt += $order_fee_amt;
    //             }
    //             $refund_total_fee += $order_total_fee;
    //         }
    //         $refund = $refund_total_fee - $refund_fee_amt;

    //         //5、余额=合计收入-合计支出-合计提现-合计退款
    //         $balance = $income - $disburse - $withdrawal - $refund;

    //         //6、可提现余额=本店铺所有已结算数据之和
    //         // $withdrawal_balance_filter  = [
    //         //     'company_id'     => $company_id,
    //         //     'distributor_id' => $distributor_id,
    //         //     'trade_time|gte' => $start_time,
    //         //     'trade_time|lte' => $end_time,
    //         //     'is_clean'       => 1,
    //         // ];
    //         // $withdrawal_balance_result  = $this->hfpayTradeRecordRepository->sum($withdrawal_balance_filter, 'sum(income) as income, sum(outcome) as outcome');
    //         // $withdrawal_balance_income  = isset($withdrawal_balance_result['income']) ? $withdrawal_balance_result['income'] : 0;
    //         // $withdrawal_balance_outcome = isset($withdrawal_balance_result['outcome']) ? $withdrawal_balance_result['outcome'] : 0;
    //         // $withdrawal_balance         = $withdrawal_balance_income - $withdrawal_balance_outcome;
    //         //6、可提现余额=已结算



    //         //7、未结算资金=余额-可提现余额
    //         //$unsettled_funds = ($income - $refund) - $withdrawal_balance; //总收入-可提现余额

    //         // 7、未结算资金=总订单分账金额（未分账）
    //         $orderFilter = [
    //             'company_id'     => $company_id,
    //             'distributor_id' => $distributor_id,
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
    //             'distributor_id'     => $distributor_id,
    //             'date'               => strtotime($date),
    //             'income'             => $income, //减去退款金额
    //             'disburse'           => $disburse,
    //             'withdrawal'         => $withdrawal,
    //             'refund'             => $refund,
    //             'balance'            => $balance,
    //             'withdrawal_balance' => 0,
    //             'unsettled_funds'    => $unsettled_funds,
    //         ];
    //         $this->hfpayDistributorStatisticsDayRepository->create($params);
    //     }

    //     return true;
    // }

    private function distributorDay($data, $date)
    {
        $orderProfitSharingService = new OrderProfitSharingService();

        $start_date = date('Y-m-d 00:00:00', strtotime($date));
        $end_date = date('Y-m-d 23:59:59', strtotime($date));
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);

        foreach ($data as $key => $val) {
            $company_id = $val['company_id'];
            $distributor_id = $val['distributor_id'];

            //已结算资金
            $orderProfitSharingFilter = [
                'company_id' => $company_id,
                'pay_type' => 'hfpay',
                'distributor_id' => $distributor_id,
                'status' => 1,
                'hf_order_date|lte' => date('Ymd', $end_time),//分账日期
            ];
            $settlement = $orderProfitSharingService->getProfitShareCapital($orderProfitSharingFilter);

            //1、总计收入(包含退款的)
            $orderFilter = [
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'pay_type' => 'hfpay',
                'is_profitsharing' => 2,
                // 'pay_status' => 'PAYED',
                'trade_state' => 'SUCCESS',
                'time_expire|lte' => $end_time,
            ];
            $income = $this->income($orderFilter)[1];

            //2、总退款
            $orderFilter = [
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'pay_type' => 'hfpay',
                'is_profitsharing' => 2,
                // 'pay_status' => 'PAYED',
                'refund_status' => 'SUCCESS',
                'refund_success_time|lte' => $end_time,
            ];
            $refund = $this->refund($orderFilter)[1];

            //3、合计提现，本店铺客户ID为500，交易类型为0的合计支出
            $withdrawal_filter = [
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'trade_time|lte' => $end_time,
                'fin_type' => 500,
                'trade_type' => 0,
            ];
            $withdrawal_result = $this->hfpayTradeRecordRepository->sum($withdrawal_filter, 'sum(outcome) as outcome');
            $withdrawal = isset($withdrawal_result['outcome']) ? $withdrawal_result['outcome'] : 0;

            // 4、未结算资金=总收入-已结算
            $unsettled_funds = $income - $refund - $settlement;

            $params = [
                'company_id' => $company_id,
                'distributor_id' => $distributor_id,
                'date' => strtotime($date),
                'income' => $income - $refund, //减去退款金额
                'disburse' => 0,
                'withdrawal' => $withdrawal,
                'refund' => $refund,
                'balance' => 0,
                'withdrawal_balance' => 0,
                'unsettled_funds' => $unsettled_funds,
                'settlement_funds' => $settlement,
            ];
            $this->hfpayDistributorStatisticsDayRepository->create($params);
        }

        return true;
    }

    /**
     * 交易统计
     */
    private function distributorTransactionDay($data, $date)
    {
        $start_date = date('Y-m-d 00:00:00', strtotime($date));
        $end_date = date('Y-m-d 23:59:59', strtotime($date));
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);

        foreach ($data as $key => $val) {
            $companyId = $val['company_id'];
            $distributorId = $val['distributor_id'];

            $orderFilter = [
                'distributor_id' => $distributorId,
                'start_date' => $start_time,
                'end_date' => $end_time,
            ];
            $orderCount = $this->orderCount($companyId, $orderFilter); //交易总笔数

            $orderTotalFee = $this->orderTotalFee($companyId, $orderFilter); //总计交易金额

            $orderRefundCount = $this->orderRefundCount($companyId, $orderFilter); //已退款总笔数

            $orderRefundTotalFee = $this->orderRefundTotalFee($companyId, $orderFilter); //退款总金额

            $orderRefundingCount = $this->orderRefundingCount($companyId, $orderFilter); //在退总笔数

            $orderRefundingTotalFee = $this->orderRefundingTotalFee($companyId, $orderFilter); //在退总金额

            $orderProfitSharingCharge = $this->orderProfitSharingCharge($companyId, $orderFilter); //已结算手续费

            $orderTotalCharge = $this->orderTotalCharge($companyId, $orderFilter); //总手续费（包含已退款）

            $orderRefundTotalCharge = $this->orderRefundTotalCharge($companyId, $orderFilter); //总退款手续费

            $orderUnProfitSharingTotalCharge = $this->orderUnProfitSharingCharge($companyId, $orderFilter); //未结算手续费（包含已退款）

            $orderUnProfitSharingRefundTotalCharge = $this->orderUnProfitSharingRefundTotalCharge($companyId, $orderFilter); //未结算已退款手续费

            $insertData = [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
                'date' => strtotime($date),
                'order_count' => $orderCount,
                'order_total_fee' => $orderTotalFee,
                'order_refund_count' => $orderRefundCount,
                'order_refund_total_fee' => $orderRefundTotalFee,
                'order_refunding_count' => $orderRefundingCount,
                'order_refunding_total_fee' => $orderRefundingTotalFee,
                'order_profit_sharing_charge' => $orderProfitSharingCharge,
                'order_total_charge' => $orderTotalCharge,
                'order_refund_total_charge' => $orderRefundTotalCharge,
                'order_un_profit_sharing_total_charge' => $orderUnProfitSharingTotalCharge,
                'order_un_profit_sharing_refund_total_charge' => $orderUnProfitSharingRefundTotalCharge,
            ];
            $this->hfpayDistributorTransactionStatisticsRepository->create($insertData);
        }
        return true;
    }
}
