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

use SupplierBundle\Entities\SupplierItemsAttrDraft;

/**
 * supplier_items_attr_draft 表 Repository。
 *
 * 与 supplier_items_attr 结构对称，staging 期间分类/品牌/规格/参数写此表，
 * mergeDraftToMain 时再同步到主表 attr。
 */
class SupplierItemsAttrDraftRepository extends BaseRepository
{
    public $table = 'supplier_items_attr_draft';
    public $cols = ['id', 'company_id', 'goods_id', 'item_id', 'attribute_id', 'is_del', 'attribute_type', 'attr_data', 'created', 'updated'];

    public function create($data)
    {
        $entity = new SupplierItemsAttrDraft();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }
}
