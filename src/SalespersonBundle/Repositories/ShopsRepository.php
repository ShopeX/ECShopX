<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class ShopsRepository extends EntityRepository
{
    public function getAllShops()
    {
        return app('registry')->getConnection('default')->fetchAssoc("select * from shops");
    }
}
