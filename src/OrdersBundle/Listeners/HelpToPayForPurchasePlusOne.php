<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
