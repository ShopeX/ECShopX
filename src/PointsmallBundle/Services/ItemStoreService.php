<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
