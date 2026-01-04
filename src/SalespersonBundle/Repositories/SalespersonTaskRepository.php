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

namespace SalespersonBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use SalespersonBundle\Entities\SalespersonTask;

use Dingo\Api\Exception\ResourceException;

class SalespersonTaskRepository extends EntityRepository
{
    public $table = "salesperson_task";
    public $cols = ['task_id','company_id','start_time','end_time','task_name','task_type','task_quota','pics','task_content','use_all_distributor','disabled','created','updated'];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new SalespersonTask();
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
            if (method_exists($entity, $fun)) {
                $values[$col] = $entity->$fun();
            }
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
                    $qb = $qb->andWhere($qb->expr()->$k($field, $value));
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
        $qb->select('count(task_id)')
             ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    /**
     * 获取任务在当前时间段是否存在
     *
     * @param int $companyId
     * @param int $distributorId
     * @param string $taskType
     * @param int $startTime
     * @param int $endTime
     * @param int $taskId
     * @return void
     */
    public function getTaskActiveValid($companyId, $distributorId, $taskType, $startTime, $endTime, $taskId = null)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('task_id')
             ->from($this->table);
        $qb = $qb->andWhere($qb->expr()->eq('company_id', $qb->expr()->literal($companyId)));
        $qb = $qb->andWhere($qb->expr()->eq('use_all_distributor', $qb->expr()->literal(true)));
        $qb = $qb->andWhere($qb->expr()->eq('task_type', $qb->expr()->literal($taskType)));
        if ($taskId) {
            $qb = $qb->andWhere($qb->expr()->neq('task_id', $qb->expr()->literal($taskId)));
        }
        $qb = $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->andX($qb->expr()->lte('start_time', $qb->expr()->literal($startTime))),
                    $qb->expr()->andX($qb->expr()->gte('end_time', $qb->expr()->literal($startTime)))
                ),
                $qb->expr()->andX(
                    $qb->expr()->andX($qb->expr()->lte('start_time', $qb->expr()->literal($endTime))),
                    $qb->expr()->andX($qb->expr()->gte('end_time', $qb->expr()->literal($endTime)))
                )
            )
        );
        $result = $qb->execute()->fetch();
        if ($result) {
            return true;
        }
        $qb2 = $conn->createQueryBuilder();
        $qb2 = $qb2->select('st.task_id')
             ->from($this->table, 'st')
             ->leftjoin('st', 'salesperson_task_rel_distributor', 'strd', 'st.task_id = strd.task_id');
        $qb2 = $qb2->andWhere($qb2->expr()->eq('st.company_id', $qb2->expr()->literal($companyId)));
        $qb2 = $qb2->andWhere($qb2->expr()->eq('st.use_all_distributor', $qb2->expr()->literal(false)));
        $qb2 = $qb2->andWhere($qb2->expr()->eq('st.task_type', $qb2->expr()->literal($taskType)));
        if ($taskId) {
            $qb2 = $qb2->andWhere($qb2->expr()->neq('st.task_id', $qb2->expr()->literal($taskId)));
        }
        $qb2 = $qb2->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->andX($qb->expr()->lte('start_time', $qb->expr()->literal($startTime))),
                    $qb->expr()->andX($qb->expr()->gte('end_time', $qb->expr()->literal($startTime)))
                ),
                $qb->expr()->andX(
                    $qb->expr()->andX($qb->expr()->lte('start_time', $qb->expr()->literal($endTime))),
                    $qb->expr()->andX($qb->expr()->gte('end_time', $qb->expr()->literal($endTime)))
                )
            )
        );
        if ($distributorId) {
            $qb2 = $qb2->andWhere($qb2->expr()->in('strd.distributor_id', $distributorId));
        }

        $result = $qb2->execute()->fetch();
        if ($result) {
            return true;
        }
        return false;
    }
}
