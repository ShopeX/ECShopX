<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Listeners;

use OrdersBundle\Events\TradeFinishEvent;

class TradeFinishNotifyPush
{
    // ShopEx EcShopX Service Component
    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        // 积分支付订单不需要
        if (in_array($event->entities->getPayType(), ['point', 'deposit'])) {
            return true;
        }

        if ($event->entities->getPayFee() > 0) {
            try {
                $data = [
                    'payFee' => $event->entities->getPayFee(),
                    'payType' => $event->entities->getPayType(),
                    'shopId' => $event->entities->getShopId(),
                    'payDate' => date('Y-m-d H:i:s', $event->entities->getTimeStart()),
                ];
                // app('websocket_client')->send(json_encode($data));
                app('websocket_client')->driver('paymentmsg')->send($data);
            } catch (\Exception $e) {
                app('log')->debug('websocket paymentnotify service Error:'.$e->getMessage());
            }
        }
    }
}
