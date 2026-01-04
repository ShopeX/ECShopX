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
