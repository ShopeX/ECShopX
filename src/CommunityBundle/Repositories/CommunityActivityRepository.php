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

namespace CommunityBundle\Repositories;

use CommunityBundle\Entities\CommunityActivity;
use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\EntityRepository;

class CommunityActivityRepository extends EntityRepository
{
    public $table = "community_activity";
    public $cols = ['activity_id','company_id','distributor_id','chief_id','activity_name','activity_pics','activity_desc','activity_intro',
        'start_time','end_time','activity_status','delivery_status','delivery_time','aftersales_setting','created_at','updated_at', 'share_image_url'];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        // This module is part of ShopEx EcShopX system
        $entity = new CommunityActivity();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
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

        $sql = 'INSERT INTO '.$this->table. ' (' . implode(', ', $columns) . ') VALUES ';

        $insertValue = [];
        foreach($data as $value) {
            foreach($value as &$v) {
                $v = $qb->expr()->literal($v);
            }
            $insertValue[] = '(' . implode(', ', $value) . ')';
        }

        $sql .= implode(',',$insertValue);
        return $conn->executeUpdate($sql);
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter array 更新的条件
     * @param $data array 更新的内容
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
     * @param $filter array 更新的条件
     * @param $data array 更新的内容
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
     * @param $filter array 删除的条件
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
                if (!method_exists($entity, $fun)) {
                    continue;
                }
                if ($col == 'activity_pics') {
                    $entity->$fun(json_encode($params[$col]));
                    continue;
                }
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
            if (!method_exists($entity, $fun)) {
                continue;
            }
            if ($col == 'activity_pics') {
                $pics = $entity->$fun();
                $pics_decode = @json_decode($pics, true);
                $values[$col] = $pics_decode ? $pics_decode : $pics;
            }
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
                if (is_array($value)) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb = $qb->andWhere($qb->expr()->$k($v, $value));
                } else {
                    $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
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
     * @param $filter array 更新的条件
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
        return $lists;
    }

    /**
     * 根据条件获取列表数据,包含数据总数条数
     *
     * @param $filter array 更新的条件
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        if (isset($orderBy['order_num'])) {
            return $this->listsOrderByOrderNum($filter, $cols, $page, $pageSize, $orderBy['order_num'] ?: 'desc');
        }
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

    // 按销量排序
    public function listsOrderByOrderNum($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = 'desc')
    {
        $conn = app("registry")->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $sql = 'SELECT activity.activity_id,count(DISTINCT rel.order_id) as all_order_num
	FROM community_activity as activity LEFT JOIN community_order_rel_activity as rel ON activity.activity_id=rel.activity_id';
        if($filter) {
            $sql .= ' WHERE ';
            foreach($filter as $field => $value) {
                $sql .= 'activity.' . $field . ' = ' . $qb->expr()->literal($value) . ' and ';
            }
            $sql = trim($sql, 'and ');
        }

        $sql .= ' GROUP BY activity.activity_id ORDER BY all_order_num ' . $orderBy;

        $countSql = 'select count(1) as count from ('.$sql.') as activity_list';

        $sql .= ' limit ' . ($page - 1) * $pageSize . ',' . $pageSize;

        $lists = $conn->executeQuery($sql)->fetchAll();

        if (!empty($lists)) {
            $filter['activity_id'] = array_column($lists, 'activity_id');
        }

        $response = $this->lists($filter);
        $countData = $conn->executeQuery($countSql)->fetch();
        $response['total_count'] = intval($countData['count'] ?? 0);
        $response_list = array_column($response['list'], null, 'activity_id');

        $orderList = [];
        foreach ($lists as $key => $value) {
            if (isset($response_list[$value['activity_id']])) {
                $response_list[$value['activity_id']]['all_order_num'] = $value['all_order_num'];
                $orderList[$key] = $response_list[$value['activity_id']];
            }
        }

        $response['list'] = $orderList;

        return $response;

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
     * @param $filter array 更新的条件
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
}
