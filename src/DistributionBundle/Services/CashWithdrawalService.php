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
        } catch (\Exception $e) {
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

        //将提现状态设置为处理中
        $this->entityRepository->updateOneBy(['id' => $cashWithdrawalId], ['status' => 'process']);

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
        //如果支付成功
        if ($data['status'] == 'SUCCESS') {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                $this->entityRepository->updateOneBy(['id' => $cashWithdrawalId], ['status' => 'success']);

                $distributeCountService = new DistributeCountService();
                $distributeCountService->agreeCashWithdrawal($companyId, $info['distributor_id'], $info['money']);

                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                // 如果一直为处理中，那么提供异常处理机制，通过申请单查询到最近一笔付款
                // 如果有付款则到微信查询是否已经付款成功，如果付款成功则进行后续处理
                // 否则改为待处理 apply
                throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.payment_success_server_error'));
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.system_error_try_later'));
        }

        return true;
    }


    /**
     * 业务员申请提现
     */
    public function salesmanApplyCashWithdrawal($data)
    {
        // 根据手机号获取当前用户是否为分销商
        $distributorService = new DistributorService();
        $filter_d = [
            'distributor_id' => $data['distributor_id'], 
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

        //1 获取全部佣金总额
        $brokerageService = new BrokerageService();
        $filter['company_id']     = $data['company_id']; 
        $filter['distributor_id'] = $data['distributor_id']; 
        $filter['user_id']        = $data['user_id']; 
        $countDataShop = $brokerageService->getSalesmanBrokerageCount($filter, 1 ,1000);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-countDataShop:". json_encode($countDataShop));

        //2 获取有效提现总额
        $filter_withdrow = $filter;
        $filter_withdrow['status'] = ['apply','process','success'];
        $list_withdraw = $this->entityRepository->lists($filter_withdrow);
        // app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-list_withdraw:". json_encode($list_withdraw));
        
        $sum_withdrawal_money = array_sum(array_column($list_withdraw['list'], 'money'));//item_sum_total_fee
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":sum_withdrawal_money:". json_encode($sum_withdrawal_money));

        //3 检查（佣金总额 > 提现总额 + 申请金额） 
        if($countDataShop['rebate_sum_close'] < ($sum_withdrawal_money + floor($data['money']))){
            $money_can_withdrawal = $countDataShop['rebate_sum_close']  - $sum_withdrawal_money ;
            throw new ResourceException(trans('DistributionBundle/Services/CashWithdrawalService.withdrawal_amount_exceeded') . ($money_can_withdrawal  / 100) . trans('DistributionBundle/Services/CashWithdrawalService.yuan_period'));
        }

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
            // $distributeCountService->salesmanApplyCashWithdrawal($data['company_id'], $distributorInfo['distributor_id'], floor($data['money']));

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        return $return;
    }


    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
