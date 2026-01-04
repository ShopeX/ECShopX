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

namespace SalespersonBundle\Services;

use AftersalesBundle\Services\AftersalesRefundService;
use SalespersonBundle\Entities\Profit;
use SalespersonBundle\Entities\ProfitLog;
use SalespersonBundle\Entities\ProfitStatistics;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use OrdersBundle\Services\Orders\AbstractNormalOrder;

class ProfitService
{
    // 导购
    public const PROFIT_USER_TYPE_SELLER = 1;
    // 店铺
    public const PROFIT_USER_TYPE_DISTRIBUTOR = 2;
    // 区域经销商
    public const PROFIT_USER_TYPE_DEALER = 3;
    // 总部
    public const PROFIT_USER_TYPE_HEADQUARTERS = 4;

    // 分润提现
    public const PROFIT_TYPE_NULL = 0;
    // 拉新分润
    public const PROFIT_TYPE_COMMISSIONS = 1;
    // 推广提成
    public const PROFIT_TYPE_POPULARIZE_COMMISSIONS = 2;
    // 货款
    public const PROFIT_TYPE_GOODS_AMOUNT = 3;
    // 补贴
    public const PROFIT_TYPE_SUBSIDY = 4;

    // 取消退款
    public const PROFIT_STATUS_REFUND = 1;
    // 售后退款
    public const PROFIT_STATUS_AFTER_SALE = 2;
    // 提现扣减
    public const PROFIT_STATUS_WITHDRAWAL = 3;
    // 购物冻结
    public const PROFIT_STATUS_FROZEN = 11;
    // 购物冻结(取消退款 售后退款 转为可提现资金之后自动变为)
    public const PROFIT_STATUS_FROZEN_CHANGE = 12;
    // 可以提现资金
    public const PROFIT_STATUS_CASHED = 13;

    public $profitRepository;
    public $profitLogRepository;
    public $profitStatisticsRepository;

    /**
     * ProfitService 构造函数.
     */
    public function __construct()
    {
        $this->profitRepository = app('registry')->getManager('default')->getRepository(Profit::class);
        $this->profitLogRepository = app('registry')->getManager('default')->getRepository(ProfitLog::class);
        $this->profitStatisticsRepository = app('registry')->getManager('default')->getRepository(ProfitStatistics::class);
    }

    /**
     * 创建分润日志
     * @param string $companyId 公司id
     * @param array $params 参数
     */
    public function createProfitLog($companyId, array $params)
    {
        $rules = [
            'profit_user_id' => ['required', '分润成员id必填'],
            'profit_user_type' => ['required_with:' . implode(',', $this->getProfitUserType()), '分润成员类型必填'],
            'profit_type' => ['required_with:' . implode(',', $this->getProfitType()), '分润类型必填'],
            'status' => ['required_with:' . implode(',', $this->getStatus()), '分润增减类型必填'],
            'remark' => ['required', '备注必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        if (!($params['income_fee'] ?? 0) && !($params['outcome_fee'] ?? 0)) {
            throw new ResourceException('增加或者扣减资金必填一项');
        }

        $data = [
            'company_id' => $companyId,
            'order_id' => $params['order_id'] ?? '',

            'profit_user_id' => $params['profit_user_id'], // 分润成员id
            'profit_user_type' => $params['profit_user_type'], // 分润成员类型
            'profit_type' => $params['profit_type'], // 分润成员类型

            'status' => $params['status'], // 资金状态 1 取消退款 2 售后退款 3 提现扣减 11 12 购物冻结 13 购物可体现

            'income_fee' => $params['income_fee'] ?? 0, // 进资金
            'outcome_fee' => $params['outcome_fee'] ?? 0, // 出资金

            'remark' => $params['remark'], // 备注
            'params' => $params['params'] ?? [], // 第三方参数
        ];
        $result = $this->profitLogRepository->create($data);
        return $result;
    }

    /**
     * 冻结分润变动的时状态修改
     * @param $id
     * @return mixed
     */
    public function updateFrozenProfitLog($id)
    {
        $result = $this->profitLogRepository->updateOneBy(['id' => $id, 'status' => self::PROFIT_STATUS_FROZEN], ['status' => self::PROFIT_STATUS_FROZEN_CHANGE]);
        return $result;
    }

    /**
     * 分润记录
     * @param int $companyId
     * @param array $params
     * @return mixed
     */
    public function createProfit($companyId, array $params)
    {
        $rules = [
            'profit_user_id' => ['required', '分润成员id必填'],
            'profit_user_type' => ['required_with:' . implode(',', $this->getProfitUserType()), '分润成员类型必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        if (!($params['commissions'] ?? 0) &&
            !($params['popularize_commissions'] ?? 0) &&
            !($params['goods_amount'] ?? 0) &&
            !($params['subsidy'] ?? 0)) {
            throw new ResourceException('分润金额必填');
        }

        $data = [
            'company_id' => $companyId,
            'order_id' => $params['order_id'] ?? '',
            'profit_user_id' => $params['profit_user_id'], // 分润成员id
            'profit_user_type' => $params['profit_user_type'], // 分润成员类型

            'total_fee' => $params['total_fee'] ?? 0, // 分润总金额
            'frozen_fee' => $params['frozen_fee'] ?? 0, // 分润冻结总金额
            'withdrawals_fee' => $params['withdrawals_fee'] ?? 0, // 分润已提现金额
            'cashed_fee' => $params['cashed_fee'] ?? 0, // 分润可提现金额

            'commissions' => $params['commissions'] ?? 0, // 拉新提成
            'popularize_commissions' => $params['popularize_commissions'] ?? 0, // 推广提成
            'goods_amount' => $params['goods_amount'] ?? 0, // 货款
            'subsidy' => $params['subsidy'] ?? 0, // 补贴
        ];
        $info = $this->profitRepository->getInfo(['profit_user_id' => $params['profit_user_id'], 'profit_user_type' => $params['profit_user_type']]);
        if ($info) {
            $data['total_fee'] = bcadd($info['total_fee'], $params['total_fee'] ?? 0, 0);
            $data['frozen_fee'] = bcadd($info['frozen_fee'], $params['frozen_fee'] ?? 0, 0);
            $data['withdrawals_fee'] = bcadd($info['withdrawals_fee'], $params['withdrawals_fee'] ?? 0, 0);
            $data['cashed_fee'] = bcadd($info['cashed_fee'], $params['cashed_fee'] ?? 0, 0);
            $data['commissions'] = bcadd($info['commissions'], $params['commissions'] ?? 0, 0);
            $data['popularize_commissions'] = bcadd($info['popularize_commissions'], $params['popularize_commissions'] ?? 0, 0);
            $data['goods_amount'] = bcadd($info['goods_amount'], $params['goods_amount'] ?? 0, 0);
            $data['subsidy'] = bcadd($info['subsidy'], $params['subsidy'] ?? 0, 0);
            $result = $this->profitRepository->updateOneBy(['profit_user_id' => $params['profit_user_id'], 'profit_user_type' => $params['profit_user_type']], $data);
        } else {
            $result = $this->profitRepository->create($data);
        }
        return $result;
    }

    /**
     * 创建分润统计
     * @param $date
     * @param $companyId
     * @param $profitUserId
     * @param $profitUserType
     * @param $withdrawalsFee
     * @param $name
     * @param $params
     * @return mixed
     */
    public function createProfitStatistics($date, $companyId, $profitUserId, $profitUserType, $withdrawalsFee, $name, $params)
    {
        $data = [
            'date' => $date,
            'company_id' => $companyId,
            'profit_user_id' => $profitUserId,
            'profit_user_type' => $profitUserType,
            'withdrawals_fee' => $withdrawalsFee ?: 0,
            'name' => $name,
            'params' => $params
        ];
        $result = $this->profitStatisticsRepository->create($data);
        return $result;
    }

    /**
     * 获取分配统计数据
     * @param $date
     * @param $companyId
     * @param $profitUserId
     * @param $profitUserType
     * @return mixed
     */
    public function getProfitStatistics($date, $companyId, $profitUserId, $profitUserType)
    {
        $data = [
            'date' => $date,
            'company_id' => $companyId,
            'profit_user_id' => $profitUserId,
            'profit_user_type' => $profitUserType,
        ];
        $result = $this->profitStatisticsRepository->getInfo($data);
        return $result;
    }

    /**
     * 支付成功分润
     * @param $companyId 公司id
     * @param $orderId 订单id
     * @param $fee  分润金额
     * @param array $data [
     * profit_user_id 分润成员id
     * profit_user_type 分润成员类型
     * commissions 拉新提成
     * popularize_commissions 推广提成
     * goods_amount 货款
     * subsidy 补贴
     * ]
     */
    public function paySuccess($companyId, $orderId, $data = [])
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ($data as $v) {
                if (!($v['profit_user_id'] ?? 0)) {
                    throw new ResourceException('分润成员id必填');
                }
                if (!($v['profit_user_type'] ?? 0)) {
                    throw new ResourceException('分润成员类型必填');
                }
                if (!($v['profit_type'] ?? 0)) {
                    throw new ResourceException('分润类型必填');
                }
                if (!($v['commissions'] ?? 0) &&
                    !($v['popularize_commissions'] ?? 0) &&
                    !($v['goods_amount'] ?? 0) &&
                    !($v['subsidy'] ?? 0)) {
                    throw new ResourceException('分润金额必填');
                }
                $params = [
                    'order_id' => $orderId,
                    'profit_user_id' => $v['profit_user_id'],
                    'profit_user_type' => $v['profit_user_type'],
                    'total_fee' => $v['fee'],
                    'frozen_fee' => $v['fee'],
                    'withdrawals_fee' => 0,
                    'cashed_fee' => 0,
                    'commissions' => $v['commissions'] ?? 0,
                    'popularize_commissions' => $v['popularize_commissions'] ?? 0,
                    'goods_amount' => $v['goods_amount'] ?? 0,
                    'subsidy' => $v['subsidy'] ?? 0,
                ];
                $this->createProfit($companyId, $params);
                $logParams = [
                    'order_id' => $orderId,
                    'profit_user_id' => $v['profit_user_id'],
                    'profit_user_type' => $v['profit_user_type'],
                    'profit_type' => $v['profit_type'],
                    'status' => self::PROFIT_STATUS_FROZEN,
                    'income_fee' => $v['fee'],
                    'outcome_fee' => 0,
                    'remark' => '订单' . $orderId . '，分润增加（冻结）',
                    'params' => [
                        'order' => $orderId
                    ],
                ];
                $this->createProfitLog($companyId, $logParams);
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
        }
    }

    /**
     * 订单退款分润
     * @param $companyId 公司ID
     * @param $orderId 订单ID
     * @return bool
     */
    public function refundSuccess($companyId, $orderId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $profitLogLists = $this->profitLogRepository->getLists(['order_id' => $orderId, 'status' => self::PROFIT_STATUS_FROZEN]);
            foreach ($profitLogLists as $v) {
                $commissions = self::PROFIT_TYPE_COMMISSIONS == $v['profit_type'] ? $v['income_fee'] : 0;
                $popularizeCommissions = self::PROFIT_TYPE_POPULARIZE_COMMISSIONS == $v['profit_type'] ? $v['income_fee'] : 0;
                $goodsAmount = self::PROFIT_TYPE_GOODS_AMOUNT == $v['profit_type'] ? $v['income_fee'] : 0;
                $subsidy = self::PROFIT_TYPE_SUBSIDY == $v['profit_type'] ? $v['income_fee'] : 0;
                $params = [
                    'order_id' => $orderId,
                    'profit_user_id' => $v['profit_user_id'],
                    'profit_user_type' => $v['profit_user_type'],
                    'total_fee' => -$v['income_fee'],
                    'frozen_fee' => -$v['income_fee'],
                    'withdrawals_fee' => 0,
                    'cashed_fee' => 0,
                    'commissions' => -$commissions,
                    'popularize_commissions' => -$popularizeCommissions,
                    'goods_amount' => -$goodsAmount,
                    'subsidy' => -$subsidy,
                ];
                $this->createProfit($companyId, $params);
                $logParams = [
                    'order_id' => $orderId,
                    'profit_user_id' => $v['profit_user_id'],
                    'profit_user_type' => $v['profit_user_type'],
                    'profit_type' => $v['profit_type'],
                    'status' => self::PROFIT_STATUS_REFUND,
                    'income_fee' => 0,
                    'outcome_fee' => $v['income_fee'],
                    'remark' => '订单' . $orderId . '，分润扣减（订单退款）',
                    'params' => [
                        'order' => $orderId
                    ],
                ];
                $this->updateFrozenProfitLog($v['id']);
                $this->createProfitLog($companyId, $logParams);
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            app('api.exception')->report($e);
            $conn->rollback();
        }

        return false;
    }

    /**
     * 售后退款分润
     * @param $companyId 公司ID
     * @param $orderId 订单ID
     * @return bool
     */
    public function afterSaleSuccess($companyId, $orderId, $data)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ($data as $v) {
                if (!($v['profit_user_id'] ?? 0)) {
                    throw new ResourceException('分润成员id必填');
                }
                if (!($v['profit_user_type'] ?? 0)) {
                    throw new ResourceException('分润成员类型必填');
                }
                if (!($v['profit_type'] ?? 0)) {
                    throw new ResourceException('分润类型必填');
                }
                if (!($v['commissions'] ?? 0) &&
                    !($v['popularize_commissions'] ?? 0) &&
                    !($v['goods_amount'] ?? 0) &&
                    !($v['subsidy'] ?? 0)) {
                    throw new ResourceException('分润金额必填');
                }
                $params = [
                    'order_id' => $orderId,
                    'profit_user_id' => $v['profit_user_id'],
                    'profit_user_type' => $v['profit_user_type'],
                    'total_fee' => -$v['total_fee'],
                    'frozen_fee' => -$v['frozen_fee'],
                    'withdrawals_fee' => 0,
                    'cashed_fee' => 0,
                    'commissions' => -$v['commissions'] ?? 0,
                    'popularize_commissions' => -$v['popularize_commissions'] ?? 0,
                    'goods_amount' => -$v['goods_amount'] ?? 0,
                    'subsidy' => -$v['subsidy'] ?? 0,
                ];
                $this->createProfit($companyId, $params);
                $logParams = [
                    'order_id' => $orderId,
                    'profit_user_id' => $v['profit_user_id'],
                    'profit_user_type' => $v['profit_user_type'],
                    'profit_type' => $v['profit_type'],
                    'status' => self::PROFIT_STATUS_AFTER_SALE,
                    'income_fee' => 0,
                    'outcome_fee' => $v['total_fee'],
                    'remark' => '订单' . $orderId . '，售后扣减',
                    'params' => [
                        'order' => $orderId
                    ],
                ];
                $this->createProfitLog($companyId, $logParams);
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
        }

        return false;
    }

    /**
     * 可提现分润增加
     * @param $companyId 公司ID
     * @param $orderId 订单ID
     * @return bool
     */
    public function cashedSuccess($companyId, $orderId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $profitLogLists = $this->profitLogRepository->getLists(['order_id' => $orderId, 'status' => self::PROFIT_STATUS_FROZEN]);
            $aftersalesRefundService = new AftersalesRefundService();
            $aftersRefundData = $aftersalesRefundService->aftersalesRefundRepository->getList(['order_id' => $orderId]);
            $refundFee = 0;
            $orderFee = 0;
            if ($aftersRefundData['total_count'] ?? 0) {
                foreach ($aftersRefundData['list'] as $v) {
                    $refundFee += $v['refund_fee'];
                    $orderFee = $v['order_fee'];
                }
            }
            $p = 0;
            if ($refundFee && $orderFee) {
                $p = bcdiv($refundFee, $orderFee, 2);
            }
            $data = [];
            foreach ($profitLogLists as $v) {
                $fee = bcmul($v['income_fee'], $p, 0);
                if ($p && $fee) {
                    $commissions = self::PROFIT_TYPE_COMMISSIONS == $v['profit_type'] ? $fee : 0;
                    $popularizeCommissions = self::PROFIT_TYPE_POPULARIZE_COMMISSIONS == $v['profit_type'] ? $fee : 0;
                    $goodsAmount = self::PROFIT_TYPE_GOODS_AMOUNT == $v['profit_type'] ? $fee : 0;
                    $subsidy = self::PROFIT_TYPE_SUBSIDY == $v['profit_type'] ? $fee : 0;
                    $data[] = [
                        'profit_user_id' => $v['profit_user_id'],
                        'profit_user_type' => $v['profit_user_type'],
                        'profit_type' => $v['profit_type'],
                        'total_fee' => $fee,
                        'frozen_fee' => $fee,
                        'commissions' => $commissions,
                        'popularize_commissions' => $popularizeCommissions,
                        'goods_amount' => $goodsAmount,
                        'subsidy' => $subsidy,
                    ];
                    $this->afterSaleSuccess($companyId, $orderId, $data);
                }
                $incomeFee = bcsub($v['income_fee'], $fee, 0);

                $commissions = self::PROFIT_TYPE_COMMISSIONS == $v['profit_type'] ? $incomeFee : 0;
                $popularizeCommissions = self::PROFIT_TYPE_POPULARIZE_COMMISSIONS == $v['profit_type'] ? $incomeFee : 0;
                $goodsAmount = self::PROFIT_TYPE_GOODS_AMOUNT == $v['profit_type'] ? $incomeFee : 0;
                $subsidy = self::PROFIT_TYPE_SUBSIDY == $v['profit_type'] ? $incomeFee : 0;

                $params = [
                    'order_id' => $orderId,
                    'profit_user_id' => $v['profit_user_id'],
                    'profit_user_type' => $v['profit_user_type'],
                    'total_fee' => 0,
                    'frozen_fee' => -$incomeFee,
                    'withdrawals_fee' => 0,
                    'cashed_fee' => $v['income_fee'],
                    'commissions' => -$commissions,
                    'popularize_commissions' => -$popularizeCommissions,
                    'goods_amount' => -$goodsAmount,
                    'subsidy' => -$subsidy,
                ];
                $this->createProfit($companyId, $params);
                $logParams = [
                    'order_id' => $orderId,
                    'profit_user_id' => $v['profit_user_id'],
                    'profit_user_type' => $v['profit_user_type'],
                    'profit_type' => $v['profit_type'],
                    'status' => self::PROFIT_STATUS_AFTER_SALE,
                    'income_fee' => bcsub($v['income_fee'], $fee, 0),
                    'outcome_fee' => 0,
                    'remark' => '订单' . $orderId . '，分润可提现',
                    'params' => [
                        'order' => $orderId
                    ],
                ];
                $this->updateFrozenProfitLog($v['id']);
                $this->createProfitLog($companyId, $logParams);
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
        }

        return false;
    }

    /**
     * 提现分润扣减
     * @param $companyId 公司ID
     * @param $profitUserId 用户id
     * @param $profitUserType 用户类型
     * @param $fee 提现金额
     * @return bool
     */
    public function withdrawalSalespersonSuccess($data)
    {
        $beginTime = strtotime(date('Y-m-01 00:00:00', strtotime('-1 month')));
        $endTime = strtotime(date("Y-m-d 23:59:59", strtotime(-date('d') . 'day')));
        $companyId = $data['company_id'];
        $profitUserId = $data['salesperson_id'];
        $name = $data['name'];
        $profitUserType = self::PROFIT_USER_TYPE_SELLER;
        $distributorName = $data['shop_name'];
        $date = date('Ym', $beginTime);
        if ($this->getProfitStatistics($date, $companyId, $profitUserId, $profitUserType)) {
            return true;
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 上月可提现金额计算
            $commissionsFeeSum = $this->profitLogRepository->sum([
                'profit_user_id' => $profitUserId,
                'profit_user_type' => $profitUserType,
                'profit_type' => self::PROFIT_TYPE_COMMISSIONS,
                'status' => self::PROFIT_STATUS_CASHED,
                'created|gte' => $beginTime,
                'created|lte' => $endTime
            ], 'income_fee');
            $commissionsFeeCount = $this->profitLogRepository->count([
                'profit_user_id' => $profitUserId,
                'profit_user_type' => $profitUserType,
                'profit_type' => self::PROFIT_TYPE_COMMISSIONS,
                'status' => self::PROFIT_STATUS_CASHED,
                'created|gte' => $beginTime,
                'created|lte' => $endTime
            ]);
            $popularizeCommissionsFeeSum = $this->profitLogRepository->sum([
                'profit_user_id' => $profitUserId,
                'profit_user_type' => $profitUserType,
                'profit_type' => self::PROFIT_TYPE_POPULARIZE_COMMISSIONS,
                'status' => self::PROFIT_STATUS_CASHED,
                'created|gte' => $beginTime,
                'created|lte' => $endTime
            ], 'income_fee');
            $popularizeCommissionsFeeCount = $this->profitLogRepository->count([
                'profit_user_id' => $profitUserId,
                'profit_user_type' => $profitUserType,
                'profit_type' => self::PROFIT_TYPE_POPULARIZE_COMMISSIONS,
                'status' => self::PROFIT_STATUS_CASHED,
                'created|gte' => $beginTime,
                'created|lte' => $endTime
            ], 'income_fee');
            $totalFee = bcadd($commissionsFeeSum, $popularizeCommissionsFeeSum);
            // 分润对象数据查询
            $info = $this->profitRepository->getInfo(['profit_user_id' => $profitUserId, 'profit_user_type' => $profitUserType]);
            if ($info && $totalFee) {
                if (bcsub($info['cashed_fee'], $totalFee, 0) >= 0) {
                    // 分润提现数据修改
                    $data = [
                        'company_id' => $companyId,
                        'order_id' => '',
                        'withdrawals_fee' => bcadd($info['withdrawals_fee'], $totalFee, 0),
                        'cashed_fee' => bcsub($info['cashed_fee'], $totalFee, 0),
                    ];
                    $this->profitRepository->updateOneBy(['profit_user_id' => $profitUserId, 'profit_user_type' => $profitUserType], $data);
                    // 分润提现流水
                    $logParams = [
                        'order_id' => '',
                        'profit_user_id' => $profitUserId,
                        'profit_user_type' => $profitUserType,
                        'profit_type' => self::PROFIT_TYPE_NULL,
                        'status' => self::PROFIT_STATUS_CASHED,
                        'income_fee' => 0,
                        'outcome_fee' => $totalFee,
                        'remark' => '分润提现',
                        'params' => [],
                    ];
                    $this->createProfitLog($companyId, $logParams);
                } else {
                    app('log')->info($name . '分润失败');
                }
            }
            $params = [
                'commissions' => $commissionsFeeSum,
                'popularize_commissions' => $popularizeCommissionsFeeSum,
                'commissions_num' => $commissionsFeeCount,
                'popularize_commissions_num' => $popularizeCommissionsFeeCount,
                'distributor_name' => $distributorName,
            ];
            $this->createProfitStatistics($date, $companyId, $profitUserId, $profitUserType, $totalFee, $name, $params);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
        }

        return false;
    }

    /**
     * 提现分润扣减
     * @param $companyId 公司ID
     * @param $profitUserId 用户id
     * @param $profitUserType 用户类型
     * @param $fee 提现金额
     * @return bool
     */
    public function withdrawalDistributorSuccess($data)
    {
        $beginTime = strtotime(date('Y-m-01 00:00:00', strtotime('-1 month')));
        $endTime = strtotime(date("Y-m-d 23:59:59", strtotime(-date('d') . 'day')));
        $companyId = $data['company_id'];
        $profitUserId = $data['distributor_id'];
        $name = $data['name'];
        $profitUserType = self::PROFIT_USER_TYPE_DISTRIBUTOR;
        $date = date('Ym', $beginTime);
        if ($this->getProfitStatistics($date, $companyId, $profitUserId, $profitUserType)) {
            return true;
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 上月可提现金额计算
            $goodsAmountSum = $this->profitLogRepository->sum([
                'profit_user_id' => $profitUserId,
                'profit_user_type' => $profitUserType,
                'profit_type' => self::PROFIT_TYPE_GOODS_AMOUNT,
                'status' => self::PROFIT_STATUS_CASHED,
                'created|gte' => $beginTime,
                'created|lte' => $endTime
            ], 'income_fee');

            // 上月可提现订单金额计算
            $goodsAmountCount = $this->profitLogRepository->count([
                'profit_user_id' => $profitUserId,
                'profit_user_type' => $profitUserType,
                'profit_type' => self::PROFIT_TYPE_GOODS_AMOUNT,
                'status' => self::PROFIT_STATUS_CASHED,
                'created|gte' => $beginTime,
                'created|lte' => $endTime
            ]);

            $commissionsSum = $this->profitLogRepository->sum([
                'profit_user_id' => $profitUserId,
                'profit_user_type' => $profitUserType,
                'profit_type' => self::PROFIT_TYPE_COMMISSIONS,
                'status' => self::PROFIT_STATUS_CASHED,
                'created|gte' => $beginTime,
                'created|lte' => $endTime
            ], 'income_fee');

            $commissionsCount = $this->profitLogRepository->count([
                'profit_user_id' => $profitUserId,
                'profit_user_type' => $profitUserType,
                'profit_type' => self::PROFIT_TYPE_COMMISSIONS,
                'status' => self::PROFIT_STATUS_CASHED,
                'created|gte' => $beginTime,
                'created|lte' => $endTime
            ]);

            $withdrawalsFeeSum = $this->profitLogRepository->sum([
                'profit_user_id' => $profitUserId,
                'profit_user_type' => $profitUserType,
                'profit_type' => self::PROFIT_TYPE_SUBSIDY,
                'status' => self::PROFIT_STATUS_CASHED,
                'created|gte' => $beginTime,
                'created|lte' => $endTime
            ], 'income_fee');


            $normalOrder = new AbstractNormalOrder();
            $orderNum = $normalOrder->countOrderNum([
                'distributor_id' => $profitUserId,
                'order_status' => ['DONE', 'PAYED', 'PART_PAYMENT', 'WAIT_GROUPS_SUCCESS', 'WAIT_BUYER_CONFIRM'],
                'create_time|gte' => $beginTime,
                'create_time|lte' => $endTime
            ]);
            $totalFee = bcadd(bcadd($goodsAmountSum, $commissionsSum), $withdrawalsFeeSum);

            $salespersonService = new SalespersonService();
            $salespersonLists = $salespersonService->salesperson->getLists(['shop_id' => $data['distributor_id']]);
            $salespersonIds = array_column($salespersonLists, 'salesperson_id');
            $sellerWithdrawalsFeeSum = 0;
            if ($salespersonIds) {
                $sellerWithdrawalsFeeSum = $this->profitLogRepository->sum([
                    'profit_user_id' => $salespersonIds,
                    'profit_user_type' => self::PROFIT_USER_TYPE_SELLER,
                    'status' => self::PROFIT_STATUS_CASHED,
                    'created|gte' => $beginTime,
                    'created|lte' => $endTime
                ], 'income_fee');
            }

            $totalFee = bcadd($totalFee, $sellerWithdrawalsFeeSum);
            // 分润对象数据查询
            $info = $this->profitRepository->getInfo(['profit_user_id' => $profitUserId, 'profit_user_type' => $profitUserType]);
            if ($info && $totalFee) {
                if (bcsub($info['cashed_fee'], $totalFee, 0) >= 0) {
                    // 分润提现数据修改
                    $data = [
                        'company_id' => $companyId,
                        'order_id' => '',
                        'withdrawals_fee' => bcadd($info['withdrawals_fee'], $totalFee, 0),
                        'cashed_fee' => bcsub($info['cashed_fee'], $totalFee, 0),
                    ];
                    $this->profitRepository->updateOneBy(['profit_user_id' => $profitUserId, 'profit_user_type' => $profitUserType], $data);
                    // 分润提现流水
                    $logParams = [
                        'order_id' => '',
                        'profit_user_id' => $profitUserId,
                        'profit_user_type' => $profitUserType,
                        'profit_type' => self::PROFIT_TYPE_NULL,
                        'status' => self::PROFIT_STATUS_CASHED,
                        'income_fee' => 0,
                        'outcome_fee' => $totalFee,
                        'remark' => '分润提现',
                        'params' => [],
                    ];
                    $this->createProfitLog($companyId, $logParams);
                } else {
                    app('log')->info($name . '分润失败');
                }
            }
            $params = [
                'goods_amount' => $goodsAmountSum,
                'commissions' => $commissionsSum,
                'withdrawals_fee' => $withdrawalsFeeSum,
                'seller_withdrawals_fee' => $sellerWithdrawalsFeeSum,
                'goods_amount_num' => $goodsAmountCount,
                'commissions_num' => $commissionsCount,
                'order_num' => $orderNum,
            ];
            $this->createProfitStatistics($date, $companyId, $profitUserId, $profitUserType, $totalFee, $name, $params);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
        }

        return false;
    }

    /**
     * 提现分润扣减
     * @param $companyId 公司ID
     * @param $profitUserId 用户id
     * @param $profitUserType 用户类型
     * @param $fee 提现金额
     * @return bool
     */
    public function withdrawalAgentSuccess($data)
    {
        $beginTime = strtotime(date('Y-m-01 00:00:00', strtotime('-1 month')));
        $endTime = strtotime(date("Y-m-d 23:59:59", strtotime(-date('d') . 'day')));
        $companyId = $data['company_id'];
        $profitUserId = $data['id'];
        $name = $data['name'];
        $profitUserType = self::PROFIT_USER_TYPE_DEALER;
        $date = date('Ym', $beginTime);
        if ($this->getProfitStatistics($date, $companyId, $profitUserId, $profitUserType)) {
            return true;
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 上月可提现金额计算
            $subsidyFeeSum = $this->profitLogRepository->sum([
                'profit_user_id' => $profitUserId,
                'profit_user_type' => $profitUserType,
                'status' => self::PROFIT_STATUS_CASHED,
                'created|gte' => $beginTime,
                'created|lte' => $endTime
            ], 'income_fee');

            $subsidyFeeCount = $this->profitLogRepository->count([
                'profit_user_id' => $profitUserId,
                'profit_user_type' => $profitUserType,
                'status' => self::PROFIT_STATUS_CASHED,
                'created|gte' => $beginTime,
                'created|lte' => $endTime
            ], 'income_fee');
            // 分润对象数据查询
            $info = $this->profitRepository->getInfo(['profit_user_id' => $profitUserId, 'profit_user_type' => $profitUserType]);
            if ($info && $subsidyFeeSum) {
                if (bcsub($info['cashed_fee'], $subsidyFeeSum, 0) >= 0) {
                    // 分润提现数据修改
                    $data = [
                        'company_id' => $companyId,
                        'order_id' => '',
                        'withdrawals_fee' => bcadd($info['withdrawals_fee'], $subsidyFeeSum, 0),
                        'cashed_fee' => bcsub($info['cashed_fee'], $subsidyFeeSum, 0),
                    ];
                    $this->profitRepository->updateOneBy(['profit_user_id' => $profitUserId, 'profit_user_type' => $profitUserType], $data);
                    // 分润提现流水
                    $logParams = [
                        'order_id' => '',
                        'profit_user_id' => $profitUserId,
                        'profit_user_type' => $profitUserType,
                        'profit_type' => self::PROFIT_TYPE_NULL,
                        'status' => self::PROFIT_STATUS_CASHED,
                        'income_fee' => 0,
                        'outcome_fee' => $subsidyFeeSum,
                        'remark' => '分润提现',
                        'params' => [],
                    ];
                    $this->createProfitLog($companyId, $logParams);
                } else {
                    app('log')->info($name . '分润失败');
                }
            }
            $params = [
                'subsidy_fee' => $subsidyFeeSum,
                'subsidy_fee_num' => $subsidyFeeCount,
            ];
            $this->createProfitStatistics($date, $companyId, $profitUserId, $profitUserType, $subsidyFeeSum, $name, $params);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
        }

        return false;
    }


    public function getWithdrawalList($companyId, array $fields, $pageSize, $page)
    {
        $fields['profit_user_type'] = $fields['profitType'];
        unset($fields['profitType']);
        $rules = [
            'profit_user_type' => ['required_with:' . implode(',', $this->getProfitUserType()), '分润成员类型必填'],
        ];
        $error = validator_params($fields, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        if (!($fields['date'] ?? 0)) {
            $fields['date'] = date('Ym', strtotime('-1 month'));
        }
        if (self::PROFIT_USER_TYPE_SELLER == $fields['profit_user_type']) {
            $name = $fields['salesperson'] ?? '';
        }
        if (self::PROFIT_USER_TYPE_DISTRIBUTOR == $fields['profit_user_type']) {
            $name = $fields['distributor'] ?? '';
        }
        if (self::PROFIT_USER_TYPE_DEALER == $fields['profit_user_type']) {
            $name = $fields['dealer'] ?? '';
        }
        unset($fields['distributor']);
        unset($fields['salesperson']);
        unset($fields['dealer']);
        if ($name) {
            $fields['name|contains'] = $name;
        }
        $lists = $this->profitStatisticsRepository->lists($fields, '*', $page, $pageSize);
        return $lists;
    }

    /**
     * 分润自动提现脚本
     */
    public function scheduleProfitWithdrawal()
    {
        $pageSize = 100;
        $distributorService = new DistributorService();
        $salespersonService = new SalespersonService();
        //$agentService = new AgentService();
        $totalCount = $distributorService->entityRepository->count([]);
        if ($totalCount > 0) {
            $totalPage = ceil($totalCount / $pageSize);
            for ($i = 1; $i <= $totalPage; $i++) {
                $list = $distributorService->entityRepository->getLists([], '*', $i, $pageSize, ['distributor_id' => 'asc']);
                if ($list) {
                    foreach ($list as $v) {
                        $this->withdrawalDistributorSuccess($v);
                    }
                }
            }
        }
        $totalCount = $salespersonService->salesperson->count([]);
        if ($totalCount > 0) {
            $totalPage = ceil($totalCount / $pageSize);
            for ($i = 1; $i <= $totalPage; $i++) {
                $list = $salespersonService->salesperson->getLists(['salesperson_type' => 'shopping_guide'], '*', $i, $pageSize, ['salesperson_id' => 'asc']);
                if ($list) {
                    foreach ($list as $v) {
                        $this->withdrawalSalespersonSuccess($v);
                    }
                }
            }
        }
        /*
        $totalCount = $agentService->agentRepository->count([]);
        if ($totalCount > 0) {
            $totalPage = ceil($totalCount / $pageSize);
            for ($i = 1; $i <= $totalPage; $i++) {
                $list = $agentService->agentRepository->getLists([], '*', $i, $pageSize, ['id' => 'asc']);
                if ($list) {
                    foreach ($list as $v) {
                        $this->withdrawalAgentSuccess($v);
                    }
                }
            }
        }
        */
    }

    /**
     * 获取分润成员类型
     * @return array
     */
    private function getProfitUserType()
    {
        $result = [
            self::PROFIT_USER_TYPE_SELLER,
            self::PROFIT_USER_TYPE_DISTRIBUTOR,
            self::PROFIT_USER_TYPE_DEALER,
        ];
        return $result;
    }

    /**
     * 获取分润类型
     * @return array
     */
    private function getProfitType()
    {
        $result = [
            self::PROFIT_TYPE_COMMISSIONS,
            self::PROFIT_TYPE_POPULARIZE_COMMISSIONS,
            self::PROFIT_TYPE_GOODS_AMOUNT,
            self::PROFIT_TYPE_SUBSIDY,
        ];
        return $result;
    }

    /**
     * 获取分润增减类型
     * @return array
     */
    private function getStatus()
    {
        $result = [
            self::PROFIT_STATUS_REFUND,
            self::PROFIT_STATUS_AFTER_SALE,
            self::PROFIT_STATUS_WITHDRAWAL,
            self::PROFIT_STATUS_FROZEN,
            self::PROFIT_STATUS_CASHED,
        ];
        return $result;
    }
}
