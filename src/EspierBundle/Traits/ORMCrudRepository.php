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

namespace EspierBundle\Traits;

use Illuminate\Support\Str;

trait ORMCrudRepository
{
    use DoctrineArrayFilter;

    public function save($entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        return $entity;
    }
    public function delete($entity)
    {
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
        return $entity;
    }

    /**
     * create
     *
     * @param array $data
     * @return bool|number
     */
    public function create(array $data)
    {
        $entityName = $this->getClassMetadata()->getName();
        $entity = new $entityName();
        foreach ($data as $key => $value) {
            $setMethod = 'set'.ucwords(Str::camel($key));
            if (method_exists($entity, $setMethod)) {
                $entity->{$setMethod}($value);
            }
        }
        return $this->save($entity);
    }
    /**
     * 批量更新
     * @param  array  $filter
     * @param  array  $data
     * @return bool
     */
    public function batchUpdate(array $filter, array $data)
    {
        $qb = $this->getORMQueryBuilder();
        $entityName = $this->getClassMetadata()->getName();

        $qb = $qb->update($entityName, 't0');
        foreach ($data as $key => $value) {
            $qb = $qb->set('t0.'.$key, $qb->expr()->literal($value));
        }
        $qb = $this->filter($filter, $qb);
        return $qb->getQuery()->getResult();
    }
    /**
     * 批量删除
     * @param  array  $filter
     * @return bool
     */
    public function batchDelete(array $filter)
    {
        $qb = $this->getORMQueryBuilder();
        $entityName = $this->getClassMetadata()->getName();

        $qb = $qb->delete($entityName, 't0');

        $qb = $this->filter($filter, $qb);
        return $qb->getQuery()->getResult();
    }
    /**
     * 获取当前 Respository 所属 Connnection 的 DBAL Query Builder
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getORMQueryBuilder()
    {
        return $this->getEntityManager()->createQueryBuilder();
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
        $qb = $this->getORMQueryBuilder();
        $qb = $qb->select('count(t0)')->from($this->getClassMetadata()->getName(), 't0');
        $qb = $this->filter($filter, $qb);
        return $qb->getQuery()->getSingleResult()[1];
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
    public function getList(array $filter = [], $page = 1, $pageSize = 100, $orderBy = [])
    {
        $qb = $this->getORMQueryBuilder();

        $qb = $qb->select('t0')->from($this->getClassMetadata()->getName(), 't0');
        $qb = $this->filter($filter, $qb);

        if ($orderBy) {
            foreach ($orderBy as $field => $val) {
                $qb->orderBy($field, $val);
            }
        }
        if ($pageSize > 0) {
            $qb->setFirstResult(($page - 1) * $pageSize)
              ->setMaxResults($pageSize);
        }
        return $qb->getQuery()->getResult();
    }
}
