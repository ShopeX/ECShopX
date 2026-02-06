<?php

namespace ThemeBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use ThemeBundle\Entities\PagesSideBar;
use Dingo\Api\Exception\ResourceException;

/**
 * PagesSideBarRepository
 */
class PagesSideBarRepository extends EntityRepository
{
    public $table = 'pages_side_bar';
    public $cols = ['id', 'company_id', 'regionauth_id', 'name', 'pages', 'disabled', 'setting'];

    /**
     * 创建侧边栏设置
     *
     * @param array $data
     * @return array
     */
    public function create($data)
    {
        $entity = new PagesSideBar();
        $entity = $this->setColumnNamesData($entity, $data);
        
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        
        return $this->getColumnNamesData($entity);
    }
    
    /**
     * 根据条件更新单个侧边栏设置
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
     * 更新多个侧边栏设置
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
     * 根据ID删除侧边栏设置
     *
     * @param int $id
     * @return bool
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
     * 根据条件删除侧边栏设置
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
     * 获取侧边栏设置列表
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
     * 获取侧边栏设置列表及总数
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
        $result['total'] = $this->count($filter);
        $result['list'] = [];
        
        if ($result['total'] > 0) {
            $result['list'] = $this->getLists($filter, $cols, $page, $pageSize, $orderBy);
        }
        
        return $result;
    }
    
    /**
     * 根据ID获取数据
     * 
     * @param int $id
     * @return array
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
     * 获取单个侧边栏设置信息
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
     * 统计侧边栏设置数量
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

    public function checkPagesExist($companyId, $regionauthId, $pages, $excludeId = 0)
    {
        if (is_string($pages)) {
            $pages = explode(',', trim($pages, ','));
        }

        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();

        $orX = [];
        array_walk($pages, function (&$page) use ($qb, &$orX) {
            $orX[] = $qb->expr()->like('pages', $qb->expr()->literal('%'.$page.'%'));
        });

        $qb->select('id,pages')->from($this->table)
            ->where( $qb->expr()->eq('disabled', 0))
            ->andWhere($qb->expr()->eq('company_id', $companyId))
            ->andWhere($qb->expr()->eq('regionauth_id', $regionauthId))
            ->andWhere($qb->expr()->orX(...$orX));

        if ($excludeId > 0) {
            $qb->andWhere($qb->expr()->neq('id', $excludeId));
        }

        $list = $qb->execute()->fetchAll();

        $existPages = [];
        foreach ($list as $item) {
            $existPages = array_merge($existPages, array_intersect($pages, explode(',', trim($item['pages'], ','))));
        }

        if ($existPages) {
            throw new ResourceException(implode(',', $existPages).'不可以重复配置');
        }
    }
}