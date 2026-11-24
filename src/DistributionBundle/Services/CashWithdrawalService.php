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

namespace DistributionBundle\Services;

use DistributionBundle\Entities\CashWithdrawal;
use PaymentBundle\Services\PaymentsService;
use PaymentBundle\Services\Payments\WechatPayService;
use OrdersBundle\Services\MerchantTradeService;
use PopularizeBundle\Services\BrokerageService;

use Dingo\Api\Exception\ResourceException;

class CashWithdrawalService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CashWithdrawal::class);
    }

    /**
     * 获取提现支付记录
     */
    public function getMerchantTradeList($companyId, $cashWithdrawalId)
    {
        $filter = [
            'rel_scene_id' => $cashWithdrawalId,
            'company_id' => $companyId,
            'rel_scene_name' => 'rebate_cash_withdrawal'
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
        $distributorService = new DistributorService();
        $filter_d = [
            'mobile' => $data['mobile'],
            'company_id' => $data['company_id']
        ];

        $distributorInfo = $distributorService->getInfo($filter_d);
        if (!$distributorInfo) {
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.distributor_invalid'));
        }

        $basicConfigService = new BasicConfigService();
        $config = $basicConfigService->getInfoById($data['company_id']);
        if ($config && $data['money'] < $config['limit_rebate']) {
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.minimum_withdrawal_amount') . ($config['limit_rebate'] / 100) . trans('DistributionBundle/Services/CashWithdrawalService.yuan'));
        }

        $distributeCountService = new DistributeCountService();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $insertData = [
                'company_id' => $data['company_id'],
                'open_id' => $data['open_id'],
                'user_id' => $data['user_id'],
                'wxa_appid' => $data['wxa_appid'],
                'money' => floor($data['money']),
                'distributor_id' => $distributorInfo['distributor_id'],
                'distributor_name' => $distributorInfo['name'],
                'distributor_mobile' => $distributorInfo['mobile'],
                'status' => 'apply',
            ];

            $return = $this->entityRepository->create($insertData);

            // 判断当前用户申请的提现金额是否合法
            $distributeCountService->applyCashWithdrawal($data['company_id'], $distributorInfo['distributor_id'], floor($data['money']));

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $return;
    }

    /**
     * 处理佣金提现
     */
    public function processCashWithdrawal($companyId, $cashWithdrawalId, $clientIp = null)
    {
        $info = $this->entityRepository->getInfo(['company_id' => $companyId, 'id' => $cashWithdrawalId]);
        if (!$info) {
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.withdrawal_request_not_exist'));
        }

        if ($info['status'] != 'apply') {
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.withdrawal_processing_completed'));
        }

        // 条件抢占：仅 apply → process 成功才允许打款，防止并发重复打款
        if ($this->entityRepository->claimProcess($companyId, $cashWithdrawalId) < 1) {
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.withdrawal_processing_completed'));
        }

        try {
            //默认直接微信付款
            $paymentsService = new PaymentsService(new WechatPayService());
            //支付参数
            $paymentData = [
                'rel_scene_id' => $cashWithdrawalId,
                'rel_scene_name' => 'rebate_cash_withdrawal',
                're_user_name' => $info['distributor_name'],
                'mobile' => $info['distributor_mobile'],
                'amount' => $info['money'], //提现金额 （分）
                'user_id' => $info['user_id'],
                'open_id' => $info['open_id'],
                'payment_desc' => trans('DistributionBundle/Services/CashWithdrawalService.commission_withdrawal'),
                'spbill_create_ip' => $clientIp ?: '127.0.0.1'
            ];

            $data = $paymentsService->merchantPayment($companyId, $info['wxa_appid'], $paymentData);
        } catch (\Throwable $e) {
            // 打款异常回滚为 apply，避免永久卡在 process
            $this->entityRepository->updateStatusIf($companyId, $cashWithdrawalId, 'process', 'apply');
            throw new ResourceException($e->getMessage());
        }

        //如果支付成功
        if ($data['status'] == 'SUCCESS') {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                if ($this->entityRepository->updateStatusIf($companyId, $cashWithdrawalId, 'process', 'success') < 1) {
                    throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.payment_success_server_error'));
                }

                $distributeCountService = new DistributeCountService();
                $distributeCountService->agreeCashWithdrawal($companyId, $info['distributor_id'], $info['money']);

                $conn->commit();
            } catch (\Throwable $e) {
                $conn->rollback();
                // 付款可能已成功但本地结算失败，保持 process 供补偿；非本路径再抛
                throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.payment_success_server_error'));
            }
        } elseif ($data['status'] == 'PROCESS') {
            // adapay 等异步渠道：保持 process，等待回调补偿
        } else {
            $this->entityRepository->updateStatusIf($companyId, $cashWithdrawalId, 'process', 'apply');
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
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.withdrawal_request_not_exist'));
        }

        if ($info['status'] != 'apply') {
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.withdrawal_processing_completed'));
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

            $distributeCountService = new DistributeCountService();
            $distributeCountService->rejectCashWithdrawal($companyId, $info['distributor_id'], $info['money']);

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.system_error_try_later'));
        }

        return true;
    }


    
    /**
     * 取消或拒绝提现申请
     */
    public function successCashWithdrawal($companyId, $cashWithdrawalId, $processType = 'apply', $remarks = null)
    {
        $info = $this->entityRepository->getInfo(['company_id' => $companyId, 'id' => $cashWithdrawalId]);
        if (!$info) {
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.withdrawal_request_not_exist'));
        }

        if ($info['status'] != 'apply') {
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.withdrawal_processing_completed'));
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $processType = ($processType == 'success') ? 'success' : '';
            $updateData['status'] = $processType;
            if ($remarks) {
                $updateData['remarks'] = $remarks;
            }
            $data = $this->entityRepository->updateOneBy(['company_id' => $companyId, 'id' => $cashWithdrawalId], $updateData);

            $distributeCountService = new DistributeCountService();
            $distributeCountService->rejectCashWithdrawal($companyId, $info['distributor_id'], $info['money']);

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollback();
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.system_error_try_later'));
        }

        return true;
    }


    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    private function redisLock(string $key, \Closure $func, int $expire = 5, int $times = 3, int $sleep = 500)
    {
        for ($i = 0; $i < $times; $i++) {
            $redis = app('redis')->connection('default');
            if ($redis->set($key, 1, 'NX', 'EX', $expire)) {
                try {
                    $re = $func();
                } finally {
                    $redis->del($key);
                }
                return $re ?: true;
            }
            usleep($sleep * 1000);
        }

        return false;
    }
}
