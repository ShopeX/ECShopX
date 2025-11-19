<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
