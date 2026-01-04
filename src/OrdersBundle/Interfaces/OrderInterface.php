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

interface OrderInterface
{
    /**
     * 获取订单列表
     */
    public function getOrderList($filter, $offset = 0, $limit = -1, $orderBy = ['create_time' => 'DESC']);

    /**
     * 获取订单详情
     */
    public function getOrderInfo($companyId, $orderId, $checkaftersales, $from);

    /**
     * 发货
     */
    public function delivery($params);
}
