<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DistributionBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // 53686f704578
    protected $listen = [
        'DistributionBundle\Events\DistributorCreateEvent' => [
            'SystemLinkBundle\Listeners\ShopCreateSendOme',
        ],

        'DistributionBundle\Events\DistributorUpdateEvent' => [
            'SystemLinkBundle\Listeners\ShopUpdateSendOme',
        ],

        // 退货退款时可退运费，自动更新到自营店
        'DistributionBundle\Events\RefundFreightAutoZyEvent' => [
            'DistributionBundle\Listeners\RefundFreightAutoZyListener',
        ],

    ];
}
