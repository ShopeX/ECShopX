<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SupplierBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Dingo\Api\Exception\ResourceException;
use SupplierBundle\Entities\SupplierItemsAttr;

class SupplierItemsAttrRepository extends BaseRepository
{
    public $table = "supplier_items_attr";
    public $cols = ['id', 'company_id', 'item_id', 'attribute_id', 'is_del', 'attribute_type', 'attr_data', 'created', 'updated'];

    /**
     * 新增
     *
     * @param array $data
     * @return array
     */
    public function create($data)
    {
        $entity = new SupplierItemsAttr();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

}
