<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Interfaces;

/**
 * Class 交易单处理接口
 */
interface Trade
{
    /**
     * 生成支付单ID
     */
    public function genTradeId($userId);

    /**
     * 创建支付单
     */
    public function create(array $data);

    /**
     * 更新支付状态
     */
    public function updateStatus($tradeId, $status = null, $options = array());

    /**
     * 支付完成后处理的事件
     */
    public function finishEvents($eventsParams);
}
