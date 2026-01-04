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
