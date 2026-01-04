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

use Dingo\Api\Exception\ResourceException;
use PopularizeBundle\Services\PromoterBrokerageStatisticsService;

class DistributeCountService
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
     * 新增分销订单，将分销商品金额和分销佣金累增
     *
     * @param int $distributorId 分销ID
     * @param int $itemPrice 新增分销商品金额
     * @param int $rebate    新增分销佣金金额
     */
    public function addDistribution($companyId, $distributorId, $itemPrice, $rebate, $isClose = false)
    {
        $this->setDataToRedis($companyId, $distributorId, $this->itemTotalPrice, $itemPrice);
        //分销商总共得到的佣金
        $this->setDataToRedis($companyId, $distributorId, $this->rebateTotal, $rebate);
        if ($isClose) {
            //新增可提现佣金
            $this->setDataToRedis($companyId, $distributorId, $this->cashWithdrawalRebate, $rebate);
        } else {
            //新增待结算佣金
            $this->setDataToRedis($companyId, $distributorId, $this->noCloseRebate, $rebate);
        }
    }

    /**
     * 添加结算佣金
     */
    public function addSettleRebate($companyId, $distributorId, $rebate)
    {
        //新增可提现佣金
        $this->setDataToRedis($companyId, $distributorId, $this->cashWithdrawalRebate, $rebate);
        //扣减待结算佣金
        $this->setDataToRedis($companyId, $distributorId, $this->noCloseRebate, -$rebate);
    }

    /**
     * 分销商申请提现
     */
    public function applyCashWithdrawal($companyId, $distributorId, $money)
    {
        //可提现金额扣除
        $cashWithdrawalRebate = $this->setDataToRedis($companyId, $distributorId, $this->cashWithdrawalRebate, -$money);
        if ($cashWithdrawalRebate < 0) {
            $cashWithdrawalRebate = $this->setDataToRedis($companyId, $distributorId, $this->cashWithdrawalRebate, $money);
            throw new ResourceException('申请提现金额额度超出限制');
        }
        //新增冻结可提现金额
        $this->setDataToRedis($companyId, $distributorId, $this->freezeCashWithdrawalRebate, $money);

        return true;
    }

    /**
     * 商家拒绝分销商提现申请
     */
    public function rejectCashWithdrawal($companyId, $distributorId, $money)
    {
        $this->setDataToRedis($companyId, $distributorId, $this->freezeCashWithdrawalRebate, -$money);
        $this->setDataToRedis($companyId, $distributorId, $this->cashWithdrawalRebate, $money);
    }

    /**
     * 商家同意分销商提现申请
     */
    public function agreeCashWithdrawal($companyId, $distributorId, $money)
    {
        $this->setDataToRedis($companyId, $distributorId, $this->freezeCashWithdrawalRebate, -$money);
    }

    /**
     * 获取指定分销商的统计
     */
    public function getDistributorCount($companyId, $distributorId)
    {
        $data['itemTotalPrice'] = $this->getDataToRedis($companyId, $distributorId, $this->itemTotalPrice) ?: 0;
        $data['rebateTotal'] = $this->getDataToRedis($companyId, $distributorId, $this->rebateTotal) ?: 0;
        $data['noCloseRebate'] = $this->getDataToRedis($companyId, $distributorId, $this->noCloseRebate) ?: 0;
        $data['cashWithdrawalRebate'] = $this->getDataToRedis($companyId, $distributorId, $this->cashWithdrawalRebate) ?: 0;
        $data['freezeCashWithdrawalRebate'] = $this->getDataToRedis($companyId, $distributorId, $this->freezeCashWithdrawalRebate) ?: 0;
        return $data;
    }

    /**
     * 获取分销的统计
     */
    public function getCount($companyId)
    {
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $info = $promoterBrokerageStatisticsService->sum('item_total_price', ['company_id' => $companyId]);
        $data['itemTotalPrice'] = $promoterBrokerageStatisticsService->sum('item_total_price', ['company_id' => $companyId]);
        $data['rebateTotal'] = $promoterBrokerageStatisticsService->sum('rebate_total', ['company_id' => $companyId]);
        $data['noCloseRebate'] = $promoterBrokerageStatisticsService->sum('no_close_rebate', ['company_id' => $companyId]);
        $data['cashWithdrawalRebate'] = $promoterBrokerageStatisticsService->sum('cash_withdrawal_rebate', ['company_id' => $companyId]);
        $data['freezeCashWithdrawalRebate'] = $promoterBrokerageStatisticsService->sum('freeze_cash_withdrawal_rebate', ['company_id' => $companyId]);
        $data['rechargeRebate'] = $promoterBrokerageStatisticsService->sum('recharge_rebate', ['company_id' => $companyId]);
        $data['payedRebate'] = $promoterBrokerageStatisticsService->sum('payed_rebate', ['company_id' => $companyId]);

        // $data['itemTotalPrice'] = $this->getDataToRedisByCompanyId($companyId, $this->itemTotalPrice) ?: 0;
        // $data['rebateTotal'] = $this->getDataToRedisByCompanyId($companyId, $this->rebateTotal) ?: 0;
        // $data['noCloseRebate'] = $this->getDataToRedisByCompanyId($companyId, $this->noCloseRebate) ?: 0;
        // $data['cashWithdrawalRebate'] = $this->getDataToRedisByCompanyId($companyId, $this->cashWithdrawalRebate) ?: 0;
        // $data['freezeCashWithdrawalRebate'] = $this->getDataToRedisByCompanyId($companyId, $this->freezeCashWithdrawalRebate) ?: 0;
        // $data['rechargeRebate'] = $this->getDataToRedisByCompanyId($companyId, $this->rechargeRebate) ?: 0;
        // $data['payedRebate'] = $this->getDataToRedisByCompanyId($companyId, $this->payedRebate) ?: 0;
        return $data;
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
        $hashKey = floor($userId / 20);
        $money = app('redis')->hincrby('promoterPopularizeCount:'.$hashKey, $key.'-'.$userId, $value);

        $hashCompanyKey = floor($companyId / 20);
        app('redis')->hincrby('companyPopularizeCount:'.$hashCompanyKey, $key.'-'.$companyId, $value);

        return $money;
    }
}
