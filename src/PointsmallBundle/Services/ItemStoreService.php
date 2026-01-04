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

namespace PointsmallBundle\Services;

use PointsmallBundle\Entities\PointsmallItems as Items;
use Dingo\Api\Exception\ResourceException;

// 商品库存处理
class ItemStoreService
{
    /**
     * 保存商品库存
     */
    public function saveItemStore($itemId, $store)
    {
        $key = $itemId;
        return app('redis')->set('pointsmall_item_store:' . $key, $store);
    }

    /**
     * 保存商品库存
     */
    public function deleteItemStore($itemId)
    {
        $key = $itemId;
        return app('redis')->del('pointsmall_item_store:' . $key);
    }

    /**
     * 批量处理库存
     */
    public function batchMinusItemStore($data)
    {
        // 456353686f7058
        foreach ($data as $row) {
            $items[] = implode(':', $row);
        }
        $itemsString = implode('/', $items) . '/';

        $redisLuaScript = new \EspierBundle\RedisLuaScript\PointsmallItemsStoreMinus();
        $result = app('redis')->eval($redisLuaScript->getScript(), 1, $itemsString);

        if (!is_array($result)) {
            throw new ResourceException($result);
        }

        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        foreach ($result as $value) {
            $itemId = $value[1];
            $store = $value[2];
            $itemkeyarr = explode('_', $value[3]);
            $itemsRepository->updateStore($itemId, $store);
        }
        return true;
    }

    /**
     * 扣减商品库存
     */
    public function minusItemStore($itemId, $num, $isTotalStore = true)
    {
        $key = $itemId;
        $msg = '商品ID ' . $itemId;

        app('log')->debug('积分商城扣减库存开始：' . $msg . ',扣减数量 ' . $num);
        $store = app('redis')->decrby('pointsmall_item_store:' . $key, $num);
        if ($store < 0) {
            app('redis')->incrby('pointsmall_item_store:' . $key, $num);
            app('log')->debug('积分商城扣减库存结束：' . $msg . ',库存数量为 ' . app('redis')->get('pointsmall_item_store:' . $key) . ',失败恢复');
            return false;
        } else {
            app('log')->debug('积分商城扣减库存结束：' . $msg . ',库存数量为 ' . app('redis')->get('pointsmall_item_store:' . $key) . ',扣减成功');

            if ($isTotalStore) {
                $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
                $itemsRepository->updateStore($itemId, $store);
            }

            return true;
        }
    }

    //设置商品库存预警
    public function setWarningStore($companyId, $store)
    {
        // 456353686f7058
        return app('redis')->set('item_warning_store:' . $companyId, $store);
    }

    //获取库存预警
    public function getWarningStore($companyId)
    {
        $store = app('redis')->get('item_warning_store:' . $companyId);
        return $store ?: 5;
    }
}
