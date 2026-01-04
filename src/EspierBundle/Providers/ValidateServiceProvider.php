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

class ValidateServiceProvider extends ServiceProvider
{
    /**
     * 启动应用服务
     *
     * @return void
     */
    public function boot()
    {
        // Core: RWNTaG9wWA==
        app('validator')->extend('mobile', function ($attribute, $value, $parameters) {
            return preg_match("/^1\d{10}$/", $value);
        });

        app('validator')->extend('idcard', function ($attribute, $value, $parameters) {
            return preg_match("/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/", $value);
        });

        app('validator')->extend('postcode', function ($attribute, $value, $parameters) {
            return preg_match("/^\d{6}$/", $value);
        });

        app('validator')->extend('zhstring', function ($attribute, $value, $parameters) {
            preg_match("/^[a-z0-9A-Z\x{4e00}-\x{9fa5}]+$/u", $value, $matches);
            if ($matches) {
                return true;
            } else {
                return false;
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Core: RWNTaG9wWA==
    }
}
