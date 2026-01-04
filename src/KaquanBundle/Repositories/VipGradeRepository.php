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

namespace KaquanBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use KaquanBundle\Entities\VipGrade;

use Dingo\Api\Exception\ResourceException;

class VipGradeRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new VipGrade();
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
            throw new ResourceException(trans('KaquanBundle.no_update_data_found'));
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
            throw new ResourceException(trans('KaquanBundle.no_update_data_found'));
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
            throw new \Exception(trans('KaquanBundle.delete_data_not_exist'));
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
            throw new \Exception(trans('KaquanBundle.delete_data_not_exist'));
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
     * @param array $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["created" => "ASC"], $pageSize = 100, $page = 1)
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

        $lists = [];
        $criteria = $criteria->orderBy($orderBy)
            ->setFirstResult($pageSize * ($page - 1))
            ->setMaxResults($pageSize);
        $entityList = $this->matching($criteria);
        foreach ($entityList as $entity) {
            $lists[] = $this->getColumnNamesData($entity);
        }

        return $lists;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["grade_name"]) && $data["grade_name"]) {
            $entity->setGradeName($data["grade_name"]);
        }

        if (isset($data["lv_type"]) && $data["lv_type"]) {
            $entity->setLvType($data["lv_type"]);
        }

        if (isset($data["default_grade"]) && $data["default_grade"] = false) {
            $entity->setDefaultGrade($data["default_grade"]);
        }

        if (isset($data["is_disabled"])) {
            if (!$data["is_disabled"] || $data["is_disabled"] === 'false') {
                $entity->setIsDisabled(false);
            } else {
                $entity->setIsDisabled(true);
            }
        }

        //当前字段非必填
        if (isset($data["background_pic_url"]) && $data["background_pic_url"]) {
            if ($data["background_pic_url"] === "false") {
                $data["background_pic_url"] = "";
            }
            $entity->setBackgroundPicUrl($data["background_pic_url"]);
        }
        //当前字段非必填
        if (isset($data["price_list"]) && $data["price_list"]) {
            $entity->setPriceList(json_encode($data["price_list"]));
        }
        //当前字段非必填
        if (isset($data["privileges"]) && $data["privileges"]) {
            $entity->setPrivileges(json_encode($data["privileges"]));
        }

        if (isset($data['description']) && $data['description']) {
            $entity->setDescription($data["description"]);
        }

        //当前字段非必填
        if (isset($data["guide_title"]) && $data["guide_title"]) {
            $entity->setGuideTitle($data["guide_title"]);
        }

        if (isset($data['is_default'])) {
            $isDefault = (!$data["is_default"] || $data["is_default"] === 'false') ? false : true;
            $entity->setIsDefault($isDefault);
        }

        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["external_id"])) {
            $entity->setExternalId($data["external_id"]);
        } else {
            $entity->setExternalId((string)$entity->getExternalId());
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
            'vip_grade_id' => $entity->getVipGradeId(),
            'company_id' => $entity->getCompanyId(),
            'grade_name' => $entity->getGradeName(),
            'lv_type' => $entity->getLvType(),
            'default_grade' => $entity->getDefaultGrade(),
            'is_disabled' => $entity->getIsDisabled(),
            'background_pic_url' => $entity->getBackgroundPicUrl(),
            'description' => $entity->getDescription(),
            'price_list' => json_decode($entity->getPriceList(), true),
            'privileges' => json_decode($entity->getPrivileges(), true),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'guide_title' => $entity->getGuideTitle(),
            'is_default' => $entity->getIsDefault(),
            'external_id' => $entity->getExternalId(),
        ];
    }
}
