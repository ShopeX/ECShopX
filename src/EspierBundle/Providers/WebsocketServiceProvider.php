<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Providers;

use Illuminate\Support\ServiceProvider;
use EspierBundle\Commands\WxappCommand;
use EspierBundle\Providers\WebSocket\WebSocketManager;

class WebsocketServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerConsoleCommands();
        $this->registerWebsocketClient();
    }

    public function registerWebsocketClient()
    {
        $this->app->singleton('websocket_client', function ($app) {
            $manager = new WebSocketManager($app);
            return $manager;
        });
    }

    public function registerConsoleCommands()
    {
        $this->commands(
            WxappCommand::class
        );
    }
}
