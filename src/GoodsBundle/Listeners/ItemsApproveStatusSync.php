<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Listeners;

use DistributionBundle\Entities\DistributorItems;
use GoodsBundle\Entities\Items;
use GoodsBundle\Events\ItemBatchEditStatusEvent;

use function Amp\call;

class ItemsApproveStatusSync 
{
    public static function handle(ItemBatchEditStatusEvent $event)
    {
        try {
            $company_id = $event->entities['company_id'];
            $goods_id = $event->entities['goods_id'];
            $approve_status = $event->entities['approve_status'];
            $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
            $distributorItems = app('registry')->getManager('default')->getRepository(DistributorItems::class);
            $itemInfo = $itemsRepository->list(['company_id' => $company_id, 'goods_id' => $goods_id]);
            if (!empty($itemInfo['list'])) {
                $itemIds = array_column($itemInfo['list'], 'item_id');
                $updateData = ['updated' => time() ];
                if ($approve_status == 'onsale') {
                    $updateData['is_can_sale'] = true;
                }elseif ($approve_status == 'instock') {
                    $updateData['is_can_sale'] = false;
                }
                $filter = [
                    'company_id' => $company_id,
                    'item_id' => $itemIds
                ];
                $res = $distributorItems->updateBy($filter, $updateData);
            }

        }catch (\Exception $e) 
        {
            
        }

    }
    
}
