<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
