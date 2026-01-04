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

namespace PointsmallBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use PointsmallBundle\Entities\PointsmallItemRelAttributes;
use Dingo\Api\Exception\ResourceException;

class PointsmallItemRelAttributesRepository extends EntityRepository
{
    public $table = 'pointsmall_items_rel_attributes';

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new PointsmallItemRelAttributes();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateBy(array $filter, array $data)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getColumnNamesData($entityProp);
        }
        return $result;
    }

    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return true;
        }
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
        return true;
    }

    /**
     * 根据条件删除指定数据
     *
     * @param $filter 删除的条件
     */
    public function deleteBy($filter)
    {
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            return true;
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
    }

    /**
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
                      ->getUnitOfWork()
                      ->getEntityPersister($this->getEntityName())
                      ->count($criteria);

        return intval($total);
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = array())
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
                      ->getUnitOfWork()
                      ->getEntityPersister($this->getEntityName())
                      ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }

            if ($pageSize > 0) {
                $criteria->setFirstResult($pageSize * ($page - 1))
                         ->setMaxResults($pageSize);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["item_id"]) && $data["item_id"]) {
            $entity->setItemId($data["item_id"]);
        }
        if (isset($data["attribute_id"]) && $data["attribute_id"]) {
            $entity->setAttributeId($data["attribute_id"]);
        }
        if (isset($data["attribute_type"]) && $data["attribute_type"]) {
            $entity->setAttributeType($data["attribute_type"]);
        }
        if (isset($data["attribute_value_id"]) && $data["attribute_value_id"]) {
            $entity->setAttributeValueId($data["attribute_value_id"]);
        }
        if (isset($data["custom_attribute_value"]) && $data["custom_attribute_value"]) {
            $entity->setCustomAttributeValue($data["custom_attribute_value"]);
        }
        if (isset($data["attribute_sort"])) {
            $entity->setAttributeSort($data["attribute_sort"]);
        }
        //当前字段非必填
        if (isset($data["image_url"]) && $data["image_url"]) {
            $entity->setImageUrl($data["image_url"]);
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        return [
            'id' => $entity->getId(),
            'company_id' => $entity->getCompanyId(),
            'item_id' => $entity->getItemId(),
            'attribute_id' => $entity->getAttributeId(),
            'attribute_sort' => $entity->getAttributeSort(),
            'attribute_type' => $entity->getAttributeType(),
            'attribute_value_id' => $entity->getAttributeValueId(),
            'custom_attribute_value' => $entity->getCustomAttributeValue(),
            'image_url' => $entity->getImageUrl(),
        ];
    }

    public function getItemdsByAttrValIds($attributeValueIds, $attributeIds, $companyId = null)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('item_id')->from($this->table);

        $filter['attribute_value_id'] = $attributeValueIds;
        if ($companyId) {
            $filter['company_id'] = $companyId;
        }
        $qb = $this->__filter($filter, $qb);

        $qb->groupBy('item_id');
        $qb->having('count(item_id)>=' . count($attributeIds));

        $lists = $qb->execute()->fetchAll();

        $itemIds = [];
        if ($lists) {
            $itemIds = array_column($lists, 'item_id');
        }
        return $itemIds;
    }

    /**
     * 根据商品ID，获取属性数据
     */
    public function getItemRelAttributeBy($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('attribute_id,attribute_value_id')->from($this->table);

        $qb = $this->__filter($filter, $qb);
        $qb->groupBy('attribute_value_id');

        $lists = $qb->execute()->fetchAll();
        return $lists;
    }

    private function __filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%' . $value . '%';
                }
                $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }
}
