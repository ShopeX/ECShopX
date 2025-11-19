<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Listeners;

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use YoushuBundle\Services\SrDataService;

class Coupon extends BaseListeners implements ShouldQueue
{
    /**
     * 处理优惠券事件
     */
    public function handle($event)
    {
        $company_id = $event->entities['company_id'];
        $card_id = $event->entities['card_id'];
        $params = [
            'company_id' => $company_id,
            'object_id' => $card_id,
        ];

        $srdata_service = new SrDataService($company_id);
        $srdata_service->sync($params, 'coupon');

        return true;
    }

    /**
     * 注册监听器
     *
     * @param  \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        //创建优惠券
        $events->listen(
            'KaquanBundle\Events\CouponAddEvent',
            'YoushuBundle\Listeners\Coupon@handle'
        );

        //编辑优惠券
        $events->listen(
            'KaquanBundle\Events\CouponEditEvent',
            'YoushuBundle\Listeners\Coupon@handle'
        );

        //删除优惠券
        $events->listen(
            'KaquanBundle\Events\CouponDeleteEvent',
            'YoushuBundle\Listeners\Coupon@handle'
        );
    }
}
