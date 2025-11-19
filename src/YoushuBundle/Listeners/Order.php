<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
