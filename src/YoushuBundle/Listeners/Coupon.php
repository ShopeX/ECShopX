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
