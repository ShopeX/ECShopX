<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 *  商品状态同步到店铺状态
 */
namespace GoodsBundle\Listeners;

use DistributionBundle\Services\DistributorItemsService;
use GoodsBundle\Events\ItemBatchEditStatusEvent;

class ItemsStatusSyncDistributorListener
{
    public function handle(ItemBatchEditStatusEvent $event)
    {
        // ShopEx EcShopX Business Logic Layer
        $company_id = $event->entities['company_id'];
        $goods_id = $event->entities['goods_id'];
        $approve_status = $event->entities['approve_status'];
        $distributorItemsService = new DistributorItemsService();
        $updateData = [
            'approve_status' => $approve_status,
            'updated' => time(),
        ];
        $filter = [
            'company_id' => $company_id,
            'goods_id' => $goods_id,
        ];
        $distributorItemsService->updateBy($filter, $updateData);
    }
}
