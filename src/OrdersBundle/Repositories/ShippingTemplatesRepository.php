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

namespace OrdersBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;
use OrdersBundle\Entities\ShippingTemplates;

use Dingo\Api\Exception\ResourceException;

class ShippingTemplatesRepository extends EntityRepository
{
    public $table = 'shipping_templates';
    public $module = 'shipping_templates'; // 多语言对应的模块
    public $primaryKey = 'template_id'; // 主键，对应data_id
    public $langField = [
        'name'
    ]; // 多语言字段
    
    public function getEntity()
    {
        // This module is part of ShopEx EcShopX system
        $entity = new ShippingTemplates();
        return $entity;
    }

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new ShippingTemplates();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        $result = $this->getColumnNamesData($entity);

        return $result;
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateOneBy(array $filter, array $data)
    {
        $filter['distributor_id'] = app('auth')->user()->get('distributor_id');

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
        $filter['distributor_id'] = app('auth')->user()->get('distributor_id');

        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("未查询到更新数据");
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
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
        if (!$entity) {
            throw new ResourceException("删除的数据不存在");
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
        $filter['distributor_id'] = app('auth')->user()->get('distributor_id');

        $entityList = $this->findBy($filter);
        if (!$entityList) {
            throw new ResourceException("删除的数据不存在");
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
    public function lists($filter, $cols = "*", $page = 1, $pageSize = 100, $orderBy = ["create_time" => "DESC"])
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

        $total = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityPersister($this->getEntityName())
            ->count($criteria);
        $res["total_count"] = intval($total);

        $lists = [];
        if ($res["total_count"]) {
            $criteria = $criteria->orderBy($orderBy)
                ->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
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
        if (isset($data["template_id"]) && $data["template_id"]) {
            $entity->setTemplateId($data["template_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["name"]) && $data["name"]) {
            $entity->setName($data["name"]);
        }
        if (isset($data["is_free"])) {
            $entity->setIsFree($data["is_free"]);
        }
        if (isset($data["valuation"]) && $data["valuation"]) {
            $entity->setValuation($data["valuation"]);
        }
        //当前字段非必填
        if (isset($data["protect"]) && $data["protect"]) {
            $entity->setProtect($data["protect"]);
        }
        //当前字段非必填
        if (isset($data["protect_rate"]) && $data["protect_rate"]) {
            $entity->setProtectRate($data["protect_rate"]);
        }
        //当前字段非必填
        if (isset($data["minprice"]) && $data["minprice"]) {
            $entity->setMinprice($data["minprice"]);
        }
        if (isset($data["status"])) {
            $entity->setStatus($data["status"]);
        }
        //当前字段非必填
        if (isset($data["fee_conf"]) && $data["fee_conf"]) {
            $entity->setFeeConf($data["fee_conf"]);
        }
        //当前字段非必填
        if (isset($data["nopost_conf"]) && $data["nopost_conf"]) {
            $entity->setNopostConf($data["nopost_conf"]);
        }
        //当前字段非必填
        if (isset($data["free_conf"]) && $data["free_conf"]) {
            $entity->setFreeConf($data["free_conf"]);
        }
        if (isset($data["create_time"]) && $data["create_time"]) {
            $entity->setCreateTime($data["create_time"]);
        }
        if (isset($data["distributor_id"]) && $data["distributor_id"]) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        if (isset($data["supplier_id"]) && $data["supplier_id"]) {
            $entity->setSupplierId($data["supplier_id"]);
        }
        //当前字段非必填
        if (isset($data["update_time"]) && $data["update_time"]) {
            $entity->setUpdateTime($data["update_time"]);
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
        return [
            'template_id' => $entity->getTemplateId(),
            'company_id' => $entity->getCompanyId(),
            'distributor_id' => $entity->getDistributorId(),
            'supplier_id' => $entity->getSupplierId(),
            'name' => $entity->getName(),
            'is_free' => $entity->getIsFree(),
            'valuation' => $entity->getValuation(),
            'protect' => $entity->getProtect(),
            'protect_rate' => $entity->getProtectRate(),
            'minprice' => $entity->getMinprice(),
            'status' => $entity->getStatus(),
            'fee_conf' => $entity->getFeeConf(),
            'nopost_conf' => $entity->getNopostConf(),
            'free_conf' => $entity->getFreeConf(),
            'create_time' => $entity->getCreateTime(),
            'update_time' => $entity->getUpdateTime(),
        ];
    }
}
