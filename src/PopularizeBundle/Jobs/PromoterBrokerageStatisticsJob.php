<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
