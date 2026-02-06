<?php

namespace ThemeBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use ThemeBundle\Entities\PagesAdPlaceRelDistributors;
use Dingo\Api\Exception\ResourceException;

/**
 * PagesAdPlaceRelDistributorsRepository
 */
class PagesAdPlaceRelDistributorsRepository extends EntityRepository
{
    public $table = 'pages_ad_place_rel_distributors';
    public $cols = ['company_id', 'ad_place_id', 'distributor_id'];

    /**
     * 创建广告位与店铺关联
     *
     * @param array $data
     * @return array
     */
    public function create($data)
    {
        $entity = new PagesAdPlaceRelDistributors();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }
    
    /**
     * 批量插入广告位与店铺关联
     *
     * @param array $data
     * @return bool
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

        $time = time();
        $insertValue = [];
        foreach ($data as $value) {
            foreach ($value as &$v) {
                $v = $qb->expr()->literal($v);
            }
            $insertValue[] = '(' . implode(', ', $value) . ')';
        }

        $sql .= implode(',', $insertValue);
        return $conn->executeUpdate($sql);
    }
    
    /**
     * 根据条件更新单个关联
     *
     * @param array $filter
     * @param array $data
     * @return array
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
     * 更新多个关联
     *
     * @param array $filter
     * @param array $data
     * @return int
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
     * 根据条件删除关联
     *
     * @param array $filter
     * @return bool
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
     * 设置实体属性值
     *
     * @param object $entity
     * @param array $params
     * @return object
     */
    private function setColumnNamesData($entity, $params)
    {
        foreach ($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = "set". str_replace(" ", "", ucwords(str_replace("_", " ", $col)));
                if (!method_exists($entity, $fun)) {
                    continue;
                }
                $entity->$fun($params[$col]);
            }
        }
        return $entity;
    }

    /**
     * 获取实体属性值
     *
     * @param object $entity
     * @param array $cols
     * @param array $ignore
     * @return array
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
            if (!method_exists($entity, $fun)) {
                continue;
            }
            $values[$col] = $entity->$fun();
        }
        return $values;
    }
    
    /**
     * 筛选条件格式化
     *
     * @param array $filter
     * @param object $qb
     * @return object
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
     * 获取关联列表
     *
     * @param array $filter
     * @param string $cols
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @return array
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
     * 获取关联列表及总数
     *
     * @param array $filter
     * @param string $cols
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy
     * @return array
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result['total_count'] = $this->count($filter);
        $result['list'] = array();
        
        if ($result['total_count'] > 0) {
            $result['list'] = $this->getLists($filter, $cols, $page, $pageSize, $orderBy);
        }
        
        return $result;
    }
    
    /**
     * 获取单个关联信息
     *
     * @param array $filter
     * @return array
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
     * 统计关联数量
     *
     * @param array $filter
     * @return int
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

    public function getRelDistributorsByAdPlaceIds($adPlaceIds)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->from($this->table, 'r')
            ->leftJoin('r', 'distribution_distributor', 'd', 'r.distributor_id = d.distributor_id')
            ->where($qb->expr()->in('r.ad_place_id', $adPlaceIds));
        $distributorList =$qb->select('r.ad_place_id, r.distributor_id, d.name')->execute()->fetchAll();
        $relDistributors = [];
        foreach ($distributorList as $distributor) {
            if (!isset($relDistributors[$distributor['ad_place_id']])) {
                $relDistributors[$distributor['ad_place_id']] = [['distributor_id' => $distributor['distributor_id'], 'distributor_name' => $distributor['name']]];
            } else {
                $relDistributors[$distributor['ad_place_id']][] = ['distributor_id' => $distributor['distributor_id'], 'distributor_name' => $distributor['name']];
            }
        }
        return $relDistributors;
    }
}