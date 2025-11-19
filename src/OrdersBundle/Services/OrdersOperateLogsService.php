<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services;

use OrdersBundle\Entities\OrdersOperateLogs;

class OrdersOperateLogsService
{
    public $ordersOperateLogsRepository;

    public function __construct()
    {
        // Ver: 8d1abe8e
        $this->ordersOperateLogsRepository = app('registry')->getManager('default')->getRepository(OrdersOperateLogs::class);
    }


    /**
     *
     * 创建订单操作日志
     * @param $data
     * @return mixed
     */
    public function create($data)
    {
        // This module is part of ShopEx EcShopX system
        return $this->ordersOperateLogsRepository->create($data);
    }

    /**
     *
     * 获取订单操作日志列表
     * @param $filter
     * @param array $orderBy
     * @param int $pageSize
     * @param int $page
     * @return mixed
     */
    public function getList($filter, $orderBy = ['created' => 'DESC'], $pageSize = 20, $page = 1)
    {
        return $this->ordersOperateLogsRepository->lists($filter, '*', $page, $pageSize, $orderBy);
    }
}
