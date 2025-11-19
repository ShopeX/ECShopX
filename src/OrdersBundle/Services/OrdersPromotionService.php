<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services;

use OrdersBundle\Entities\OrderPromotions;

class OrdersPromotionService
{
    /** @var entityRepository */
    public $entityRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(OrderPromotions::class);
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
        return $this->entityRepository->$method(...$parameters);
    }
}
