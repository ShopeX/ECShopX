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

namespace SupplierBundle\Services;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsCategoryService;
use SupplierBundle\Entities\SupplierItemsAttr;

class SupplierItemsAttrService
{
    /**
     * @var \SupplierBundle\Repositories\SupplierItemsAttrRepository
     */
    public $repository;

    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(SupplierItemsAttr::class);
    }

    //批量获取多个商品的关联属性信息
    public function getItemRelAttr($item_ids, $attribute_type = '')
    {
        $filter = [
            'item_id' => $item_ids,
            'attribute_type' => $attribute_type,
        ];
        $rs = $this->repository->getLists($filter);
        if (!$rs) {
            return [];
        }
        foreach ($rs as $k => $v) {
            if (!$v['attr_data']) continue;
            $attr_data = json_decode($v['attr_data'], true);
            if (is_array($attr_data[$v['attribute_type']])) {
                $v = array_merge($v, $attr_data[$v['attribute_type']]);
            } else {
                $v['attribute_value_id'] = $attr_data[$v['attribute_type']];
            }
            $v['image_url'] = $v['image_url'] ?? '';
            $v['custom_attribute_value'] = $v['custom_attribute_value'] ?? '';
            $rs[$k] = $v;
        }
        return $rs;
    }

    //批量获取多个商品的属性信息
    public function getAttrDataBatch($item_ids, $company_id, $attribute_type = '')
    {
        $filter = [
            'item_id' => $item_ids,
            'attribute_type' => $attribute_type,
        ];
        $rs = $this->repository->getLists($filter);
        if (!$rs) {
            return [];
        }
        $result = [];
        foreach ($rs as $v) {
            if (!$v['attr_data']) continue;
            $attr_data = json_decode($v['attr_data'], true);
            $result[$v['item_id']] = $attr_data[$v['attribute_type']];;
        }
        return $result;
    }

    //获取单个商品的属性
    public function getAttrData($item_id, $attribute_type)
    {
        $filter = [
            'item_id' => $item_id,
            'attribute_type' => $attribute_type,
        ];
        $rsAttr = $this->repository->getInfo($filter);
        if ($rsAttr && $rsAttr['attr_data']) {
            $attr_data = json_decode($rsAttr['attr_data'], true);
            return $attr_data[$attribute_type];
        }
        return [];
    }

    //获取单个商品的全部属性
    public function getAttrDataList($item_id, $attribute_type = [])
    {
        $res = [];
        $filter = [
            'item_id' => $item_id,
        ];
        if ($attribute_type) {
            $filter['attribute_type'] = $attribute_type;
        }
        $rsAttr = $this->repository->getLists($filter);
        if ($rsAttr) {
            foreach ($rsAttr as $v) {
                if ($v['attr_data']) {
                    $attr_data = json_decode($v['attr_data'], true);
                    if (is_array($attr_data[$v['attribute_type']])) {
                        $v = array_merge($v, $attr_data[$v['attribute_type']]);
                    }
                }
                $v['image_url'] = $v['image_url'] ?? '';
                $v['custom_attribute_value'] = $v['custom_attribute_value'] ?? '';
                $res[] = $v;
            }
        }
        return $res;
    }

    //标记要删除的数据
    public function setDelData($filter)
    {
        if ($this->repository->getInfo($filter)) {
            $this->repository->updateBy($filter, ['is_del' => 1]);
        }
    }

    //删掉多余的数据
    public function execDelData($filter)
    {
        $filter['is_del'] = 1;
        $this->repository->deleteBy($filter);
    }

    public function saveAttrData($filter, $attrData)
    {
        if (is_array($attrData)) {
            $attrData = json_encode($attrData, 256);
        }
        $rsAttr = $this->repository->getInfo($filter);
        if ($rsAttr) {
            $this->repository->updateOneBy(['id' => $rsAttr['id']], ['attr_data' => $attrData, 'is_del' => 0]);
        } else {
            $filter['attr_data'] = $attrData;
            $filter['is_del'] = 0;
            $rsAttr = $this->repository->create($filter);
        }
        return $rsAttr;
    }

    /**
     * 根据销售分类获取供应商商品 default_item_id 列表
     */
    public function getItemIdsByCatId($categoryId, $companyId, $supplierId = null)
    {
        $itemsCategoryService = new ItemsCategoryService();
        $categoryIds = $itemsCategoryService->getItemsCategoryIds($categoryId, $companyId);
        $categoryIds = array_values(array_unique(array_map('intval', (array)$categoryIds)));
        if (!$categoryIds) {
            return [];
        }

        $targetCategoryIds = array_fill_keys($categoryIds, true);
        $itemIds = [];
        $conn = app('registry')->getConnection('default');
        $lastId = 0;
        $batchSize = 500;

        while (true) {
            $qb = $conn->createQueryBuilder();
            $qb->select('sa.id, sa.item_id, sa.attr_data')
                ->from('supplier_items_attr', 'sa')
                ->innerJoin('sa', 'supplier_items', 'si', 'sa.item_id = si.item_id AND si.company_id = :company_id')
                ->where('sa.company_id = :company_id')
                ->andWhere('sa.attribute_type = :attribute_type')
                ->andWhere('sa.is_del = 0')
                ->andWhere('sa.id > :last_id')
                ->orderBy('sa.id', 'ASC')
                ->setMaxResults($batchSize)
                ->setParameter('company_id', $companyId)
                ->setParameter('attribute_type', 'category')
                ->setParameter('last_id', $lastId);

            if ($supplierId) {
                $qb->andWhere('si.supplier_id = :supplier_id')
                    ->setParameter('supplier_id', $supplierId);
            }

            $rows = $qb->execute()->fetchAll();
            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = (int)$row['id'];
                if (empty($row['attr_data'])) {
                    continue;
                }
                $attrData = json_decode($row['attr_data'], true);
                $saleCategoryIds = $attrData['category'] ?? [];
                if (!is_array($saleCategoryIds) || !$saleCategoryIds) {
                    continue;
                }
                foreach ($saleCategoryIds as $saleCategoryId) {
                    if (isset($targetCategoryIds[(int)$saleCategoryId])) {
                        $itemIds[] = (int)$row['item_id'];
                        break;
                    }
                }
            }
        }

        return array_values(array_unique($itemIds));
    }

    public function __call($method, $parameters)
    {
        return $this->repository->$method(...$parameters);
    }
}
