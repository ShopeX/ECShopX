<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Traits;

trait DBALCrudRepository
{
    use DoctrineArrayFilter;
    public $compositeExpression = \Doctrine\DBAL\Query\Expression\CompositeExpression::class;

    public function save(array &$data)
    {
        $indexs = $this->getClassMetadata()->getIdentifier();
        $indexCount = count($indexs);
        $filter = [];
        foreach ($indexs as $feild) {
            if (isset($data[$feild]) && $data[$feild]) {
                $filter[$feild] = $data[$feild];
                unset($data[$feild]);
            }
        }
        if ($indexCount == count($filter) && $this->count($filter) > 0) {
            $result = $this->batchUpdate($filter, $data);
            $data = array_merge($data, $filter);
            return $result;
        }

        $isMultiplePrimaryKey = $indexCount > 1;
        if ($isMultiplePrimaryKey) {
            if ($indexCount != count($filter)) {
                throw new \Exception('复合主键不能为空');
            } else {
                $data = array_merge($data, $filter);
            }
        }

        return $this->create($data);
    }
    /**
     * create
     *
     * @param array $data
     * @return bool|number
     */
    public function create(array &$data)
    {
        $indexs = $this->getClassMetadata()->getIdentifier();
        $indexCount = count($indexs);
        $qb = $this->getQueryBuilder();
        $qb = $qb->insert($this->getTableName());
        foreach ($data as $key => $value) {
            $qb = $qb->setValue($key, $qb->expr()->literal($value));
        }
        $insert = $qb->execute();
        $isAutoKey = isset($this->getClassMetadata()->fieldMappings[$indexs[0]]['id']);
        if ($indexCount == 1 && $isAutoKey) {
            $data[$indexs[0]] = $this->getEntityManager()->getConnection()->lastInsertId();
        }
        return $insert > 0 ? $insert : false;
    }
    /**
     * 批量更新
     * @param  array  $filter
     * @param  array  $data
     * @return bool
     */
    public function batchUpdate(array $filter, array $data)
    {
        $qb = $this->getQueryBuilder();
        $qb = $qb->update($this->getTableName());
        foreach ($data as $key => $value) {
            $qb = $qb->set($key, $qb->expr()->literal($value));
        }
        $qb = $this->filter($filter, $qb);
        return $qb->execute();
    }
    /**
     * 批量删除
     * @param  array  $filter
     * @return bool
     */
    public function batchDelete(array $filter)
    {
        $qb = $this->getQueryBuilder();
        $qb = $qb->delete($this->getTableName());
        $qb = $this->filter($filter, $qb);
        return $qb->execute();
    }
    /**
     * 获取当前 Respository 所属 Connnection 的 DBAL Query Builder
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->getEntityManager()->getConnection()->createQueryBuilder();
    }
    /**
     * 获取当前 Respository 关联实体的表名
     * @return string
     */
    public function getTableName()
    {
        return $this->getClassMetadata()->getTableName();
    }
    /**
     * count
     * @param  array  $filter
     * @return number
     */
    public function count(array $filter = [])
    {
        $qb = $this->getQueryBuilder();
        $qb->select('count(*) as _count')->from($this->getTableName()) ;
        $qb = $this->filter($filter, $qb);
        $total = $qb->execute()->fetchColumn();
        return $total;
    }
    /**
     * 获取列表数据库
     * @param  string  $cols     字段
     * @param  array   $filter   过滤条件
     * @param  integer $page     分页
     * @param  integer $pageSize 分页大小
     * @param  array   $orderBy  排序
     * @return array
     */
    public function getList($cols = '*', array $filter = [], $page = 1, $pageSize = 100, $orderBy = [])
    {
        $qb = $this->getQueryBuilder();
        $qb->select($cols)->from($this->getTableName()) ;
        $qb = $this->filter($filter, $qb);
        if ($orderBy) {
            foreach ($orderBy as $filed => $val) {
                $qb->orderBy($filed, $val);
            }
        }
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
              ->setMaxResults($pageSize);
        }
        // dd($qb->getSQL());
        return $qb->execute()->fetchAll();
    }
}
