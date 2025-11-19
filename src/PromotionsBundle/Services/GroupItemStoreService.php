<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Services;

use PromotionsBundle\Entities\PromotionGroupsActivity;

// 商品拼团库存处理
class GroupItemStoreService
{
    /**
     * 保存商品拼团库存
     */
    public function saveGroupItemStore($groupId, $store)
    {
        return app('redis')->set('group_item_store:' . $groupId, $store);
    }

    /**
     * 获取商品拼团库存
     */
    public function getGroupItemStore($groupId)
    {
        return app('redis')->get('group_item_store:' . $groupId);
    }

    /**
     * 扣减商品拼团库存
     */
    public function minusGroupItemStore($groupId, $num)
    {
        $store = app('redis')->decrby('group_item_store:' . $groupId, $num);
        if ($store < 0) {
            app('redis')->incrby('group_item_store:' . $groupId, $num);
            app('log')->debug('扣减拼团库存结束：商品ID ' . $groupId . ',拼团库存数量为 ' . app('redis')->get('group_item_store:' . $groupId) . ',失败恢复');
            return false;
        } else {
            app('log')->debug('扣减拼团库存结束：商品ID ' . $groupId . ',拼团库存数量为 ' . app('redis')->get('group_item_store:' . $groupId) . ',扣减成功');
            $promotionGroupsActivityRepository = app('registry')->getManager('default')->getRepository(PromotionGroupsActivity::class);
            $promotionGroupsActivityRepository->updateStore($groupId, $store);
            return true;
        }
    }


    /**
     * 拼团失败恢复库存
     * @param $groupId
     * @param $num
     * @return bool
     */
    public function addGroupItemStore($groupId, $num)
    {
        $store = app('redis')->incrby('group_item_store:' . $groupId, $num);
        $promotionGroupsActivityRepository = app('registry')->getManager('default')->getRepository(PromotionGroupsActivity::class);
        $promotionGroupsActivityRepository->updateStore($groupId, $store);
        return true;
    }
}
