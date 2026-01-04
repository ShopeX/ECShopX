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

namespace PromotionsBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use PromotionsBundle\Entities\MarketingActivityItems;

use Dingo\Api\Exception\ResourceException;

class MarketingActivityItemsRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new MarketingActivityItems();
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
            throw new ResourceException(trans("PromotionsBundle.no_update_data_found"));
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
            throw new ResourceException(trans("PromotionsBundle.no_update_data_found"));
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
    public function lists($filter, $page = 1, $pageSize = -1, $orderBy = array())
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
        if (isset($data["marketing_id"]) && $data["marketing_id"]) {
            $entity->setMarketingId($data["marketing_id"]);
        }
        if (isset($data["item_id"]) && $data["item_id"]) {
            $entity->setItemId($data["item_id"]);
        }
        //当前字段非必填
        if (isset($data["marketing_type"]) && $data["marketing_type"]) {
            $entity->setMarketingType($data["marketing_type"]);
        }
        if (isset($data["item_type"]) && $data["item_type"]) {
            $entity->setItemType($data["item_type"]);
        }
        if (isset($data["item_name"]) && $data["item_name"]) {
            $entity->setItemName($data["item_name"]);
        }
        if (isset($data["is_show"])) {
            $entity->setIsShow($data["is_show"]);
        }
        if (isset($data["item_spec_desc"]) && $data["item_spec_desc"]) {
            $entity->setItemSpecDesc($data["item_spec_desc"]);
        }

        if (isset($data["price"])) {
            $entity->setPrice($data["price"]);
        }
        if (isset($data["item_brief"]) && $data["item_brief"]) {
            $entity->setItemBrief($data["item_brief"]);
        }
        if (isset($data["pics"]) && $data["pics"]) {
            $entity->setPics(json_encode($data["pics"]));
        }
        if (isset($data["promotion_tag"]) && $data["promotion_tag"]) {
            $entity->setPromotionTag($data["promotion_tag"]);
        }
        if (isset($data["start_time"]) && $data["start_time"]) {
            $entity->setStartTime($data["start_time"]);
        }
        if (isset($data["end_time"]) && $data["end_time"]) {
            $entity->setEndTime($data["end_time"]);
        }
        if (isset($data["status"]) && $data["status"]) {
            $entity->setStatus($data["status"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["goods_id"]) && $data["goods_id"]) {
            $entity->setGoodsId($data["goods_id"]);
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
            'marketing_id' => $entity->getMarketingId(),
            'item_id' => $entity->getItemId(),
            'goods_id' => $entity->getGoodsId(),
            'is_show' => $entity->getIsShow(),
            'item_spec_desc' => $entity->getItemSpecDesc(),
            'marketing_type' => $entity->getMarketingType(),
            'item_type' => $entity->getItemType(),
            'item_name' => $entity->getItemName(),
            'price' => $entity->getPrice(),
            'item_brief' => $entity->getItemBrief(),
            'pics' => json_decode($entity->getPics(), true) ?: ['null'],
            'promotion_tag' => $entity->getPromotionTag(),
            'start_time' => $entity->getStartTime(),
            'end_time' => $entity->getEndTime(),
            'status' => $entity->getStatus(),
            'company_id' => $entity->getCompanyId(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }

    public function updateBySimpleFilter($filter, $data)
    {
        if ($this->lists($filter)['list']) {
            $conn = app('registry')->getConnection('default');
            $data['updated'] = time();
            return $conn->update('promotions_marketing_activity_items', $data, $filter);
        }
        return true;
    }

    /**
     * 获取时间段内是否有活动
     *
     * @param $filter 更新的条件
     */
    public function getIsHave($itemId, $begin_time, $end_time, $marketingId = '', $marketingType = '')
    {
        $criteria = Criteria::create();
        $itemId = (array)$itemId;
        $criteria = $criteria->andWhere(Criteria::expr()->in('item_id', $itemId));
        if ($marketingId) {
            $criteria = $criteria->andWhere(Criteria::expr()->neq('marketing_id', $marketingId));
        }

        if ($marketingType) {
            $criteria = $criteria->andWhere(Criteria::expr()->in('marketing_type', $marketingType));
        }
        $criteria = $criteria->andWhere(Criteria::expr()->orX(
            Criteria::expr()->andX(
                Criteria::expr()->lte('start_time', $begin_time),
                Criteria::expr()->gte('end_time', $begin_time)
            ),
            Criteria::expr()->andX(
                Criteria::expr()->lte('start_time', $end_time),
                Criteria::expr()->gte('end_time', $end_time)
            )
        ));

        $entityList = $this->matching($criteria);
        $lists = [];
        foreach ($entityList as $entity) {
            $lists[] = $this->getColumnNamesData($entity);
        }

        return $lists;
    }
}
