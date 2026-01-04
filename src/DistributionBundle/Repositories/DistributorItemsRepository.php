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

namespace DistributionBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use DistributionBundle\Entities\DistributorItems;

class DistributorItemsRepository extends EntityRepository
{
    public $table = 'distribution_distributor_items';

    /**
     * 新增
     *
     * @param array $data
     */
    public function create($data)
    {
        $entity = new DistributorItems();
        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $result = $this->getColumnNamesData($entity);

        $this->checkGoodsCanSales([$result]);

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
        // Built with ShopEx Framework
        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return false;
        }

        $entity = $this->setColumnNamesData($entity, $data);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $result = $this->getColumnNamesData($entity);

        if (isset($data['is_can_sale'])) {
            $this->checkGoodsCanSales([$result]);
        }

        return $result;
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
            return true;
        }

        $em = $this->getEntityManager();
        $result = [];
        foreach ($entityList as $entityProp) {
            $entityProp = $this->setColumnNamesData($entityProp, $data);
            $em->persist($entityProp);
            $em->flush();
            $result[] = $this->getColumnNamesData($entityProp);
        }

        if (isset($data['is_can_sale'])) {
            $this->checkGoodsCanSales($result);
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
            throw new \Exception(trans('DistributionBundle/Repositories.delete_data_not_exist'));
        }
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();

        $this->checkGoodsCanSales([$this->getColumnNamesData($entity)]);

        return true;
    }

    /**
     * 根据条件删除指定数据
     *
     * @param $filter 删除的条件
     */
    public function deleteBy($filter)
    {
        $conn = app("registry")->getConnection("default");

        $qb = $conn->createQueryBuilder()->select('distinct goods_id,distributor_id')->from($this->table);
        $this->_filter($filter, $qb);
        $list = $qb->execute()->fetchAll();

        $qb = $conn->createQueryBuilder()->delete($this->table);

        $qb = $this->_filter($filter, $qb);
        $qb->execute();

        $this->checkGoodsCanSales($list);

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

    private function _filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
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
     * 获取到对应的店铺ID
     */
    public function getDistributorIdBy($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('distributor_id')->from($this->table);

        $qb = $this->_filter($filter, $qb);

        $qb->groupBy('distributor_id');
        $lists = $qb->execute()->fetchAll();
        if ($lists) {
            return array_column($lists, 'distributor_id');
        }
        return [];
    }

    public function getList($filter, $columns = "*", $page = 1, $pageSize = 100, $orderBy = [])
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($columns)->from($this->table);

        $qb = $this->_filter($filter, $qb);

        if ($pageSize != -1) {
            $qb->setFirstResult($pageSize * ($page - 1))->setMaxResults($pageSize);
        }

        if ($orderBy) {
            foreach ($orderBy as $key => $value) {
                $qb->addOrderBy($key, $value);
            }
        }

        $lists = $qb->execute()->fetchAll();

        return $lists;
    }

    /**
     * 根据条件获取单条数据
     *
     * @param $filter 更新的条件
     */
    public function lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 100, $page = 1)
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
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
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
     * 更新销量
     * @param $itemId 商品id
     * @param $sales 销量
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function incrSales($distributorId, $itemId, $sales)
    {
        $filter['distributor_id'] = $distributorId;
        $filter['item_id'] = $itemId;

        $entity = $this->findOneBy($filter);
        if (!$entity) {
            return true;
        }

        $entity->setSales((int)$sales + (int)$entity->getSales());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush($entity);

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
        if (isset($data["distributor_id"]) && $data["distributor_id"]) {
            $entity->setDistributorId($data["distributor_id"]);
        }
        if (isset($data["company_id"]) && $data["company_id"]) {
            $entity->setCompanyId($data["company_id"]);
        }
        if (isset($data["item_id"]) && $data["item_id"]) {
            $entity->setItemId($data["item_id"]);
        }
        if (isset($data["created"]) && $data["created"]) {
            $entity->setCreated($data["created"]);
        }
        if (isset($data["updated"]) && $data["updated"]) {
            $entity->setUpdated($data["updated"]);
        }
        if (isset($data["is_show"])) {
            $entity->setIsShow($data["is_show"]);
        }
        if (isset($data['shop_id'])) {
            $entity->setShopId($data["shop_id"]);
        }
        if (isset($data['price'])) {
            $entity->setPrice($data["price"]);
        }
        if (isset($data['store'])) {
            $entity->setStore($data["store"]);
        }
        if (isset($data['is_total_store'])) {
            $entity->setIsTotalStore($data["is_total_store"]);
        }
        if (isset($data['is_can_sale'])) {
            $entity->setIsCanSale($data["is_can_sale"]);
        }
        if (isset($data['goods_can_sale'])) {
            $entity->setGoodsCanSale($data["goods_can_sale"]);
        }
        if (isset($data['default_item_id'])) {
            $entity->setDefaultItemId($data["default_item_id"]);
        }
        if (isset($data['goods_id'])) {
            $entity->setGoodsId($data["goods_id"]);
        }
        if (isset($data['is_self_delivery'])) {
            $entity->setIsSelfDelivery($data["is_self_delivery"]);
        }
        if (isset($data['is_express_delivery'])) {
            $entity->setIsExpressDelivery($data["is_express_delivery"]);
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
            'distributor_id' => $entity->getDistributorId(),
            'company_id' => $entity->getCompanyId(),
            'item_id' => $entity->getItemId(),
            'shop_id' => $entity->getShopId(),
            'price' => $entity->getPrice(),
            'store' => $entity->getStore(),
            'is_total_store' => $entity->getIsTotalStore(),
            'goods_can_sale' => $entity->getGoodsCanSale(),
            'is_can_sale' => $entity->getIsCanSale(),
            'goods_id' => $entity->getGoodsId(),
            'is_show' => $entity->getIsShow(),
            'created' => $entity->getCreated(),
            'updated' => $entity->getUpdated(),
            'default_item_id' => $entity->getDefaultItemId(),
            'is_express_delivery' => $entity->getIsExpressDelivery(),
            'is_self_delivery' => $entity->getIsSelfDelivery(),
        ];
    }

    //如果有一个sku上架，将所有sku都设置成上架
    private function checkGoodsCanSales($list)
    {
        $conn = app('registry')->getConnection('default');
        foreach ($list as $row) {
            $updateFilter = [
                'distributor_id' => $row['distributor_id'],
                'goods_id' => $row['goods_id'],
            ];
            $exist = $this->count(array_merge($updateFilter, ['is_can_sale' => true]));
            $qb = $conn->createQueryBuilder()->update($this->table);
            $qb->set('goods_can_sale', $exist ? 1 : 0);
            $this->_filter($updateFilter, $qb);
            $qb->execute();
        }

    }
}
