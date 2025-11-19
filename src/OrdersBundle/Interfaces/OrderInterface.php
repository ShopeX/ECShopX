<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
