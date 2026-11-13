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

namespace EspierBundle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class Job implements ShouldQueue
{
    // ShopEx EcShopX Core Module
    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "queueOn" and "delay" queue helper methods.
    |
    */

    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __destruct()
    {
        // 析构阶段容器/facade 可能已被销毁（例如 PHPUnit 的 clearResolvedInstances()
        // 会释放对象并触发本析构），此时再调 config()/app() 会抛
        // BindingResolutionException: Target class [config] does not exist。
        // 析构函数不能抛异常，这里做容错处理。
        try {
            $app = app();
            if (!$app instanceof \Illuminate\Contracts\Container\Container || !$app->bound('config') || !$app->bound('registry')) {
                return;
            }

            $managers = config('doctrine.managers');
            if (!is_array($managers)) {
                return;
            }

            foreach ($managers as $mangerName => $v) {
                $app['registry']->getManager($mangerName)->getConfiguration()->getResultCacheImpl()->flushAll();
                $app['registry']->getManager($mangerName)->getConfiguration()->getQueryCacheImpl()->flushAll();
                $app['registry']->getManager($mangerName)->clear();
            }
        } catch (\Throwable $e) {
            // 忽略：析构期间的异常不应影响主流程
        }
    }
}
