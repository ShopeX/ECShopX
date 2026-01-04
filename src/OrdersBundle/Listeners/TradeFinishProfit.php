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

namespace OrdersBundle\Listeners;

use SalespersonBundle\Services\SalespersonService;
use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Services\OrderProfitService;
use SalespersonBundle\Services\LeaderboardService;
use SalespersonBundle\Services\SalespersonTaskRecordService;

class TradeFinishProfit
{
    public function handle(TradeFinishEvent $event)
    {
        app('log')->debug('order profit start');
        $orderId = $event->entities->getOrderId();
        $userId = $event->entities->getUserId();
        $companyId = $event->entities->getCompanyId();

        $date = date('Ymd');
        $expire = 3 * 24 * 3600;
        $filter = [
            'order_id' => $orderId,
            'company_id' => $companyId,
            'user_id' => $userId,
        ];
        $orderProfitService = new OrderProfitService();
        $salespersonService = new SalespersonService();
        $result = $orderProfitService->getInfo($filter);

        if (!$result) {
            app('log')->debug('订单:' . $orderId . '无分润信息');
            return true;
        }
        if ($result['popularize_seller_id']) {
            // 存在导购id才会计算门店业绩
            $leaderboardService = new LeaderboardService();
            $leaderboardService->addSalespersonLeaderboard($companyId, $result['order_distributor_id'], $result['popularize_seller_id'], $result['pay_fee']);
            $leaderboardService->addDistributorLeaderboard($companyId, $result['order_distributor_id'], $result['pay_fee']);

            // 存在导购id才会计算完成客户下单任务
            $SalespersonTaskRecordService = new SalespersonTaskRecordService();
            $params = [
                'company_id' => $companyId,
                'salesperson_id' => $result['popularize_seller_id'],
                'user_id' => $userId,
                'order_id' => $orderId,
            ];
            $SalespersonTaskRecordService->completeOrder($params);
        }

        $orderProfitUpdate = [
            'order_profit_status' => 1,
            'plan_close_time' => time() + 86400 * 10 * 365,
        ];
        $orderItemsProfitUpdate = [
            'order_profit_status' => 1,
        ];

        try {
            $orderProfitService->updateOneBy($filter, $orderProfitUpdate);
            $orderProfitService->orderItemsProfitRepository->updateBy($filter, $orderItemsProfitUpdate);
        } catch (\Exception $e) {
            app('api.exception')->report($e);
        }
    }
}
