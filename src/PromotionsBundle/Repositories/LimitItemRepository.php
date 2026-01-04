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
use PromotionsBundle\Entities\LimitItemPromotions;

use Dingo\Api\Exception\ResourceException;

class LimitItemRepository extends EntityRepository
{
    public $table = "promotions_limit_item";
    public $cols = ['limit_id','distributor_id','item_id','limit_num','company_id','item_name','pics','price','item_spec_desc','start_time','end_time','created','updated',];

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new LimitItemPromotions();
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
        $conn = app("registry")->getConnection("default");
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $qb = $qb->set($key, $val);
        }

        $qb = $this->_filter($filter, $qb);

        return $qb->execute();
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
     * 筛选条件格式化
     *
     * @param $filter
     * @param $qb
     */
    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                    $value = '%'.$value.'%';
                }
                $qb = $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                array_walk($value, function (&$colVal) use ($qb) {
                    $colVal = $qb->expr()->literal($colVal);
                });
                $qb = $qb->andWhere($qb->expr()->in($field, $value));
            } else {
                $qb = $qb->andWhere($qb->expr()->eq($field, $qb->expr()->literal($value)));
            }
        }
        return $qb;
    }

    /**
     * 根据条件获取列表数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $cols = '*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $result['total_count'] = $this->count($filter);
        if ($result['total_count'] > 0) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
            $qb = $this->_filter($filter, $qb);
            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $qb->orderBy($filed, $val);
                }
            }
            if ($pageSize > 0) {
                $qb->setFirstResult(($page - 1) * $pageSize)
                  ->setMaxResults($pageSize);
            }
            $lists = $qb->execute()->fetchAll();
        }
        $result['list'] = $lists ?? [];
        return $result;
    }



    /**
     * 获取时间段内是否有拼团活动
     *
     * @param $filter 更新的条件
     */
    public function getIsHave($itemId, $begin_time, $end_time, $groupId = '')
    {
        $criteria = Criteria::create();
        $itemId = (array)$itemId;
        $criteria = $criteria->andWhere(Criteria::expr()->in('item_id', $itemId));
        if ($groupId) {
            $criteria = $criteria->andWhere(Criteria::expr()->neq('limit_id', $groupId));
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
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
             ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $count = $qb->execute()->fetchColumn();
        return intval($count);
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["limit_id"]) && $data["limit_id"]) {
            $entity->setLimitId($data["limit_id"]);
        }
        if (isset($data["limit_num"])) {
            $entity->setLimitNum($data["limit_num"]);
        }
        if (isset($data["item_id"]) && $data["item_id"]) {
            $entity->setItemId($data["item_id"]);
        }
        if (isset($data["item_type"]) && $data["item_type"]) {
            $entity->setItemType($data["item_type"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["distributor_id"]) && $data["distributor_id"]) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        if (isset($data["item_name"])) {
            $entity->setItemName($data["item_name"]);
        }
        if (isset($data["pics"])) {
            $entity->setPics($data["pics"]);
        }
        if (isset($data["price"])) {
            $entity->setPrice($data["price"]);
        }
        //当前字段非必填
        if (isset($data["item_spec_desc"])) {
            $entity->setItemSpecDesc($data["item_spec_desc"]);
        }
        if (isset($data["start_time"]) && $data["start_time"]) {
            $entity->setStartTime($data["start_time"]);
        }
        if (isset($data["end_time"]) && $data["end_time"]) {
            $entity->setEndTime($data["end_time"]);
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
            'limit_id' => $entity->getLimitId(),
            'limit_num' => $entity->getLimitNum(),
            'item_id' => $entity->getItemId(),
            'item_type' => $entity->getItemType(),
            'company_id' => $entity->getCompanyId(),
            'distributor_id' => $entity->getDistributorId(),
            'item_name' => $entity->getItemName(),
            'pics' => $entity->getPics(),
            'price' => $entity->getPrice(),
            'item_spec_desc' => $entity->getItemSpecDesc(),
            'start_time' => $entity->getStartTime(),
            'end_time' => $entity->getEndTime(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }

    /**
     * 获取未结束的活动
     * @param $itemId
     * @param $end_time 结束时间
     * @return array
     */
    public function getNotFinished($itemId, $end_time)
    {
        $criteria = Criteria::create();
        $itemId = (array)$itemId;
        $criteria = $criteria->andWhere(Criteria::expr()->in('item_id', $itemId));
        $criteria = $criteria->andWhere(Criteria::expr()->gte('end_time', $end_time));

        $entityList = $this->matching($criteria);
        $lists = [];
        foreach ($entityList as $entity) {
            $lists[] = $this->getColumnNamesData($entity);
        }

        return $lists;
    }
}
