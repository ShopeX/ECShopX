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

namespace CompanysBundle\Repositories;

use Doctrine\Common\Collections\Criteria;
use Dingo\Api\Exception\ResourceException;
use CompanysBundle\Services\CommonLangModService;

class LangueRepository 
{
    private $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function createLangue($data)
    {   
        $repository = $this->repository;
        $entity = $repository->getEntity();
        $entity = $repository->setColumnNamesData($entity, $data);
        $em = $repository->getEntityManager();
        $em->persist($entity);
        $em->flush();
        $info = $repository->getColumnNamesData($entity);

        if (!empty($info)) {
            $companyId = $data['company_id'] ?? 0;
            $data_id = $repository->primaryKey;
            $table = $repository->table;
            $module = $repository->module;
            $fieldLangue = $repository->langField;
            $ns = new CommonLangModService();
            $langueData = $ns->getLangData($data, $fieldLangue);
            $ns->saveLang($companyId, $langueData['langBag'], $table, $data_id, $module);
        }
        
        return $info;
    }

    public function updateOneByLangue($filter, $data)
    {
        $repository = $this->repository;
        $entity = $repository->findOneBy($filter);
        if (!$entity) {
            return [];
        }
        $entity = $repository->setColumnNamesData($entity, $data);
        $em = $repository->getEntityManager();
        $em->persist($entity);
        $em->flush();
        $info = $repository->getColumnNamesData($entity);
        if (!empty($info)) {
            $companyId = $info['company_id'] ?? 0;
            $data_id = $info[$repository->primaryKey];
            $table = $repository->table;
            $module = $repository->module;
            $fieldLangue = $repository->langField;
            $ns = new CommonLangModService();
            $langueData = $ns->getLangData($data, $fieldLangue);
            $ns->updateLangData( $companyId,$langueData['langBag'], $table, $data_id, $module);
        }

        return $info;
    }

    public function updateByLangue($filter, $data)
    {
        $repository = $this->repository;
        $entityList = $repository->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }
        $em = $repository->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $repository->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $info =  $repository->getColumnNamesData($entityProp);
            $result[] = $info;
            if (!empty($info)) {
                $companyId = $info['company_id'] ?? 0;
                $data_id = $info[$repository->primaryKey];
                $table = $repository->table;
                $module = $repository->module;
                $fieldLangue = $repository->langField;
                $ns = new CommonLangModService();
                $langueData = $ns->getLangData($data, $fieldLangue);
                $ns->updateLangData( $companyId,$langueData['langBag'], $table, $data_id, $module);
            }
        }

        return $result;
    }

    public function deleteByIdLangue($id)
    {
        $repository = $this->repository;
        $entity = $repository->find($id);
        if (!$entity) {
            throw new ResourceException("删除的数据不存在");
        }
        $info = $repository->getColumnNamesData($entity);
        $em = $repository->getEntityManager();
        $em->remove($entity);
        $em->flush();
        if (!empty($info)) {
            $companyId = $info['company_id'] ?? 0;
            $data_id = $info[$repository->primaryKey];
            $table = $repository->table;
            $module = $repository->module;
            $ns = new CommonLangModService();
            $ns->deleteLang($companyId, $table, $data_id, $module);
        }

        return true;
    }

    public function deleteByLangue($filter)
    {
        $repository = $this->repository;
        $entityList = $repository->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("删除的数据不存在");
        }
        $em = $repository->getEntityManager();
        foreach ($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
            $info = $repository->getColumnNamesData($entityProp);
            if (!empty($info)) {
                $companyId = $info['company_id'] ?? 0;
                $data_id = $info[$repository->primaryKey];
                $table = $repository->table;
                $module = $repository->module;
                $ns = new CommonLangModService();
                $ns->deleteLang($companyId, $table, $data_id, $module);
            }
        }

        return true;
    }

    public function getInfoByIdLangue($id)
    {
        $repository = $this->repository;
        $entity = $repository->find($id);
        if (!$entity) {
            return [];
        }
        $info =  $repository->getColumnNamesData($entity);
        if (!empty($info)) {
            $data_id = $repository->primaryKey;
            $table = $repository->table;
            $module = $repository->module;
            $fieldLangue = $repository->langField;
            $ns = new CommonLangModService();
            $lang = $ns->getLang();
            $info = $ns->getOneAddLang($info, $fieldLangue, $table, $lang, $data_id, $module);
        }

        return $info;
    }

    public function getInfoLangue(array $filter)
    {
        $repository = $this->repository;
        $entity = $repository->findOneBy($filter);
        if (!$entity) {
            return [];
        }
        $info =  $repository->getColumnNamesData($entity);
        if (!empty($info)) {
            $data_id = $repository->primaryKey;
            $table = $repository->table;
            $module = $repository->module;
            $fieldLangue = $repository->langField;
            $ns = new CommonLangModService();
            $lang = $ns->getLang();
            $info = $ns->getOneAddLang($info, $fieldLangue, $table, $lang, $data_id, $module);
        }

        return $info;
    }

    public function listsLangue($filter, $cols="*", $page, $pageSize, $orderBy)
    {
        $filter = $this->filterLang($filter);
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode("|", $field);
                if (count($list) > 1) {
                    list($v, $k) = $list;
                    $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                    continue;
                } elseif (is_array($value)) {
                    $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
                } else {
                    $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
                }
            }
        }
        $repository = $this->repository;
        $total = $repository->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($repository->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);
        $lists = [];
        if ($res["total_count"]) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $repository->matching($criteria);
            foreach ($entityList as $entity) {
                $info = $repository->getColumnNamesData($entity);
                $lists[] = $info;
            }
            if (!empty($lists)) {
                $data_id = $repository->primaryKey;
                $table = $repository->table;
                $module = $repository->module;
                $fieldLangue = $repository->langField;
                $ns = new CommonLangModService();
                $lang = $ns->getLang();
                $lists = $ns->getListAddLang($lists, $fieldLangue, $table, $lang, $data_id, $module);
            }
        }

        $res["list"] = $lists;
        return $res;
    }

    public function getListsLangue($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $filter = $this->filterLang($filter);
        $repository = $this->repository;
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($repository->table);
        $qb = $repository->_filter($filter, $qb);
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
        if (!empty($lists)) {
            $data_id = $repository->primaryKey;
            $table = $repository->table;
            $module = $repository->module;
            $fieldLangue = $repository->langField;
            $ns = new CommonLangModService();
            $lang = $ns->getLang();
            $lists = $ns->getListAddLang($lists, $fieldLangue, $table, $lang, $data_id, $module);
        }
        
        return $lists;
    }

    public function filterLang($filter) 
    {
        $repository = $this->repository;
        $ns = new CommonLangModService();
        $prk = $repository->primaryKey;
        $table = $repository->table;
        $module = $repository->module;
        $fieldLangue = $repository->langField;
        $lang = $ns->getLang();
        $prkFilter = $filter[$prk] ?? ''; // 所有过滤字段keys
        foreach ($filter as $key => $value) {
            // 必须是多语言字段
            if (in_array($key, $fieldLangue)) {
                $dataIdArr = $ns->filterByLang($lang, $key, $value, $table);
            }
            // 如果存在多语言字段主键，说明可能其他地方使用了主键过滤，我们需要合并掉
            if (!empty($dataIdArr)) {
                if (!empty($prkFilter)) {
                    $filter[$key] = array_merge($filter[$key], $dataIdArr);
                }else{
                    $filter[$key] = $dataIdArr;
                }
            }
        }
        
        return $filter;
    }
    
}