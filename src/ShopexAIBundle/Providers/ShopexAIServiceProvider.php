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

namespace ShopexAIBundle\Providers;

use Illuminate\Support\ServiceProvider;
use ShopexAIBundle\Services\DeepseekService;
use ShopexAIBundle\Services\AliyunImageService;
use ShopexAIBundle\Services\ArticleService;
use ShopexAIBundle\Services\PromptService;

class ShopexAIServiceProvider extends ServiceProvider
{
    public function register()
    {
        // 注册 DeepseekService
        $this->app->singleton(DeepseekService::class, function ($app) {
            return new DeepseekService();
        });

        // 注册 AliyunImageService
        $this->app->singleton(AliyunImageService::class, function ($app) {
            return new AliyunImageService();
        });

        // 注册 PromptService
        $this->app->singleton(PromptService::class, function ($app) {
            return new PromptService();
        });

        // 注册 ArticleService
        $this->app->singleton(ArticleService::class, function ($app) {
            return new ArticleService(
                $app->make(DeepseekService::class),
                $app->make(AliyunImageService::class)
            );
        });
    }

    public function boot()
    {
        // 加载路由
        if (file_exists($routes = __DIR__.'/../routes/api.php')) {
            require $routes;
        }
        
        // 加载前端路由
        if (file_exists($frontRoutes = __DIR__.'/../routes/frontapi.php')) {
            require $frontRoutes;
        }
    }
} 