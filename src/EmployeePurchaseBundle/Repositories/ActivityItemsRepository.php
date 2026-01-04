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

namespace EmployeePurchaseBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use EmployeePurchaseBundle\Entities\ActivityItems;
use Dingo\Api\Exception\ResourceException;

class ActivityItemsRepository extends EntityRepository
{
    public $table = 'employee_purchase_activity_items';
    public $cols = ['activity_id', 'item_id', 'goods_id', 'company_id', 'activity_price', 'activity_store', 'limit_fee', 'limit_num', 'sort', 'created', 'updated'];

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new ActivityItems();
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
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $qb = $qb->set($key, $qb->expr()->literal($val));
        }

        $qb = $this->_filter($filter, $qb);

        return $qb->execute();
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

    private function setColumnNamesData($entity, $params)
    {
        foreach ($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = "set". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
                $entity->$fun($params[$col]);
            }
        }
        return $entity;
    }

    private function getColumnNamesData($entity, $cols = [], $ignore = [])
    {
        if (!$cols) {
            $cols = $this->cols;
        }

        $values = [];
        foreach ($cols as $col) {
            if ($ignore && in_array($col, $ignore)) {
                continue;
            }
            $fun = "get". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
            $values[$col] = $entity->$fun();
        }
        return $values;
    }

    /**
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%'.$value.'%';
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

    /**
     * 根据条件获取列表数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result['total_count'] = $this->count($filter);
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
            $qb = $this->_filter($filter, $qb);
            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $qb->addOrderBy($filed, $val);
                }
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)
                  ->setMaxResults($pageSize);
            }
            $lists = $qb->execute()->fetchAll();
        }
        $result['list'] = $lists ?? [];
        return $result;
    }

    /**
     * 根据条件获取列表数据
     *
     * @param $filter 更新的条件
     */
    public function getLists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->_filter($filter, $qb);
        if ($orderBy) {
            foreach ($orderBy as $filed => $val) {
                $qb->addOrderBy($filed, $val);
            }
        }
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);
        }
        return $qb->execute()->fetchAll();
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
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
             ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    /**
     * 批量插入
     * @param array $data
     * @return false
     */
    public function batchInsert(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder();

        $columns = array();
        foreach ($data[0] as $columnName => $value) {
            $columns[] = $columnName;
        }
        $columns[] = 'created';

        $sql = 'INSERT IGNORE INTO '.$this->table. ' (' . implode(', ', $columns) . ') VALUES ';

        $insertValue = [];
        foreach($data as $value) {
            $value[] = time();
            foreach($value as &$v) {
                $v = $qb->expr()->literal($v);
            }
            $insertValue[] = '(' . implode(', ', $value) . ')';
        }

        $sql .= implode(',',$insertValue);
        return $conn->executeUpdate($sql);
    }

    public function getActivityItemsList($companyId, $activityId, $goodsId, $itemSpec = false, $isDefault = false, $orderBy = ['item_id' => 'desc'])
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('ai.*,it.item_name,it.item_bn,it.nospec,it.store,it.price,it.pics,it.default_item_id,it.is_medicine,it.is_prescription')
            ->from('employee_purchase_activity_items', 'ai')
            ->leftJoin('ai', 'items', 'it', 'ai.item_id = it.item_id')
            ->andWhere($qb->expr()->eq('ai.company_id', $companyId))
            ->andWhere($qb->expr()->in('ai.activity_id', $activityId))
            ->andWhere($qb->expr()->in('ai.goods_id', $goodsId))
            ->andWhere($qb->expr()->isNotNull('it.item_id'));
            // ->addOrderBy('ai.goods_id', 'DESC')
            // ->addOrderBy('ai.item_id', 'ASC');
        foreach ($orderBy as $key => $val) {
            if ($key == 'sales') {
                $qb->addOrderBy('it.'.$key, $val);
            } else {
                $qb->addOrderBy('ai.'.$key, $val);
            }
        }
        if ($isDefault) {
            $qb->groupBy('ai.goods_id');
        }
        $itemList = $qb->execute()->fetchAll();

        if ($itemSpec && $itemList) {
            $qb2 = $conn->createQueryBuilder();
            $qb2 = $qb2->select('r.item_id,v.attribuattribute_valuete_name,r.custom_attribute_value')
            ->from('items_rel_attributes', 'r')
            ->leftJoin('r', 'items_attribute_values', 'v', 'r.attribute_value_id = v.attribute_value_id')
            ->andWhere($qb2->expr()->in('r.item_id', array_column($itemList, 'item_id')))
            ->andWhere($qb2->expr()->eq('r.attribute_type', $qb2->expr()->literal('item_spec')))
            ->addOrderBy('r.item_id', 'DESC')
            ->addOrderBy('r.attribute_sort', 'DESC');
            $attrList = $qb2->execute()->fetchAll();
            $itemsSpec = [];
            foreach ($attrList as $attr) {
                if (isset($attr['custom_attribute_value'])) {
                    $itemsSpec[$attr['item_id']][] = $attr['custom_attribute_value'];
                } else {
                    $itemsSpec[$attr['item_id']][] = $attr['attribuattribute_valuete_name'];
                }
            }
        }

        $result = [];
        foreach ($itemList as $item) {
            $item['pics'] = json_decode($item['pics'], true);

            if (($item['nospec'] === false || $item['nospec'] === 'false' || $item['nospec'] === 0 || $item['nospec'] === '0') && $itemSpec && isset($itemsSpec[$item['item_id']])) {
                $item['item_spec_desc'] = implode(',', $itemsSpec[$item['item_id']]);
            }

            if (isset($result[$item['goods_id']])) {
                $result[$item['goods_id']]['spec_items'][] = $item;
            } else {
                $result[$item['goods_id']] = $item;
                if ($item['nospec'] === false || $item['nospec'] === 'false' || $item['nospec'] === 0 || $item['nospec'] === '0') {
                    $result[$item['goods_id']]['spec_items'][] = $item;
                }
            }
        }

        return array_values($result);
    }

    public function minusActivityItemStore($companyId, $activityId, $itemId, $num) {
        $conn = app('registry')->getConnection('default');
        $affectNum = $conn->executeUpdate('UPDATE employee_purchase_activity_items SET activity_store=activity_store-'.$num.' WHERE company_id='.$companyId.' AND activity_id='.$activityId.' AND item_id='.$itemId.' AND activity_store>='.$num);
        if (!$affectNum) {
            throw new ResourceException('库存不足');
        }
    }

    public function addActivityItemStore($companyId, $activityId, $itemId, $num) {
        $conn = app('registry')->getConnection('default');
        $affectNum = $conn->executeUpdate('UPDATE employee_purchase_activity_items SET activity_store=activity_store+'.$num.' WHERE company_id='.$companyId.' AND activity_id='.$activityId.' AND item_id='.$itemId);
        if (!$affectNum) {
            throw new ResourceException('商品不参与活动');
        }
    }
}
