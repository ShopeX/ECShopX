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

use DistributionBundle\Entities\Distributor;
use HfPayBundle\Entities\HfpayDistributorTransactionStatistics;
use HfPayBundle\Entities\HfpayEnterapply;
use HfPayBundle\Traits\HfpayStatisticsTrait;

class HfpayDistributorTransactionStatisticsService extends HfpayStatisticsBaseService
{
    use HfpayStatisticsTrait;

    private $hfpayDistributorTransactionStatisticsRepository;

    private $hfpayEnterapplyRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->hfpayDistributorTransactionStatisticsRepository = app('registry')->getManager('default')->getRepository(HfpayDistributorTransactionStatistics::class);
        $this->hfpayEnterapplyRepository = app('registry')->getManager('default')->getRepository(HfpayEnterapply::class);
    }

    // /**
    //  * 交易统计数量
    //  */
    // public function transactionCount($filter)
    // {
    //     if (isset($filter['start_date']) && !empty($filter['start_date'])) {
    //         $filter['date|gte'] = strtotime($filter['start_date']);
    //         unset($filter['start_date']);
    //     }
    //     if (isset($filter['end_date']) && !empty($filter['end_date'])) {
    //         $filter['date|lte'] = strtotime($filter['end_date']);
    //         unset($filter['end_date']);
    //     }

    //     $count = $this->hfpayDistributorTransactionStatisticsRepository->transactionCount($filter);

    //     //获取今日数量
    //     $start_time = strtotime(date('Y-m-d 00:00:00', time()));
    //     if (!$count && (!isset($filter['date|lte']) || $filter['date|lte'] >= $start_time)) {
    //         //查询入驻店铺数据
    //         $enterapplyFilter    = [
    //             'apply_type' => [1, 2],
    //             'status'     => 3,
    //         ];
    //         if ($filter['distributor_id'] ?? null) {
    //             $enterapplyFilter['distributor_id'] = $filter['distributor_id'];
    //         }
    //         $count     = $this->hfpayEnterapplyRepository->count($enterapplyFilter);
    //     }

    //     return $count;
    // }

    // /**
    //  * 交易统计
    //  */
    // public function transactionList($params, $page = 1, $pageSize = -1, $orderBy = ['a.distributor_id' => 'asc'])
    // {
    //     if (isset($params['start_date']) && !empty($params['start_date'])) {
    //         $params['date|gte'] = strtotime($params['start_date']);
    //         unset($params['start_date']);
    //     }
    //     if (isset($params['end_date']) && !empty($params['end_date'])) {
    //         $params['date|lte'] = strtotime($params['end_date']);
    //         unset($params['end_date']);
    //     }

    //     $result = $this->hfpayDistributorTransactionStatisticsRepository->transactionList($params, $page, $pageSize, $orderBy);
    //     //获取今日数据
    //     $start_time = strtotime(date('Y-m-d 00:00:00', time()));

    //     $hfpayStatisticsService = new HfpayStatisticsService();

    //     if ($result['list']) {
    //         //结束时间大于等于今日日期
    //         foreach ($result['list'] as $key => $val) {
    //             $companyId     = $val['company_id'];
    //             $distributorId = $val['distributor_id'];
    //             //获取可提现金额
    //             $enterapplyFilter = [
    //                 'company_id' => $companyId,
    //                 'distributor_id' => $distributorId,
    //                 'apply_type' => [1, 2],
    //                 'status'     => 3
    //             ];
    //             $enterapplyInfo = $this->hfpayEnterapplyRepository->getInfo($enterapplyFilter);

    //             $withdrawalBalance = 0;
    //             if ($enterapplyInfo) {
    //                 //查询汇付账户余额
    //                 $qryParams = [
    //                     'user_cust_id' => $enterapplyInfo['user_cust_id'],
    //                     'acct_id'      => $enterapplyInfo['acct_id']
    //                 ];
    //                 $service = new AcouService($enterapplyInfo['company_id']);
    //                 $qry_result  = $service->qry001($qryParams);
    //                 if ($qry_result['resp_code'] == 'C00000') {
    //                     $withdrawalBalance = bcmul($qry_result['balance'], 100); //余额单位元转换为分
    //                 }
    //             }
    //             $result['list'][$key]['withdrawal_balance'] = $withdrawalBalance;

    //             if (!isset($params['date|lte']) || $params['date|lte'] >= $start_time) {
    //                 $orderFilter = [
    //                     'distributor_id' => $distributorId,
    //                     'start_date' => $start_time,
    //                 ];
    //                 $statisticsCount = $hfpayStatisticsService->count($companyId, $orderFilter);

    //                 $result['list'][$key]['order_count'] = $val['order_count'] + $statisticsCount['order_count'];
    //                 $result['list'][$key]['order_total_fee'] = (string) ($val['order_total_fee'] + bcmul($statisticsCount['order_total_fee'], 100));
    //                 $result['list'][$key]['order_refund_count'] = $val['order_refund_count'] + $statisticsCount['order_refund_count'];
    //                 $result['list'][$key]['order_refund_total_fee'] = (string) ($val['order_refund_total_fee'] + bcmul($statisticsCount['order_refund_total_fee'], 100));
    //                 $result['list'][$key]['order_refunding_count'] = $val['order_refunding_count'] + $statisticsCount['order_refunding_count'];
    //                 $result['list'][$key]['order_refunding_total_fee'] = (string) ($val['order_refunding_total_fee'] + bcmul($statisticsCount['order_refunding_total_fee'], 100));
    //                 $result['list'][$key]['order_profit_sharing_charge'] = (string) ($val['order_profit_sharing_charge'] + bcmul($statisticsCount['order_profit_sharing_charge'], 100));
    //                 $result['list'][$key]['order_total_charge'] = (string) ($val['order_total_charge'] + bcmul($statisticsCount['order_total_charge'], 100));
    //                 $result['list'][$key]['order_refund_total_charge'] = (string) ($val['order_refund_total_charge'] + bcmul($statisticsCount['order_refund_total_charge'], 100));
    //                 $result['list'][$key]['order_un_profit_sharing_total_charge'] = (string) ($val['order_un_profit_sharing_total_charge'] + bcmul($statisticsCount['order_un_profit_sharing_total_charge'], 100));
    //                 $result['list'][$key]['order_un_profit_sharing_refund_total_charge'] = (string) ($val['order_un_profit_sharing_refund_total_charge'] + bcmul($statisticsCount['order_un_profit_sharing_refund_total_charge'], 100));
    //             }
    //             $result['list'][$key]['order_un_profit_sharing_charge'] = (string) ($result['list'][$key]['order_un_profit_sharing_total_charge'] - $result['list'][$key]['order_un_profit_sharing_refund_total_charge']);
    //         }
    //     } else {
    //         //查询入驻店铺数据
    //         $filter    = [
    //             'apply_type' => [1, 2],
    //             'status'     => 3,
    //         ];
    //         if ($params['distributor_id'] ?? null) {
    //             $filter['distributor_id'] = $params['distributor_id'];
    //         }
    //         $count     = $this->hfpayEnterapplyRepository->count($filter);
    //         $result['total_count'] = $count;
    //         $result['list'] = $this->hfpayEnterapplyRepository->getLists($filter, 'company_id, distributor_id', $page, $pageSize);
    //         if (! $result['list']) {
    //             return [
    //                 'total_count' => 0,
    //                 'list' => [],
    //             ];
    //         }
    //         // 获取店铺名称
    //         $distributorEntityRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
    //         $distributorIds = array_column($result['list'], 'distributor_id');
    //         $distributorLists = $distributorEntityRepository->lists(['distributor_id'=>$distributorIds], ["created" => "DESC"], $pageSize, 1, false);
    //         $distributors = array_column($distributorLists['list'], 'name', 'distributor_id');
    //         //结束时间大于等于今日日期
    //         foreach ($result['list'] as $key => $val) {
    //             $result['list'][$key]['distributor_name'] = '';
    //             if (isset($distributors[$val['distributor_id']])) {
    //                 $result['list'][$key]['distributor_name'] = $distributors[$val['distributor_id']];
    //             }
    //             //设置初始值
    //             $result['list'][$key]['order_count'] = 0;
    //             $result['list'][$key]['order_total_fee'] = 0;
    //             $result['list'][$key]['order_refund_count'] = 0;
    //             $result['list'][$key]['order_refund_total_fee'] = 0;
    //             $result['list'][$key]['order_refunding_count'] = 0;
    //             $result['list'][$key]['order_refunding_total_fee'] = 0;
    //             $result['list'][$key]['order_profit_sharing_charge'] = 0;
    //             $result['list'][$key]['order_un_profit_sharing_charge'] = 0;

    //             $companyId     = $val['company_id'];
    //             $distributorId = $val['distributor_id'];
    //             //获取可提现金额
    //             $enterapplyFilter = [
    //                 'company_id' => $companyId,
    //                 'distributor_id' => $distributorId,
    //                 'apply_type' => [1, 2],
    //                 'status'     => 3
    //             ];
    //             $enterapplyInfo = $this->hfpayEnterapplyRepository->getInfo($enterapplyFilter);

    //             $withdrawalBalance = 0;
    //             if ($enterapplyInfo) {
    //                 //查询汇付账户余额
    //                 $qryParams = [
    //                     'user_cust_id' => $enterapplyInfo['user_cust_id'],
    //                     'acct_id'      => $enterapplyInfo['acct_id']
    //                 ];
    //                 $service = new AcouService($enterapplyInfo['company_id']);
    //                 $qry_result  = $service->qry001($qryParams);
    //                 if ($qry_result['resp_code'] == 'C00000') {
    //                     $withdrawalBalance = bcmul($qry_result['balance'], 100); //余额单位元转换为分
    //                 }
    //             }
    //             $result['list'][$key]['withdrawal_balance'] = $withdrawalBalance;

    //             if (!isset($params['date|lte']) || $params['date|lte'] >= $start_time) {
    //                 $orderFilter = [
    //                     'distributor_id' => $distributorId,
    //                     'start_date' => $start_time,
    //                 ];
    //                 $statisticsCount = $hfpayStatisticsService->count($companyId, $orderFilter);

    //                 $result['list'][$key]['order_count'] = $statisticsCount['order_count'];
    //                 $result['list'][$key]['order_total_fee'] = bcmul($statisticsCount['order_total_fee'], 100);
    //                 $result['list'][$key]['order_refund_count'] = $statisticsCount['order_refund_count'];
    //                 $result['list'][$key]['order_refund_total_fee'] = bcmul($statisticsCount['order_refund_total_fee'], 100);
    //                 $result['list'][$key]['order_refunding_count'] = $statisticsCount['order_refunding_count'];
    //                 $result['list'][$key]['order_refunding_total_fee'] = bcmul($statisticsCount['order_refunding_total_fee'], 100);
    //                 $result['list'][$key]['order_profit_sharing_charge'] = bcmul($statisticsCount['order_profit_sharing_charge'], 100);
    //                 $result['list'][$key]['order_un_profit_sharing_charge'] = bcmul($statisticsCount['order_un_profit_sharing_charge'], 100);
    //             }
    //         }
    //     }


    //     return $result;
    // }

    /**
     * 交易统计数量
     */
    public function transactionCount($filter)
    {
        if (isset($filter['start_date']) && !empty($filter['start_date'])) {
            unset($filter['start_date']);
        }
        if (isset($filter['end_date']) && !empty($filter['end_date'])) {
            unset($filter['end_date']);
        }

        //查询入驻店铺数据
        $enterapplyFilter = [
            'apply_type' => [1, 2],
            'status' => 3,
        ];
        if ($filter['distributor_id'] ?? null) {
            $enterapplyFilter['distributor_id'] = $filter['distributor_id'];
        }
        $count = $this->hfpayEnterapplyRepository->count($enterapplyFilter);


        return $count;
    }

    /**
     * 交易统计
     */
    public function transactionList($params, $page = 1, $pageSize = -1, $orderBy = ['a.distributor_id' => 'asc'])
    {
        if (isset($params['start_date']) && !empty($params['start_date'])) {
            $start_time = strtotime($params['start_date']);
            unset($params['start_date']);
        }
        if (isset($params['end_date']) && !empty($params['end_date'])) {
            $end_date = strtotime($params['end_date']);
            unset($params['end_date']);
        }

        $hfpayStatisticsService = new HfpayStatisticsService();

        //查询入驻店铺数据
        $filter = [
            'apply_type' => [1, 2],
            'status' => 3,
        ];
        if ($params['distributor_id'] ?? null) {
            $filter['distributor_id'] = $params['distributor_id'];
        }
        $count = $this->hfpayEnterapplyRepository->count($filter);
        $result['total_count'] = $count;
        $result['list'] = $this->hfpayEnterapplyRepository->getLists($filter, 'company_id, distributor_id', $page, $pageSize);
        if (!$result['list']) {
            return [
                'total_count' => 0,
                'list' => [],
            ];
        }
        // 获取店铺名称
        $distributorEntityRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $distributorIds = array_column($result['list'], 'distributor_id');
        $distributorLists = $distributorEntityRepository->lists(['distributor_id' => $distributorIds], ["created" => "DESC"], $pageSize, 1, false);
        $distributors = array_column($distributorLists['list'], 'name', 'distributor_id');

        foreach ($result['list'] as $key => $val) {
            $result['list'][$key]['distributor_name'] = '';
            if (isset($distributors[$val['distributor_id']])) {
                $result['list'][$key]['distributor_name'] = $distributors[$val['distributor_id']];
            }
            //设置初始值
            $result['list'][$key]['order_count'] = 0;
            $result['list'][$key]['order_total_fee'] = 0;
            $result['list'][$key]['order_refund_count'] = 0;
            $result['list'][$key]['order_refund_total_fee'] = 0;
            $result['list'][$key]['order_refunding_count'] = 0;
            $result['list'][$key]['order_refunding_total_fee'] = 0;
            $result['list'][$key]['order_profit_sharing_charge'] = 0;
            $result['list'][$key]['order_un_profit_sharing_charge'] = 0;

            $companyId = $val['company_id'];
            $distributorId = $val['distributor_id'];
            //获取可提现金额
            $enterapplyFilter = [
                'company_id' => $companyId,
                'distributor_id' => $distributorId,
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
            $result['list'][$key]['withdrawal_balance'] = $withdrawalBalance;

            $orderFilter = [
                'distributor_id' => $distributorId,
                'start_date' => $start_time,
                'end_date' => $end_date,
            ];
            $statisticsCount = $hfpayStatisticsService->count($companyId, $orderFilter);

            $result['list'][$key]['order_count'] = $statisticsCount['order_count'];
            $result['list'][$key]['order_total_fee'] = bcmul($statisticsCount['order_total_fee'], 100);
            $result['list'][$key]['order_refund_count'] = $statisticsCount['order_refund_count'];
            $result['list'][$key]['order_refund_total_fee'] = bcmul($statisticsCount['order_refund_total_fee'], 100);
            $result['list'][$key]['order_refunding_count'] = $statisticsCount['order_refunding_count'];
            $result['list'][$key]['order_refunding_total_fee'] = bcmul($statisticsCount['order_refunding_total_fee'], 100);
            $result['list'][$key]['order_profit_sharing_charge'] = bcmul($statisticsCount['order_profit_sharing_charge'], 100);
            $result['list'][$key]['order_un_profit_sharing_charge'] = bcmul($statisticsCount['order_un_profit_sharing_charge'], 100);
        }

        return $result;
    }

    public function __call($name, $arguments)
    {
        return $this->hfpayDistributorTransactionStatisticsRepository->$name(...$arguments);
    }
}
