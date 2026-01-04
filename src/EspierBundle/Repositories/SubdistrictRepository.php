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

namespace EspierBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use EspierBundle\Entities\Subdistrict;

use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\ResourceException;

class SubdistrictRepository extends EntityRepository
{

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        // ShopEx EcShopX Service Component
        $entity = new Subdistrict();
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
        if( !$entity ) {
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
        $entityList = $this->findBy($filter);
        if( !$entityList ) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getColumnNamesData($entityProp);
        }
        return $result;
     }

    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        $entity = $this->find($id);
        if(!$entity) {
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
        if(!$entityList) {
            return true;
        }
        $em = $this->getEntityManager();
        foreach($entityList as $entityProp) {
            $em->remove($entityProp);
            $em->flush();
        }
        return true;
    }

    /**
     * 根据主键获取数据
     *
     * @param $id
     */
    public function getInfoById($id)
    {
        $entity = $this->find($id);
        if( !$entity ) {
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
        $criteria = Criteria::create();
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
        $criteria->setFirstResult(0)
            ->setMaxResults(1);
        $entityList = $this->matching($criteria);
        return isset($entityList[0]) ? $this->getColumnNamesData($entityList[0]) : null;
     }

   /**
    * 统计数量
    */
    public function count($filter)
    {
        $criteria = Criteria::create();
        foreach($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v,$k) = $list;
                if ($v == 'distributor_id' && $k == 'contains' && is_array($value)) {
                    $criteria->andWhere(
                        $criteria->expr()->orX(
                            ...(array_map(function ($did) use ($criteria, $k, $v) {
                                return $criteria->expr()->$k($v, $did);
                            }, $value))
                        )
                    );
                } else {
                    $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                }
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);

        return intval($total);
     }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = array('id' => 'DESC'))
    {
        $criteria = Criteria::create();
        foreach($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v,$k) = $list;
                if ($v == 'distributor_id' && $k == 'contains' && is_array($value)) {
                    $criteria->andWhere(
                        $criteria->expr()->orX(
                            ...(array_map(function ($did) use ($criteria, $k, $v) {
                                return $criteria->expr()->$k($v, $did);
                            }, $value))
                        )
                    );
                } else {
                    $criteria = $criteria->andWhere(Criteria::expr()->$k($v, $value));
                }
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
            }
        }

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);


        $lists = [];
        if ($res["total_count"]) {
            $criteria = $criteria->orderBy($orderBy);
            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        return $res;
     }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if(isset($data["id"]) && $data["id"]) {
            $entity->setId($data["id"]);
        }
        if(isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if(isset($data["label"]) && $data["label"]) {
            $entity->setLabel($data["label"]);
        }
        if(isset($data["parent_id"])) {
            $entity->setParentId($data["parent_id"]);
        }
        if(isset($data["distributor_id"])) {
            if ($data["distributor_id"]) {
                if (is_array($data["distributor_id"])) {
                    $entity->setDistributorId(','.implode(',', $data["distributor_id"]).',');
                } else {
                    $entity->setDistributorId($data["distributor_id"]);
                }
            } else {
                $entity->setDistributorId(',');
            }
        }
        if(isset($data["province"]) && $data["province"]) {
            $entity->setProvince($data["province"]);
        }
        if(isset($data["city"]) && $data["city"]) {
            $entity->setCity($data["city"]);
        }
        if(isset($data["area"]) && $data["area"]) {
            $entity->setArea($data["area"]);
        }
        if(isset($data["regions_id"])) {
            if ($data["regions_id"]) {
                if (is_array($data["regions_id"])) {
                    $entity->setRegionsId(json_encode($data["regions_id"]));
                } else {
                    $entity->setRegionsId($data["regions_id"]);
                }
            } else {
                $entity->setRegionsId('[]');
            }
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getColumnNamesData($entity)
    {
        $distributorId = explode(',', trim($entity->getDistributorId(), ','));
        $regionsId = json_decode($entity->getRegionsId(), true);
        return [
            'id' => $entity->getId(),
            'company_id' => $entity->getCompanyId(),
            'label' => $entity->getLabel(),
            'parent_id' => $entity->getParentId(),
            'distributor_id' => $distributorId,
            'province' => $entity->getProvince(),
            'city' => $entity->getCity(),
            'area' => $entity->getArea(),
            'regions_id' => $regionsId,
        ];
    }
}