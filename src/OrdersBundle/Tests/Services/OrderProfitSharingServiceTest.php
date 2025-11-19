<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Tests\Services;

use OrdersBundle\Services\OrderProfitSharingService;

class OrderProfitSharingServiceTest extends \EspierBundle\Services\TestBaseService
{
    /**
     * @var OrderProfitSharingService
     */
    protected $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new OrderProfitSharingService();
    }

    /**
     * 分账测试
     */
    public function testLists()
    {
        $this->service->lists();
    }
}
