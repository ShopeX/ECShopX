<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Created by PhpStorm.
 * User: xiaqc
 * Date: 2020/10/30
 * Time: 11:33
 */

namespace GoodsBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class ItemCreateProvider extends ServiceProvider
{
    protected $listen = [
        'GoodsBundle\Events\ItemCreateEvent' => [
            'PromotionsBundle\Listeners\CreateItemSuccessPromotions',
        ],
    ];
}
