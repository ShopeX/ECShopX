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

namespace GoodsBundle\Listeners;

use DistributionBundle\Entities\DistributorItems;
use GoodsBundle\Entities\Items;
use GoodsBundle\Events\ItemBatchEditStatusEvent;
use ShuyunOpenPlatformBundle\Listeners\ItemsProductSyncToShuyunOpenPlatformDispatch;

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
                if (is_array($res) && $res !== []) {
                    $relList = $distributorItems->lists(
                        ['company_id' => $company_id, 'item_id' => $itemIds],
                        ['item_id' => 'ASC'],
                        -1,
                        1,
                    );
                    $seen = [];
                    foreach (($relList['list'] ?? []) as $rel) {
                        $did = (int) ($rel['distributor_id'] ?? 0);
                        $def = (int) ($rel['default_item_id'] ?? 0);
                        if ($did < 1 || $def < 1) {
                            continue;
                        }
                        $k = $did.':'.$def;
                        if (isset($seen[$k])) {
                            continue;
                        }
                        $seen[$k] = true;
                        ItemsProductSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows($company_id, $did, $def);
                    }
                }
            }

        }catch (\Exception $e) 
        {
            
        }

    }
    
}
