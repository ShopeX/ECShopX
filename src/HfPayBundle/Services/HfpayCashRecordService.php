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

use CompanysBundle\Services\OperatorsService;
use Dingo\Api\Exception\ResourceException;
use HfPayBundle\Entities\HfpayBankCard;
use HfPayBundle\Entities\HfpayCashRecord;
use HfPayBundle\Entities\HfpayEnterapply;
use HfPayBundle\Events\HfPayDistributorWithdrawEvent;
use HfPayBundle\Events\HfPayDistributorWithdrawSuccessEvent;

class HfpayCashRecordService
{
    private $cashRecordRepository;

    private $bankRepository;

    private $hfpayEnterapplyRepository;

    public function __construct()
    {
        $this->cashRecordRepository = app('registry')->getManager('default')->getRepository(HfpayCashRecord::class);
        $this->bankRepository = app('registry')->getManager('default')->getRepository(HfpayBankCard::class);
        $this->hfpayEnterapplyRepository = app('registry')->getManager('default')->getRepository(HfpayEnterapply::class);
    }

    /**
     * 提现总统计
     */
    public function total($params)
    {
        $count = $this->cashRecordRepository->count($params); //提现笔数

        if (isset($params['cash_status'])) {
            unset($params['cash_status']);
        }
        $totalAmt = $this->cashRecordRepository->sum($params, 'trans_amt'); //提现总金额

        $params['cash_status'] = 2;
        $finishTotalAmt = $this->cashRecordRepository->sum($params, 'trans_amt'); //提现成功金额

        if (isset($params['cash_status'])) {
            unset($params['cash_status']);
        }
        $params['cash_status'] = [0, 1];
        $totalAmting = $this->cashRecordRepository->sum($params, 'trans_amt'); //提现中金额

        if (isset($params['cash_status'])) {
            unset($params['cash_status']);
        }
        $params['cash_status'] = 3;
        $failTotalAmt = $this->cashRecordRepository->sum($params, 'trans_amt'); //提现失败金额

        return [
            'count' => $count,
            'total_amt' => $totalAmt,
            'finish_total_amt' => $finishTotalAmt,
            'total_amting' => $totalAmting,
            'fail_total_amt' => $failTotalAmt,
        ];
    }

    /**
     * 获取提现记录
     */
    public function lists($filter, $page = 1, $pageSize = 20, $cols = '*', $orderBy = ['created_at' => 'desc'])
    {
        $lists = $this->cashRecordRepository->lists($filter, $cols, $page, $pageSize, $orderBy);

        if ($lists['total_count']) {
            $operatorIds = array_column($lists['list'], 'operator_id');
            $operatorFilter = [
                'company_id' => $filter['company_id'],
                'operator_id' => $operatorIds,
            ];
            $operatorsService = new OperatorsService();
            $operatorsList = $operatorsService->lists($operatorFilter);

            $operatorsList = array_column($operatorsList['list'], 'login_name', 'operator_id');
            foreach ($lists['list'] as $key => $value) {
                if ($value['operator_id'] == 0) {
                    $login_name = '系统操作';
                } else {
                    $login_name = $operatorsList[$value['operator_id']] ?? '';
                }
                $lists['list'][$key]['login_name'] = $login_name;
            }
        }

        return $lists;
    }

    /**
     * 提现
     */
    public function withdraw($filter)
    {
        $this->__check($filter);

        $baseService = new HfBaseService();

        //生成提现记录
        $params = [
            'company_id' => $filter['company_id'],
            'distributor_id' => $filter['distributor_id'],
            'order_id' => $baseService->getOrderId(),
            'user_cust_id' => $filter['user_cust_id'],
            'trans_amt' => $filter['withdrawal_amount'],
            'cash_type' => 'T1',
            'bind_card_id' => $filter['bind_card_id'],
            'operator_id' => $filter['operator_id'] ?? 0,
        ];

        $result = $this->cashRecordRepository->create($params);
        //提现处理事件
        $eventData = [
            'hfpay_cash_record_id' => $result['hfpay_cash_record_id'],
            'company_id' => $filter['company_id'],
            'distributor_id' => $filter['distributor_id'],
            'trans_amt' => $filter['withdrawal_amount'],
        ];
        event(new HfPayDistributorWithdrawEvent($eventData));

        return $result;
    }

    public function checkStatus()
    {
        //查询超过两天处理中的记录
        $filter = [
            'cash_status' => 1,
            'created_at|lte' => date('Y-m-d H:i:s', strtotime('-2 day')),
        ];

        $count = $this->cashRecordRepository->count($filter);

        if (!$count) {
            return true;
        }

        $limit = 500;
        $fileNum = ceil($count / $limit);

        for ($page = 1; $page <= $fileNum; $page++) {
            //获取数据
            $list = $this->cashRecordRepository->getLists($filter, '*', $page, $fileNum);

            foreach ($list as $value) {
                //锁定当前行
                $expire = 10; //有效期10秒
                $key = 'hfpay:' . $value['hfpay_cash_record_id']; //key
                $lockValue = time() + $expire; //锁的值 = Unix时间戳 + 锁的有效期
                $lock = app('redis')->setnx($key, $lockValue);
                if (empty($lock)) {
                    $value = app('redis')->get($key);
                    if ($value < time()) {
                        app('redis')->del($key);
                    }
                } else {
                    app('redis')->expire($key, $expire);
                    //查询状态
                    $service = new HfpayService($value['company_id']);
                    $params = [
                        'order_id' => $value['order_id'],
                        'order_date' => $value['hf_order_date'],
                        'trans_type' => '15',
                    ];
                    $result = $service->qry008($params);

                    $cash_status = '';
                    if ($result['resp_code'] == 'C00000') {
                        if ($result['trans_stat'] != 'S') {
                            app('redis')->del($key);
                            continue;
                        }
                        //成功
                        $cash_status = 2;
                    } elseif ($result['resp_code'] != 'C00001' && $result['resp_code'] != 'C00002') {
                        //失败
                        $cash_status = 3;
                    }
                    if ($cash_status == 2 || $cash_status == 3) {
                        $filter = [
                            'hfpay_cash_record_id' => $value['hfpay_cash_record_id'],
                        ];
                        $resp_code = $result['resp_code'] ?? '';
                        $resp_desc = $result['resp_desc'] ?? '';
                        $qryParams = [
                            'real_trans_amt' => bcmul($result['trans_amt'] ?? 0, 100),
                            'cash_status' => $cash_status,
                            'resp_code' => $resp_code,
                            'resp_desc' => $resp_desc,
                        ];
                        $this->cashRecordRepository->updateOneBy($filter, $qryParams);
                        if ($cash_status == 2) {
                            event(new HfPayDistributorWithdrawSuccessEvent($value));
                        }
                    }
                }
            }
        }
        return true;
    }

    private function __check(&$filter)
    {
        $filter['withdrawal_amount'] = bcmul($filter['withdrawal_amount'], 100);
        //获取汇付账户
        $hfpayEnterapplyFilter = [
            'company_id' => $filter['company_id'],
            'distributor_id' => $filter['distributor_id'],
            'status' => 3,
            'apply_type' => ['1', '2'],
        ];
        $hfpayEnterapplyInfo = $this->hfpayEnterapplyRepository->getInfo($hfpayEnterapplyFilter);

        if (empty($hfpayEnterapplyInfo)) {
            throw new ResourceException('未查询到汇付账户');
        }

        $_filter = [
            'company_id' => $filter['company_id']
        ];
        $hfpay_withdraw_set_service = new HfpayWithdrawSetService();
        $withdraw_set = $hfpay_withdraw_set_service->getWithdrawSet($_filter);
        if (empty($withdraw_set) || $withdraw_set['withdraw_method'] != 2) {
            throw new ResourceException('未开启手动提现');
        }

        $filter['user_cust_id'] = $hfpayEnterapplyInfo['user_cust_id'];
        $filter['acct_id'] = $hfpayEnterapplyInfo['acct_id'];

        //判断是否有绑定银行卡
        $bankFilter = [
            'distributor_id' => $filter['distributor_id'],
            'is_cash' => 1,
        ];
        $brank = $this->bankRepository->getInfo($bankFilter);
        if (empty($brank)) {
            throw new ResourceException('未绑定银行卡');
        }
        $filter['bind_card_id'] = $brank['bind_card_id'];

        //查询汇付账户余额
        $params = [
            'user_cust_id' => $filter['user_cust_id'],
            'acct_id' => $filter['acct_id']
        ];
        $service = new AcouService($filter['company_id']);
        $result = $service->qry001($params);
        if ($result['resp_code'] != 'C00000') {
            throw new ResourceException($result['resp_desc']);
        }
        $balance = bcmul($result['balance'], 100); //余额单位元转换为分

        if ($balance <= 0 || $filter['withdrawal_amount'] > $balance) {
            throw new ResourceException('可提现余额不足');
        }

        //判断余额是否满足最低提现金额
        $distributor_withdraw_money = bcmul($withdraw_set['distributor_money'], 100); //元转分
        if ($distributor_withdraw_money > 0 && ($balance - $distributor_withdraw_money < 0)) {
            throw new ResourceException('可提现余额未满足提现限额');
        }
    }

    public function __call($name, $arguments)
    {
        return $this->cashRecordRepository->$name(...$arguments);
    }
}
