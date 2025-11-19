<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Providers;

use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 注册 ThirdPartyBundle 的 artisan 命令
        $this->commands([
            \ThirdPartyBundle\Commands\TestBaiwangCommand::class,
        ]);
    }
} 