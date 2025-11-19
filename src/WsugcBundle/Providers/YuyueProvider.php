<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WsugcBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class YuyueProvider extends ServiceProvider
{
    // Powered by ShopEx EcShopX
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
      /*  //类目监听
        'GoodsBundle\Events\ItemCategoryAddEvent' => [
            'WsugcBundle\Listeners\ItemCategory',
        ],
        //会员注册监听
        'MembersBundle\Events\CreateMemberSuccessEvent' => [
            'WsugcBundle\Listeners\Member',
        ],*/
    ];
    
    /**
     * 需要注册的订阅者类。
     *
     * @var array
     */
    protected $subscribe = [
        //订单监听
        'WsugcBundle\Listeners\Order',
    ];
}
