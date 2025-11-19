<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services;

use OrdersBundle\Entities\CompanyRelShansong;
use OrdersBundle\Services\LocalDeliveryService;

class CompanyRelShansongService
{
    private $companyRelShansongReposity;

    public function __construct()
    {
        $this->companyRelShansongReposity = app('registry')->getManager('default')->getRepository(CompanyRelShansong::class);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->companyRelShansongReposity->$method(...$parameters);
    }
}
