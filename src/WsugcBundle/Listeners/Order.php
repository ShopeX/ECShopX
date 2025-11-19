<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WsugcBundle\Listeners;
use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use WsugcBundle\Services\YuyueRecordService;

class Order extends BaseListeners implements ShouldQueue
{  
    /**
     * 普通订单事件
     */
    public function handle($event)
    {
        $company_id = $event->entities['company_id'];
        $order_id   = $event->entities['order_id'];
        $params     = [
            'company_id' => $company_id,
            'order_id'  => $order_id,
        ];
        $srdata_service = new YuyueRecordService();
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
        //支付成功
        $events->listen(
            'OrdersBundle\Events\NormalOrderPaySuccessEvent',
            'WsugcBundle\Listeners\Order@handle'
        ); 
    }
}