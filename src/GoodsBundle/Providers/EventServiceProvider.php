<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'GoodsBundle\Events\ItemCreateEvent' => [
            'PromotionsBundle\Listeners\CreateItemSuccessPromotions',
        ],
        'GoodsBundle\Events\ItemDeleteEvent' => [
        ],
        'GoodsBundle\Events\ItemEditEvent' => [
            'DistributionBundle\Listeners\UpdateItemStore',
        ],
        'GoodsBundle\Events\ItemTagEditEvent' => [
            'PromotionsBundle\Listeners\ItemTagEditSuccessPromotions',
        ],
        'GoodsBundle\Events\ItemStoreUpdateEvent' => [
            'MembersBundle\Listeners\SendTemplateMsgListener',
        ],
    ];
}
