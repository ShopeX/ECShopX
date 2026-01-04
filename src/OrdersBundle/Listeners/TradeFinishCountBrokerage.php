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

use HfPayBundle\Events\HfpayBrokerageEvent;
use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Services\OrderAssociationService;
use PopularizeBundle\Services\BrokerageService;
use PopularizeBundle\Services\TaskBrokerageService;

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

class TradeFinishCountBrokerage extends BaseListeners implements ShouldQueue
{
    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        app('log')->info(':export brokerage:订单分销佣金开始');

        $companyId = $event->entities->getCompanyId();
        // 支付金额
        $payFee = $event->entities->getPayFee();
        // 支付订单号
        $orderId = $event->entities->getOrderId();
        if (!$orderId) {
            return true;
        }

        $paymentstate = $event->entities->getTradeState();
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $orderId);
        if (!$order) {
            return true;
        }

        $brokerageService = new BrokerageService();
        $taskBrokerageService = new TaskBrokerageService();

        app('log')->info('订单分销佣金 -> company_id:' . $companyId . '-payFee:' . $payFee . '-orderId:' . $orderId . '-paymentstate:' . $paymentstate);
        // app('log')->info('订单分销佣金order ->' . var_export($order, 1));
        try {
            if ($paymentstate == 'SUCCESS' && $order['order_type'] != 'memberCard') {
                $taskBrokerageService->promoterTaskBrokerage($order);
            }

            // 积分支付订单不需要
            if (in_array($event->entities->getPayType(), ['point'])) {
                return true;
            }

            // 社区团购订单不需要分佣
            // if ($order['order_class'] == 'community') {
            //     return true;
            // }

            // 社区团购订单不需要分佣
            // if ($order['order_class'] == 'groups') {
            //     return true;
            // }

            // 社区团购订单不需要分佣
            // if ($order['order_class'] == 'seckill') {
            //     return true;
            // }

            // 不需要分佣的订单类型
            if (in_array($order['order_class'], ['community', 'groups', 'seckill', 'pointsmall'])) {
                return true;
            }

            // 目前只支持实体类商品返佣
            if ($paymentstate == 'SUCCESS' && ($order['order_type'] == 'normal' || $order['order_type'] == 'memberCard')) {
                $brokerageService->insertOrderBrokerage($order, $payFee);

                $eventData = [
                    'order_id' => $order['order_id']
                ];
                event(new HfpayBrokerageEvent($eventData));
            }
        } catch (\Exception $e) {
            app('log')->debug('订单分销佣金'. $e->getMessage(). var_export($order, true));
        }
    }
}
