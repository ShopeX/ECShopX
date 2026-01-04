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
use OrdersBundle\Entities\Cart;

use Dingo\Api\Exception\ResourceException;

class CartRepository extends EntityRepository
{
    public $table = 'orders_cart';

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new Cart();
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
        // ShopEx framework
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            throw new ResourceException("未查询到更新数据");
        }

        if (!isset($data['isAccumulate']) || !$data['isAccumulate']) {
            //购物车数量为替换记录
            $entity = $this->setColumnNamesData($entity, $data, true);
        } else {
            //购物车数量为累积记录
            $entity = $this->setColumnNamesData($entity, $data, false);
        }


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
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->update($this->table);
        foreach ($data as $key => $val) {
            $val = (in_array($key, ['is_checked', 'is_plus_buy']) && $val === true) ? 1 : 0;
            $qb = $qb->set($key, $val);
        }

        $qb = $this->_filter($filter, $qb);
        return $qb->execute();
    }

    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if (in_array($k, ['like', 'notlike'])) {
                    $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal('%'.$value.'%')));
                } elseif (in_array($k, ['in', 'notIn'])) {
                    array_walk($value, function (&$colVal) use ($qb) {
                        $colVal = $qb->expr()->literal($colVal);
                    });
                    $qb->andWhere($qb->expr()->$k($v, $value));
                } else {
                    $qb->andWhere($qb->expr()->$k($v, $qb->expr()->literal($value)));
                }
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
    public function lists($filter, $page = 1, $pageSize = 100, $orderBy = array('cart_id' => 'DESC'))
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
            $criteria->setFirstResult($pageSize * ($page - 1))
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
     * 购物车统计
     */
    public function countCart($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*) as cart_count, sum(`num`) as item_count')
           ->from($this->table);
        if ($filter) {
            $this->_filter($filter, $qb);
        }
        $result = $qb->execute()->fetchAll();
        return [
            'cart_count' => isset($result[0]) && $result[0]['cart_count'] ? intval($result[0]['cart_count']) : 0,
            'item_count' => isset($result[0]) && $result[0]['item_count'] ? intval($result[0]['item_count']) : 0,
        ];
    }

    /**
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data, $isReplace = true)
    {
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["user_id"]) && $data["user_id"]) {
            $entity->setUserId($data["user_id"]);
        }
        //当前字段非必填
        if (isset($data["user_ident"]) && $data["user_ident"]) {
            $entity->setUserIdent($data["user_ident"]);
        }
        if (isset($data["shop_type"]) && $data["shop_type"]) {
            $entity->setShopType($data["shop_type"]);
        }
        if (isset($data["shop_id"])) {
            $entity->setShopId($data["shop_id"]);
        }
        //当前字段非必填
        if (isset($data["activity_type"]) && $data["activity_type"]) {
            $entity->setActivityType($data["activity_type"]);
        }
        //当前字段非必填
        if (isset($data["activity_id"]) && $data["activity_id"]) {
            $entity->setActivityId($data["activity_id"]);
        }
        if (isset($data["item_type"]) && $data["item_type"]) {
            $entity->setItemType($data["item_type"]);
        }
        if (isset($data["item_id"]) && $data["item_id"]) {
            $entity->setItemId($data["item_id"]);
        }
        if (isset($data["items_id"]) && $data["items_id"]) {
            $entity->setItemsId(implode(',', $data["items_id"]));
        }
        if (isset($data["item_name"]) && $data["item_name"]) {
            $entity->setItemName($data["item_name"]);
        }
        if (isset($data["pics"]) && $data["pics"]) {
            $entity->setPics($data["pics"]);
        }
        if (isset($data["price"])) {
            $entity->setPrice($data["price"]);
        }
        if (isset($data["point"]) && $data["point"]) {
            $entity->setPoint($data["point"]);
        }
        if (isset($data["num"]) && intval($data["num"]) > 0) {
            $num = $isReplace ? $data["num"] : ($entity->getNum() + $data["num"]);
            $entity->setNum($num);
        } elseif (isset($data["num"]) && intval($data["num"]) == 0) {
            $entity->setNum(intval($data["num"]));
        }

        if (isset($data["wxa_appid"]) && $data["wxa_appid"]) {
            $entity->setWxaAppid($data["wxa_appid"]);
        }
        if (isset($data["is_checked"])) {
            $entity->setIsChecked($data["is_checked"]);
        }
        if (isset($data["is_plus_buy"])) {
            $entity->setIsPlusBuy($data["is_plus_buy"]);
        }

        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        //当前字段非必填
        if (isset($data["marketing_type"]) && $data["marketing_type"]) {
            $entity->setMarketingType($data["marketing_type"]);
        }
        //当前字段非必填
        if (isset($data["marketing_id"]) && $data["marketing_id"]) {
            $entity->setMarketingId($data["marketing_id"]);
        }
        if (isset($data['promoter_user_id'])) {
            $entity->setPromoterUserId($data['promoter_user_id']);
        }
        if (isset($data['source_type'])) {
            $entity->setSourceType($data['source_type']);
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
            'cart_id' => $entity->getCartId(),
            'company_id' => $entity->getCompanyId(),
            'user_id' => $entity->getUserId(),
            'promoter_user_id' => $entity->getPromoterUserId(),
            'source_type' => $entity->getSourceType(),
            'user_ident' => $entity->getUserIdent(),
            'shop_type' => $entity->getShopType(),
            'shop_id' => $entity->getShopId(),
            'activity_type' => $entity->getActivityType(),
            'activity_id' => $entity->getActivityId(),
            'marketing_type' => $entity->getMarketingType(),
            'marketing_id' => $entity->getMarketingId(),
            'item_type' => $entity->getItemType(),
            'item_id' => $entity->getItemId(),
            'items_id' => $entity->getItemsId() ? explode(',', $entity->getItemsId()) : [],
            'item_name' => $entity->getItemName(),
            'pics' => $entity->getPics(),
            'price' => $entity->getPrice(),
            'num' => $entity->getNum(),
            'wxa_appid' => $entity->getWxaAppid(),
            'is_checked' => $entity->getIsChecked(),
            'is_plus_buy' => $entity->getIsPlusBuy(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
        ];
    }
}
