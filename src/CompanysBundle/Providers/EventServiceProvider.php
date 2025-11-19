<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'CompanysBundle\Events\CompanyCreateEvent' => [
            'CompanysBundle\Listeners\DeveloperDataToShuyunListener', // 开发者配置数据同步数云
            'CompanysBundle\Listeners\DefaultGradeCreateListener',
            'CompanysBundle\Listeners\OnlineOpenCallbackListener', //线上开通发邮件
            'CompanysBundle\Listeners\OnlineOpenSendSmsListener', //线上开通发短信
            'CompanysBundle\Listeners\OnlineOpenSendEmailListener', //线上开通发邮件
            'CompanysBundle\Listeners\InitDemoDataListener', // 账号开通自动新建测试数据
            // 'CompanysBundle\Listeners\InitDeveloperDataListener', // 初始化开发者配置
        ],
    ];
}
