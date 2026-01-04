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

namespace YoushuBundle\Listeners;

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use YoushuBundle\Services\SrDataService;

class Distribution extends BaseListeners implements ShouldQueue
{
    /**
     * 处理店铺事件
     */
    public function handle($event)
    {
        $company_id = $event->entities['company_id'];
        $distributor_id = $event->entities['distributor_id'];
        $params = [
            'company_id' => $company_id,
            'object_id' => $distributor_id,
        ];

        $srdata_service = new SrDataService($company_id);
        $srdata_service->sync($params, 'store');

        return true;
    }

    /**
     * 注册监听器
     *
     * @param  \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        //创建店铺
        $events->listen(
            'DistributionBundle\Events\DistributionAddEvent',
            'YoushuBundle\Listeners\Distribution@handle'
        );

        //删除店铺
        $events->listen(
            'DistributionBundle\Events\DistributionEditEvent',
            'YoushuBundle\Listeners\Distribution@handle'
        );
    }
}
