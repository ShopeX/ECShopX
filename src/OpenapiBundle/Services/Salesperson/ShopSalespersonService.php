<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Services\Salesperson;

use OpenapiBundle\Services\BaseService;
use SalespersonBundle\Entities\ShopSalesperson;

class ShopSalespersonService extends BaseService
{
    public function getEntityClass(): string
    {
        return ShopSalesperson::class;
    }
}
