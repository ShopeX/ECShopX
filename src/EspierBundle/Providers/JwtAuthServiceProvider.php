<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Providers;

use Illuminate\Support\ServiceProvider;
use EspierBundle\Auth\Jwt\EspierUserProvider as EspierUserProvider;
use EspierBundle\Auth\Jwt\EspierLocalUserProvider as EspierLocalUserProvider;
use EspierBundle\Auth\Jwt\EspierSuperAccountProvider as EspierSuperAccountProvider;
use EspierBundle\Auth\Jwt\EspierOauthUserProvider;
use EspierBundle\Auth\Jwt\EspierShuyunUserProvider;
use EspierBundle\Auth\Jwt\EspierMerchantAccountProvider;

class JwtAuthServiceProvider extends ServiceProvider
{
    // ID: 53686f704578
    public function boot()
    {
        // ID: 53686f704578
        $this->app->make('auth')->provider('espier', function ($app, $config) {
            return new EspierUserProvider();
        });
        // shopexid，oauth登录
        $this->app->make('auth')->provider('espier_oauth', function ($app, $config) {
            return new EspierOauthUserProvider();
        });
        // 数云，code登录
        $this->app->make('auth')->provider('espier_shuyun', function ($app, $config) {
            return new EspierShuyunUserProvider();
        });
        // @todo espier_local 性能很差
        $this->app->make('auth')->provider('espier_local', function ($app, $config) {
            return new EspierLocalUserProvider($app, $config);
        });
        $this->app->make('auth')->provider('espier_super', function ($app, $config) {
            return new EspierSuperAccountProvider($app, $config);
        });
        $this->app->make('auth')->provider('espier_merchant', function ($app, $config) {
            return new EspierMerchantAccountProvider($app, $config);
        });
    }
}
