<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace HfPayBundle\Tests\Services;

use EspierBundle\Services\TestBaseService;
use HfPayBundle\Services\HfpayCashRecordService;

class HfpayCashRecordServiceTest extends TestBaseService
{
    /**
     * @var HfpayCashRecordService
     */
    protected $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new HfpayCashRecordService();
    }

    public function testTotal()
    {
        $result = $this->service->total([]);

        $this->assertArrayHasKey('count', $result);
    }

    public function testLists()
    {
        $filter = [
            'company_id' => $this->getCompanyId(),
            'distributor_id' => 2,
        ];
        $this->service->lists($filter);
    }

    public function testWithdraw()
    {
        $filter = [
            'company_id' => $this->getCompanyId(),
            'distributor_id' => 4,
            'withdrawal_amount' => 100,
        ];

        // $result = $this->service->withdraw($filter);
    }
}
