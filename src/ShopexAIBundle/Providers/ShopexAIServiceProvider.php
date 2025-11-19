<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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