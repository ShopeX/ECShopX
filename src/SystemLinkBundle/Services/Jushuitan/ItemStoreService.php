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

namespace SystemLinkBundle\Services\Jushuitan;

use Dingo\Api\Exception\ResourceException;

use GoodsBundle\Services\ItemsService;

use Exception;

class ItemStoreService
{

    public function __construct()
    {

    }

    /**
     * 生成发给聚水潭商品结构体
     *
     */
    public function getItemBn($companyId, $itemIds)
    {
        // Ver: 1e2364-fe10
        $filter['company_id'] = $companyId;
        $filter['item_id'] = $itemIds;
        $itemsService = new ItemsService();
        $itemsList = $itemsService->getItemsList($filter, 1, 20);
        if ($itemsList['total_count'] == 0) {
            throw new Exception("获取商品信息失败");
        }

        $itemBn = array_column($itemsList['list'], 'item_bn');

        $itemStruct = [
            'wms_co_id' => 0,
            'page_index' => 1,
            'page_size' => 20,
            'sku_ids' => implode(',', $itemBn),
        ];

        return $itemStruct;
    }

    public function saveItemStore($companyId, $data) {
        $itemsService = new ItemsService();
        $itemStoreService = new ItemStoreService();

        $filter['company_id'] = $companyId;
        $filter['item_bn'] = array_column($data, 'sku_id');
        $itemsList = $itemsService->getItemsList($filter, 1, 20);
        $itemsBn = array_column($itemsList['list'], 'item_id', 'item_bn');
        $conn = app('registry')->getConnection('default');
        $storeParams = [];
        foreach ($data as $val) {
            if ($itemId = ($itemsBn[$val['sku_id']] ?? 0)) {
                $criteria = $conn->createQueryBuilder();
                $criteria->select('sum(i.num)')
                    ->from('orders_normal_orders_items', 'i')
                    ->leftJoin('i', 'orders_normal_orders', 'o', 'i.order_id = o.order_id')
                    ->andWhere($criteria->expr()->eq('i.item_id', $itemId))
                    ->andWhere($criteria->expr()->andX(
                        $criteria->expr()->eq('o.order_status', $criteria->expr()->literal('NOTPAY')),
                        $criteria->expr()->gt('o.auto_cancel_time', time())
                    ));
                $freez = $criteria->execute()->fetchColumn();
                
                $store = $val['qty'] + $val['virtual_qty'] + $val['purchase_qty'] + $val['return_qty'] + $val['in_qty'] - $val['order_lock'] - $freez;
                $store = $store > 0 ? $store : 0;

                $storeParams[] = [
                    'item_id' => $itemId,
                    'store' => $store
                ];
            }
        }

        $itemsService->updateItemsStore($companyId, $storeParams);
    }
}
