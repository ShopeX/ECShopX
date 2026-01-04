<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
