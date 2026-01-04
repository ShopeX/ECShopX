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

namespace SuperAdminBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Dingo\Api\Exception\ResourceException;

use SuperAdminBundle\Entities\WxappTemplate;
use CompanysBundle\Traits\LangueRepositoryTraits;

class WxappTemplateRepository extends EntityRepository
{
    public $table = 'superadmin_wxapp_template'; // 多语言对应的表名
    public $module = 'superadmin_wxapp_template'; // 多语言对应的模块
    public $primaryKey = 'id'; // 主键，对应data_id
    public $langField = [
        'name','description'
    ]; // 多语言字段
    
    public function getEntity()
    {
        $entity = new WxappTemplate();
        return $entity;
    }
    
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new WxappTemplate();
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
    public function count($filter = [])
    {
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
    public function lists($filter = [], $cols = '*', $page = 1, $pageSize = 100, $orderBy = ["created" => "DESC"])
    {
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
        if (isset($data["id"]) && $data["id"]) {
            $entity->setId($data["id"]);
        }
        if (isset($data["key_name"]) && $data["key_name"]) {
            $entity->setKeyName($data["key_name"]);
        }
        //当前字段非必填
        if (isset($data["name"]) && $data["name"]) {
            $entity->setName($data["name"]);
        }
        //当前字段非必填
        if (isset($data["tag"]) && $data["tag"]) {
            $entity->setTag($data["tag"]);
        }
        //当前字段非必填
        if (isset($data["template_id"]) && $data["template_id"]) {
            $entity->setTemplateId($data["template_id"]);
        }
        if (isset($data["template_id_2"]) && $data["template_id_2"]) {
            $entity->setTemplateId2($data["template_id_2"]);
        }
        //当前字段非必填
        if (isset($data["version"]) && $data["version"]) {
            $entity->setVersion($data["version"]);
        }
        if (isset($data["is_only"])) {
            $entity->setIsOnly($data["is_only"]);
        }
        //当前字段非必填
        if (isset($data["description"]) && $data["description"]) {
            $entity->setDescription($data["description"]);
        }
        //当前字段非必填
        if (isset($data["domain"]) && $data["domain"]) {
            $entity->setDomain(json_encode($data["domain"]));
        }
        if (isset($data["is_disabled"])) {
            if (!$data['is_disabled'] || $data['is_disabled'] === 'false') {
                $entity->setIsDisabled(false);
            } else {
                $entity->setIsDisabled(true);
            }
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
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
            'id' => $entity->getId(),
            'key_name' => $entity->getKeyName(),
            'name' => $entity->getName(),
            'tag' => $entity->getTag(),
            'template_id' => $entity->getTemplateId(),
            'template_id_2' => $entity->getTemplateId2(),
            'version' => $entity->getVersion(),
            'is_only' => $entity->getIsOnly(),
            'description' => $entity->getDescription(),
            'domain' => json_decode($entity->getDomain(), true),
            'is_disabled' => $entity->getIsDisabled(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }

}
