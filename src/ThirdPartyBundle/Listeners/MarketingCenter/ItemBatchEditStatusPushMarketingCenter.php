<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Listeners\MarketingCenter;

use GoodsBundle\Entities\Items;
use GoodsBundle\Events\ItemBatchEditStatusEvent;

use ThirdPartyBundle\Services\MarketingCenter\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;

class ItemBatchEditStatusPushMarketingCenter
{
    use GetOrderServiceTrait;
    /**
     * Handle the event.
     *
     * @param ItemBatchEditStatusEvent $event
     * @return void
     */
    public function handle(ItemBatchEditStatusEvent $event)
    {
        $company_id = $event->entities['company_id'];
        $goods_id = $event->entities['goods_id'];
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $itemInfo = $itemsRepository->list(['company_id' => $company_id, 'goods_id' => $goods_id]);

        foreach ($itemInfo['list'] as $key => $value) {
            $input['item_bn'] = $value['item_bn'];
            $input['approve_status'] = $value['approve_status'];

            $params[$key] = $input;
        }

        $request = new Request();
        $request->call($company_id, 'basics.item.proccess', $params);
    }
}
