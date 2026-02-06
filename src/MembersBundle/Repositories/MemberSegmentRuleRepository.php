<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use MembersBundle\Entities\MemberSegmentRule;

use Dingo\Api\Exception\ResourceException;

class MemberSegmentRuleRepository extends EntityRepository
{
    public $table = "member_segment_rules";
    public $cols = ['rule_id','company_id','distributor_id','rule_name','description','rule_config','tag_ids','status','created','updated'];
    private $prk = 'rule_id';

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new MemberSegmentRule();
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
     * 根据条件获取单条数据
     *
     * @param $filter 查询条件
     */
    public function getOneBy($filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return null;
        }
        return $this->getColumnNamesData($entity);
    }

    /**
     * 根据主键获取单条数据
     *
     * @param $id
     */
    public function getById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return null;
        }
        return $this->getColumnNamesData($entity);
    }

    /**
     * 统计数量
     *
     * @param $filter 查询条件
     */
    public function count($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count('.$this->prk.')')
            ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    /**
     * 根据条件获取列表数据
     *
     * @param $filter 查询条件
     * @param $cols 查询字段
     * @param $page 页码
     * @param $pageSize 每页数量
     * @param $orderBy 排序
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
        } else {
            $qb->addOrderBy('created', 'DESC');
        }
        if ($pageSize > 0) {
            $offset = ($page - 1) * $pageSize;
            $qb->setFirstResult($offset);
            $qb->setMaxResults($pageSize);
        }
        return $qb->execute()->fetchAll();
    }

    /**
     * 根据条件获取列表数据（带分页信息）
     *
     * @param $filter 查询条件
     * @param $cols 查询字段
     * @param $page 页码
     * @param $pageSize 每页数量
     * @param $orderBy 排序
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = 20, $orderBy = array())
    {
        $result['total_count'] = $this->count($filter);
        $result['list'] = [];
        if ($result['total_count'] > 0) {
            $result['list'] = $this->getLists($filter, $cols, $page, $pageSize, $orderBy);
        }
        return $result;
    }

    /**
     * 设置实体字段数据
     *
     * @param $entity
     * @param $params
     */
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

    /**
     * 获取实体字段数据
     *
     * @param $entity
     * @param $cols
     * @param $ignore
     */
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
            $value = $entity->$fun();
            
            // 特殊处理 JSON 字段
            if ($col === 'rule_config' || $col === 'tag_ids') {
                if (is_string($value)) {
                    $value = json_decode($value, true) ?: ($col === 'tag_ids' ? [] : []);
                }
            }
            
            $values[$col] = $value;
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
}
