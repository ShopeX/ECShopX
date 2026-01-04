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
use EspierBundle\Entities\UploadImagesCat;

use Dingo\Api\Exception\ResourceException;

class UploadImagesCatRepository extends EntityRepository
{   
    public $table = "espier_uploadimages_cat";
    public $module = 'espier_uploadimages_cat'; // 多语言对应的模块
    public $primaryKey = 'image_cat_id'; // 主键，对应data_id
    public $langField = [
        'image_cat_name',
    ]; // 多语言字段
    
    public function getEntity()
    {
        $entity = new UploadImagesCat();
        return $entity;
    }

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        // $filter = [
        //     'company_id' => $data['company_id'],
        //     'image_cat_name' => $data['image_cat_name'],
        //     'parent_id' => $data['parent_id'],
        // ];
        // $catInfo = $this->findOneBy($filter);
        // if ($catInfo) {
        //     throw new ResourceException("同一等级下分类名称不能重复");
        // }
        $entity = new UploadImagesCat();
        $entity = $this->setImagesCatData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getImagesCatData($entity);
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
            throw new ResourceException("分类Id为{$filter['image_cat_id']}不存在");
        }

        $entity = $this->setImagesCatData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $this->getImagesCatData($entity);
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
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setImagesCatData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getImagesCatData($entityProp);
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
        if (!$entity) {
            throw new \Exception("删除的数据不存在");
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
            throw new \Exception("删除的数据不存在");
        }
        $em = $this->getEntityManager();
        foreach ($entityList as $entityProp) {
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
        if (!$entity) {
            return [];
        }

        return $this->getImagesCatData($entity);
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

        return $this->getImagesCatData($entity);
    }

    /**
     * 统计数量
     */
    public function count($filter)
    {
        $criteria = $this->__preFilter($filter);

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
    public function lists($filter, $cols = '*', $page = 1, $pageSize = 100 , $orderBy = ["created" => "DESC"])
    {
        $criteria = $this->__preFilter($filter);

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res['total_count'] = intval($total);

        $lists = [];
        if ($res['total_count']) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getImagesCatData($entity);
            }
        }

        $res['list'] = $lists;
        return $res;
    }

    private function __preFilter($filter)
    {
        $criteria = Criteria::create();
        foreach ($filter as $field => $value) {
            if (is_numeric($value) || is_bool($value) || $value) {
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

        return $criteria;
    }
    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setImagesCatData($entity, $data)
    {
        // if (isset($data['image_cat_id']) && $data['image_cat_id']) {
        //     $entity->setImageCatId($data['image_cat_id']);
        // }
        if (isset($data['company_id']) && $data['company_id']) {
            $entity->setCompanyId($data['company_id']);
        }
        if (isset($data['image_cat_name']) && $data['image_cat_name']) {
            $entity->setImageCatName($data['image_cat_name']);
        }
        if (isset($data['parent_id'])) {
            $entity->setParentId($data['parent_id']);
        }
        //当前字段非必填
        if (isset($data['image_type']) && $data['image_type']) {
            $entity->setImageType($data['image_type']);
        }
        if (isset($data['path']) && $data['path']) {
            $entity->setPath($data['path']);
        }
        if (isset($data['sort']) && $data['sort']) {
            $entity->setSort($data['sort']);
        }
        if (isset($data['created']) && $data['created']) {
            $entity->setCreated($data['created']);
        }
        //当前字段非必填
        if (isset($data['updated']) && $data['updated']) {
            $entity->setUpdated($data['updated']);
        }
        if (isset($data["supplier_id"])) {
            $entity->setSupplierId(intval($data["supplier_id"]));
        }
        if (isset($data['distributor_id'])) {
            $entity->setDistributorId($data['distributor_id']);
        }
        if (isset($data["merchant_id"])) {
            $entity->setMerchantId(intval($data["merchant_id"]));
        }
        return $entity;
    }

    /**
     * 获取数据表字段数据
     *
     * @param entity
     */
    private function getImagesCatData($entity)
    {
        return [
            'image_cat_id' => $entity->getImageCatId(),
            'company_id' => $entity->getCompanyId(),
            'image_cat_name' => $entity->getImageCatName(),
            'parent_id' => $entity->getParentId(),
            'image_type' => $entity->getImageType(),
            'path' => $entity->getPath(),
            'sort' => $entity->getSort(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'supplier_id' => $entity->getSupplierId(),
            'distributor_id' => $entity->getDistributorId(),
            'merchant_id' => $entity->getMerchantId(),
        ];
    }
}
