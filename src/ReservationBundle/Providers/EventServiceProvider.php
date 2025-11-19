<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ReservationBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // 1e236443e5a30b09910e0d48c994b8e6 core
    protected $listen = [
        'ReservationBundle\Events\ReservationFinishEvent' => [
            'ReservationBundle\Listeners\ReservationFinishWorkShiftAdd',
            'ReservationBundle\Listeners\ReservationFinishSendWxaTemplate',
            'ReservationBundle\Listeners\ReservationRemindSendWxaTemplate',
        ],
    ];
}
