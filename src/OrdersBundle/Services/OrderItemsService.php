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

namespace OrdersBundle\Services;

use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Entities\NormalOrders;

class OrderItemsService
{
    /**
     * @var \OrdersBundle\Repositories\NormalOrdersItemsRepository
     */
    public $repository;

    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
    }

    /**
     * 获取导出财务销售列表，不分页
     * @param array filter:条件
     * @return array 列表数据
     */
    public function exportFinancialSalesreport($filter)
    {
        // Powered by ShopEx EcShopX
        $filter = $this->_filter($filter);
        $lists = $this->repository->getList($filter);

        // 查询订单的运费
        $order_ids = array_column($lists['list'], 'order_id');
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $orderLists = $normalOrdersRepository->getList(['order_id' => $order_ids], 0, -1, null, 'order_id,freight_fee,create_time');

        $_orderLists = array_column($orderLists, null, 'order_id');
        // 查询商品的品牌
        $item_ids = array_column($lists['list'], 'item_id');
        $brandLists = $this->getBrand($item_ids);
        $categoryLists = $this->getMainCategory($item_ids);
        $_list = [];
        foreach ($lists['list'] as $key => $orderItems) {
            $order_id = $orderItems['order_id'];
            $item_id = $orderItems['item_id'];
            $_brand_key = $brandLists[$item_id]['attribute_id'] ?? 0;
            $_cat_key = $categoryLists[$item_id]['item_category'] ?? 0;
            $_key = $_brand_key.'_'.$_cat_key.'_'.$orderItems['delivery_time'];
            $_list[$order_id][$_key][] = [
                'order_id' => $orderItems['order_id'],
                'barnd' => $brandLists[$item_id]['attribute_name'] ?? '',
                'main_category' => $categoryLists[$item_id]['category_name'] ?? '',
                'create_time' => $orderItems['create_time'],
                'delivery_time' => $orderItems['delivery_time'],
                'item_fee' => $orderItems['item_fee'],
                'discount_fee' => $orderItems['discount_fee'],
                'total_fee' => $orderItems['total_fee'],
            ];
        }
        foreach ($_orderLists as $order_id => $value) {
            $_list[$order_id]['freight'] = [
                'order_id' => $value['order_id'],
                'barnd' => '',
                'main_category' => '运费',
                'create_time' => $_orderLists[$order_id]['create_time'],
                'delivery_time' => '',
                'item_fee' => '',
                'discount_fee' => '',
                'total_fee' => $_orderLists[$order_id]['freight_fee'],
            ];
        }
        $_result = [];
        foreach ($_list as $order_id => $items) {
            foreach ($items as $key => $value) {
                if ($key != 'freight') {
                    $item_fee = array_sum(array_column($value, 'item_fee'));
                    $discount_fee = array_sum(array_column($value, 'discount_fee'));
                    $total_fee = array_sum(array_column($value, 'total_fee'));
                    $_result[] = [
                        'order_id' => $value[0]['order_id'],
                        'barnd' => $value[0]['barnd'],
                        'main_category' => $value[0]['main_category'],
                        'create_time' => $value[0]['create_time'],
                        'delivery_time' => $value[0]['delivery_time'],
                        'item_fee' => $item_fee,
                        'discount_fee' => $discount_fee,
                        'total_fee' => $total_fee,
                    ];
                } else {
                    $_result[] = $value;
                }
            }
        }


        $result['list'] = $_result;
        return $result;
    }

    /**
    * 总条数
    */
    public function salesReportCount($filter)
    {
        $filter = $this->_filter($filter);
        return $this->count($filter);
    }

    /**
    * 根据item_id查询品牌
    * @param array item_ids
    * @return array 品牌列表
    */
    public function getBrand($item_ids)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        // 查询所有商品的default_item_id
        $criteria->select('item_id', 'default_item_id')
            ->from('items');
        $criteria->where($criteria->expr()->in('item_id', $item_ids));
        $item_lists = $criteria->execute()->fetchAll();
        $default_item_ids = array_column($item_lists, 'default_item_id');

        // 查询default_item_id下的品牌
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('rel_attr.item_id', 'attr.attribute_name', 'attr.attribute_id')
            ->from('items_attributes', 'attr')
            ->leftJoin('attr', 'items_rel_attributes', 'rel_attr', 'attr.attribute_id = rel_attr.attribute_id');
        $criteria->where($criteria->expr()->in('rel_attr.item_id', $default_item_ids));
        $criteria->andWhere($criteria->expr()->eq('attr.attribute_type', $criteria->expr()->literal('brand')));
        $lists = $criteria->execute()->fetchAll();

        $_lists = array_column($lists, null, 'item_id');
        $_item_lists = [];
        foreach ($item_lists as $key => $value) {
            $_item_lists[$value['item_id']] = [
                'attribute_name' => $_lists[$value['default_item_id']]['attribute_name'] ?? '',
                'attribute_id' => $_lists[$value['default_item_id']]['attribute_id'] ?? '',
            ];
        }

        return $_item_lists;
    }

    /**
    * 根据item_id查询主类目
    * @param array item_ids
    * @return array 主类目列表
    */
    public function getMainCategory($item_ids)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('i.item_id', 'cat.category_name', 'i.item_category')
            ->from('items', 'i')
            ->leftJoin('i', 'items_category', 'cat', 'i.item_category = cat.category_id');

        $criteria->where($criteria->expr()->in('i.item_id', $item_ids));
        $criteria->andWhere($criteria->expr()->eq('cat.is_main_category', $criteria->expr()->literal('1')));
        $lists = $criteria->execute()->fetchAll();
        $_lists = array_column($lists, null, 'item_id');
        return $_lists;
    }

    /**
    * 条件转换
    */
    private function _filter($filter)
    {
        if (isset($filter['brand'])) {
            $conn = app('registry')->getConnection('default');
            $criteria = $conn->createQueryBuilder();
            $criteria->select('rel_attr.item_id')
                ->from('items_attributes', 'attr')
                ->leftJoin('attr', 'items_rel_attributes', 'rel_attr', 'attr.attribute_id = rel_attr.attribute_id');

            $criteria->where($criteria->expr()->eq('attr.company_id', $criteria->expr()->literal($filter['company_id'])));
            $criteria->andWhere($criteria->expr()->eq('attr.attribute_name', $criteria->expr()->literal($filter['brand'])));
            $criteria->andWhere($criteria->expr()->eq('attr.attribute_type', $criteria->expr()->literal('brand')));
            $lists = $criteria->execute()->fetchAll();
            $item_ids = array_column($lists, 'item_id');
            if ($item_ids) {
                $filter['item_id'] = $item_ids;
            } else {
                $filter['item_id'] = false;
            }
            unset($filter['brand']);
        }
        if (isset($filter['main_category'])) {
            $conn = app('registry')->getConnection('default');
            $criteria = $conn->createQueryBuilder();
            $criteria->select('i.item_id')
                ->from('items', 'i')
                ->leftJoin('i', 'items_category', 'cat', 'i.item_category = cat.category_id');

            $criteria->where($criteria->expr()->eq('i.company_id', $criteria->expr()->literal($filter['company_id'])));
            $criteria->andWhere($criteria->expr()->eq('cat.category_name', $criteria->expr()->literal($filter['main_category'])));
            $criteria->andWhere($criteria->expr()->eq('cat.is_main_category', $criteria->expr()->literal('1')));
            $lists = $criteria->execute()->fetchAll();
            $item_ids = array_column($lists, 'item_id');
            if (isset($filter['item_id']) && $filter['item_id']) {
                if (!$item_ids) {
                    $filter['item_id'] = false;
                } else {
                    $filter['item_id'] = array_intersect($filter['item_id'], $item_ids);
                }
            } else {
                if (!$item_ids) {
                    $filter['item_id'] = false;
                } else {
                    $filter['item_id'] = $item_ids;
                }
            }
            unset($filter['main_category']);
        }
        return $filter;
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->repository->$method(...$parameters);
    }
}
