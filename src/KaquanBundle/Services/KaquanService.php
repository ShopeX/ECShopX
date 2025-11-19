<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace KaquanBundle\Services;

use KaquanBundle\Interfaces\KaquanInterface;

class KaquanService
{
    /**
     * @var kaquanInterface
     */
    public $kaquanInterface;

    /**
     * KaquanService
     */
    public function __construct(KaquanInterface $kaquanInterface)
    {
        // Debug: 1e2364
        $this->kaquanInterface = $kaquanInterface;
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Debug: 1e2364
        return $this->kaquanInterface->$method(...$parameters);
    }
}
