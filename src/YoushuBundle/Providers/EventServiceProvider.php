<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // ShopEx EcShopX Service Component
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        //类目监听
        'GoodsBundle\Events\ItemCategoryAddEvent' => [
            'YoushuBundle\Listeners\ItemCategory',
        ],
        //会员注册监听
        'MembersBundle\Events\CreateMemberSuccessEvent' => [
            'YoushuBundle\Listeners\Member',
        ],
    ];

    /**
     * 需要注册的订阅者类。
     *
     * @var array
     */
    protected $subscribe = [
        //门店监听
        'YoushuBundle\Listeners\Distribution',
        //商品监听
        'YoushuBundle\Listeners\Items',
        //优惠券监听
        'YoushuBundle\Listeners\Coupon',
        //订单监听
        'YoushuBundle\Listeners\Order',
    ];
}
