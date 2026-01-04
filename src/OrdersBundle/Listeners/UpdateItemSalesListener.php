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
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\OrderAssociationService;


use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateItemSalesListener extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;
    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        try {
            // 积分支付订单不需要
            // if (in_array($event->entities->getPayType(), ['point', 'deposit'])) {
            //     return true;
            // }
            $companyId = $event->entities->getCompanyId();
            $orderId = $event->entities->getOrderId();
            // 会员小程序直接买单 只有支付单 没有订单
            if (!$orderId) {
                return true;
            }
            app('log')->debug('订单号商品销量增加：' . $orderId);
            $orderAssociationService = new OrderAssociationService();
            $order = $orderAssociationService->getOrder($companyId, $orderId);
            if ($order) {
                $orderService = $this->getOrderServiceByOrderInfo($order);
                $orderService->incrSales($orderId, $companyId);
            }
        } catch (\Exception $e) {
            app('log')->debug('订单号商品销量增加失败：' .$orderId.';msg:'.$e->getMessage());
        }

        return true;
    }
}
