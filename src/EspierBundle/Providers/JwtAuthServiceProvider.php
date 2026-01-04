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
