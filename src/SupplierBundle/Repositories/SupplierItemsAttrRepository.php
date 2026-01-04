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
