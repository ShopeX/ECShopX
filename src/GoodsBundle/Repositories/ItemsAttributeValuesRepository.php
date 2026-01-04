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
use GoodsBundle\Entities\ItemsAttributeValues;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;
use CompanysBundle\MultiLang\MultiLangItem;

class ItemsAttributeValuesRepository extends EntityRepository
{
    use MagicLangTrait;

    private  $table = 'items_attribute_values';

    private $prk = 'attribute_value_id';
    private $multiLangField = [
        'attribute_value'
    ];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $service = new MultiLangService();
//        $dataTmp = $service->getLangData($data,$this->multiLangField);
//        $data = $dataTmp['data'];
//        $langBag = $dataTmp['langBag'];
        $entity = new ItemsAttributeValues();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $dataRet = $this->getColumnNamesData($entity);
        $service->addMultiLangByParams($dataRet[$this->prk],$data,$this->table);
        return $dataRet;
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
        if(isset($filter[$this->prk])){
            //更新数据
            $service = new MultiLangService();
            $service->updateLangData($data,$this->table,$filter[$this->prk]);
        }

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
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = array())
    {
        app('log')->debug(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
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

        //转化语言
        $lang = $this->getLang();
        $res["list"] = (new MultiLangService())->getListAddLang($res["list"],$this->multiLangField,$this->table,$lang,'attribute_value_id',);
        return $res;
    }

    /**
     * 根据多语言属性值查找属性值列表
     * 先通过多语言表查找 attribute_value_id，再查询主表信息
     *
     * @param array $filter 查询条件，如果包含 attribute_value，会通过多语言表查找
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param array $orderBy 排序
     * @return array
     */
    public function listsByAttributeValue($filter, $page = 1, $pageSize = 100, $orderBy = array())
    {
        // 如果 filter 中包含 attribute_value，先通过多语言表查找对应的 attribute_value_id
        if (isset($filter['attribute_value']) && !empty($filter['attribute_value'])) {
            $attributeValues = is_array($filter['attribute_value']) ? $filter['attribute_value'] : [$filter['attribute_value']];
            $attributeValueIds = $this->getAttributeValueIdsByValues($attributeValues, $filter);
            app('log')->debug("listsByAttributeValue attributeValueIds =>:".json_encode($attributeValueIds, 256));
            // 如果通过多语言表找到了 attribute_value_id，替换 filter 中的 attribute_value
            if (!empty($attributeValueIds)) {
                // 移除 attribute_value，添加 attribute_value_id
                unset($filter['attribute_value']);
                // 如果 filter 中已有 attribute_value_id，需要取交集
                if (isset($filter['attribute_value_id'])) {
                    $existingIds = is_array($filter['attribute_value_id']) ? $filter['attribute_value_id'] : [$filter['attribute_value_id']];
                    $attributeValueIds = array_values(array_intersect($existingIds, $attributeValueIds));
                    app('log')->debug("listsByAttributeValue attributeValueIds2 =>:".json_encode($attributeValueIds, 256));
                }
                $filter['attribute_value_id'] = $attributeValueIds;
            }
            // 如果多语言表中没找到，保留原 filter，让 lists 方法在主表中查找默认语言的值
        }

        // 调用原有的 lists 方法
        return $this->lists($filter, $page, $pageSize, $orderBy);
    }

    /**
     * 根据属性值（支持多语言）查找对应的 attribute_value_id
     *
     * @param array $attributeValues 属性值数组
     * @param array $filter 额外的过滤条件（如 company_id, attribute_id 等）
     * @return array attribute_value_id 数组
     */
    private function getAttributeValueIdsByValues(array $attributeValues, array $filter = [])
    {
        if (empty($attributeValues)) {
            return [];
        }

        $lang = $this->getLang();
        $multiLangItem = new MultiLangItem($lang);

        // 从多语言表中查找
        // 如果 filter 中有 company_id，在多语言表查询时就加上，可以提前过滤，提高效率
        $langFilter = [
            'table_name' => $this->table,
            'field' => 'attribute_value',
            'attribute_value|in' => $attributeValues
        ];
        
        // 多语言表中有 company_id 字段，可以在查询时加上这个条件
        if (isset($filter['company_id'])) {
            $langFilter['company_id'] = $filter['company_id'];
        }
        
        app('log')->debug("getAttributeValueIdsByValues langFilter =>:".json_encode($langFilter, 256));
        $langList = $multiLangItem->getListByFilter($langFilter, -1);

        app('log')->debug("getAttributeValueIdsByValues langList =>:".json_encode($langList, 256));
        if (empty($langList)) {
            return [];
        }

        $attributeValueIds = array_column($langList, 'data_id');
        $attributeValueIds = array_unique($attributeValueIds);

        // 验证这些 attribute_value_id 是否满足 filter 中的其他条件（如 attribute_id）
        // company_id 已经在多语言表查询时过滤了，这里只需要验证 attribute_id
        if (!empty($attributeValueIds)) {
            $verifyFilter = ['attribute_value_id' => $attributeValueIds];

            // 合并其他过滤条件（attribute_id 不在多语言表中，需要在主表中验证）
            if (isset($filter['attribute_id'])) {
                $verifyFilter['attribute_id'] = $filter['attribute_id'];
            }

            // 如果没有任何额外条件，直接返回
            if (count($verifyFilter) === 1) {
                return $attributeValueIds;
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
                $verifiedIds[] = $entity->getAttributeValueId();
            }

            return $verifiedIds;
        }

        return [];
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["attribute_id"]) && $data["attribute_id"]) {
            $entity->setAttributeId($data["attribute_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["shop_id"])) {
            $entity->setShopId($data["shop_id"]);
        }
        if (isset($data["attribute_value"]) && $data["attribute_value"]) {
            $entity->setAttributeValue($data["attribute_value"]);
        }
        if (isset($data["sort"])) {
            $entity->setSort($data["sort"]);
        }
        //当前字段非必填
        if (isset($data["image_url"])) {
            $entity->setImageUrl($data["image_url"] ?: '');
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }

        //当前字段非必填
        if (isset($data["oms_value_id"]) && $data["oms_value_id"]) {
            $entity->setOmsValueId($data["oms_value_id"]);
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
            'attribute_value_id' => $entity->getAttributeValueId(),
            'attribute_id' => $entity->getAttributeId(),
            'company_id' => $entity->getCompanyId(),
            'shop_id' => $entity->getShopId(),
            'attribute_value' => $entity->getAttributeValue(),
            'sort' => $entity->getSort(),
            'image_url' => $entity->getImageUrl(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'oms_value_id' => $entity->getOmsValueId()
        ];
    }
}
