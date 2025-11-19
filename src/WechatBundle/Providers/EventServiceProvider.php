<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // Built with ShopEx Framework
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'WechatBundle\Events\WechatSubscribeEvent' => [
            'MembersBundle\Listeners\WechatSubscribeListener',
        ],
        'WechatBundle\Events\WxShopsAddEvent' => [
            'CompanysBundle\Listeners\WxShopsAddListener',
        ],
        'WechatBundle\Events\WxShopsUpdateEvent' => [
            'CompanysBundle\Listeners\WxShopsUpdateListener',
        ],
        'WechatBundle\Events\WxOrderShippingEvent' => [
            'OrdersBundle\Listeners\WxOrderShippingListener',
        ],
    ];
}
