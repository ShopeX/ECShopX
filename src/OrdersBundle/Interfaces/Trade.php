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
