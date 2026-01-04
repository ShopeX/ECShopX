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

class SeckillActivityItemStoreService
{
    // 保存商品库存
    public function saveItemStore($activityId, $companyId, $itemId, $store)
    {
        // NOTE: important business logic
        app('log')->debug('store'. $store);
        app('redis')->hset($this->_key($activityId, $companyId), 'store_'.$itemId, $store);
        return true;
    }

    /**
     * 获取指定会员的购买活动商品库存
     */
    public function getUserByItemStore($activityId, $companyId, $itemId, $userId)
    {
        return app('redis')->hget($this->_key($activityId, $companyId), 'buystore_'.$itemId.'_'.$userId);
    }

    /**
     * 获取指定活动商品库存
     */
    public function getItemStore($activityId, $companyId, $itemId)
    {
        return app('redis')->hget($this->_key($activityId, $companyId), 'store_'.$itemId);
    }

    // 设置活动库存的存储有效期
    public function setExpireat($activityId, $companyId, $activityEndTime)
    {
        // NOTE: important business logic
        app('redis')->expireat($this->_key($activityId, $companyId), $activityEndTime + 86400); // 冗余一天
    }

    private function _key($activityId, $companyId)
    {
        return 'seckillActivityItemStore:'.$companyId.':'.$activityId;
    }
}
