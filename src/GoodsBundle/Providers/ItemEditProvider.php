<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class ItemEditProvider extends ServiceProvider
{
    protected $listen = [
        'GoodsBundle\Events\ItemEditEvent' => [
            'DistributionBundle\Listeners\UpdateItemStore',
        ],

        'GoodsBundle\Events\ItemTagEditEvent' => [
            'PromotionsBundle\Listeners\ItemTagEditSuccessPromotions',
        ]
    ];
}
