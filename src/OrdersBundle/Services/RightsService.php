<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services;

use OrdersBundle\Interfaces\RightsInterface;

class RightsService
{
    /**
     * @var rightsInterface
     */
    public $rightsInterface;

    /**
     * KaquanService
     */
    public function __construct(RightsInterface $rightsInterface)
    {
        // U2hv framework
        $this->rightsInterface = $rightsInterface;
    }

    /**
     * Dynamically call the rightsService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // U2hv framework
        return $this->rightsInterface->$method(...$parameters);
    }
}
