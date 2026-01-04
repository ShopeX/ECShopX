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

namespace PointsmallBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use PointsmallBundle\Entities\PointsmallItems;
use Doctrine\Common\Collections\Criteria;

class PointsmallItemsRepository extends EntityRepository
{
    /**
     * 当前表名称
     */
    public $table = 'pointsmall_items';

    public $cols = [
        'item_id', 'item_type', 'consume_type', 'is_show_specimg','store', 'barcode', 'sales', 'approve_status', 'cost_price', 'point', 'goods_id', 'brand_id',
        'consume_type', 'item_name', 'item_unit', 'item_bn', 'brief', 'price', 'market_price', 'special_type', 'goods_function', 'goods_series', 'volume',
        'goods_color', 'goods_brand', 'item_address_province', 'item_address_city', 'regions_id', 'brand_logo', 'sort', 'templates_id', 'is_default', 'nospec', 'default_item_id', 'pics',
        'company_id', 'enable_agreement', 'date_type', 'item_category', 'weight', 'begin_date', 'end_date', 'fixed_term','tax_rate', 'created', 'updated', 'video_type', 'videos', 'video_pic_url', 'purchase_agreement',
        'intro', 'audit_status', 'audit_reason', 'crossborder_tax_rate','origincountry_id','type', 'pay_class'
    ];

    /**
     * 添加商品
     */
    public function create($params)
    {
        $itemsEnt = new PointsmallItems();

        $itemsEnt = $this->setColumnNamesData($itemsEnt, $params);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        $result = $this->getColumnNamesData($itemsEnt, $this->cols);
        return $result;
    }

    public function updateSort($itemId, $sort)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setSort($sort);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();
        $result = [
            'item_id' => $itemsEnt->getItemId(),
            'item_type' => $itemsEnt->getItemType() ? $itemsEnt->getItemType() : 'services',
            'item_category' => $itemsEnt->getItemCategory(),
            'approve_status' => $itemsEnt->getApproveStatus(),
            'store' => $itemsEnt->getStore(),
            'sales' => $itemsEnt->getSales(),
            'created' => $itemsEnt->getCreated(),
            'updated' => $itemsEnt->getUpdated(),
        ];

        return $result;
    }

    public function updateStore($itemId, $store, $is_log = false)
    {
        if ($is_log) {
            app('log')->info('NormalGoodsStoreUploadService updateStore itemId:' . $itemId . ',store===>' . $store . ',line:' . __LINE__);
        }
        $itemsEnt = $this->find($itemId);

        if (!$itemsEnt) {
            if ($is_log) {
                app('log')->info('NormalGoodsStoreUploadService updateStore itemId:' . $itemId . ',store===>' . $store . ',itemsEnt is null,line:' . __LINE__);
            }
            return true;
        }

        $itemsEnt->setStore($store);
        if ($is_log) {
            app('log')->info('NormalGoodsStoreUploadService updateStore itemId:' . $itemId . ',store===>' . $store . ',line:' . __LINE__);
        }
        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();
        $result = [
            'item_id' => $itemsEnt->getItemId(),
            'item_type' => $itemsEnt->getItemType() ? $itemsEnt->getItemType() : 'services',
            'item_category' => $itemsEnt->getItemCategory(),
            'approve_status' => $itemsEnt->getApproveStatus(),
            'store' => $itemsEnt->getStore(),
            'sales' => $itemsEnt->getSales(),
            'created' => $itemsEnt->getCreated(),
            'updated' => $itemsEnt->getUpdated(),
        ];
        if ($is_log) {
            app('log')->info('NormalGoodsStoreUploadService updateStore itemId:' . $itemId . ',store===>' . $store . '====end====,line:' . __LINE__);
        }
        return $result;
    }

    /**
     * 更新销量
     * @param $itemId 商品id
     * @param $sales 销量
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateSales($itemId, $sales)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setSales((int)$sales + (int)$itemsEnt->getSales());

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush($itemsEnt);

        return true;
    }

    /**
     * 更新运费模板
     * @param $itemId 商品id
     * @param $templates_id 运费模板id
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setTemplatesId($itemId, $templates_id)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setTemplatesId($templates_id);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        return true;
    }

    /**
     * 更新商品分类
     * @param $itemId 商品id
     * @param $category_id 分类id
     * @return array|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function setCategoryId($itemId, $category_id)
    {
        $itemsEnt = $this->find($itemId);
        if (!$itemsEnt) {
            return true;
        }

        $itemsEnt->setItemCategory($category_id);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        return true;
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
            $qb = $qb->set($key, $qb->expr()->literal($val));
        }

        $qb = $this->__filter($filter, $qb);

        return $qb->execute();
    }



    /**
     * 更新多条数数据
     *
     * @param $filter 更新的条件
     * @param $data 更新的内容
     */
    public function updateProfitBy($filter, $profitType, $profitScale)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()
            ->update($this->table, 'i');
        $qb->set('i.profit_type', $qb->expr()->literal($profitType))
            ->set('i.profit_fee', 'FLOOR(i.price * ' . $profitScale . ')');

        $qb = $this->__filter($filter, $qb);

        return $qb->execute();
    }

    private function __filter($filter, $qb)
    {
        foreach ($filter as $field => $value) {
            $list = explode('|', $field);
            if (count($list) > 1) {
                list($v, $k) = $list;
                if ($k == 'contains') {
                    $k = 'like';
                }
                if ($k == 'like') {
                    $value = '%' . $value . '%';
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

    public function deleteBy($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->delete($this->table);

        $qb = $this->__filter($filter, $qb);
        return $qb->execute();
    }

    /**
     * 更新商品信息
     */
    public function update($item_id, $params)
    {
        $itemsEnt = $this->find($item_id);

        $itemsEnt = $this->setColumnNamesData($itemsEnt, $params);

        $em = $this->getEntityManager();
        $em->persist($itemsEnt);
        $em->flush();

        $result = $this->getColumnNamesData($itemsEnt);
        return $result;
    }

    /**
     * 删除商品
     */
    public function delete($item_id)
    {
        $delItemsEntity = $this->find($item_id);
        if (!$delItemsEntity) {
            return true;
        }
        $this->getEntityManager()->remove($delItemsEntity);

        return $this->getEntityManager()->flush($delItemsEntity);
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
     * 获取会员商品详细信息
     */
    public function get($item_id)
    {
        $itemsEnt = $this->find($item_id);
        if (!$itemsEnt) {
            return [];
        }

        $result = $this->getColumnNamesData($itemsEnt);
        return $result;
    }

    public function count($filter)
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
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
     * 指定条件，获取最多的商品所属catid
     */
    public function countItemsMainCatIdBy($filter)
    {
        if (isset($filter['distributor_id'])) {
            unset($filter['distributor_id']);
        }
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('count("item_id") as _count, item_category')->from($this->table);

        $qb = $this->__filter($filter, $qb);

        $qb->orderBy('_count', 'desc');
        $qb->groupBy('item_category');

        $lists = $qb->execute()->fetchAll();
        return $lists;
    }

    /**
     * 指定条件，获取所有的品牌id
     */
    public function getBrandIds($filter)
    {
        unset($filter['brand_id']);
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select('DISTINCT brand_id')->from($this->table);

        $qb = $this->__filter($filter, $qb);

        $lists = $qb->execute()->fetchAll();

        return $lists;
    }

    /**
     * 获取商品列表
     */
    public function list($filter, $orderBy = [], $pageSize = 100, $page = 1, $columns = null)
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
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
        $res['total_count'] = intval($total);

        $newItemsList = [];
        if ($res['total_count']) {
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }

            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                                    ->setMaxResults($pageSize);
            }
            $list = $this->matching($criteria);
            if (!$columns) {
                $columns = $this->cols;
            }
            foreach ($list as $v) {
                $newItemsList[] = $this->getColumnNamesData($v, $columns, ['intro', 'purchase_agreement']);
            }
        }
        $res['list'] = $newItemsList;
        return $res;
    }

    /**
     * 获取商品列表
     */
    public function listCopy($filter, $orderBy = [], $pageSize = 100, $page = 1, $columns = null)
    {
        $criteria = Criteria::create();
        if ($filter) {
            foreach ($filter as $field => $value) {
                $list = explode('|', $field);
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
        $res['total_count'] = intval($total);

        $newItemsList = [];
        if ($res['total_count']) {
            if ($orderBy) {
                $criteria = $criteria->orderBy($orderBy);
            }

            if ($pageSize > 0) {
                $criteria = $criteria->setFirstResult($pageSize * ($page - 1))
                    ->setMaxResults($pageSize);
            }
            $list = $this->matching($criteria);
            if (!$columns) {
                $columns = $this->cols;
            }
            foreach ($list as $v) {
                $newItemsList[] = $this->getColumnNamesData($v, $columns);
            }
        }
        $res['list'] = $newItemsList;
        return $res;
    }

    private function setColumnNamesData($entity, $params)
    {
        foreach ($this->cols as $col) {
            if (isset($params[$col])) {
                $fun = 'set' . str_replace(" ", '', ucwords(str_replace('_', ' ', $col)));
                $entity->$fun($params[$col]);
            }
        }
        return $entity;
    }

    private function getColumnNamesData($entity, $cols = [], $ignore = [])
    {
        if (!$cols) {
            $cols = $this->cols;
        }

        $values = [];
        foreach ($cols as $col) {
            if ($ignore && in_array($col, $ignore)) {
                continue;
            }
            $fun = 'get' . str_replace(" ", '', ucwords(str_replace('_', ' ', $col)));
            $values[$col] = $entity->$fun();
        }
        // 历史原因特使处理
        if (isset($values['intro'])) {
            $intro = json_decode($values['intro'], true);
            $values['intro'] = $intro ? $intro : $values['intro'];
        }
        $values['itemId'] = $values['item_id'];
        $values['consumeType'] = $values['consume_type'] ?? '';
        $values['itemName'] = $values['item_name'] ?? '';
        $values['itemBn'] = $values['item_bn'] ?? '';
        $values['companyId'] = $values['company_id'] ?? '';
        $values['item_main_cat_id'] = $values['item_category'] ?? '';
        $values['nospec'] = (isset($values['nospec']) && ($values['nospec'] === true || $values['nospec'] === 'true' || $values['nospec'] === 1 || $values['nospec'] === '1')) ? true : false;
        return $values;
    }

    /**
     * 简单的更新操作，不支持大于 小于等条件更新
     */
    public function simpleUpdateBy($filter, $data)
    {
        $conn = app('registry')->getConnection('default');
        return $conn->update($this->table, $data, $filter);
    }

    //获取指定条件的所有商品列表，可指定字段
    public function getItemsLists($filter, $cols = 'item_id, default_item_id')
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->select($cols)->from($this->table);
        $qb = $this->__filter($filter, $qb);
        $lists = $qb->execute()->fetchAll();
        return $lists;
    }

    /**
    * 统计数量
    */
    public function sum($filter, $field)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum('.$field.')')
            ->from($this->table);
        if ($filter) {
            $this->__filter($filter, $qb);
        }
        $sum = $qb->execute()->fetchColumn();
        return $sum;
    }
}
