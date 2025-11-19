<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DistributionBundle\Tests\Services;

use DistributionBundle\Services\DistributorItemsService;

class DistributorItemsTest extends \EspierBundle\Services\TestBaseService
{
    protected $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new DistributorItemsService();
    }

    public function testGetDistributorRelItemList()
    {
        $data = $this->service->getDistributorRelItemList([
            "company_id" => $this->getCompanyId(),
            "distributor_id" => 145,
            "is_default" => true,
            "item_type" => "normal"
        ]);
        dd($data);
    }
}
