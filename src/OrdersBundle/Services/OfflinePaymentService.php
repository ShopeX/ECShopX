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

namespace OrdersBundle\Services;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Entities\OfflinePayment;
use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\OrderAssociationService;
use EspierBundle\Entities\OfflineBankAccount;

class OfflinePaymentService
{
    use GetOrderServiceTrait;
    /**
     * @var \OrdersBundle\Repositories\OfflinePaymentRepository
     */
    public $repository;
    public $offlineBankAccountRepository;
    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(OfflinePayment::class);
        $this->offlineBankAccountRepository = app('registry')->getManager('default')->getRepository(OfflineBankAccount::class);
    }

    /**
     * 上传凭证
     * @param array $params
     */
    public function uploadVoucher($params)
    {
        // 定义过滤条件
        $filter = [
            'company_id' => $params['company_id'],
            'order_id' => $params['order_id']
        ];
        $orderService = new NormalOrderService();
        // 获取订单信息
        $orderInfo = $orderService->normalOrdersRepository->getInfo($filter);
        if (!$orderInfo) throw new ResourceException('订单不存在');
        if ($orderInfo['order_status'] != 'NOTPAY') throw new ResourceException('订单已支付，请勿重复操作');
        if ($orderInfo['pay_type'] != 'offline_pay') throw new ResourceException('订单支付方式错误');
        if (isset($params['promoter_user_id']) && $params['promoter_user_id']) {
            if ($orderInfo['salesman_id'] != $params['promoter_user_id']) throw new ResourceException('订单用户错误');
        } else {
            if ($orderInfo['user_id'] != $params['user_id']) throw new ResourceException('订单用户错误');
        }
        $offlinePayInfo = $this->repository->getInfo(['company_id' => $params['company_id'], 'order_id' => $params['order_id']]);
        if ($offlinePayInfo) throw new ResourceException('转账凭证已存在，请勿重复上传');
        $bankAccountInfo = $this->offlineBankAccountRepository->getInfo(['company_id' => $params['company_id'], 'id' => $params['bank_account_id']]);
        if (!$bankAccountInfo) throw new ResourceException('收款账户不存在');
        // 定义支付数据
        $paymentData = [
            'company_id' => $params['company_id'],
            'order_id' => $params['order_id'],
            'user_id' => $orderInfo['user_id'] ?? 0,
            'shop_id' => $orderInfo['shop_id'] ?? 0,
            'distributor_id' => $orderInfo['distributor_id'] ?? 0,
            'total_fee' => $orderInfo['total_fee'] ?? 0,
            'pay_fee' => 0,
            'check_status' => 0,
            'bank_account_id' => $bankAccountInfo['id'],
            'bank_account_name' => $bankAccountInfo['bank_account_name'] ?? '',
            'bank_account_no' => $bankAccountInfo['bank_account_no'] ?? '',
            'bank_name' => $bankAccountInfo['bank_name'] ?? '',
            'china_ums_no' => $bankAccountInfo['china_ums_no'] ?? '',
            'pay_account_name' => $params['pay_account_name'] ?? '',
            'pay_account_bank' => $params['pay_account_bank'] ?? '',
            'pay_account_no' => $params['pay_account_no'] ?? '',
            'pay_sn' => $params['pay_sn'] ?? '',
            'voucher_pic' => $params['voucher_pic'] ?? [],
            'transfer_remark' => $params['transfer_remark'] ?? '',
        ];

        // 创建支付记录
        $result = $this->repository->create($paymentData);
        // 更新订单主表中的offline_payment_status
        $orderService->normalOrdersRepository->updateOneBy(
            ['order_id' => $result['order_id']],
            ['offline_payment_status' => $result['check_status']],
        );
        // 订单流程操作记录
        $orderProcessLog = [
            'order_id' => $orderInfo['order_id'],
            'company_id' => $orderInfo['company_id'],
            'supplier_id' => $orderInfo['supplier_id'] ?? 0,
            'operator_type' => 'user',
            'operator_id' => $orderInfo['user_id'],
            'remarks' => '线下转账提交',
            'detail' => '订单号：' . $orderInfo['order_id'] . '，线下转账信息提交',
            'params' => $params,
        ];
        event(new OrderProcessLogEvent($orderProcessLog));
        return $result;
    }

    /**
     * 获取凭证
     */
    public function getVoucher($params)
    {
        return $this->repository->getInfo(['company_id' => $params['company_id'], 'order_id' => $params['order_id']]);
    }

    /**
     * 修改凭证
     */
    public function updateVoucher($params)
    {
        $info = $this->repository->getInfo(['id' => $params['id']]);
        if (!$info) throw new ResourceException('凭证不存在');
        $orderService = new NormalOrderService();
        // 获取订单信息
        $orderInfo = $orderService->normalOrdersRepository->getInfo(['company_id' => $params['company_id'], 'order_id' => $params['order_id']]);
        if (!$orderInfo) throw new ResourceException('订单不存在');
        if ($orderInfo['order_status'] != 'NOTPAY') throw new ResourceException('订单已支付，请勿重复操作');
        if ($orderInfo['pay_type'] != 'offline_pay') throw new ResourceException('订单支付方式错误');
        if (isset($params['promoter_user_id']) && $params['promoter_user_id']) {
            if ($orderInfo['salesman_id'] != $params['promoter_user_id']) throw new ResourceException('订单用户错误');
        } else {
            if ($orderInfo['user_id'] != $params['user_id']) throw new ResourceException('订单用户错误');
        }
        // 只有审核拒绝状态，可以修改凭证
        if (!in_array($info['check_status'], [2])) throw new ResourceException('不能修改凭证');
        // 获取收款账户信息
        $bankAccountInfo = $this->offlineBankAccountRepository->getInfo(['company_id' => $params['company_id'], 'id' => $params['bank_account_id']]);
        if (!$bankAccountInfo) throw new ResourceException('收款账户不存在');
        $params['bank_account_id'] = $bankAccountInfo['id'];
        $params['bank_account_name'] = $bankAccountInfo['bank_account_name'];
        $params['bank_account_no'] = $bankAccountInfo['bank_account_no'];
        $params['bank_name'] = $bankAccountInfo['bank_name'];
        $params['china_ums_no'] = $bankAccountInfo['china_ums_no'];
        // 修改凭证状态为待审核
        $params['check_status'] = 0;
        $result = $this->repository->updateOneBy(['id' => $params['id']], $params);
        // 更新订单主表中的offline_payment_status
        $orderService->normalOrdersRepository->updateOneBy(
            ['order_id' => $result['order_id']],
            ['offline_payment_status' => $result['check_status']],
        );
        // 订单流程操作记录
        $orderProcessLog = [
            'order_id' => $orderInfo['order_id'],
            'company_id' => $orderInfo['company_id'],
            'supplier_id' => $orderInfo['supplier_id'] ?? 0,
            'operator_type' => 'user',
            'operator_id' => $orderInfo['user_id'],
            'remarks' => '线下转账提交',
            'detail' => '订单号：' . $orderInfo['order_id'] . '，线下转账信息提交',
            'params' => $params,
        ];
        event(new OrderProcessLogEvent($orderProcessLog));
        return $result;
    }
    
    //订单取消
    public function doCancel($company_id, $order_id)
    {
        $filter = [
            'company_id' => $company_id,
            'order_id' => $order_id,
            'check_status' => 0,
        ];
        $rs = $this->repository->getInfo($filter);
        if ($rs) {
            $filter = [
                'id' => $rs['id'],
            ];
            $this->repository->updateOneBy($filter, ['check_status' => 9]);
        }
        return true;
    }

    /**
     * 审核
     */
    public function doCheck($params = [], $operater)
    {
        $id = $params['id'];
        $rs = $this->repository->getInfoById($id);
        if (!$rs) {
            throw new ResourceException('审核数据不存在');
        }
        if ($rs['check_status']) {
            throw new ResourceException('订单已审核，请勿重复操作');
        }
        // 获取收款账户信息
        $bankAccountInfo = $this->offlineBankAccountRepository->getInfo(['company_id' => $params['company_id'], 'id' => $params['bank_account_id']]);
        if (!$bankAccountInfo) throw new ResourceException('收款账户不存在');
        $params['bank_account_name'] = $bankAccountInfo['bank_account_name'];
        $params['bank_account_no'] = $bankAccountInfo['bank_account_no'];
        $params['bank_name'] = $bankAccountInfo['bank_name'];
        $params['china_ums_no'] = $bankAccountInfo['china_ums_no'];
        
        if ($params['check_status'] == 1) {
            $res = $this->doConfirm($params, $operater);
        } else {
            $res = $this->doRefuse($params, $operater);
        }
        return $res;
    }

    //订单更新成已支付状态
    public function doConfirm($params = [], $operater)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $id = $params['id'];
            $company_id = $params['company_id'];
            $order_id = $params['order_id'];
            $updateData = [];
            $cols = [
                'pay_fee', 'check_status', 'bank_account_id', 'bank_account_name', 'bank_account_no', 'bank_name', 'china_ums_no', 'pay_sn', 'pay_account_bank', 'pay_account_no',
                'operator_name', 'pay_account_name', 'voucher_pic', 'remark'
            ];
            foreach ($cols as $col) {
                if (!isset($params[$col])) continue;
                if (is_array($params[$col])) $params[$col] = json_encode($params[$col], 256);
                if ($col == 'pay_fee') $params[$col] = floatval($params[$col]);
                $updateData[$col] = $params[$col];
            }
            $res = $this->repository->updateOneBy(['id' => $id, 'company_id' => $company_id], $updateData);
            // 更新订单主表中的offline_payment_status
            $orderService = new NormalOrderService();
            $orderService->normalOrdersRepository->updateOneBy(
                ['order_id' => $res['order_id']],
                ['offline_payment_status' => $res['check_status']],
            );
            // 订单流程操作记录
            $orderProcessLog = [
                'order_id' => $params['order_id'],
                'company_id' => $params['company_id'],
                'operator_type' => $operater['operator_type'],
                'operator_id' => $operater['operator_id'],
                'remarks' => '转账确认',
                'detail' => '订单号：' . $params['order_id'] . '，线下转账审核通过',
                'params' => $params,
            ];
            $this->updateTradeStatus($company_id, $order_id, $orderProcessLog);

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
        return $res;
    }
    
    public function updateTradeStatus($company_id, $order_id, $orderProcessLog)
    {
        $tradeService = new TradeService();
        $tradeData = $tradeService->tradeRepository->getInfo(['company_id' => $company_id, 'order_id' => $order_id]);
        if (!$tradeData) {
            throw new ResourceException('交易单不存在');
        }
        $tradeId = $tradeData['trade_id'];
        $tradeService->tradeRepository->updateStatus($tradeId, 'SUCCESS');
        event(new OrderProcessLogEvent($orderProcessLog));
        $eventData = $tradeService->tradeRepository->find($tradeId);
        event(new TradeFinishEvent($eventData));
    }

    //更改审核状态，为已拒绝
    public function doRefuse($params = [], $operater)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 获取参数中的订单ID、公司ID和检查状态
            $id = $params['id'];
            // $order_id = $params['order_id'];
            $company_id = $params['company_id'];
            $check_status = $params['check_status'];

            // 更新检查状态为$check_status的订单信息，条件是order_id和company_id匹配
            $updateData = [
                'check_status' => $check_status,
                'remark' => $params['remark'],
                'operator_name' => $params['operator_name'],
                'bank_account_id' => $params['bank_account_id'],
                'bank_account_name' => $params['bank_account_name'],
                'bank_account_no' => $params['bank_account_no'],
                'bank_name' => $params['bank_name'],
                'china_ums_no' => $params['china_ums_no'],
            ];
            $res = $this->repository->updateOneBy(['id' => $id, 'company_id' => $company_id], $updateData);
            // 更新订单主表中的offline_payment_status
            $orderService = new NormalOrderService();
            $orderService->normalOrdersRepository->updateOneBy(
                ['order_id' => $res['order_id']],
                ['offline_payment_status' => $res['check_status']],
            );
            // 订单流程操作记录
            $orderProcessLog = [
                'order_id' => $params['order_id'],
                'company_id' => $params['company_id'],
                'operator_type' => $operater['operator_type'],
                'operator_id' => $operater['operator_id'],
                'remarks' => '转账确认',
                'detail' => '订单号：' . $params['order_id'] . '，线下转账审核拒绝',
                'params' => $params,
            ];
            event(new OrderProcessLogEvent($orderProcessLog));
            // 提交事务
            $conn->commit();
        } catch (Exception $e) {
            // 回滚事务
            $conn->rollback();
            // 抛出异常并抛出错误消息
            throw new Exception($e->getMessage());
        }
        return $res;
    }

    /**
     * 获取凭证详情
     * @param array $params
     */
    public function getDetail($params)
    {
        $info = $this->repository->getInfoById($params['id']);
        if (!$info) {
            throw new ResourceException('凭证不存在');
        }
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($info['company_id'], $info['order_id']);
        if (!$order) {
            return $this->response->error('此订单不存在！', 422);
        }
        $orderService = $this->getOrderServiceByOrderInfo($order);
        $result = $orderService->getOrderInfo($info['company_id'], $info['order_id']);
        $info['order_info'] = $result['orderInfo'];
        $info['tradeInfo'] = $result['tradeInfo'];
        return $info;
    }
    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->repository->$method(...$parameters);
    }
}

