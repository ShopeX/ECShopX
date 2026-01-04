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
