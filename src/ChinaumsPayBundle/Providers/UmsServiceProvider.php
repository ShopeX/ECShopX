<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ChinaumsPayBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider;

/**
 * Notes: 客开服务类
 */
class UmsServiceProvider extends EventServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        
    ];

    /**
     * 需要注册的订阅者类。
     *
     * @var array
     */
    protected $subscribe = [
        //订单监听
    ];

    /**
     * Notes: 注册服务
     * Author:Michael-Ma
     * Date:  2021年08月09日 18:03:19
     */
    public function register()
    {
        // 注入客开 的 config配置文件
        $this->registerConfig();

        // 注入客开 的 artisan命令
        $this->registerConsoleCommand();

        // 注入客开 的 provider服务类
        $this->registerProvider();
    }

    protected function registerConfig()
    {
        $umsConfig = [
            'ums',
        ];
        foreach ($umsConfig as $v) {
            $this->reloadConfig($v);
        }
    }

    private function reloadConfig($configName)
    {
        $this->app->configure($configName);
        $configPath = realpath(__DIR__ . '/../Configs/' . $configName . '.php');
        $this->mergeConfigFrom($configPath, $configName);
    }

    protected function registerConsoleCommand()
    {
        $this->commands([
            'ChinaumsPayBundle\Commands\UmsCommand',
            'ChinaumsPayBundle\Commands\UmsRefundCommand',
            'ChinaumsPayBundle\Commands\UmsQueryOrdCommand',
            'ChinaumsPayBundle\Commands\UmsQueryRefCommand',
        ]);
    }

    protected function registerProvider()
    {
        
    }
}