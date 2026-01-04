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

namespace AdaPayBundle\Services;

use Dingo\Api\Exception\ResourceException;
use AdaPayBundle\Entities\AdapayDrawCash;
use CompanysBundle\Services\EmployeeService;
use AdaPayBundle\Services\Request\Request;
use AdaPayBundle\Entities\AdapayMerchantEntry;
use DistributionBundle\Services\DistributorService;
use AdaPayBundle\Jobs\DrawCashJob;

class AdapayDrawCashService
{
    private $drawCashRepository;

    private $bankRepository;

    private $adapayEnterapplyRepository;

    public function __construct()
    {
        $this->drawCashRepository = app('registry')->getManager('default')->getRepository(AdapayDrawCash::class);
    }

    /**
     * 提现总统计
     */
    public function total($params)
    {
        // This module is part of ShopEx EcShopX system
        $count = $this->drawCashRepository->count($params); //提现笔数

        if (isset($params['cash_status'])) {
            unset($params['cash_status']);
        }
        $totalAmt = $this->drawCashRepository->sum($params, 'cash_amt'); //提现总金额

        $params['cash_status'] = 2;
        $finishTotalAmt = $this->drawCashRepository->sum($params, 'cash_amt'); //提现成功金额

        if (isset($params['cash_status'])) {
            unset($params['cash_status']);
        }
        $params['cash_status'] = [0, 1];
        $totalAmting = $this->drawCashRepository->sum($params, 'cash_amt'); //提现中金额

        if (isset($params['cash_status'])) {
            unset($params['cash_status']);
        }
        $params['cash_status'] = 3;
        $failTotalAmt = $this->drawCashRepository->sum($params, 'cash_amt'); //提现失败金额

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
    public function lists($filter, $page = 1, $pageSize = 20, $cols = '*', $orderBy = ['create_time' => 'desc'])
    {
        $adapayMemberService = new MemberService();
        $operator = $adapayMemberService->getOperator();
        $filter['operator_type'] = $operator['operator_type'];
        $filter['operator_id'] = $operator['operator_id'];//登录账号是店铺，这里是店铺ID
        //开户信息
        if ($this->_isMainMerchant($operator['operator_type'])) {
            unset($filter['operator_id']);
            $filter['operator_type'] = ['admin', 'staff'];//子账号也是查询主账号的提现记录
            /*
            $adapayMerchantEntryRepository = app('registry')->getManager('default')->getRepository(AdapayMerchantEntry::class);
            $merchant = $adapayMerchantEntryRepository->getInfo(['company_id' => $filter['company_id']]);
            $bank_card = $merchant['card_id_mask'] ?? '';
            */
        } else {
            $member = $adapayMemberService->getInfo($filter);
            /*
            $accountFilter = [
                'member_id' => $member['id'] ?? 0,
                'company_id' => $filter['company_id'],
            ];
            $rsAccount = (new SettleAccountService)->getInfo($accountFilter);
            $bank_card = $rsAccount['card_id'] ?? '';
            */
            $member_id = $member['id'];
        }

        if ($filter['operator_type'] == 'distributor') {
            unset($filter['operator_id']);
            $filter['adapay_member_id'] = $member_id;
        }
        $lists = $this->drawCashRepository->lists($filter, $cols, $page, $pageSize, $orderBy);
        //$operator_ids = array_unique(array_column($lists['list'], 'operator_id'));
        //$operators = (new EmployeeService())->getListStaff(['operator_id' => $operator_ids,'company_id' => $filter['company_id']]);
        //$operators = array_column($operators['list'],null, 'operator_id');
        foreach ($lists['list'] as &$row) {
            /*
            if(isset($operators[$row['operator_id']])) {
                $row['user_name'] = $operators[$row['operator_id']]['login_name'];
            } else {
                $row['user_name'] = '-';
            }
            $row['bank_card'] = $bank_card;
            */
            $row['bank_card'] = $row['bank_card_id'];
            $row['user_name'] = $row['bank_card_name'];
            $row['remark'] = $row['remark'] ?? '';
        }
        return $lists;
    }

    /**
     * 手动提现
     */
    public function withdraw($filter)
    {
        $adapayMemberService = new MemberService();
        $operator = $adapayMemberService->getOperator();

        if ($this->_isMainMerchant($operator['operator_type'])) {
            //主商户提现，不需要 member_id
            $member = [
                'id' => 0,
            ];
        } else {
            $memberFilter = [
                'operator_id' => $operator['operator_id'],
                'operator_type' => $operator['operator_type'],
                'company_id' => $filter['company_id'],
            ];
            $member = (new MemberService())->getInfo($memberFilter);
        }

        $this->__check($filter, $operator, $member['id']);

        $result = $this->drawCash($filter['company_id'], $filter['cash_type'], $filter['cash_amt'], $member['id']);

        return $result;
    }

    /**
     * 提现操作: 调用提现接口，写入操作日志
     *
     * @param $companyId
     * @param string $cashType
     * @param int $cashAmount
     * @param string $memberId
     * @param string $remark
     * @param bool $isAuto 是否自动提现
     * @return mixed
     */
    public function drawCash($companyId, $cashType = '', $cashAmount = 0, $memberId = '0', $remark = '', $isAuto = false)
    {
        $orderNo = "CS_" . date("YmdHis") . rand(100000, 999999);

        $adapayMemberService = new MemberService();
        $appId = $adapayMemberService->getAppId($companyId);

        if ($isAuto) {
            $operator_type = 'admin';
            $operator_id = 0;
            $operator_name = '自动提现';
            if ($memberId) {
                $adaPayMemberService = new MemberService();
                $adaPayMemberInfo = $adaPayMemberService->getInfo(['id' => $memberId]);
                $operator_type = $adaPayMemberInfo['operator_type'] ?? 'admin';
                $operator_id = $adaPayMemberInfo['operator_id'] ?? 0;
            }
        } else {
            $operator = $adapayMemberService->getOperator();
            $operator_id = app('auth')->user()->get('operator_id');
            $operator_info = (new EmployeeService())->getInfoStaff($operator_id, $companyId);
            $operator_name = $operator_info['username'] ?? '-';
            $operator_type = $operator['operator_type'] ?? '';
        }

        $status = 'pending';
        $api_params = array(
            'company_id' => $companyId,
            'order_no' => $orderNo,
            'app_id' => $appId,
            'cash_type' => $cashType,
            'cash_amt' => number_format($cashAmount, 2, '.', ''),//提现金额 单位：元
            'member_id' => $memberId,
            'notify_url' => config('adapay.notify_url'),
            'api_method' => 'DrawCash.create',
            'operator' => $operator_name
        );
        $request = new Request();
        $resData = $request->call($api_params);
        // if ($resData['errcode'] != 0) {
        //     $status = 'failed';
        //     $remark = $resData['errmsg'] ?? '';
        //     app('log')->error('提现错误1:' . $resData['errmsg']);
        //     if ($isAuto == false) {
        //         throw new ResourceException($resData['errmsg'] ?? "汇付接口错误");
        //     }
        // }
        if ($resData['data']['status'] == 'failed') {
            $status = 'failed';
            $remark = $resData['data']['error_msg'] ?? '';
            app('log')->error('提现错误2:' . $resData['data']['error_msg']);
            if ($isAuto == false) {
                throw new ResourceException($resData['data']['error_msg']);
            }
        }

        //结算账户信息：银行卡和开户名
        if ($memberId == '0') {
            //主商户
            $adapayMerchantEntryRepository = app('registry')->getManager('default')->getRepository(AdapayMerchantEntry::class);
            $merchant = $adapayMerchantEntryRepository->getInfo(['company_id' => $companyId]);
            $bank_card_id = $merchant['card_id_mask'] ?? '';
            $bank_card_name = $merchant['card_name'] ?? '';
        } else {
            //子商户
            $accountFilter = [
                'member_id' => $memberId,
                'company_id' => $companyId,
            ];
            $rsAccount = (new SettleAccountService())->getInfo($accountFilter);
            $bank_card_id = $rsAccount['card_id'] ?? '';
            $bank_card_name = $rsAccount['card_name'] ?? '';
        }

        //生成提现记录
        $params = [
            'app_id' => $appId,
            'order_no' => $orderNo,
            'company_id' => $companyId,
            'adapay_member_id' => $memberId,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id, //这里的operator_id是实际店铺管理员的id
            'request_params' => json_encode($resData['data'] ?? []),
            'cash_id' => $resData['data']['id'] ?? '',
            'bank_card_id' => $bank_card_id,//卡号
            'bank_card_name' => $bank_card_name,//开户名
            'cash_amt' => bcmul($cashAmount, 100),
            'cash_type' => $cashType,
            'operator' => $operator_name,
            'status' => $status,
            'remark' => $remark,
        ];
        $result = $this->drawCashRepository->create($params);

        //自动提现不记录操作日志
        if ($isAuto == false) {
            (new AdapayLogService())->recordLogByType($companyId, 'withdraw');
        }

        return $result;
    }

    //是否主商户
    private function _isMainMerchant($operator_type)
    {
        if ($operator_type == 'admin') {
            return true;
        }
        if ($operator_type == 'staff') {
            return true;
        }
        return false;
    }

    public function getBalance($companyId, $filter = [])
    {
        $memberService = new MemberService();
        $params['app_id'] = $memberService->getAppId($companyId);
        if ($filter) {
            $params['member_id'] = $filter['member_id'] ?? 0;
            $filter['settle_account_id'] = $filter['settle_account_id'] ?? '';
            if ($filter['settle_account_id']) {
                $params['settle_account_id'] = $filter['settle_account_id'];
            }
        } else {
            $operator = $memberService->getOperator();
            if ($this->_isMainMerchant($operator['operator_type'])) {
                $params['member_id'] = 0;
            } else {
                $member = $memberService->getMemberInfo(['operator_type' => $operator['operator_type'], 'operator_id' => $operator['operator_id'], 'company_id' => $companyId]);
                if (!isset($member['member_id'])) {
                    throw new ResourceException("未入网,开户信息不全");
                }
                $params['settle_account_id'] = $member['settle_account_id'] ?? 0;
                $params['member_id'] = $member['member_id'] ?? 0;
            }
        }

        $params['company_id'] = $companyId;
        $params['notify_url'] = config('adapay.notify_url');
        $params['api_method'] = 'SettleAccount.balance';

        $request = new Request();
        $resData = $request->call($params);//调用汇付余额查询接口
        // if ($resData['errcode'] != 0) {
        //     throw new ResourceException($resData['errmsg'] ?? "汇付接口错误");
        // }
        if ($resData['data']['status'] == 'failed') {
            throw new ResourceException($resData['data']['error_msg']);
        }
        return $resData['data']['avl_balance'];
    }

    private function __check($filter, $operator = [], $memberId = 0)
    {
        $adapayCashRecordService = new AdapayDrawCashService();
        $cash_limit = (new SubMerchantService())->getDrawLimit($filter['company_id']);
        $cash_limit = $cash_limit ? $cash_limit['draw_limit'] : 0;

        //指定商户的冻结金额
        $subMerchantService = new SubMerchantService();
        $drawLimitList = $subMerchantService->getDrawLimitList($filter['company_id'], true);//指定商户冻结金额
        if (isset($drawLimitList[$memberId])) {
            $cash_limit = $drawLimitList[$memberId];
        }

        //子经销商禁用后没有保证金的限制
        if ($operator['operator_type'] == 'dealer') {
            $dealer = (new EmployeeService())->getInfoStaff($operator['operator_id'], $filter['company_id']);
            if ($dealer['is_disable'] == '1') {
                $cash_limit = 0;
            }
        } elseif ($operator['operator_type'] == 'distributor') {
            $distributorInfo = (new DistributorService())->getInfo(['company_id' => $filter['company_id'], 'distributor_id' => $operator['operator_id']]);
            if ($distributorInfo['is_valid'] != 'true') {
                $cash_limit = 0;
            }
        } else { //主商户没有限制
            $cash_limit = 0;
        }
        $cash_balance = $adapayCashRecordService->getBalance($filter['company_id']);
        $filter['cash_amt'] = number_format($filter['cash_amt'], 2, '.', '');
        if (bcadd($cash_limit / 100, $filter['cash_amt'], 2) > $cash_balance) {
            throw new ResourceException("余额不足");
        }
        return true;
    }

    //自动提现，将需要提现的商户信息进队列执行
    public function autoDrawCashQueue()
    {
        // app('log')->info('自动提现队列 - 开始');
        //查询所有的主商户
        $filter = [];
        $merchantService = new MerchantService();
        $merchantList = $merchantService->getLists($filter, $cols = '*');
        if (!$merchantList) {
            return true;
        }

        $subMerchantService = new SubMerchantService();
        $settleAccountService = new SettleAccountService();
        foreach ($merchantList as $v) {
            //自动提现设置开关
            $autoConfig = $subMerchantService->getAutoCashConfig($v['company_id']);
            $auto_draw_cash = $autoConfig['auto_draw_cash'] ?? 'N';
            if ($auto_draw_cash != 'Y') {
                // app('log')->info('自动提现队列 - 未开启: company_id=' . $v['company_id']);
                continue;//未开启
            }

            //是否在提现时间点
            $next_time = $autoConfig['next_time'] ?? strtotime('+10 days');
            if ($next_time > time()) {
                // app('log')->info('自动提现队列 - 未到时间: company_id=' . $v['company_id'] . ', 下次提现时间=' . date('y-m-d H:i:s', $next_time));
                continue;
            }

            //更新最后一次提现的时间节点
            if (!$this->getNextTime($autoConfig)) {
                // app('log')->info('自动提现队列 - 自动提现类型错误: company_id=' . $v['company_id']);
                continue;
            }
            $result = $subMerchantService->setAutoCashConfig($v['company_id'], $autoConfig);

            //主商户提现
            //$this->autoDrawCash($v['company_id']);
            $job = (new DrawCashJob($v['company_id']))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);

            //子商户提现
            $filter = ['company_id' => $v['company_id']];
            $accountList = $settleAccountService->getLists($filter, $cols = '*');
            foreach ($accountList as $account) {
                //$this->autoDrawCash($v['company_id'], $account['member_id'], $account['settle_account_id']);
                if (!$account['settle_account_id']) {
                    // app('log')->info('自动提现队列 - 结算账户未开通: member_id=' . $account['member_id']);
                }
                $job = (new DrawCashJob($account['company_id'], $account['member_id'], $account['settle_account_id']))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            }
        }
        // app('log')->info('自动提现队列 - 完成');
        return true;
    }

    //执行自动提现
    public function autoDrawCash($companyId, $memberId = 0, $settleAccountId = '')
    {
        app('log')->info('自动提现 - 开始: company_id=' . $companyId . ', member_id=' . $memberId);

        if ($memberId) {
            $adaPayMemberService = new MemberService();
            $adaPayMemberInfo = $adaPayMemberService->getInfo(['id' => $memberId]);
            if ($adaPayMemberInfo['operator_type'] == 'promoter') {
                app('log')->info('自动提现 - 分销员不提现: company_id=' . $companyId . ', member_id=' . $memberId);
                return true;
            }
        }

        $subMerchantService = new SubMerchantService();
        $autoConfig = $subMerchantService->getAutoCashConfig($companyId);
        $draw_limit = $subMerchantService->getDrawLimit($companyId);
        $freezeAmount = $draw_limit['draw_limit'] ?? 999;//冻结金额
        $drawLimitList = $subMerchantService->getDrawLimitList($companyId, true);//指定商户冻结金额
        if (isset($drawLimitList[$memberId])) {
            $freezeAmount = $drawLimitList[$memberId];
        }
        $freezeAmount = bcdiv($freezeAmount, 100, 2);//冻结金额: 元

        $cashType = $autoConfig['cash_type'] ?? 'D0';
        $minCashAmount = $autoConfig['min_cash'] ?? 999;//最低门槛

        //查询当前商户的余额
        $adaPayDrawCashService = new AdapayDrawCashService();
        $filter = [
            'member_id' => $memberId,
            'settle_account_id' => $settleAccountId,
        ];
        $availableBalance = $adaPayDrawCashService->getBalance($companyId, $filter);
        if ($memberId == 0) {
            $cashAmount = $availableBalance;//可提现金额: 元
        } else {
            $cashAmount = $availableBalance - $freezeAmount;//子商户需要扣除冻结金额: 元
        }

        //可提现金额是否满足最低门槛
        if ($cashAmount < $minCashAmount) {
            app('log')->info('自动提现 - 商户 member_id=' . $memberId . '提现余额不足:' . $cashAmount . ' < ' . $minCashAmount);
            return true;
        }

        //执行提现
        $this->drawCash($companyId, $cashType, $cashAmount, $memberId, '', true);

        app('log')->info('自动提现 - 完成: company_id=' . $companyId . ', member_id=' . $memberId);

        return true;
    }

    //获取下一次自动提现的时间节点
    public function getNextTime(&$config = [])
    {
        if (!$config) {
            return false;
        }

        $auto_type = $config['auto_type'];
        $auto_time = $config['auto_time'];
        $auto_day = $config['auto_day'];
        if ($auto_type == 'day') {//每天提现一次
            $next_time = date('Y-m-d', strtotime('+1 days')) . " {$auto_time}";
        } elseif ($auto_type == 'month') {//每月提现一次
            $next_time = date('Y-m', strtotime('+1 month')) . "-{$auto_day} {$auto_time}";
        } else {
            $config['auto_draw_cash'] = 'N';//关闭自动提现
            return false;
        }

        $config['next_time'] = strtotime($next_time);
        return true;
    }

    public function __call($name, $arguments)
    {
        return $this->drawCashRepository->$name(...$arguments);
    }
}
