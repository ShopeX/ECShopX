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

namespace PopularizeBundle\Services;

use AdaPayBundle\Services\AdapayDrawCashService;
use AdaPayBundle\Services\SettleAccountService;
use AdaPayBundle\Services\AdapayPromoterService;
use HfPayBundle\Services\HfpayMerchantPaymentService;
use PopularizeBundle\Entities\PromoterCashWithdrawal;

use PaymentBundle\Services\PaymentsService;
use PaymentBundle\Services\Payments\WechatPayService;
use OrdersBundle\Services\MerchantTradeService;


use Dingo\Api\Exception\ResourceException;

class CashWithdrawalService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(PromoterCashWithdrawal::class);
    }

    /**
     * 获取提现支付记录
     */
    public function getMerchantTradeList($companyId, $cashWithdrawalId)
    {
        $filter = [
            'rel_scene_id' => $cashWithdrawalId,
            'company_id' => $companyId,
            'rel_scene_name' => 'popularize_rebate_cash_withdrawal'
        ];

        $merchantTradeService = new MerchantTradeService();
        return $merchantTradeService->lists($filter);
    }

    /**
     * 用户申请提现
     */
    public function applyCashWithdrawal($data)
    {
        // 根据手机号获取当前用户是否为分销商
        $promoterService = new PromoterService();
        $promoterInfo = $promoterService->getPromoterInfo($data['company_id'], $data['user_id']);
        if (!$promoterInfo || !$promoterInfo['is_promoter']) {
            throw new ResourceException('不是推广员，不可以申请');
        }

        if ($promoterInfo['disabled']) {
            throw new ResourceException('推广员已被商家禁用，请联系商家');
        }

        if ($data['pay_type'] == 'alipay') {
            if (isset($promoterInfo['alipay_account']) && isset($promoterInfo['alipay_name'])) {
                $data['pay_account'] = $promoterInfo['alipay_account'];
                $data['account_name'] = $promoterInfo['alipay_name'];
            } else {
                throw new ResourceException('请先设置提现支付宝账号');
            }
        }

        $settingService = new SettingService();
        $config = $settingService->getConfig($data['company_id']);
        if ($config && $data['money'] < ($config['limit_rebate'] * 100)) {
            throw new ResourceException('最少申请提现' . ($config['limit_rebate']) . '元');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $insertData = [
                'company_id' => $data['company_id'],
                'pay_account' => ($data['pay_type'] == 'alipay') ? $data['pay_account'] : $data['open_id'],
                'user_id' => $data['user_id'],
                'pay_type' => $data['pay_type'],
                'mobile' => $data['mobile'],
                'account_name' => $data['account_name'],
                'wxa_appid' => $data['wxa_appid'],
                'money' => floor($data['money']),
                'status' => 'apply',
            ];

            $return = $this->entityRepository->create($insertData);

            $promoterCountService = new PromoterCountService();
            // 判断当前用户申请的提现金额是否合法
            $promoterCountService->applyCashWithdrawal($data['company_id'], $data['user_id'], floor($data['money']));

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $return;
    }

    public function updateStatus($companyId, $applyId, $applyStatus, $error_msg = [])
    {
        $filter = ['id' => $applyId];
        $updateData = [
            'status' => $applyStatus,
            'remarks' => implode(';', $error_msg),
        ];
        $this->entityRepository->updateOneBy($filter, $updateData);

        if ($applyStatus == 'success') {
            $info = $this->entityRepository->getInfo(['company_id' => $companyId, 'id' => $applyId]);
            $promoterCountService = new PromoterCountService();
            $promoterCountService->agreeCashWithdrawal($companyId, $info['user_id'], $info['money']);
        }
    }

    /**
     * 处理佣金提现
     */
    public function processCashWithdrawal($companyId, $cashWithdrawalId, $clientIp = null)
    {
        $info = $this->entityRepository->getInfo(['company_id' => $companyId, 'id' => $cashWithdrawalId]);
        if (!$info) {
            throw new ResourceException('处理的佣金提现申请不存在');
        }

        if ($info['status'] != 'apply') {
            throw new ResourceException('当前佣金提现正在处理或已完成');
        }

        //将提现状态设置为处理中
        $this->entityRepository->updateOneBy(['id' => $cashWithdrawalId], ['status' => 'process']);

        $data = [];
        switch ($info['pay_type']) {

            case 'wechat'://微信支付
                $data = $this->payToWechat($cashWithdrawalId, $info, $clientIp, $companyId);
                break;

            case 'hfpay'://微信支付(汇付渠道)
                $data = $this->payToHfpay($companyId, $info, $cashWithdrawalId, $clientIp);
                break;

            case 'bankcard'://银行卡
                $data = $this->payToBankcard($companyId, $info);
                break;

            default:
                $data['status'] = 'SUCCESS';
        }

        //如果支付成功
        if ($data['status'] == 'SUCCESS') {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                $this->entityRepository->updateOneBy(['id' => $cashWithdrawalId], ['status' => 'success']);

                $promoterCountService = new PromoterCountService();
                $promoterCountService->agreeCashWithdrawal($companyId, $info['user_id'], $info['money']);

                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                // 如果一直为处理中，那么提供异常处理机制，通过申请单查询到最近一笔付款
                // 如果有付款则到微信查询是否已经付款成功，如果付款成功则进行后续处理
                // 否则改为待处理 apply
                throw new ResourceException('付款成功，服务器异常，请通过异常处理重试');
            } catch (\Throwable $e) {
                $conn->rollback();
                throw new ResourceException('付款成功，服务器异常，请通过异常处理重试');
            }
        } elseif ($data['status'] == 'PROCESS') {
            //adapay提现T1类型有处理时间，回调时改提现状态
            $this->entityRepository->updateOneBy(['id' => $cashWithdrawalId], ['status' => 'process']);
        } else {
            $this->entityRepository->updateOneBy(['id' => $cashWithdrawalId], ['status' => 'apply']);
            throw new ResourceException($data['error_desc']);
        }

        return true;
    }

    /**
     * 取消或拒绝提现申请
     */
    public function rejectCashWithdrawal($companyId, $cashWithdrawalId, $processType = 'reject', $remarks = null)
    {
        $info = $this->entityRepository->getInfo(['company_id' => $companyId, 'id' => $cashWithdrawalId]);
        if (!$info) {
            throw new ResourceException('处理的佣金提现申请不存在');
        }

        if ($info['status'] != 'apply') {
            throw new ResourceException('当前佣金提现正在处理或已完成');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $processType = ($processType == 'reject') ? 'reject' : 'cancel';
            $updateData['status'] = $processType;
            if ($remarks) {
                $updateData['remarks'] = $remarks;
            }
            $data = $this->entityRepository->updateOneBy(['company_id' => $companyId, 'id' => $cashWithdrawalId], $updateData);

            $promoterCountService = new PromoterCountService();
            $promoterCountService->rejectCashWithdrawal($companyId, $info['user_id'], $info['money']);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException('系统错误，请稍后再试');
        } catch (\Throwable $e) {
            $conn->rollback();
            throw new ResourceException('系统错误，请稍后再试');
        }

        return true;
    }

    /**
     * 推广业绩以及提现相关统计
     * @param $companyId
     * @return mixed
     */
    public function cashWithdrawalCount($companyId)
    {
        $applyFilter = [
            'company_id' => $companyId,
            'status' => 'apply',
        ];
        $count['apply'] = $this->entityRepository->sum($applyFilter, 'money');
        $successFilter = [
            'company_id' => $companyId,
            'status' => 'success',
        ];
        $count['success'] = $this->entityRepository->sum($successFilter, 'money');
        $userCountFilter = [
            'company_id' => $companyId,
        ];
        $count['userCount'] = $this->entityRepository->userCount($userCountFilter);

        $brokerageFilter = [
            'company_id' => $companyId,
            'is_close' => true,
        ];
        $brokerageService = new BrokerageService();
        $brokerageCount = $brokerageService->brokerageRepository->sumRebate($brokerageFilter);

        //        $taskBrokerageCountFilter = [
        //            'company_id' => $companyId,
        //        ];
        //        $taskBrokerageService = new TaskBrokerageService();
        //        $taskBrokerageCount = $taskBrokerageService->taskBrokerageCountRepository->getRebateMoneyTotal($taskBrokerageCountFilter);
        $count['all'] = $brokerageCount;
        return $count;
    }

    private function payToWechat($cashWithdrawalId, $info, $clientIp, $companyId)
    {
        //默认直接微信付款
        $paymentsService = new PaymentsService(new WechatPayService());
        //支付参数
        $paymentData = [
            'rel_scene_id' => $cashWithdrawalId,
            'rel_scene_name' => 'popularize_rebate_cash_withdrawal',
            're_user_name' => $info['account_name'],
            'mobile' => $info['mobile'],
            'amount' => $info['money'], //提现金额 （分）
            'user_id' => $info['user_id'],
            'open_id' => $info['pay_account'],
            'payment_desc' => '佣金提现',
            'spbill_create_ip' => $clientIp ?: '127.0.0.1'
        ];
        return $paymentsService->merchantPayment($companyId, $info['wxa_appid'], $paymentData);
    }

    private function payToHfpay($companyId, $info, $cashWithdrawalId, $clientIp)
    {
        $paymentData = [
            'company_id' => $companyId,
            'user_id' => $info['user_id'],
            'rel_scene_id' => $cashWithdrawalId,
            'rel_scene_name' => 'popularize_rebate_cash_withdrawal',
            'trans_amt' => $info['money'], //提现金额 （分）,
            'spbill_create_ip' => $clientIp ?: '127.0.0.1'
        ];
        $service = new HfpayMerchantPaymentService();
        return $service->merchantPayment($paymentData);
    }

    private function payToBankcard($companyId, $info)
    {
        $result = [];
        $transAmt = bcdiv($info['money'], 100, 2);//转换成元

        //查询主商户的余额
        $adaPayDrawCashService = new AdapayDrawCashService();
        $filter = ['member_id' => '0'];
        $availableBalance = $adaPayDrawCashService->getBalance($companyId, $filter);
        app('log')->info("佣金提现：availableBalance={$availableBalance}, transAmt={$transAmt}");
        if ($transAmt > $availableBalance) {
            return ['status' => 'FAILED', 'error_desc' => '主商户余额不足'];
        }

        //推广员的汇付用户id
        $fromMemberId = '0';
        $toMemberId = $info['user_id'];
        $adapayPromoterService = new AdapayPromoterService();
        $promoterInfo = $adapayPromoterService->getCertInfo($companyId, $toMemberId);
        if ($promoterInfo) {
            $toMemberId = $promoterInfo['member_id'] ?? '';
        }
        if (!$toMemberId) {
            return ['status' => 'FAILED', 'error_desc' => '推广员账户不存在'];
        }

        //转账给推广员
        //查询推广员提现的账户余额
        $filter = [
            'member_id' => $toMemberId,
            'settle_account_id' => $promoterInfo['settle_account_id'],
        ];
        $availableBalance = $adaPayDrawCashService->getBalance($companyId, $filter);
//        $availableBalance = floatval($availableBalance);
        $settleAccountService = new SettleAccountService();
        if ($availableBalance < $transAmt) {
            //如果有余额，那么不再重复转账
            $resData = $settleAccountService->transfer($companyId, $fromMemberId, $toMemberId, $transAmt, '推广员提现');
            app('log')->info("佣金提现：transfer=" . json_encode($resData, 256));
            // if ($resData['errcode'] != 0) {
            //     return ['status' => 'FAILED', 'error_desc' => ($resData['errmsg'] ?? '未知错误')];
            // }
            if ($resData['data']['status'] == 'failed') {
                return ['status' => 'FAILED', 'error_desc' => ($resData['data']['error_msg'] ?? '未知错误')];
            }
        }

        //子商户提现
        //如果异步提现失败，要更改提现申请的状态
        try {
            //提现类型默认为 T1
            $remark = 'apply_id:' . $info['id'];//提现回调的时候根据id来更新状态
            $result = $adaPayDrawCashService->drawCash($companyId, 'T1', $transAmt, $toMemberId, $remark);
            app('log')->info("佣金提现：drawCash=" . json_encode($result, 256));
            if ($result['status'] ?? '' == 'pending') {
                $result['status'] = 'PROCESS';
            } else {
                $result['status'] = 'FAILED';
                $result['error_desc'] = '生成提现记录错误';
            }
        } catch (\Exception $e) {
            $resData = $settleAccountService->transfer($companyId, $toMemberId, $fromMemberId, $transAmt, '推广员提现失败-转账回溯');
            app('log')->info("佣金提现：transfer-reverse=" . json_encode($resData, 256));
            $errmsg = '';
            // if ($resData['errcode'] != 0) {
            //     $errmsg = '转账回溯错误:'.$resData['errmsg'];
            // }
            if ($resData['data']['status'] == 'failed') {
                $errmsg = '转账回溯错误:'.$resData['data']['error_msg'];
            }
            $errDesc = $errmsg ? $e->getMessage().'-'.$errmsg : $e->getMessage();
            //如果提现失败，不能直接抛出错误，要更改提现申请的状态
            return ['status' => 'FAILED', 'error_desc' => $errDesc];
        }

        return $result;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
