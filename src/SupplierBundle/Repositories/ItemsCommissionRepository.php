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
