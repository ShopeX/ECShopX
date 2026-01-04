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

use PointBundle\Services\PointMemberService;
use Dingo\Api\Exception\ResourceException;

// 推广员统计
class PromoterCountService
{
    /**
     * 分销商品总金额
     */
    public $itemTotalPrice = 'itemTotalPrice';

    /**
     * 分销佣金总金额
     */
    public $rebateTotal = 'rebateTotal';

    /**
     * 未结算佣金
     */
    public $noCloseRebate = 'noCloseRebate';

    /**
     * 可提现佣金
     */
    public $cashWithdrawalRebate = 'cashWithdrawalRebate';

    /**
     * 申请提现佣金，冻结提现佣金
     */
    public $freezeCashWithdrawalRebate = 'freezeCashWithdrawalRebate';

    /**
     * 已提现佣金
     */
    public $payedRebate = 'payedRebate';

    /**
     * 充值返佣
     */
    public $rechargeRebate = 'rechargeRebate';

    /**
     * 新增分销订单，将分销商品金额和分销佣金累增
     *
     * @param int $companyId 商家ID
     * @param int $userId 分销ID
     * @param int $itemPrice 新增分销商品金额
     * @param int $rebate    新增分销佣金金额
     */
    public function addPopularize($companyId, $userId, $itemPrice, $rebate, $isClose = false)
    {
        $this->setDataToRedis($companyId, $userId, $this->itemTotalPrice, $itemPrice);
        //分销商总共得到的佣金
        $this->setDataToRedis($companyId, $userId, $this->rebateTotal, $rebate);
        $data = [
            'item_total_price' => $itemPrice,
            'rebate_total' => $rebate,
        ];
        if ($isClose) {
            // //新增可提现佣金
            $this->setDataToRedis($companyId, $userId, $this->cashWithdrawalRebate, $rebate);
            $data['cash_withdrawal_rebate'] = $rebate;
        } else {
            // //新增待结算佣金
            $this->setDataToRedis($companyId, $userId, $this->noCloseRebate, $rebate);
            $data['no_close_rebate'] = $rebate;
        }
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $promoterBrokerageStatisticsService->add($data, ['user_id' => $userId, 'company_id' => $companyId]);
        return true;
    }

    /**
     * 新增分销订单，将分销商品金额累加
     *
     * @param int $companyId 商家ID
     * @param int $userId 分销ID
     * @param int $itemPrice 新增分销商品金额
     * @return mixed
     */
    public function addItemTotalPrice($companyId, $userId, $itemPrice)
    {
        $this->setDataToRedis($companyId, $userId, $this->itemTotalPrice, $itemPrice);

        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $data = [
            'item_total_price' => $itemPrice,
        ];
        $promoterBrokerageStatisticsService->add($data, ['user_id' => $userId, 'company_id' => $companyId]);

        return true;
    }

    /**
     * 新增充值返佣
     *
     * @param int $companyId 商家ID
     * @param int $userId 分销ID
     * @param int $rebate    新增分销佣金金额
     */
    public function addRechargeRebate($companyId, $userId, $rebate)
    {
        // EcShopX core
        $this->setDataToRedis($companyId, $userId, $this->rechargeRebate, $rebate);
        $this->setDataToRedis($companyId, $userId, $this->rebateTotal, $rebate);
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $data = [
            'rebate_total' => $rebate,
            'recharge_rebate' => $rebate,
        ];
        $promoterBrokerageStatisticsService->add($data, ['user_id' => $userId, 'company_id' => $companyId]);

        return true;
    }

    /**
     * 更新返佣订单积分
     *
     * @param $companyId
     * @param $userId
     * @param $itemPrice
     * @param $point
     * @return bool
     */
    public function updatePromoterOrderPoint($companyId, $userId, $itemPrice, $point): bool
    {
        // 数云，不处理积分
        if (config('common.oem-shuyun')) {
            return true;
        }
        $data = [
            'item_total_price' => $itemPrice,
            'point_total' => $point,
            'no_close_point' => $point //待结算积分
        ];

        (new PromoterBrokerageStatisticsService())->add($data, ['user_id' => $userId, 'company_id' => $companyId]);

        return true;
    }

    /**
     * 更新充值返佣 积分
     *
     * @param $companyId
     * @param $userId
     * @param $point
     * @return bool
     * @throws \Exception
     */
    public function updateRechargePoint($companyId, $userId, $point): bool
    {
        // EcShopX core
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $data = [
            'point_total' => $point,
            'recharge_point' => $point,
        ];
        $promoterBrokerageStatisticsService->add($data, ['user_id' => $userId, 'company_id' => $companyId]);

        // 添加积分入账
        $pointMemberService = new PointMemberService();
        $pointMemberService->addPoint($userId, $companyId, $point, 3);

        return true;
    }


    /**
     * 添加结算积分数量
     *
     * @param $companyId
     * @param $userId
     * @param $point
     * 订单ID
     * @param $orderId
     * @throws \Exception
     */
    public function updateSettlePoint($companyId, $userId, $point, $orderId)
    {
        // 数云模式，不处理积分
        if (config('common.oem-shuyun')) {
            return true;
        }
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $data = [
            'cash_withdrawal_point' => $point,  // 累计已结算积分
            'no_close_point' => -$point, // 未结算积分
        ];
        $promoterBrokerageStatisticsService->add($data, ['user_id' => $userId, 'company_id' => $companyId]);

        app('log')->debug('分发积分start:'. $orderId);
        // 添加积分入账
        $pointMemberService = new PointMemberService();
        $pointStatus = $point > 0;
        $record = $pointStatus ? '分佣获取积分' : '分佣获取积分返回';

        $pointMemberService->addPoint($userId, $companyId, abs($point), PointMemberService::JOURNAL_TYPE_PROMOTER, $pointStatus, $record, $orderId);
    }

    /**
     * 添加结算佣金
     *
     * @param int $companyId 商家ID
     * @param int $userId 分销ID
     * @param int $rebate    新增分销佣金金额
     */
    public function addSettleRebate($companyId, $userId, $rebate)
    {
        //新增可提现佣金
        $this->setDataToRedis($companyId, $userId, $this->cashWithdrawalRebate, $rebate);
        //扣减待结算佣金
        $this->setDataToRedis($companyId, $userId, $this->noCloseRebate, -$rebate);
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $data = [
            'cash_withdrawal_rebate' => $rebate,
            'no_close_rebate' => -$rebate,
        ];
        $promoterBrokerageStatisticsService->add($data, ['user_id' => $userId, 'company_id' => $companyId]);

        return true;
    }


    /**
     * 分销商申请提现
     *
     * @param int $companyId 商家ID
     * @param int $userId 分销ID
     * @param int $money    新增分销佣金金额
     */
    public function applyCashWithdrawal($companyId, $userId, $money)
    {
        //可提现金额扣除
        $cashWithdrawalRebate = $this->setDataToRedis($companyId, $userId, $this->cashWithdrawalRebate, -$money);
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $info = $promoterBrokerageStatisticsService->getInfo(['user_id' => $userId, 'company_id' => $companyId]);
        if (!($info['cash_withdrawal_rebate'] ?? 0)) {
            throw new ResourceException('申请提现金额额度超出限制');
        }
        if (($info['cash_withdrawal_rebate'] - $money) < 0) {
            $cashWithdrawalRebate = $this->setDataToRedis($companyId, $userId, $this->cashWithdrawalRebate, $money);
            throw new ResourceException('申请提现金额额度超出限制');
        }
        //新增冻结可提现金额
        $this->setDataToRedis($companyId, $userId, $this->freezeCashWithdrawalRebate, $money);
        $data = [
            'cash_withdrawal_rebate' => -$money,
            'freeze_cash_withdrawal_rebate' => $money,
        ];
        $promoterBrokerageStatisticsService->add($data, ['user_id' => $userId, 'company_id' => $companyId]);
        return true;
    }

    /**
     * 商家拒绝分销商提现申请
     *
     * @param int $companyId 商家ID
     * @param int $userId 分销ID
     * @param int $money    新增分销佣金金额
     */
    public function rejectCashWithdrawal($companyId, $userId, $money)
    {
        $this->setDataToRedis($companyId, $userId, $this->freezeCashWithdrawalRebate, -$money);
        $this->setDataToRedis($companyId, $userId, $this->cashWithdrawalRebate, $money);
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $data = [
            'freeze_cash_withdrawal_rebate' => -$money,
            'cash_withdrawal_rebate' => $money,
        ];
        $promoterBrokerageStatisticsService->add($data, ['user_id' => $userId, 'company_id' => $companyId]);
        return true;
    }

    /**
     * 商家同意分销商提现申请
     *
     * @param int $companyId 商家ID
     * @param int $userId 分销ID
     * @param int $money    新增分销佣金金额
     */
    public function agreeCashWithdrawal($companyId, $userId, $money)
    {
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $info = $promoterBrokerageStatisticsService->getInfo(['user_id' => $userId, 'company_id' => $companyId]);
        if ($info['cash_withdrawal_rebate'] < 0) {
            throw new ResourceException('申请提现金额额度超出限制');
        }
        $this->setDataToRedis($companyId, $userId, $this->freezeCashWithdrawalRebate, -$money);
        $this->setDataToRedis($companyId, $userId, $this->payedRebate, $money);
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $data = [
            'freeze_cash_withdrawal_rebate' => -$money,
            'payed_rebate' => $money,
        ];
        $promoterBrokerageStatisticsService->add($data, ['user_id' => $userId, 'company_id' => $companyId]);
        return true;
    }

    /**
     * 获取指定分销商的统计
     */
    public function getPromoterCount($companyId, $userId)
    {
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $info = $promoterBrokerageStatisticsService->getInfo(['user_id' => $userId, 'company_id' => $companyId]);
        $data['itemTotalPrice'] = $info['item_total_price'] ?? 0;
        $data['rebateTotal'] = $info['rebate_total'] ?? 0;
        $data['noCloseRebate'] = $info['no_close_rebate'] ?? 0;
        $data['cashWithdrawalRebate'] = $info['cash_withdrawal_rebate'] ?? 0;
        $data['freezeCashWithdrawalRebate'] = $info['freeze_cash_withdrawal_rebate'] ?? 0;
        $data['rechargeRebate'] = $info['recharge_rebate'] ?? 0;
        $data['payedRebate'] = $info['payed_rebate'] ?? 0;
        $data['noClosePoint'] = $info['no_close_point'] ?? 0;// 未结算积分
        $data['pointTotal'] = $info['point_total'] ?? 0;// 积分总数


        // $data['itemTotalPrice'] = $this->getDataToRedis($companyId, $userId, $this->itemTotalPrice) ?: 0;
        // $data['rebateTotal'] = $this->getDataToRedis($companyId, $userId, $this->rebateTotal) ?: 0;
        // $data['noCloseRebate'] = $this->getDataToRedis($companyId, $userId, $this->noCloseRebate) ?: 0;
        // $data['cashWithdrawalRebate'] = $this->getDataToRedis($companyId, $userId, $this->cashWithdrawalRebate) ?: 0;
        // $data['freezeCashWithdrawalRebate'] = $this->getDataToRedis($companyId, $userId, $this->freezeCashWithdrawalRebate) ?: 0;
        // $data['rechargeRebate'] = $this->getDataToRedis($companyId, $userId, $this->rechargeRebate) ?: 0;
        // $data['payedRebate'] = $this->getDataToRedis($companyId, $userId, $this->payedRebate) ?: 0;
        return $data;
    }

    /**
     * 获取推广员统计数据
     *
     * @param int $companyId
     * @param array $userIdList
     * @return array
     */
    public function getPromoterIndexCount(int $companyId, array $userIdList): array
    {
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $filter = [
            'company_id' => $companyId
        ];
        if (!empty($userIdList)) {
            $filter['user_id'] = $userIdList;
        }


        $list = $promoterBrokerageStatisticsService->getLists($filter);

        $result = [];
        foreach ($list as $item) {
            $result[$item['user_id']] = [
                'itemTotalPrice' => $item['item_total_price'] ?? 0,
                'rebateTotal' => $item['rebate_total'] ?? 0,
                'noCloseRebate' => $item['no_close_rebate'] ?? 0,
                'cashWithdrawalRebate' => $item['cash_withdrawal_rebate'] ?? 0,
                'freezeCashWithdrawalRebate' => $item['freeze_cash_withdrawal_rebate'] ?? 0,
                'rechargeRebate' => $item['recharge_rebate'] ?? 0,
                'payedRebate' => $item['payed_rebate'] ?? 0,
                'rechargePoint' => $item['recharge_point'] ?? 0,
                'cashWithdrawalPoint' => $item['cash_withdrawal_point'] ?? 0,
                'noClosePoint' => $item['no_close_point'] ?? 0,
                'pointTotal' => $item['point_total'] ?? 0,
            ];
        }

        return $result;
    }


    /**
     * 获取分销的统计
     */
    public function getCount($companyId)
    {
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $data['itemTotalPrice'] = $promoterBrokerageStatisticsService->sum('item_total_price', ['company_id' => $companyId]);
        $data['rebateTotal'] = $promoterBrokerageStatisticsService->sum('rebate_total', ['company_id' => $companyId]);
        $data['noCloseRebate'] = $promoterBrokerageStatisticsService->sum('no_close_rebate', ['company_id' => $companyId]);
        $data['cashWithdrawalRebate'] = $promoterBrokerageStatisticsService->sum('cash_withdrawal_rebate', ['company_id' => $companyId]);
        $data['freezeCashWithdrawalRebate'] = $promoterBrokerageStatisticsService->sum('freeze_cash_withdrawal_rebate', ['company_id' => $companyId]);
        $data['rechargeRebate'] = $promoterBrokerageStatisticsService->sum('recharge_rebate', ['company_id' => $companyId]);
        $data['payedRebate'] = $promoterBrokerageStatisticsService->sum('payed_rebate', ['company_id' => $companyId]);
        $data['pointTotal'] = $promoterBrokerageStatisticsService->sum('point_total', ['company_id' => $companyId]);

        // $data['itemTotalPrice'] = $this->getDataToRedisByCompanyId($companyId, $this->itemTotalPrice) ?: 0;
        // $data['rebateTotal'] = $this->getDataToRedisByCompanyId($companyId, $this->rebateTotal) ?: 0;
        // $data['noCloseRebate'] = $this->getDataToRedisByCompanyId($companyId, $this->noCloseRebate) ?: 0;
        // $data['cashWithdrawalRebate'] = $this->getDataToRedisByCompanyId($companyId, $this->cashWithdrawalRebate) ?: 0;
        // $data['freezeCashWithdrawalRebate'] = $this->getDataToRedisByCompanyId($companyId, $this->freezeCashWithdrawalRebate) ?: 0;
        // $data['rechargeRebate'] = $this->getDataToRedisByCompanyId($companyId, $this->rechargeRebate) ?: 0;
        // $data['payedRebate'] = $this->getDataToRedisByCompanyId($companyId, $this->payedRebate) ?: 0;
        return $data;
    }

    /**
     * 分销积分统计
     *
     * @param int $companyId
     * @param int $userId
     * @return array
     */
    public function promoterPointCount(int $companyId, int $userId)
    {
        // 推广积分总额
        $promoterCountData = $this->getPromoterCount($companyId, $userId);

        $orderBrokerage = (new BrokerageService())->getOrderBrokerage($companyId, $userId);

        $orderBrokerage['order_close_rebate'] = $orderBrokerage['order_close_rebate'] ?? 0;
        $orderBrokerage['order_team_close_rebate'] = $orderBrokerage['order_team_close_rebate'] ?? 0;
        $orderBrokerage['order_no_close_rebate'] = $orderBrokerage['order_no_close_rebate'] ?? 0;
        $orderBrokerage['order_close_rebate'] = $orderBrokerage['order_close_rebate'] ?? 0;
        $orderBrokerage['order_team_no_close_rebate'] = $orderBrokerage['order_team_no_close_rebate'] ?? 0;

        $grandPointTotal = bcadd($orderBrokerage['order_close_rebate'], $orderBrokerage['order_team_close_rebate']);

        return [
            'grand_point_total' => $grandPointTotal, // 累计获得积分
            'point_total' => (int)$promoterCountData['pointTotal'] ?? 0, // 推广积分总额
            'rebate_point' => 0, // 小店积分提成 目前指的是任务制商品积分 任务制是没有积分
            'order_no_close_rebate' => $orderBrokerage['order_no_close_rebate'],
            'order_close_rebate' => $orderBrokerage['order_close_rebate'],
            'order_total' => bcadd($orderBrokerage['order_no_close_rebate'], $orderBrokerage['order_close_rebate']),
            'order_team_no_close_rebate' => $orderBrokerage['order_team_no_close_rebate'],
            'order_team_close_rebate' => $orderBrokerage['order_team_close_rebate'],
            'order_team_total' => bcadd($orderBrokerage['order_team_no_close_rebate'], $orderBrokerage['order_team_close_rebate']),
        ];
    }

    private function getDataToRedis($companyId, $userId, $key)
    {
        $hashKey = floor($userId / 20);
        return app('redis')->hget('promoterPopularizeCount:'.$hashKey, $key.'-'.$userId);
    }

    private function getDataToRedisByCompanyId($companyId, $key)
    {
        $hashCompanyKey = floor($companyId / 20);
        return app('redis')->hget('companyPopularizeCount:'.$hashCompanyKey, $key.'-'.$companyId);
    }

    private function setDataToRedis($companyId, $userId, $key, $value)
    {
        if ($value === floatval(0)) {
            $value = 0;
        }

        $hashKey = floor($userId / 20);
        $money = app('redis')->hincrby('promoterPopularizeCount:'.$hashKey, $key.'-'.$userId, $value);

        $hashCompanyKey = floor($companyId / 20);
        app('redis')->hincrby('companyPopularizeCount:'.$hashCompanyKey, $key.'-'.$companyId, $value);

        return $money;
    }
}
