<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
