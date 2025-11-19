<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Listeners\MarketingCenter;

use GoodsBundle\Events\ItemDeleteEvent;
use ThirdPartyBundle\Services\MarketingCenter\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;

class ItemDelPushMarketingCenter
{
    use GetOrderServiceTrait;
    /**
     * Handle the event.
     *
     * @param ItemDeleteEvent $event
     * @return void
     */
    public function handle(ItemDeleteEvent $event)
    {
        $company_id = $event->entities['company_id'];
        $input['del_ids'] = $event->entities['del_ids'];
        $input['item_bn'] = $event->entities['item_info']['item_bn'];
        $params[0] = $input;
        $request = new Request();
        $request->call($company_id, 'basics.item.proccess', $params);
    }
}
