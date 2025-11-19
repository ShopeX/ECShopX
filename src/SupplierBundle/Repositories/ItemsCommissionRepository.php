<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SupplierBundle\Repositories;

use Dingo\Api\Exception\ResourceException;
use SupplierBundle\Entities\SupplierItemsCommission;

class ItemsCommissionRepository extends BaseRepository
{
    public $table = "supplier_items_commission";
    public $cols = ['id', 'company_id', 'item_id', 'goods_id', 'commission_ratio', 'supplier_id', 'add_time', 'modify_time'];

    /**
     * 新增
     *
     * @param array $data
     * @return array
     */
    public function create($data)
    {
        $entity = new SupplierItemsCommission();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }
}
