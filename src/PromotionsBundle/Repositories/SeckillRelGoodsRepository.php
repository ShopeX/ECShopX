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
use PromotionsBundle\Entities\SeckillRelGoods;

use Dingo\Api\Exception\ResourceException;

class SeckillRelGoodsRepository extends EntityRepository
{
    public $activityData = [];
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new SeckillRelGoods();
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
        // XXX: review this code
        $entityList = $this->findBy($filter);
        if (!$entityList) {
            return true;
        }
        $em = $this->getEntityManager();
        $batchSize = 20;
        $i = 1;
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
            ++$i;
        }
        $em->flush();
        $em->clear();
        return true;
    }

    public function updateBySimpleFilter($filter, $data)
    {
        $conn = app('registry')->getConnection('default');
        $data['updated'] = time();
        return $conn->update('promotions_seckill_rel_goods', $data, $filter);
    }

    /**
     * 根据主键删除指定数据
     *
     * @param $id
     */
    public function deleteById($id)
    {
        // XXX: review this code
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
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = array('sort' => 'desc', 'item_id' => 'desc', 'activity_start_time' => 'desc'))
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
        if (isset($filter['seckill_id']) && !is_array($filter['seckill_id'])) {
            $res['activity'] = $this->activityData[$filter['seckill_id']] ?? null;
        }
        return $res;
    }

    public function updateSalesStore(array $filter, $salesStore = 0)
    {
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return true;
        }
        $entity->setSalesStore($entity->getSalesStore() + $salesStore);
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
        $em->clear();
        return true;
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["seckill_id"]) && $data["seckill_id"]) {
            $entity->setSeckillId($data["seckill_id"]);
        }
        if (isset($data["item_id"]) && $data["item_id"]) {
            $entity->setItemId($data["item_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["item_title"]) && $data["item_title"]) {
            $entity->setItemTitle($data["item_title"]);
        }
        if (isset($data["activity_price"]) && $data["activity_price"]) {
            $activityPrice = round($data["activity_price"] * 100);
            $entity->setActivityPrice($activityPrice);
        }
        //当前字段非必填
        if (isset($data["activity_store"]) && $data["activity_store"]) {
            $entity->setActivityStore($data["activity_store"]);
        }

        if (isset($data["sales_store"]) && $data["sales_store"]) {
            $entity->setSalesStore($data["sales_store"]);
        }

        if (isset($data["item_pic"]) && $data["item_pic"]) {
            $entity->setItemPic($data["item_pic"]);
        }

        if (isset($data["activity_start_time"]) && $data["activity_start_time"]) {
            $entity->setActivityStartTime($data["activity_start_time"]);
        }
        if (isset($data["activity_end_time"]) && $data["activity_end_time"]) {
            $entity->setActivityEndTime($data["activity_end_time"]);
        }
        if (isset($data["activity_release_time"]) && $data["activity_release_time"]) {
            $entity->setActivityReleaseTime($data["activity_release_time"]);
        }

        //当前字段非必填
        if (isset($data["limit_num"]) && $data["limit_num"]) {
            $entity->setLimitNum($data["limit_num"]);
        }
        if (isset($data["item_type"]) && $data["item_type"]) {
            $entity->setItemType($data["item_type"]);
        }

        if (isset($data['sort'])) {
            $entity->setSort($data['sort']);
        }

        if (isset($data["is_show"])) {
            $entity->setIsShow($data["is_show"]);
        }

        if (isset($data["item_spec_desc"]) && $data["item_spec_desc"]) {
            $entity->setItemSpecDesc($data["item_spec_desc"]);
        }

        if (isset($data["seckill_type"]) && $data["seckill_type"]) {
            $entity->setSeckillType($data["seckill_type"]);
        }

        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["disabled"]) && $data["disabled"]) {
            $entity->setDisabled($data["disabled"]);
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
        $result = [
            'seckill_id' => $entity->getSeckillId(),
            'item_id' => $entity->getItemId(),
            'company_id' => $entity->getCompanyId(),
            'seckill_type' => $entity->getSeckillType(),
            'item_title' => $entity->getItemTitle(),
            'activity_price' => $entity->getActivityPrice(),
            'activity_store' => $entity->getActivityStore(),
            'activity_start_time' => $entity->getActivityStartTime(),
            'activity_end_time' => $entity->getActivityEndTime(),
            'activity_release_time' => $entity->getActivityReleaseTime(),
            'sales_store' => $entity->getSalesStore(),
            'limit_num' => $entity->getLimitNum(),
            'item_type' => $entity->getItemType(),
            'item_pic' => $entity->getItemPic(),
            'sort' => $entity->getSort(),
            'is_show' => $entity->getIsShow(),
            'item_spec_desc' => $entity->getItemSpecDesc(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'disabled' => $entity->getDisabled(),
        ];
        $nowTime = time();
        if ($nowTime >= $result['activity_end_time']) {
            $result['status'] = 'it_has_ended';    //已结束
        } elseif ($nowTime >= $result['activity_start_time'] && $nowTime < $result['activity_end_time']) {
            $result['status'] = 'in_sale';         //售卖中
            $result['last_seconds'] = ($result['activity_end_time'] - $nowTime) > 0 ? ($result['activity_end_time'] - $nowTime) : 0;
        } elseif ($nowTime >= $result['activity_release_time'] && $nowTime < $result['activity_start_time']) {
            $result['status'] = 'in_the_notice';   //预览中
            $result['last_seconds'] = ($result['activity_start_time'] - $nowTime) > 0 ? ($result['activity_start_time'] - $nowTime) : 0;
        } elseif ($nowTime < $result['activity_release_time']) {
            $result['status'] = 'waiting';   //等待中
        }
        $result['created_date'] = date('Y-m-d H:i:s', $result['created']);
        $result['updated_date'] = date('Y-m-d H:i:s', $result['updated']);
        $this->activityData[$result['seckill_id']] = [
            'seckill_id' => $result['seckill_id'],
            'activity_price' => $result['activity_price'],
            'activity_store' => $result['activity_store'],
            'activity_start_time' => $result['activity_start_time'],
            'activity_end_time' => $result['activity_end_time'],
            'activity_release_time' => $result['activity_release_time'],
            'status' => $result['status'],
            'last_seconds' => $result['last_seconds'] ?? 0,
        ];
        return $result;
    }
}
