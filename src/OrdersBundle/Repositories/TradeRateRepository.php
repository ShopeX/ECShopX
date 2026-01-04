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
use OrdersBundle\Entities\TradeRate;

use Dingo\Api\Exception\ResourceException;

class TradeRateRepository extends EntityRepository
{
    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        // Powered by ShopEx EcShopX
        $entity = new TradeRate();
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
     * 设置entity数据，用于插入和更新操作
     *
     * @param $entity
     * @param $data
     */
    private function setColumnNamesData($entity, $data)
    {
        if (isset($data["rate_id"]) && $data["rate_id"]) {
            $entity->setRateId($data["rate_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }

        //当前字段非必填
        if (isset($data["item_id"]) && $data["item_id"]) {
            $entity->setItemId($data["item_id"]);
        }
        //当前字段非必填
        if (isset($data["goods_id"]) && $data["goods_id"]) {
            $entity->setGoodsId($data["goods_id"]);
        }
        //当前字段非必填
        if (isset($data["order_id"]) && $data["order_id"]) {
            $entity->setOrderId($data["order_id"]);
        }
        //当前字段非必填
        if (isset($data["user_id"]) && $data["user_id"]) {
            $entity->setUserId($data["user_id"]);
        }

        //当前字段非必填
        if (isset($data["rate_pic"]) && $data["rate_pic"]) {
            $entity->setRatePic($data["rate_pic"]);
        }
        //当前字段非必填
        if (isset($data["rate_pic_num"]) && $data["rate_pic_num"]) {
            $entity->setRatePicNum($data["rate_pic_num"]);
        }
        //当前字段非必填
        if (isset($data["content"]) && $data["content"]) {
            $entity->setContent($data["content"]);
        }
        //当前字段非必填
        if (isset($data["content_len"]) && $data["content_len"]) {
            $entity->setContentLen($data["content_len"]);
        }
        if (isset($data["is_reply"])) {
            $entity->setIsReply($data["is_reply"]);
        }
        if (isset($data["disabled"])) {
            $entity->setDisabled($data["disabled"]);
        }
        if (isset($data["anonymous"])) {
            $entity->setAnonymous($data["anonymous"]);
        }
        if (isset($data["star"]) && $data["star"]) {
            $entity->setStar($data["star"]);
        }

        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        //当前字段非必填
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }

        if (isset($data['unionid']) && $data['unionid']) {
            $entity->setUnionid($data['unionid']);
        }
        if (isset($data['item_spec_desc']) && $data['item_spec_desc']) {
            $entity->setItemSpecDesc($data['item_spec_desc']);
        }

        if (isset($data['order_type']) && $data['order_type']) {
            $entity->setOrderType($data['order_type']);
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
            'rate_id' => $entity->getRateId(),
            'company_id' => $entity->getCompanyId(),
            'item_id' => $entity->getItemId(),
            'goods_id' => $entity->getGoodsId(),
            'order_id' => $entity->getOrderId(),
            'user_id' => $entity->getUserId(),
            'rate_pic' => $entity->getRatePic(),
            'rate_pic_num' => $entity->getRatePicNum(),
            'content' => $entity->getContent(),
            'content_len' => $entity->getContentLen(),
            'is_reply' => $entity->getIsReply(),
            'disabled' => $entity->getDisabled(),
            'anonymous' => $entity->getAnonymous(),
            'star' => $entity->getStar(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'unionid' => $entity->getUnionid(),
            'item_spec_desc' => $entity->getItemSpecDesc(),
            'order_type' => $entity->getOrderType(),
        ];
    }
    /**
     * 查询评价列表
     *
     * @param $filter 更新的条件
     */
    public function getListsByDistributor($filter, $page = 1, $pageSize = 100, $orderBy = array())
    {
        $cols = 't.*,o.distributor_id';
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select($cols)
        ->from('trade_rate', 't')
        ->leftJoin('t', 'orders_normal_orders', 'o', 't.order_id = o.order_id');
        if (isset($filter['order_type'])) {
            $filter['t.order_type'] = $filter['order_type'];
            unset($filter['order_type']);
        }
        if (isset($filter['company_id'])) {
            $filter['t.company_id'] = $filter['company_id'];
            unset($filter['company_id']);
        }
        if (isset($filter['order_id'])) {
            $filter['t.order_id'] = $filter['order_id'];
            unset($filter['order_id']);
        }
        foreach ($filter as $field => $value) {
            $list = explode("|", $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                $criteria = $criteria->andWhere($criteria->expr()->$k($v, $criteria->expr()->literal($value)));
                continue;
            } elseif (is_array($value)) {
                $criteria = $criteria->andWhere($criteria->expr()->in($field, $value));
            } else {
                $criteria = $criteria->andWhere($criteria->expr()->eq($field, $criteria->expr()->literal($value)));
            }
        }
        $res['total_count'] = count($criteria->select($cols)->execute()->fetchAll());
        $lists = [];
        if ($res["total_count"]) {
            if ($orderBy) {
                foreach ($orderBy as $filed => $val) {
                    $criteria->addOrderBy($filed, $val);
                }
            }
            $criteria->setFirstResult($pageSize * ($page - 1))
                ->setMaxResults($pageSize);
            $lists = $criteria->select($cols)->execute()->fetchAll();
        }

        $res["list"] = $lists;
        return $res;
    }

    /**
     * 根据店铺id去查询到对应店铺下面的平均分
     * @param int $companyId 公司id
     * @param array $distributorIds 店铺id
     * @param int $defaultStar 默认分
     * @return array
     */
    public function getAvgStarByDistributorIds(int $companyId, array $distributorIds, int $defaultStar = 5): array
    {
        $orderTableAlias = "orders";
        $tradeRateTableAlias = "trade_rate";
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        return $criteria->select(sprintf("%s.distributor_id, AVG(COALESCE(%s.star, %d)) as avg_star", $orderTableAlias, $tradeRateTableAlias, $defaultStar))
            ->from("orders_normal_orders", $orderTableAlias)
            ->leftJoin($orderTableAlias, "trade_rate", $tradeRateTableAlias, sprintf("%s.order_id = %s.order_id AND %s.company_id = %s.company_id", $orderTableAlias, $tradeRateTableAlias, $orderTableAlias, $tradeRateTableAlias))
            ->andWhere($criteria->expr()->eq(sprintf("%s.company_id", $orderTableAlias), $companyId))
            ->andWhere($criteria->expr()->in(sprintf("%s.distributor_id", $orderTableAlias), $distributorIds))
            ->groupBy(sprintf("%s.distributor_id", $orderTableAlias))
            ->execute()
            ->fetchAll();
    }
}
