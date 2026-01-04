<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
