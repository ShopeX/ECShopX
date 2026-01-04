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
use WechatBundle\OvertrueWechat\WechatManager;
use EasyWeChat\Factory;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use WechatBundle\Services\Payment\BatchTransfer\Client;

class EasyWechatServiceProvider extends ServiceProvider
{
    public function register()
    {
        // ShopEx EcShopX Business Logic Layer
        $this->app->configure('wechat');
        $config = config('wechat');

        // 开放平台实例化
        $this->app->singleton('easywechat.open_platform', function () use ($config) {
            // 因为将一些其他的微信配置也放到这个文件中，但是默认的wechat.php的配置文件中是没有这个配置的，所以变相合并一下
            $wechatConfig = array_merge($config['open_platform'], $config);
            $app = Factory::openPlatform($wechatConfig);
            // 创建缓存实例
            $cache = new RedisAdapter(app('redis')->connection()->client());
            $app->rebind('cache', $cache);
            return $app;
        });

        // 微信支付实例化
        $this->app->singleton('easywechat.app.payment', function ($app, $params) use ($config) {
            $app = Factory::payment($params);
            // 创建缓存实例
            $cache = new RedisAdapter(app('redis')->connection()->client());
            $app->rebind('cache', $cache);
            return $app;
        });

        $this->app->extend('easywechat.app.payment', function($app, $container) {
            $app['batch_transfer'] = function ($app) {
                return new Client($app);
            };
            return $app;
        });

        // 多配置小程序实例化
        foreach (WechatManager::SUPPORT_MINI_PROGRAMS as $miniProgram) {
            $this->app->singleton('easywechat.mini_program.'.$miniProgram, function ($app) use ($miniProgram, $config) {
                $config = [
                    'app_id' => env(strtoupper($miniProgram).'_APPID'),   // AppID
                    'secret' => env(strtoupper($miniProgram).'_APP_SECRET'), // AppSecret

                ];
                $app = Factory::miniProgram($config);
                // 创建缓存实例
                $cache = new RedisAdapter(app('redis')->connection()->client());
                $app->rebind('cache', $cache);
                return $app;
            });
        }

        // 公用微信manager
        $this->app->singleton('easywechat.manager', function () {
            return new WechatManager();
        });

        // 微信公众号实例
        $this->app->singleton("easywechat.official_account", function ($app, $config) {
            $app = Factory::officialAccount([
                "app_id" => $config["app_id"] ?? "",
                "secret" => $config["secret"] ?? "",
                "response_type" => "array",
            ]);
            // 创建缓存实例
            $cache = new RedisAdapter(app('redis')->connection()->client());
            $app->rebind('cache', $cache);
            return $app;
        });
    }
}
