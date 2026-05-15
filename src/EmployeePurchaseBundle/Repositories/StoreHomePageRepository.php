<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 */

namespace EmployeePurchaseBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use EmployeePurchaseBundle\Entities\StoreHomePage;
use Dingo\Api\Exception\ResourceException;

class StoreHomePageRepository extends EntityRepository
{
    public $table = 'employee_purchase_store_home_page';
    public $cols = [
        'id', 'company_id', 'distributor_id', 'template_name', 'page_name', 'page_description',
        'page_share_title', 'page_share_desc', 'page_share_imageUrl', 'is_open', 'weapp_customize_page_id',
        'created', 'updated',
    ];

    public function create($data)
    {
        $entity = new StoreHomePage();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

    public function updateOneBy(array $filter, array $data)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException('未查询到更新数据');
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getColumnNamesData($entity);
    }

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

    private function setColumnNamesData($entity, $params)
    {
        foreach ($this->cols as $col) {
            if (array_key_exists($col, $params)) {
                $fun = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $col)));
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
            $fun = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $col)));
            $values[$col] = $entity->$fun();
        }

        return $values;
    }

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
            }
            if (is_array($value)) {
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

    public function lists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = [])
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

    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

    public function getInfo(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return [];
        }

        return $this->getColumnNamesData($entity);
    }

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
