<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Repositories;

use Doctrine\ORM\EntityRepository;

class OperatorBindShopRepository extends EntityRepository
{
    public function getAllOperatorBindShop()
    {
        return app('registry')->getConnection('default')->fetchAssoc("select * from operator_bind_shop");
    }
}
