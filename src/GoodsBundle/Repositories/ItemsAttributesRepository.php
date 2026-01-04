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

namespace GoodsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use GoodsBundle\Entities\ItemsAttributes;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;
use CompanysBundle\MultiLang\MultiLangItem;

class ItemsAttributesRepository extends EntityRepository
{
    use MagicLangTrait;

    private  $table = 'items_attributes';

    private $prk = 'attribute_id';
    private $multiLangField = [
        'attribute_name','attribute_memo'
    ];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
//        $service = new MultiLangService();
//        $dataTmp = $service->getLangData($data,$this->multiLangField);
//        $data = $dataTmp['data'];
//        $langBag = $dataTmp['langBag'];
        $entity = new ItemsAttributes();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $dataRet = $this->getColumnNamesData($entity);
        $service = new MultiLangService();
        $service->addMultiLangByParams($dataRet['attribute_id'],$dataRet,$this->table);
        //$service->saveLang($dataRet['company_id'],$langBag,$this->table,$dataRet['attribute_id'],$this->table);
        return $dataRet;
    }

    /**
     * 更新数据表字段数据
     *
     * @param $filter array
     * @param $data array
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
        $service = new MultiLangService();
        $service->updateLangData($data,$this->table,$filter[$this->prk]);

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
        // FIXME: check performance
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

        $info = $this->getColumnNamesData($entity);
        $service = new MultiLangService();
        $info = $service->getTranslationByLang($info,$info[$this->prk],$this->table);
        return $info;
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
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = array())
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
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }

            if ($pageSize > 0) {
                $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            $entityList = $this->matching($criteria);
            foreach ($entityList as $entity) {
                $lists[] = $this->getColumnNamesData($entity);
            }
        }

        $res["list"] = $lists;
        $service = new MultiLangService();
        $res["list"] = $service->getListAddLang($res["list"],$this->multiLangField,$this->table,$this->getLang(),$this->prk);
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
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["shop_id"])) {
            $entity->setShopId($data["shop_id"]);
        }
        if (isset($data["attribute_type"]) && $data["attribute_type"]) {
            $entity->setAttributeType($data["attribute_type"]);
        }
        if (isset($data["attribute_name"]) && $data["attribute_name"]) {
            $entity->setAttributeName($data["attribute_name"]);
        }
        //当前字段非必填
        if (isset($data["attribute_memo"])) {
            $entity->setAttributeMemo($data["attribute_memo"]);
        }
        if (isset($data["attribute_sort"])) {
            $entity->setAttributeSort($data["attribute_sort"]);
        }
        if (isset($data["distributor_id"])) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        if (isset($data["is_show"])) {
            $entity->setIsShow($data["is_show"]);
        }
        if (isset($data["is_image"])) {
            $entity->setIsImage($data["is_image"]);
        }
        //当前字段非必填
        if (isset($data["image_url"])) {
            $entity->setImageUrl($data["image_url"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["attribute_code"]) && $data["attribute_code"]) {
            $entity->setAttributeCode($data["attribute_code"]);
        }
        if (isset($data["attribute_show"]) && $data["attribute_show"]) {
            $entity->setAttributeShow($data["attribute_show"]);
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
            'attribute_id' => $entity->getAttributeId(),
            'company_id' => $entity->getCompanyId(),
            'shop_id' => $entity->getShopId(),
            'attribute_type' => $entity->getAttributeType(),
            'attribute_name' => $entity->getAttributeName(),
            'attribute_memo' => $entity->getAttributeMemo(),
            'attribute_sort' => $entity->getAttributeSort(),
            'distributor_id' => $entity->getDistributorId(),
            'is_show' => $entity->getIsShow(),
            'is_image' => $entity->getIsImage(),
            'image_url' => $entity->getImageUrl(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'attribute_code' => $entity->getAttributeCode(),
            'attribute_show' => $entity->getAttributeShow(),
        ];
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function getLists($filter, $page = 1, $pageSize = 100, $orderBy = array())
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
        if ($orderBy) {
            $criteria = $criteria->orderBy($orderBy);
        }

        if ($pageSize > 0) {
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
        }
        $entityList = $this->matching($criteria);
        foreach ($entityList as $entity) {
            $lists[] = $this->getColumnNamesData($entity);
        }
        $service = new MultiLangService();
        $lists = $service->getListAddLang($lists,$this->multiLangField,$this->table,$this->getLang(),$this->prk);

        return $lists;
    }

    /**
     * 根据多语言属性名称查找属性列表
     * 先通过多语言表查找 attribute_id，再查询主表信息
     *
     * @param array $filter 查询条件，如果包含 attribute_name，会通过多语言表查找
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param array $orderBy 排序
     * @return array
     */
    public function listsByAttributeName($filter, $page = 1, $pageSize = 100, $orderBy = array())
    {
        // 如果 filter 中包含 attribute_name，先通过多语言表查找对应的 attribute_id
        if (isset($filter['attribute_name']) && !empty($filter['attribute_name'])) {
            $attributeNames = is_array($filter['attribute_name']) ? $filter['attribute_name'] : [$filter['attribute_name']];
            $attributeIds = $this->getAttributeIdsByNames($attributeNames, $filter);

            // 如果通过多语言表找到了 attribute_id，替换 filter 中的 attribute_name
            if (!empty($attributeIds)) {
                // 移除 attribute_name，添加 attribute_id
                unset($filter['attribute_name']);
                // 如果 filter 中已有 attribute_id，需要取交集
                if (isset($filter['attribute_id'])) {
                    $existingIds = is_array($filter['attribute_id']) ? $filter['attribute_id'] : [$filter['attribute_id']];
                    $attributeIds = array_values(array_intersect($existingIds, $attributeIds));
                }
                $filter['attribute_id'] = $attributeIds;
            }
            // 如果多语言表中没找到，保留原 filter，让 lists 方法在主表中查找默认语言的值
        }

        // 调用原有的 lists 方法
        return $this->lists($filter, $page, $pageSize, $orderBy);
    }

    /**
     * 根据属性名称（支持多语言）查找对应的 attribute_id
     *
     * @param array $attributeNames 属性名称数组
     * @param array $filter 额外的过滤条件（如 company_id, distributor_id, attribute_type 等）
     * @return array attribute_id 数组
     */
    private function getAttributeIdsByNames(array $attributeNames, array $filter = [])
    {
        if (empty($attributeNames)) {
            return [];
        }

        $lang = $this->getLang();
        $multiLangItem = new MultiLangItem($lang);

        // 从多语言表中查找（只根据名称查找，其他条件在主表中验证）
        $langFilter = [
            'table_name' => $this->table,
            'field' => 'attribute_name',
            'attribute_value|in' => $attributeNames
        ];

        $langList = $multiLangItem->getListByFilter($langFilter, -1);

        if (empty($langList)) {
            return [];
        }

        $attributeIds = array_column($langList, 'data_id');
        $attributeIds = array_unique($attributeIds);

        // 验证这些 attribute_id 是否满足 filter 中的其他条件（如 company_id, distributor_id, attribute_type）
        if (!empty($attributeIds)) {
            $verifyFilter = ['attribute_id' => $attributeIds];

            // 合并其他过滤条件
            foreach (['company_id', 'distributor_id', 'attribute_type'] as $key) {
                if (isset($filter[$key])) {
                    $verifyFilter[$key] = $filter[$key];
                }
            }

            // 如果没有任何额外条件，直接返回
            if (count($verifyFilter) === 1) {
                return $attributeIds;
            }

            // 在主表中验证这些 ID 是否满足条件
            $criteria = Criteria::create();
            foreach ($verifyFilter as $field => $value) {
                if (is_array($value)) {
                    $criteria = $criteria->andWhere(Criteria::expr()->in($field, $value));
                } else {
                    $criteria = $criteria->andWhere(Criteria::expr()->eq($field, $value));
                }
            }

            $entityList = $this->matching($criteria);
            $verifiedIds = [];
            foreach ($entityList as $entity) {
                $verifiedIds[] = $entity->getAttributeId();
            }

            return $verifiedIds;
        }

        return [];
    }
}
