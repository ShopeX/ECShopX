<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Traits;

use OrdersBundle\Services\Cart\DistributorCartObject;
use OrdersBundle\Services\CartDataService;
use Dingo\Api\Exception\ResourceException;

trait GetCartTypeServiceTrait
{
    public function getCartTypeService($shopType)
    {
        $shopType = strtolower($shopType);
        switch ($shopType) {
            case 'distributor':
                $cartTypeService = new CartDataService(new DistributorCartObject());
                break;
            default:
                throw new ResourceException("无此购车类型");
        }

        return $cartTypeService;
    }
}
