<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Providers;

use Illuminate\Support\ServiceProvider;
use WorkWechatBundle\Services\WechatManagerService;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class WorkWechatServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('wechat.work.wechat', function () {
            return new WechatManagerService();
        });
    }
}
