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

namespace GoodsBundle\Services;

use GoodsBundle\Entities\Items;
use Dingo\Api\Exception\ResourceException;

use DistributionBundle\Entities\DistributorItems;
use GoodsBundle\Events\ItemStoreUpdateEvent;
use SupplierBundle\Services\SupplierItemsService;

// 商品库存处理
class ItemStoreService
{
    /**
     * 保存商品库存
     */
    public function saveItemStore($itemId, $store, $distributorId = 0)
    {
        if ($distributorId) {
            $key = $distributorId . '_' . $itemId;
        } else {
            $key = $itemId;
        }

        event(new ItemStoreUpdateEvent($itemId, intval($store), $distributorId));
        return app('redis')->set('item_store:' . $key, $store);
    }

    /**
     * 保存商品库存
     */
    public function deleteItemStore($itemId, $distributorId = 0)
    {
        if ($distributorId) {
            $key = $distributorId . '_' . $itemId;
        } else {
            $key = $itemId;
        }
        return app('redis')->del('item_store:' . $key);
    }

    /**
     * 批量处理库存
     */
    public function batchMinusItemStore($data)
    {
        foreach ($data as $row) {
            $items[] = implode(':', $row);
        }
        $itemsString = implode('/', $items) . '/';

        $redisLuaScript = new \EspierBundle\RedisLuaScript\ItemsStoreMinus();
        $result = app('redis')->eval($redisLuaScript->getScript(), 1, $itemsString);

        if (!is_array($result)) {
            throw new ResourceException($result);
        }

        /** @var \GoodsBundle\Repositories\ItemsRepository $itemsRepository */
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $distributorItemsRepository = app('registry')->getManager('default')->getRepository(DistributorItems::class);
        $supplierItemsService = new SupplierItemsService();
        foreach ($result as $value) {
            $itemId = $value[1];
            $store = $value[2];
            $itemkeyarr = explode('_', $value[3]);
            $distributorId = $itemkeyarr[0];
            if ((count($itemkeyarr) == 2) && ($distributorId > 0)) {
                $filter = [
                    'item_id' => $itemId,
                    'distributor_id' => $distributorId
                ];
                $distributorItemsRepository->updateOneBy($filter, ['store' => $store]);
            } else {
                $res = $itemsRepository->updateStore($itemId, $store);
                //更新供应商商品库存
                if ($res['supplier_item_id']) {
                    $supplierItemsService->repository->updateOneBy(['item_id' => $res['supplier_item_id']], ['store' => $store]);
                }
            }
        }
        return true;
    }

    /**
     * 扣减商品库存
     */
    public function minusItemStore($itemId, $num, $distributorId = 0, $isTotalStore = true)
    {
        if ($distributorId && !$isTotalStore) {
            $key = $distributorId . '_' . $itemId;
            $msg = '经销商ID ' . $distributorId . ',商品ID ' . $itemId;
        } else {
            $key = $itemId;
            $msg = '商品ID ' . $itemId;
        }

        app('log')->debug('扣减库存开始：' . $msg . ',扣减数量 ' . $num);
        $store = app('redis')->decrby('item_store:' . $key, $num);
        if ($store < 0) {
            app('redis')->incrby('item_store:' . $key, $num);
            app('log')->debug('扣减库存结束：' . $msg . ',库存数量为 ' . app('redis')->get('item_store:' . $key) . ',失败恢复');
            return false;
        } else {
            app('log')->debug('扣减库存结束：' . $msg . ',库存数量为 ' . app('redis')->get('item_store:' . $key) . ',扣减成功');

            if ($distributorId && !$isTotalStore) {
                $itemsRepository = app('registry')->getManager('default')->getRepository(DistributorItems::class);
                $filter['distributor_id'] = $distributorId;
                $filter['item_id'] = $itemId;
                $data['store'] = $store;
                $itemsRepository->updateOneBy($filter, $data);
            } else {
                $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
                $itemsRepository->updateStore($itemId, $store);
            }

            return true;
        }
    }

    //设置商品库存预警
    public function setWarningStore($companyId, $store, $distributorId = 0)
    {
        if ($distributorId) {
            return app('redis')->set('item_warning_store:' . $companyId . $distributorId, $store);
        } else {
            return app('redis')->set('item_warning_store:' . $companyId, $store);
        }
    }

    //获取库存预警
    public function getWarningStore($companyId, $distributorId = 0)
    {
        if ($distributorId) {
            $store = app('redis')->get('item_warning_store:' . $companyId . $distributorId);
        } else {
            $store = app('redis')->get('item_warning_store:' . $companyId);
        }
        return $store ?: 5;
    }
}
