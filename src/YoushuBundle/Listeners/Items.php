<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Listeners;

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use YoushuBundle\Services\SrDataService;

class Items extends BaseListeners implements ShouldQueue
{
    /**
     * 处理商品事件
     */
    public function handle($event)
    {
        $company_id = $event->entities['company_id'];
        $item_id = $event->entities['item_id'];

        $params = [
            'company_id' => $company_id,
            'object_id' => $item_id,
        ];

        $srdata_service = new SrDataService($company_id);
        $srdata_service->sync($params, 'items');

        return true;
    }

    /**
     * 注册监听器
     *
     * @param  \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        //创建商品
        $events->listen(
            'GoodsBundle\Events\ItemAddEvent',
            'YoushuBundle\Listeners\Items@handle'
        );

        //更新商品
        $events->listen(
            'GoodsBundle\Events\ItemEditEvent',
            'YoushuBundle\Listeners\Items@handle'
        );

        //删除商品
        $events->listen(
            'GoodsBundle\Events\ItemDeleteEvent',
            'YoushuBundle\Listeners\Items@handle'
        );
    }
}
