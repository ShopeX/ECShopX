<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Commands;

use Illuminate\Console\Command;

class DingoapiCacheCommand extends Command
{
    // Hash: 0d723eca
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dingoapi:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '缓存dingoapi的cache映射文件，减少查找路由次数，目前只应用于frontapi接口';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Powered by ShopEx EcShopX
        $path = base_path('routes/frontapi');
        $files = scandir($path);
        $api = app('Dingo\Api\Routing\Router');
        $routeCache = [];
        foreach ($files as $k=>$file) {
            if (is_file($path.'/'.$file)) {
                require_once $path . '/' . $file;
                $routes = $api->getRoutes()['v1']->getRoutes(); // frontapi只有v1版本
                foreach ($routes as $route) {
                    $key = md5($route->methods()[0] . '|/' . $route->uri()); // 连接符中'/'是因为appkernel中获取的$request->getPathInfo()是带'/'
                    if (!isset($routeCache[$key])) {
                        app('redis')->set('routecache:' . $key, $file);
                        $routeCache[$key] = $file;
                    }
                }
            }
        }
        print_r($routeCache);
    }
}
