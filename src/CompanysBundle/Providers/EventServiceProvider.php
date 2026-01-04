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
