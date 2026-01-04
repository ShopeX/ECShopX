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
use EmployeePurchaseBundle\Entities\OrdersRelActivity;

use Dingo\Api\Exception\ResourceException;

class OrdersRelActivityRepository extends EntityRepository
{
    public $table = "employee_purchase_orders_rel_activity";
    public $cols = ['order_id', 'company_id', 'enterprise_id', 'activity_id', 'user_id', 'if_share_store', 'close_modify_time'];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new OrdersRelActivity();
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
                if (method_exists($entity, $fun)) {
                    $entity->$fun($params[$col]);
                }
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
    private function _filter($filter, $qb, $alias = '')
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                    $value = '%'.$value.'%';
                }
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k(($alias ? $alias.'.' : '').$v, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k(($alias ? $alias.'.' : '').$v, $qb->expr()->literal($value)));
                }
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in(($alias ? $alias.'.' : '').$field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq(($alias ? $alias.'.' : '').$field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
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
        $lists = $qb->execute()->fetchAll();
        foreach ($lists as &$value) {
            $value['shop_id'] = 0;
            $value['shop_type'] = 'employee_purchase';
        }
        return $lists;
    }

    /**
     * 根据条件获取列表数据,包含数据总数条数
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
        $qb->select('count(cart_id)')
             ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    public function getOrderListWithActivity($filter, $offset, $limit, $orderBy)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()
            ->select('count(*)')
            ->from('orders_normal_orders', 'o');
        if (isset($filter['item_name'])) {
            if ($filter['item_name']) {
                $qb = $qb->leftJoin('o', 'orders_normal_orders_items', 'i', 'o.order_id = i.order_id');
                $qb = $this->_filter(['item_name|contains' => $filter['item_name']], $qb, 'i');
            }
            unset($filter['item_name']);
        }
        if (isset($filter['enterprise_id'])) {
            $qb = $qb->leftJoin('o', 'employee_purchase_orders_rel_activity', 'r', 'o.order_id = r.order_id');
            $qb = $this->_filter(['enterprise_id' => $filter['enterprise_id']], $qb, 'r');
            unset($filter['enterprise_id']);
        }

        $qb = $this->_filter($filter, $qb, 'o');

        $result['total_count'] = $qb->execute()->fetchColumn();

        if ($orderBy) {
            foreach ($orderBy as $columns => $value) {
                $qb->orderBy('o.'.$columns, $value);
            }
        } else {
            $qb->orderBy('o.create_time', 'DESC');
        }

        if ($limit > 0) {
            $qb = $qb->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        $result['list'] = [];
        if ($result['total_count'] > 0) {
            $result['list'] = $qb->select('o.*')->execute()->fetchAll();
            foreach ($result['list'] as $key => $row) {
                $row['mobile'] && $result['list'][$key]['mobile'] = fixeddecrypt($row['mobile']);
                $row['receiver_name'] && $result['list'][$key]['receiver_name'] = fixeddecrypt($row['receiver_name']);
                $row['receiver_mobile'] && $result['list'][$key]['receiver_mobile'] = fixeddecrypt($row['receiver_mobile']);
                $row['receiver_address'] && $result['list'][$key]['receiver_address'] = fixeddecrypt($row['receiver_address']);
            }
        }

        return $result;
    }
}
