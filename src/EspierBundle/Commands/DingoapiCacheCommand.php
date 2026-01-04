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
