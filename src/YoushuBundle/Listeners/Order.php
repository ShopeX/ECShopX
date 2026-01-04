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

namespace YoushuBundle\Listeners;

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use YoushuBundle\Services\SrDataService;

class Order extends BaseListeners implements ShouldQueue
{
    /**
     * 普通订单事件
     */
    public function handle($event)
    {
        $company_id = $event->entities['company_id'];
        $order_id = $event->entities['order_id'];
        $params = [
            'company_id' => $company_id,
            'object_id' => $order_id,
        ];

        $srdata_service = new SrDataService($company_id);
        $srdata_service->sync($params, 'order');

        return true;
    }

    /**
     * 注册监听器
     *
     * @param  \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        //下单
        $events->listen(
            'OrdersBundle\Events\NormalOrderAddEvent',
            'YoushuBundle\Listeners\Order@handle'
        );

        //取消订单
        $events->listen(
            'OrdersBundle\Events\NormalOrderCancelEvent',
            'YoushuBundle\Listeners\Order@handle'
        );

        //支付成功
        $events->listen(
            'OrdersBundle\Events\NormalOrderPaySuccessEvent',
            'YoushuBundle\Listeners\Order@handle'
        );

        //已发货
        $events->listen(
            'OrdersBundle\Events\NormalOrderDeliveryEvent',
            'YoushuBundle\Listeners\Order@handle'
        );

        //确认收货
        $events->listen(
            'OrdersBundle\Events\NormalOrderConfirmReceiptEvent',
            'YoushuBundle\Listeners\Order@handle'
        );
    }
}
