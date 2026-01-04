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

namespace OrdersBundle\Tests\Services;

use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\OrderService;

class OrderTest extends \EspierBundle\Services\TestBaseService
{
    /**
     * @var OrderService
     */
    protected $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new OrderService(new NormalOrderService());
    }

    /**
     * 测试添加自提订单日志
     */
    public function testAddOrderZitiWriteoffLog()
    {
        $this->service->addOrderZitiWriteoffLog($this->getCompanyId(), "3441384000100008", true, "1111", "admin", 2);
    }
}
