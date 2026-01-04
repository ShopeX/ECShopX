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

namespace BsPayBundle\Services;

use Dingo\Api\Exception\ResourceException;
use BsPayBundle\Entities\WithdrawApply;
use BsPayBundle\Entities\UserCard;
use BsPayBundle\Enums\WithdrawStatus;
use BsPayBundle\Services\DivFeeService;
use PaymentBundle\Services\Payments\BsPayService;
use DistributionBundle\Services\DistributorService;
use MerchantBundle\Services\MerchantService;

class WithdrawApplyService
{
    /** @var \BsPayBundle\Repositories\WithdrawApplyRepository */
    public $withdrawApplyRepository;

    /** @var DivFeeService */
    public $divFeeService;

    /** @var BsPayService */
    public $bsPayService;

    public function __construct()
    {
        $this->withdrawApplyRepository = app('registry')->getManager('default')->getRepository(WithdrawApply::class);
        $this->divFeeService = new DivFeeService();
        $this->bsPayService = new BsPayService();
    }

    /**
     * 获取用户可提现余额和进行中余额
     */
    public function getUserBalance($companyId, $operatorType, $distributorId = null, $merchantId = null)
    {
        app('log')->info('bspay::getUserBalance::companyId:'.$companyId.',operatorType:'.$operatorType.',distributorId:'.$distributorId.',merchantId:'.$merchantId);
        // 1. 获取可提现余额
        $availableBalance = $this->getAvailableBalance(
            $companyId,
            $operatorType,
            $distributorId,
            $merchantId
        );
        
        // 2. 获取进行中余额
        $pendingBalance = $this->getPendingBalance(
            $companyId,
            $operatorType,
            $distributorId,
            $merchantId
        );

        return [
            'available_balance' => $availableBalance,  // 可提现余额（分）
            'pending_balance' => $pendingBalance       // 进行中余额（分）
        ];
    }

    /**
     * 获取可提现余额
     *
     * @param string $companyId 企业ID
     * @param string $operatorType 操作者类型
     * @param string|null $distributorId 分销商ID
     * @param string|null $merchantId 商户ID
     * @return int 可提现余额（分）
     */
    public function getAvailableBalance($companyId, $operatorType, $distributorId = null, $merchantId = null)
    {
        $filter = $this->buildBaseFilter($companyId, $operatorType, $distributorId, $merchantId);
        app('log')->info('bspay::getAvailableBalance::filter:'.json_encode($filter));
        // 1. 获取分账总额
        $totalDivFee = (int)$this->divFeeService->divFeeRepository->sum($filter, 'div_fee');
        app('log')->info('bspay::getAvailableBalance::totalDivFee:'.$totalDivFee);
        
        // 2. 获取已提现成功总额
        $totalWithdrawn = $this->getSuccessWithdrawnAmount($companyId, $operatorType, $distributorId, $merchantId);
        app('log')->info('bspay::getAvailableBalance::totalWithdrawn:'.$totalWithdrawn);
        // 3. 计算可提现余额（确保不会出现负数）
        return max(0, $totalDivFee - $totalWithdrawn);
    }

    /**
     * 获取已提现成功总额
     */
    private function getSuccessWithdrawnAmount($companyId, $operatorType, $distributorId = null, $merchantId = null)
    {
        $filter = $this->buildBaseFilter($companyId, $operatorType, $distributorId, $merchantId);
        $filter['status'] = WithdrawStatus::SUCCESS;
        
        return (int)$this->withdrawApplyRepository->sum($filter, 'amount');
    }

    /**
     * 获取进行中提现余额
     *
     * @param string $companyId 企业ID
     * @param string $operatorType 操作者类型
     * @param string|null $distributorId 分销商ID
     * @param string|null $merchantId 商户ID
     * @return int 进行中提现余额（分）
     */
    public function getPendingBalance($companyId, $operatorType, $distributorId = null, $merchantId = null)
    {
        $filter = $this->buildBaseFilter($companyId, $operatorType, $distributorId, $merchantId);
        $filter['status'] = WithdrawStatus::$pendingStatuses;
        
        return (int)$this->withdrawApplyRepository->sum($filter, 'amount');
    }

    /**
     * 构造基础查询条件
     * @param string $companyId 企业ID
     * @param string $operatorType 操作者类型
     * @param string|null $distributorId 分销商ID
     * @param string|null $merchantId 商户ID
     * @return array 查询条件
     */
    private function buildBaseFilter($companyId, $operatorType, $distributorId = null, $merchantId = null)
    {
        $filter = [
            'company_id' => $companyId,
            'operator_type' => $operatorType
        ];
        
        if ($operatorType === 'merchant' && $merchantId) {
            $filter['merchant_id'] = $merchantId;
        } elseif ($operatorType === 'distributor' && $distributorId) {
            $filter['distributor_id'] = $distributorId;
        }
        
        return $filter;
    }

    /**
     * 申请提现
     *
     * @param array $data
     * @return WithdrawApply
     * @throws ResourceException
     */
    public function applyWithdraw($data)
    {
        // 获取汇付用户ID
        $operatorId = $data['operator_id'] ?? 0;
        if ($data['operator_type'] == 'distributor') {
            $operatorId = $data['distributor_id'] ?? 0;
        } elseif ($data['operator_type'] == 'merchant') {
            $operatorId = $data['merchant_id'] ?? 0;
        }
        $huifuId = $this->bsPayService->getHuifuId($data['company_id'], $operatorId, $data['operator_type']);
        if (!$huifuId) {
            throw new ResourceException('未找到对应的汇付用户ID');
        }
        // 验证申请金额
        if (empty($data['amount']) || $data['amount'] <= 0) {
            throw new ResourceException('申请金额必须大于0');
        }

        // 验证提现类型
        if (empty($data['withdraw_type']) || $data['withdraw_type'] !== 'T1') {
            throw new ResourceException('提现类型只能是T1');
        }

        // 验证发票文件
        if (empty($data['invoice_url'])) {
            throw new ResourceException('请上传发票文件');
        }

        // 将金额转换为分
        $applyData = [
            'company_id' => $data['company_id'],
            'merchant_id' => $data['merchant_id'],
            'distributor_id' => $data['distributor_id'],
            'operator_type' => $data['operator_type'],
            'operator_id' => $data['operator_id'],
            'operator' => $data['operator'],
            'huifu_id' => $huifuId,
            'amount' => intval($data['amount'] * 100), // 转换为分
            'withdraw_type' => $data['withdraw_type'],
            'invoice_file' => $data['invoice_url'],
            'status' => WithdrawStatus::PENDING // 审核中
        ];

        // 验证余额（金额已经是分）
        $balanceInfo = $this->getUserBalance(
            $data['company_id'],
            $data['operator_type'],
            $data['distributor_id'] ?? null,
            $data['merchant_id'] ?? null
        );
        // 计算可提现余额
        $availableBalance = max(0, $balanceInfo['available_balance'] - $balanceInfo['pending_balance']);
        // 检查可提现金额
        if (intval($data['amount'] * 100) > $availableBalance) {
            throw new ResourceException('可提现余额不足');
        }

        // 创建提现申请
        $withdrawApply = $this->create($applyData);

        // 记录操作日志
        app('log')->info('提现申请创建成功 apply_id:'.$withdrawApply['id'].',amount:'.$data['amount'].',operator_type:'.$data['operator_type'].',operator_id:'.$data['operator_id'].',operator:'.$data['operator']);

        return $withdrawApply;
    }

    /**
     * 审核提现申请
     *
     * @param int $applyId
     * @param string $action
     * @param string $auditor 审核人账号
     * @param int $auditorOperatorId
     * @param string $remark
     * @return bool
     * @throws ResourceException
     */
    public function auditWithdraw($applyId, $action, $auditor, $auditorOperatorId, $remark = '')
    {
        // 获取申请记录
        $applyInfo = $this->getInfo(['id' => $applyId]);
        if (!$applyInfo) {
            throw new ResourceException('提现申请记录不存在');
        }

        // 检查当前用户是否有权限审核该提现申请
        $user = app('auth')->user();
        $operatorType = $user->get('operator_type');
        if ($operatorType !== 'admin') {
            throw new ResourceException('只有管理员可以审核提现申请');
        }

        if (!WithdrawStatus::canAudit($applyInfo['status'])) {
            throw new ResourceException('该申请已审核，不能重复审核');
        }

        $updateData = [
            'audit_time' => time(),
            'auditor' => $auditor,
            'auditor_operator_id' => $auditorOperatorId,
            'audit_remark' => $remark
        ];

        if ($action === 'approve') {
            $updateData['status'] = WithdrawStatus::APPROVED; // 审核通过，等待处理
        } elseif ($action === 'reject') {
            $updateData['status'] = WithdrawStatus::REJECTED; // 已拒绝
        } else {
            throw new ResourceException('审核操作无效');
        }

        $result = $this->updateOneBy(['id' => $applyId], $updateData);

        if ($result) {
            // 记录操作日志
            app('log')->info('提现申请审核完成 apply_id:'.$applyId.',action:'.$action.',auditor:'.$auditor.',auditor_operator_id:'.$auditorOperatorId.',remark:'.$remark);
        }

        return $result;
    }

    /**
     * 执行汇付取现
     *
     * @param int $applyId
     * @return void
     * @throws ResourceException
     */
    public function executeHuifuWithdraw($applyId): void
    {
        try {
            // 获取申请记录
            $applyInfo = $this->getInfo(['id' => $applyId]);
            if (!$applyInfo) {
                throw new ResourceException('提现申请记录不存在');
            }

            // 幂等性检查：如果已经处理成功或正在处理，直接返回
            $currentStatus = $applyInfo['status'];
            if ($currentStatus === WithdrawStatus::SUCCESS || $currentStatus === WithdrawStatus::PROCESSING) {
                app('log')->info('提现申请已处理成功或正在处理，跳过重复执行 apply_id:' . $applyId . ',status:' . $currentStatus);
                return;
            }

            // 只有审核通过或失败状态可以执行
            if ($currentStatus !== WithdrawStatus::APPROVED && $currentStatus !== WithdrawStatus::FAILED) {
                throw new ResourceException('提现申请状态不正确，无法执行，当前状态：' . WithdrawStatus::getLabel($currentStatus));
            }

            $companyId = $applyInfo['company_id'];
            $huifuId = $applyInfo['huifu_id'];
            $amount = $applyInfo['amount'];
            $operatorType = $applyInfo['operator_type'];

            // 获取token_no
            $tokenNo = '';
            if ($operatorType === 'admin') {
                // 从支付配置中获取token_no
                $config = $this->bsPayService->getPaymentSetting($companyId);
                if (empty($config) || empty($config['admin_token_no'])) {
                    throw new ResourceException('未配置管理员提现卡序列号，请在支付配置中设置');
                }
                $tokenNo = $config['admin_token_no'];
            } else {
                // 获取用户银行卡信息
                $userCardRepository = app('registry')->getManager('default')->getRepository(UserCard::class);
                $bankCardInfo = $userCardRepository->getInfo([
                    'company_id' => $companyId,
                    'huifu_id' => $huifuId
                ]);

                if (!$bankCardInfo) {
                    throw new ResourceException('未找到用户银行卡信息，请先完成银行卡绑定');
                }

                // 验证取现卡序列号
                if (empty($bankCardInfo['apply_no'])) {
                    throw new ResourceException('取现卡序列号不存在，请先完成银行卡绑定');
                }

                $tokenNo = $bankCardInfo['apply_no'];
            }

            // 根据汇付斗拱取现接口规范构建参数
            $withdrawParams = [
                'company_id' => $companyId,
                'huifu_id' => $huifuId,
                'withdraw_type' => $applyInfo['withdraw_type'],
                'amount' => $amount,
                'token_no' => $tokenNo,
            ];

            // 获取EntityManager进行事务处理
            $em = app('registry')->getManager('default');
            
            // 开始事务
            $em->beginTransaction();
            
            // 先更新状态为处理中，作为乐观锁机制
            $updateResult = $this->updateOneBy(
                [
                    'id' => $applyId,
                    'status' => [WithdrawStatus::APPROVED, WithdrawStatus::FAILED] // 允许从审核通过或失败状态更新
                ],
                ['status' => WithdrawStatus::PROCESSING]
            );
            
            if (!$updateResult) {
                throw new ResourceException('提现申请状态已发生变化，无法执行');
            }
            
            // 记录请求时间
            $requestTime = time();
            
            // 调用汇付取现接口
            $result = $this->bsPayService->doWithdraw($withdrawParams);

            $updateData = [
                'hf_seq_id' => $result['hf_seq_id'] ?? '',
                'req_seq_id' => $result['req_seq_id'] ?? '',
                'request_time' => $requestTime,
                'failure_reason' => ''
            ];

            // 根据交易状态更新提现申请状态
            if ($result['trans_stat'] === 'P') {
                $updateData['status'] = WithdrawStatus::PROCESSING;
                $updateData['failure_reason'] = '取现申请已受理，正在处理中';
            } else {
                $updateData['status'] = WithdrawStatus::SUCCESS;
            }
            $this->updateOneBy(['id' => $applyId], $updateData);

            // 提交事务
            $em->commit();
            app('log')->info('bspay::doWithdraw::提现申请处理成功::apply_id:'.$applyId.',huifu_id:'.$huifuId.',amount:'.$amount.',hf_seq_id:'.$result['hf_seq_id'] ?? '');

        } catch (\Exception $e) {
            // 如果事务已开始，进行回滚
            if (isset($em) && $em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }
            
            // 更新申请记录为失败状态
            $errorMessage = $e instanceof ResourceException ? $e->getMessage() : '汇付取现失败：' . $e->getMessage();
            $updateData = [
                'status' => WithdrawStatus::FAILED,
                'request_time' => $requestTime ?? time(),
                'failure_reason' => $errorMessage
            ];
            
            $this->updateOneBy(['id' => $applyId], $updateData);
            app('log')->error('bspay::doWithdraw::提现申请处理失败::apply_id:'.$applyId.',huifu_id:'.$huifuId.',amount:'.$amount.',error:'.$errorMessage);

            throw new ResourceException($errorMessage);
        }
    }

    /**
     * 根据请求流水号获取提现申请记录
     * @param string $reqSeqId 请求流水号
     * @return array|null
     */
    public function getByReqSeqId($reqSeqId)
    {
        return $this->withdrawApplyRepository->getInfo(['req_seq_id' => $reqSeqId]);
    }

    /**
     * 处理提现回调通知
     * @param array $notifyData 回调数据
     */
    public function handleWithdrawNotify($notifyData, $withdrawApply)
    {
        // 判断是否重复通知
        if ($withdrawApply['status'] === WithdrawStatus::SUCCESS) {
            app('log')->info('bspay::doWithdraw::提现回调重复通知:req_seq_id:'.$notifyData['req_seq_id'].',apply_id:'.$withdrawApply['id'].',status:'.$withdrawApply['status']);
            return;
        }
        // 更新提现申请记录
        $updateData = [
            'updated' => time()
        ];
        // 更新提现状态
        switch ($notifyData['trans_status']) {
            case 'S': // 成功
                $status = WithdrawStatus::SUCCESS;
                $updateData['failure_reason'] = '';
                break;
            case 'F': // 失败
                $status = WithdrawStatus::FAILED;
                $updateData['failure_reason'] = sprintf(
                    '错误码：%s，错误描述：%s',
                    $notifyData['sub_resp_code'],
                    $notifyData['sub_resp_desc']
                );
                break;
            case 'P': // 处理中
                $status = WithdrawStatus::PROCESSING;
                break;
            default:
                app('log')->info('bspay::doWithdraw::提现回调状态异常:req_seq_id:'.$notifyData['req_seq_id'].',apply_id:'.$withdrawApply['id'].',trans_status:'.$notifyData['trans_status']);
                return;
        }
        $updateData['status'] = $status;
        app('log')->info('bspay::doWithdraw::提现回调状态更新:req_seq_id:'.$notifyData['req_seq_id'].',apply_id:'.$withdrawApply['id'].'updateData:'.json_encode($updateData));
        $this->withdrawApplyRepository->updateBy(['id' => $withdrawApply['id']], $updateData);
        // 记录日志
        app('log')->info('bspay::doWithdraw::提现回调状态更新:req_seq_id:'.$notifyData['req_seq_id'].',apply_id:'.$withdrawApply['id'].',status:'.$status.',trans_status:'.$notifyData['trans_status'].',hf_seq_id:'.$notifyData['hf_seq_id']);
    }

    public function __call($name, $arguments)
    {
        return $this->withdrawApplyRepository->$name(...$arguments);
    }

    /**
     * 获取提现记录列表，并附加店铺和商户名称
     *
     * @param array $filter 过滤条件
     * @param string $cols 查询字段
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param array $orderBy 排序
     * @return array
     */
    public function getListsWithNames($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = [])
    {
        // 获取提现记录列表
        $result = $this->lists($filter, $cols, $page, $pageSize, $orderBy);

        // 获取店铺和商户名称
        if (!empty($result['list'])) {
            $distributorService = new DistributorService();
            $distributorIds = array_column($result['list'], 'distributor_id');
            $distributorName = [];
            // 获取平台自营信息
            $selfInfo = [];
            if (in_array(0, $distributorIds)) {
                $selfInfo = $distributorService->getDistributorSelfSimpleInfo($filter['company_id']);
                $distributorName[0] = $selfInfo['name'];
            }
            
            // 获取其他店铺信息
            $otherDistributorIds = array_filter($distributorIds);
            if ($otherDistributorIds) {
                $distributorList = $distributorService->getDistributionNameListByDistributorId($filter['company_id'], $otherDistributorIds);
                $distributorName += array_column($distributorList, 'name', 'distributor_id');
            }
            $merchantService = new MerchantService();
            $merchantList = $merchantService->getLists(['id' => array_column($result['list'], 'merchant_id')], 'id,merchant_name');
            $merchantName = array_column($merchantList, 'merchant_name', 'id');

            foreach ($result['list'] as $key => $value) {
                $distributor_name = $distributorName[$value['distributor_id']] ?? '';
                if ($value['distributor_id'] == '0' && !in_array($value['operator_type'], ['admin', 'staff'])) {
                    $distributor_name = '';
                }
                $result['list'][$key]['distributor_name'] = $distributor_name;
                $result['list'][$key]['merchant_name'] = $merchantName[$value['merchant_id']] ?? '';
            }
        }

        return $result;
    }
} 