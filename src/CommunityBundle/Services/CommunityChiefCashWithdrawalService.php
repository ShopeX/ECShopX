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

namespace CommunityBundle\Services;

use CommunityBundle\Entities\CommunityChiefCashWithdrawal;

use PaymentBundle\Services\PaymentsService;
use PaymentBundle\Services\Payments\WechatPayService;
use OrdersBundle\Services\MerchantTradeService;

use Dingo\Api\Exception\ResourceException;

class CommunityChiefCashWithdrawalService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CommunityChiefCashWithdrawal::class);
    }

    public function getChiefRebateCount($companyId, $chiefIds)
    {
        $conn = app('registry')->getConnection('default');
        
        $chiefRebate = [];

        //佣金总额
        $criteria = $conn->createQueryBuilder();
        $totalList = $criteria->select('rel.chief_id,sum(o.total_fee - coalesce(r.refund_fee, 0)) as total_fee,sum(floor((o.total_fee - coalesce(r.refund_fee, 0)) * rel.rebate_ratio / 100)) as rebate_total')
            ->from('orders_normal_orders', 'o')
            ->leftJoin('o', 'community_order_rel_activity', 'rel', 'o.order_id = rel.order_id')
            ->leftJoin('o', '(select order_id,sum(refund_fee) as refund_fee from aftersales_refund a where refund_status in("AUDIT_SUCCESS", "SUCCESS", "CHANGE") and order_id in (select order_id from orders_normal_orders where order_type="normal" and order_class="community") group by order_id)', 'r', 'o.order_id = r.order_id')
            ->andWhere($criteria->expr()->eq('o.order_type', $criteria->expr()->literal('normal')))
            ->andWhere($criteria->expr()->eq('o.order_class', $criteria->expr()->literal('community')))
            ->andWhere($criteria->expr()->eq('o.pay_status', $criteria->expr()->literal('PAYED')))
            ->andWhere($criteria->expr()->neq('o.order_status', $criteria->expr()->literal('CANCEL')))
            ->andWhere($criteria->expr()->eq('o.company_id', $companyId))
            ->andWhere($criteria->expr()->in('rel.chief_id', (array)$chiefIds))
            ->groupBy('chief_id')->execute()->fetchAll();
        foreach ($totalList as $value) {
            $chiefRebate[$value['chief_id']]['total_fee'] = $value['total_fee'];
            $chiefRebate[$value['chief_id']]['rebate_total'] = $value['rebate_total'];
        }

        //已结算
        $criteria = $conn->createQueryBuilder();
        $closeList = $criteria->select('rel.chief_id,sum(floor((o.total_fee - coalesce(r.refund_fee, 0)) * rel.rebate_ratio / 100)) as close_rebate')
            ->from('orders_normal_orders', 'o')
            ->leftJoin('o', 'community_order_rel_activity', 'rel', 'o.order_id = rel.order_id')
            ->leftJoin('o', '(select order_id,sum(refund_fee) as refund_fee from aftersales_refund a where refund_status in("AUDIT_SUCCESS", "SUCCESS", "CHANGE") and order_id in (select order_id from orders_normal_orders where order_type="normal" and order_class="community") group by order_id)', 'r', 'o.order_id = r.order_id')
            ->andWhere($criteria->expr()->eq('o.order_type', $criteria->expr()->literal('normal')))
            ->andWhere($criteria->expr()->eq('o.order_class', $criteria->expr()->literal('community')))
            ->andWhere($criteria->expr()->eq('o.pay_status', $criteria->expr()->literal('PAYED')))
            ->andWhere($criteria->expr()->neq('o.order_status', $criteria->expr()->literal('CANCEL')))
            ->andWhere($criteria->expr()->eq('o.company_id', $companyId))
            ->andWhere($criteria->expr()->isNotNull('o.order_auto_close_aftersales_time'))
            ->andWhere($criteria->expr()->lt('o.order_auto_close_aftersales_time', time()))
            ->andWhere($criteria->expr()->in('rel.chief_id', (array)$chiefIds))
            ->groupBy('chief_id')->execute()->fetchAll();
        foreach ($closeList as $value) {
            $chiefRebate[$value['chief_id']]['close_rebate'] = $value['close_rebate'];
        }

        //申请提现
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $applyList = $criteria->select('chief_id,sum(money) as money')
            ->from('community_chief_cash_withdrawal')
            ->andWhere($criteria->expr()->eq('status', $criteria->expr()->literal('apply')))
            ->andWhere($criteria->expr()->eq('company_id', $companyId))
            ->andWhere($criteria->expr()->in('chief_id', (array)$chiefIds))
            ->groupBy('chief_id')->execute()->fetchAll();
        foreach ($applyList as $value) {
            $chiefRebate[$value['chief_id']]['freeze_cash_withdrawal_rebate'] = $value['money'];
        }

        //已提现
        $criteria = $conn->createQueryBuilder();
        $successList = $criteria->select('chief_id,sum(money) as money')
            ->from('community_chief_cash_withdrawal')
            ->andWhere($criteria->expr()->eq('status', $criteria->expr()->literal('success')))
            ->andWhere($criteria->expr()->eq('company_id', $companyId))
            ->andWhere($criteria->expr()->in('chief_id', (array)$chiefIds))
            ->groupBy('chief_id')->execute()->fetchAll();
        foreach ($successList as $value) {
            $chiefRebate[$value['chief_id']]['payed_rebate'] = $value['money'];
        }

        foreach ($chiefRebate as $key => $value) {
            $chiefRebate[$key]['no_close_rebate'] = $value['rebate_total'] - ($value['close_rebate'] ?? 0); //未结算
            $chiefRebate[$key]['cash_withdrawal_rebate'] = ($value['close_rebate'] ?? 0) - ($value['payed_rebate'] ?? 0) - ($value['freeze_cash_withdrawal_rebate'] ?? 0); //可提现
        }

        return $chiefRebate;
    }

    public function cashWithdrawalCount($companyId, $distributorId = 0)
    {
        $conn = app('registry')->getConnection('default');
        
        $chiefRebate = [];

        //佣金总额
        $criteria = $conn->createQueryBuilder();
        $result['rebate_total'] = $criteria->select('sum(floor((o.total_fee - coalesce(r.refund_fee, 0)) * rel.rebate_ratio / 100)) as rebate_total')
            ->from('orders_normal_orders', 'o')
            ->leftJoin('o', 'community_order_rel_activity', 'rel', 'o.order_id = rel.order_id')
            ->leftJoin('o', '(select order_id,sum(refund_fee) as refund_fee from aftersales_refund a where refund_status in("AUDIT_SUCCESS", "SUCCESS", "CHANGE") and order_id in (select order_id from orders_normal_orders where order_type="normal" and order_class="community") group by order_id)', 'r', 'o.order_id = r.order_id')
            ->andWhere($criteria->expr()->eq('o.order_type', $criteria->expr()->literal('normal')))
            ->andWhere($criteria->expr()->eq('o.order_class', $criteria->expr()->literal('community')))
            ->andWhere($criteria->expr()->eq('o.pay_status', $criteria->expr()->literal('PAYED')))
            ->andWhere($criteria->expr()->neq('o.order_status', $criteria->expr()->literal('CANCEL')))
            ->andWhere($criteria->expr()->eq('o.company_id', $companyId))
            ->andWhere($criteria->expr()->eq('o.distributor_id', $distributorId))
            ->execute()->fetchColumn();

        //申请提现
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $result['freeze_cash_withdrawal_rebate'] = $criteria->select('coalesce(sum(money), 0) as money')
            ->from('community_chief_cash_withdrawal')
            ->andWhere($criteria->expr()->eq('status', $criteria->expr()->literal('apply')))
            ->andWhere($criteria->expr()->eq('company_id', $companyId))
            ->andWhere($criteria->expr()->eq('distributor_id', $distributorId))
            ->execute()->fetchColumn();

        //已提现
        $criteria = $conn->createQueryBuilder();
        $result['payed_rebate'] = $criteria->select('coalesce(sum(money), 0) as money')
            ->from('community_chief_cash_withdrawal')
            ->andWhere($criteria->expr()->eq('status', $criteria->expr()->literal('success')))
            ->andWhere($criteria->expr()->eq('company_id', $companyId))
            ->andWhere($criteria->expr()->eq('distributor_id', $distributorId))
            ->execute()->fetchColumn();

        //申请提现人数
        $criteria = $conn->createQueryBuilder();
        $result['apply_chief_num'] = $criteria->select('count(distinct chief_id)')
            ->from('community_chief_cash_withdrawal')
            ->andWhere($criteria->expr()->eq('company_id', $companyId))
            ->andWhere($criteria->expr()->eq('distributor_id', $distributorId))
            ->execute()->fetchColumn();

        return $result;
    }

    /**
     * 获取提现支付记录
     */
    public function getMerchantTradeList($companyId, $cashWithdrawalId)
    {
        // KEY: U2hvcEV4
        $filter = [
            'rel_scene_id' => $cashWithdrawalId,
            'company_id' => $companyId,
            'rel_scene_name' => 'community_chief_cash_withdrawal'
        ];

        $merchantTradeService = new MerchantTradeService();
        return $merchantTradeService->lists($filter);
    }

    /**
     * 用户申请提现
     */
    public function applyCashWithdrawal($data)
    {
        $chiefService = new CommunityChiefService();
        $chief = $chiefService->getInfo(['chief_id' => $data['chief_id']]);
        if (!$chief) {
            throw new ResourceException('不是团长，不可以申请');
        }

        if ($data['pay_type'] == 'alipay') {
            if (isset($chief['alipay_account']) && isset($chief['alipay_name'])) {
                $data['pay_account'] = $chief['alipay_account'];
                $data['account_name'] = $chief['alipay_name'];
            } else {
                throw new ResourceException('请先设置提现支付宝账号信息');
            }
        }

        if ($data['pay_type'] == 'bankcard') {
            if (isset($chief['bank_name']) && isset($chief['bankcard_no'])) {
                $data['bank_name'] = $chief['bank_name'];
                $data['pay_account'] = $chief['bankcard_no'];
            } else {
                throw new ResourceException('请先设置提现银行卡信息');
            }
        }

        $chiefRebate = $this->getChiefRebateCount($data['company_id'], $data['chief_id']);
        if (!isset($chiefRebate[$data['chief_id']])) {
            throw new ResourceException('申请提现金额额度超出限制');
        }
        if (($chiefRebate[$data['chief_id']]['cash_withdrawal_rebate'] ?? 0) <  floor($data['money'])) {
            throw new ResourceException('申请提现金额额度超出限制');
        }

        $insertData = [
            'company_id' => $data['company_id'],
            'distributor_id' => $data['distributor_id'],
            'pay_account' => $data['pay_type'] == 'wechat' ? $data['open_id'] : $data['pay_account'],
            'chief_id' => $data['chief_id'],
            'pay_type' => $data['pay_type'],
            'mobile' => $data['mobile'],
            'account_name' => $data['account_name'],
            'bank_name' => $data['pay_type'] == 'bankcard' ? $data['bank_name'] : null,
            'wxa_appid' => $data['wxa_appid'],
            'money' => floor($data['money']),
            'status' => 'apply',
        ];

        return $this->entityRepository->create($insertData);
    }

    public function updateStatus($companyId, $applyId, $applyStatus, $error_msg = [])
    {
        $filter = ['id' => $applyId];
        $updateData = [
            'status' => $applyStatus,
            'remarks' => implode(';', $error_msg),
        ];
        $this->entityRepository->updateOneBy($filter, $updateData);
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
    public function rejectCashWithdrawal($companyId, $cashWithdrawalId, $remarks = null)
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
            $updateData['status'] = 'reject';
            if ($remarks) {
                $updateData['remarks'] = $remarks;
            }
            $data = $this->entityRepository->updateOneBy(['company_id' => $companyId, 'id' => $cashWithdrawalId], $updateData);

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

    private function payToWechat($cashWithdrawalId, $info, $clientIp, $companyId)
    {
        //默认直接微信付款
        $paymentsService = new PaymentsService(new WechatPayService());
        //支付参数
        $paymentData = [
            'rel_scene_id' => $cashWithdrawalId,
            'rel_scene_name' => 'community_chief_cash_withdrawal',
            're_user_name' => $info['account_name'],
            'mobile' => $info['mobile'],
            'amount' => $info['money'], //提现金额 （分）
            'user_id' => $info['chief_id'],
            'open_id' => $info['pay_account'],
            'payment_desc' => '佣金提现',
            'spbill_create_ip' => $clientIp ?: '127.0.0.1'
        ];
        return $paymentsService->merchantPayment($companyId, $info['wxa_appid'], $paymentData);
    }

    private function payToBankcard($companyId, $info)
    {
        $result = ['status' => 'SUCCESS'];

        return $result;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
