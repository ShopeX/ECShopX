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

namespace DataCubeBundle\Services;

use DataCubeBundle\Entities\GoodsData;
use DataCubeBundle\Jobs\GoodsStatisticJob;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\Distributor;
use EspierBundle\Services\ExportFileService;

class GoodsDataService
{
    /** @var goodsDataRepository */
    private $goodsDataRepository;

    /**
     * MonitorsService 构造函数.
     */
    public function __construct()
    {
        $this->goodsDataRepository = app('registry')->getManager('default')->getRepository(GoodsData::class);
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
    }

    /**
    * 数据导出。
    *
    * @return void
    */

    public function exportData($filter)
    {
        $title = ['NO','商品编号','分类','商品名称','销量','销售额','实付额'];
        $fileName = date("YmdHis")."goodsData";
        $fileDir = storage_path('csv');
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0777, true);
        }
        $fileArr = $fileDir. "/".$fileName.".csv";
        //$fn = iconv('UTF-8', 'GB2312//IGNORE', $fileArr);
        $fn = $fileArr;
        $fh = fopen($fn, 'w');
        // fwrite($fh, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($fh, $title);
        $orderList = $this->getGoodsDataList($filter);
        foreach ($orderList as $order) {
            fputcsv($fh, $order);
        }
        fclose($fh);
        $exportService = new ExportFileService();
        $result = $exportService->downloadOrderFile($fileName, $fileArr);
        return $result;
    }
    /**
    * 获取数据列表。
    *
    * @return array
    */

    public function getGoodsDataList($filter)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('item_id,sum(sales_count) as quantity,sum(fixed_amount_count) as fix_price,sum(settle_amount_count) as settle_price')
           ->from('datacube_goods_data')
           ->andWhere($qb->expr()->gt('sales_count', 0))
           ->andWhere($qb->expr()->gte('count_date', $qb->expr()->literal($filter['date_start'])))
           ->andWhere($qb->expr()->lte('count_date', $qb->expr()->literal($filter['date_end'])))
           ->groupBy('item_id')
           ->orderBy('sum(sales_count)', 'DESC');
        if (!empty($filter['company_id'])) {
            $qb->andWhere($qb->expr()->eq('company_id', $filter['company_id']));
        }
        if (!empty($filter['merchant_id'])) {
            $qb->andWhere($qb->expr()->eq('merchant_id', $filter['merchant_id']));
        }
        $filter['order_class'] = $filter['order_class'] ?? '';
        $filter['act_id'] = $filter['act_id'] ?? 0;
        if ($filter['order_class']) {
            $qb->andWhere($qb->expr()->eq('order_class', $qb->expr()->literal($filter['order_class'])));
                // ->andWhere($qb->expr()->eq('act_id', $filter['act_id']));
        } else {
            $qb->andWhere($qb->expr()->isNull('order_class'));
                // ->andWhere($qb->expr()->eq('act_id', 0));
        }
        if ($filter['act_id']) {
            if (!is_array($filter['act_id'])) {  
                $act_id = explode(',', $filter['act_id']);
            }
            $qb->andWhere($qb->expr()->in('act_id', $act_id));
        }else {
            $qb->andWhere($qb->expr()->eq('act_id', 0));
        }

        $list = $qb->execute()->fetchAll();
        $total = count($list);
        $quantity = 0;
        $fix_price = 0;
        $settle_price = 0;
        foreach ($list as $k => &$v) {
            $quantity += $v['quantity'];
            $fix_price += $v['fix_price'];
            $settle_price += $v['settle_price'];
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb->select('item_bn,item_name,item_category')
               ->from('items')
               ->where($qb->expr()->eq('item_id', $v['item_id']));
            $item_res = $qb->execute()->fetchAll();
            if (!$item_res) {
                continue;
            }
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb->select('category_name')
               ->from('items_category')
               ->where($qb->expr()->like('path', $qb->expr()->literal('%,'.$item_res[0]['item_category'])));
            $cat_res = $qb->execute()->fetchAll();
            $item[] = [
                  'no' => $k + 1,
                  'sap_code' => $item_res[0]['item_bn'] ?? '',
                  'top_level' => $cat_res[0]['category_name'] ?? '',
                  'product' => isset($item_res[0]['item_name']) ? str_replace('#', '', $item_res[0]['item_name']) : '',
                  'quantity' => $v['quantity'],
                  'fix_price' => bcdiv($v['fix_price'], 100, 2),
                  'settle_price' => bcdiv($v['settle_price'], 100, 2),
            ];
        }
        $item[] = [
            'no' => '总计',
            'sap_code' => '',
            'product' => '',
            'top_level' => '',
            'quantity' => $quantity,
            'fix_price' => bcdiv($fix_price, 100, 2),
            'settle_price' => bcdiv($settle_price, 100, 2),
        ];
        return $item;
    }

    public function scheduleInitEmployeePurchaseStatistic($date = '')
    {
        $count_date = date('Y-m-d', strtotime('-1 day')); // 默认统计昨天的数据
        if ($date) {
            $count_date = $date;
        }
        $start = strtotime($count_date.' 00:00:00');
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('id')
            ->from('employee_purchase_activities')
            ->andWhere($qb->expr()->eq('status', $qb->expr()->literal('active')))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->lt('employee_begin_time', $start),
                        $qb->expr()->gt('employee_end_time', $start)
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->lt('relative_begin_time', $start),
                        $qb->expr()->gt('relative_end_time', $start)
                    )
                )
            );
        $activityList = $qb->execute()->fetchAll();
        foreach ($activityList as $row) {
            $this->scheduleInitStatistic($count_date, 'employee_purchase', $row['id']);
        }
    }

    /**
     * 初始化任务。
     *
     * @return void
     */
    public function scheduleInitStatistic($date = '', $order_class = '', $act_id = 0)
    {
        app('log')->info('执行统计商品数据初始化脚本');
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $count_date = date('Y-m-d', strtotime('-1 day')); // 默认统计昨天的数据

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        //$items = $criteria->select('item_id, company_id')->from('items')->execute()->fetchAll();
        array_walk($trade_state, function (&$value) use ($criteria) {
            $value = $criteria->expr()->literal($value);
        });
        if ($date) {
            $count_date = $date;
        }
        $start = strtotime($count_date.' 00:00:00');
        $end = strtotime($count_date.' 23:59:59');

        $criteria->select('count(*)')
            ->from('trade', 't')
            ->leftJoin('t', 'orders_normal_orders_items', 'onoi', 'onoi.order_id = t.order_id')
            ->andWhere($criteria->expr()->gte('onoi.create_time', $start))
            ->andWhere($criteria->expr()->lte('onoi.create_time', $end))
            ->andWhere($criteria->expr()->in('t.trade_state', $trade_state));
        if ($order_class) {
            $criteria->leftJoin('t', 'orders_normal_orders', 'o', 'o.order_id = t.order_id')
                ->andWhere($criteria->expr()->eq('o.order_class', $criteria->expr()->literal($order_class)))
                ->andWhere($criteria->expr()->eq('o.act_id', $act_id));
        }
        $count = $criteria->execute()->fetchColumn();

        $page = 100;
        $page_size = ceil($count / $page);

        for ($i = 0;$i < $page_size;$i++) {
            $qb = $conn->createQueryBuilder();
            $qb->select('onoi.order_id,onoi.id')
                ->from('trade', 't')
                ->leftJoin('t', 'orders_normal_orders_items', 'onoi', 'onoi.order_id = t.order_id')
                ->andWhere($qb->expr()->gte('onoi.create_time', $start))
                ->andWhere($qb->expr()->lte('onoi.create_time', $end))
                ->andWhere($qb->expr()->in('t.trade_state', $trade_state))
                ->setFirstResult($i * $page)->setMaxResults($page);
            if ($order_class) {
                $qb->leftJoin('t', 'orders_normal_orders', 'o', 'o.order_id = t.order_id')
                    ->andWhere($qb->expr()->eq('o.order_class', $qb->expr()->literal($order_class)))
                    ->andWhere($qb->expr()->eq('o.act_id', $act_id));
            }
            $order_ids = $qb->execute()->fetchAll();

            if ($order_ids) {
                $job = (new GoodsStatisticJob($order_ids, $count_date, $order_class, $act_id))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            }
        }
    }

    /**
     * 执行每日统计。
     *
     * @param integer $company_id
     * @param date $date 日期格式为 Y-m-d
     * @param date $date 日期格式为 Y-m-d
     * @return void
     */
    public function runStatistics($order_ids, $date, $order_class, $act_id)
    {
        app('log')->info('统计商品数据开始,参数{order_ids:'.json_encode($order_ids).',count_date:'.$date.'}');
        if (!$order_ids) {
            throw new ResourceException('必须指定order_id才能统计数据');
        }
        if (!$date || !$this->isDate($date)) {
            throw new ResourceException('必须填写日期，且格式为为"Y-m-d"');
        }

        foreach ($order_ids as $v) {
            app('log')->info('订单{order_ids:'.$v['order_id'].'开始');
            $conn = app('registry')->getConnection('default');

            $qba = $conn->createQueryBuilder();
            $qba->select('*')->from('orders_normal_orders_items')
               ->where($qba->expr()->eq('order_id', $qba->expr()->literal($v['order_id'])))
               ->andWhere($qba->expr()->eq('id', $v['id']));
            $order = $qba->execute()->fetchAll()[0];
            $merchant_id = 0;
            ## 查找店铺的商户，商户存在
            if (!empty($order['distributor_id'])) {
                $distributorInfo = $this->distributorRepository->getInfo(['distributor_id' => $order['distributor_id'],'company_id' => $order['company_id']]);
                $merchant_id = $distributorInfo['merchant_id'] ?? 0;
            }


            $qb = $conn->createQueryBuilder();
            $qb->select('*')
               ->from('datacube_goods_data')
               ->where($qb->expr()->eq('count_date', $qb->expr()->literal($date)))
               ->andWhere($qb->expr()->eq('company_id', $order['company_id']))
               ->andWhere($qb->expr()->eq('item_id', $order['item_id']));
            if ($order_class) {
                $qb->andWhere($qb->expr()->eq('order_class', $qb->expr()->literal($order_class)))
                    ->andWhere($qb->expr()->eq('act_id', $act_id));
            } else {
                $qb->andWhere($qb->expr()->isNull('order_class'))
                    ->andWhere($qb->expr()->eq('act_id', 0));
            }
            $fetchcount = $qb->execute()->fetchAll();

            if (!$fetchcount) {
                $data = [
                    'company_id' => $order['company_id'],
                    'count_date' => $date,
                    'item_id' => $order['item_id'],
                    'sales_count' => $order['num'],
                    'fixed_amount_count' => $order['item_fee'],
                    'settle_amount_count' => $order['total_fee'],
                    'merchant_id' => $merchant_id,
                    'act_id' => 0,
                ];
                if ($order_class) {
                    $data['order_class'] = $order_class;
                    $data['act_id'] = $act_id;
                }
                $conn->insert('datacube_goods_data', $data);
            } else {
                $updateData = [
                   'sales_count' => $order['num'] + $fetchcount[0]['sales_count'],
                   'fixed_amount_count' => $order['item_fee'] + $fetchcount[0]['fixed_amount_count'],
                   'settle_amount_count' => $order['total_fee'] + $fetchcount[0]['settle_amount_count'],
                ];
                if ($order_class) {
                    $conn->update('datacube_goods_data', $updateData, ['count_date' => $date, 'company_id' => $order['company_id'], 'item_id' => $order['item_id'], 'order_class' => $order_class, 'act_id' => $act_id]);
                } else {
                    $conn->update('datacube_goods_data', $updateData, ['count_date' => $date, 'company_id' => $order['company_id'], 'item_id' => $order['item_id'], 'act_id' => 0]);
                }
            }

            app('log')->info('订单{order_ids:'.$v['order_id'].'结束');
        }

        app('log')->info('统计商品数据结束');
    }


    // 检查日期格式是否正确
    private function isDate($strDate, $format = 'Y-m-d')
    {
        $arr = explode('-', $strDate);
        return checkdate($arr[1], $arr[2], $arr[0]) ? true : false;
    }
}
