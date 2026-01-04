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
