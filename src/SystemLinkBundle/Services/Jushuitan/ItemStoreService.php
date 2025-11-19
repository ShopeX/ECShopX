<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
