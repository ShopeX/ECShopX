<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services;

use OrdersBundle\Entities\StatementDetails;

class StatementDetailsService
{
    /** @var \OrdersBundle\Repositories\StatementDetailsRepository */
    private $entityRepository;

    public function __construct()
    {
        // ModuleID: 76fe2a3d
        $this->entityRepository = app('registry')->getManager('default')->getRepository(StatementDetails::class);
    }


    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // ModuleID: 76fe2a3d
        return $this->entityRepository->$method(...$parameters);
    }
}
