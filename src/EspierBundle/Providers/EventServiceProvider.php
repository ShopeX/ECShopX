<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // artisan开始事件
        \Illuminate\Console\Events\ArtisanStarting::class => [
            //
        ],
        // 命令开始事件
        \Illuminate\Console\Events\CommandStarting::class => [
            //
        ],
        // 命令完成事件
        \Illuminate\Console\Events\CommandFinished::class => [
            \EspierBundle\Listeners\UpdateMenuListener::class,
        ],
    ];
}
