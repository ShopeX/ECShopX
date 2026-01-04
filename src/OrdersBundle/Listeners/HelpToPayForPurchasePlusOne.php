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

use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Services\Orders\BargainNormalOrderService;
use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\TradeService;

class HelpToPayForPurchasePlusOne
{
    /**
     * Handle the event.
     *
     * @param TradeFinishEvent $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfoById($event->entities->getTradeId());
        if ($tradeInfo['trade_source_type'] == 'bargain') {
            $orderId = $tradeInfo['order_id'];
            $companyId = $tradeInfo['company_id'];
            $orderService = new OrderService(new BargainNormalOrderService());
            $orderInfo = $orderService->getOrderInfo($companyId, $orderId);
            if ($orderInfo['orderInfo']['order_class'] == 'bargain') {
                $bargainNormalOrderService = new BargainNormalOrderService();
                $params['user_id'] = $orderInfo['orderInfo']['user_id'];
                $params['bargain_id'] = $orderInfo['orderInfo']['act_id'];
                $bargainNormalOrderService->changeOrderActivityStatus($params);
            }
        }
    }
}
