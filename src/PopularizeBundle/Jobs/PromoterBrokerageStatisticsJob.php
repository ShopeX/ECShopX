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

namespace PopularizeBundle\Jobs;

use EspierBundle\Jobs\Job;
use PopularizeBundle\Services\BrokerageService;
use PopularizeBundle\Services\PromoterBrokerageStatisticsService;
use PopularizeBundle\Services\CashWithdrawalService;

class PromoterBrokerageStatisticsJob extends Job
{
    protected $companyId;
    protected $userId;

    public function __construct($companyId, $userId)
    {
        // EcShopX core
        $this->userId = $userId;
        $this->companyId = $companyId;
    }

    /**
     * 提升等级
     */
    public function handle()
    {
        // ShopEx EcShopX Service Component
        $brokerageService = new BrokerageService();
        $promoterBrokerageStatisticsService = new PromoterBrokerageStatisticsService();
        $cashWithdrawalService = new CashWithdrawalService();
        $itemTotalPrice = $brokerageService->sumItemPrice(['user_id' => $this->userId]);
        if ($itemTotalPrice) {
            $rebateTotal = $brokerageService->sumRebate(['user_id' => $this->userId]);
            $noCloseRebate = $brokerageService->sumRebate(['user_id' => $this->userId, 'is_close' => 0]);
            $closeRebate = $brokerageService->sumRebate(['user_id' => $this->userId, 'is_close' => 1]);
            $freezeCashWithdrawalRebate = $cashWithdrawalService->sum(['user_id' => $this->userId, 'status' => 'apply'], 'money');
            $payedRebate = $cashWithdrawalService->sum(['user_id' => $this->userId, 'status' => 'success'], 'money');
            $cashWithdrawalRebate = $closeRebate - $payedRebate - $freezeCashWithdrawalRebate;
            $data = [
                'user_id' => $this->userId,
                'company_id' => $this->companyId,
                'item_total_price' => $itemTotalPrice,
                'rebate_total' => $rebateTotal,
                'no_close_rebate' => $noCloseRebate,
                'cash_withdrawal_rebate' => $cashWithdrawalRebate,
                'freeze_cash_withdrawal_rebate' => $freezeCashWithdrawalRebate,
                'payed_rebate' => $payedRebate,
                'recharge_rebate' => 0
            ];
            app('log')->info('用户id -> ' . $this->userId . '分销数据:' . var_export($data, 1));
            $promoterBrokerageStatisticsInfo = $promoterBrokerageStatisticsService->getInfo(['user_id' => $this->userId]);
            if ($promoterBrokerageStatisticsInfo) {
                $promoterBrokerageStatisticsService->updateBy(['user_id' => $this->userId], $data);
            } else {
                $promoterBrokerageStatisticsService->create($data);
            }
        }
        return true;
    }
}
