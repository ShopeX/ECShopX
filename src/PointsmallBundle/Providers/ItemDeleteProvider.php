<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PointsmallBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class ItemDeleteProvider extends ServiceProvider
{
    // 53686f704578
    protected $listen = [
        'PointsmallBundle\Events\ItemDeleteEvent' => [
        ],
    ];
}
