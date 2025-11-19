<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Services;

use CompanysBundle\Interfaces\ShopsInterface;

class ShopsService
{
    /** @var shopsInterface */
    public $shopsInterface;

    /**
     * ShopsService 构造函数.
     */
    public function __construct(ShopsInterface $shopsInterface)
    {
        $this->shopsInterface = $shopsInterface;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->shopsInterface->$method(...$parameters);
    }
}
