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

use DataCubeBundle\Entities\CompanyData;
use DataCubeBundle\Jobs\StatisticJob;
use Dingo\Api\Exception\ResourceException;

class CompanyDataService
{
    /** @var companyDataRepository */
    private $companyDataRepository;

    /**
     * MonitorsService 构造函数.
     */
    public function __construct()
    {
        $this->companyDataRepository = app('registry')->getManager('default')->getRepository(CompanyData::class);
    }

    public function getCompanyDataList($filter, $page, $pageSize, $orderBy = ['count_date' => 'ASC'])
    {
        $filter['order_class'] = $filter['order_class'] ?? '';
        $filter['act_id'] = $filter['act_id'] ?? 0;
        $companyDataist = $this->companyDataRepository->lists($filter, $page, $pageSize, $orderBy);

        return $companyDataist;
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
        $count_date = date('Y-m-d', strtotime('-1 day'));
        if ($date) {
            $count_date = $date;
        }
        app('log')->info('执行统计商城数据初始化脚本');
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $companys = $criteria->select('company_id')->from('companys')->execute()->fetchAll();
        foreach ($companys as $v) {
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb->select('count(*)')
               ->from('datacube_company_data')
               ->where($qb->expr()->eq('count_date', $qb->expr()->literal($count_date)))
               ->andWhere($qb->expr()->eq('company_id', $v['company_id']));
            if ($order_class) {
                $qb->andWhere($qb->expr()->eq('order_class', $qb->expr()->literal($order_class)))
                    ->andWhere($qb->expr()->eq('act_id', $act_id));
            } else {
                $qb->andWhere($qb->expr()->isNull('order_class'))
                    ->andWhere($qb->expr()->eq('act_id', 0));
            }
            $fetchcount = $qb->execute()->fetchColumn();
            if (!$fetchcount) {
                $conn = app('registry')->getConnection('default');
                $data = ['company_id' => $v['company_id'], 'count_date' => $count_date, 'act_id' => 0];
                if ($order_class) {
                    $data['order_class'] = $order_class;
                    $data['act_id'] = $act_id;
                }
                $conn->insert('datacube_company_data', $data);
            }
            $v['count_date'] = $count_date;
            $v['order_class'] = $order_class;
            $v['act_id'] = $act_id;
            $job = (new StatisticJob($v))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
    }

    /**
     * 执行每日统计。
     *
     * @param integer $company_id
     * @param date $date 日期格式为 Y-m-d
     * @return void
     */
    public function runStatistics($company_id, $date, $order_class, $act_id)
    {
        app('log')->info('统计商城数据开始,参数{company_id:'.$company_id.',count_date:'.$date.'}');
        if (!$company_id) {
            throw new ResourceException('必须指定company_id才能统计数据');
        }
        if (!$date || !$this->isDate($date)) {
            throw new ResourceException('必须填写日期，且格式为为"Y-m-d"');
        }
        $start = strtotime($date.' 00:00:00');
        $end = strtotime($date.' 23:59:59');
        if (!$order_class) {
            $member_count = $this->member_count($company_id, $start, $end); // 新增会员
        }
        $aftersales_count = $this->aftersales_count($company_id, $start, $end, $order_class, $act_id); // 新增售后单
        $refunded_count = $this->refunded_count($company_id, $start, $end, $order_class, $act_id); // 新增退款额
        $amount_payed_count = $this->amount_payed_count($company_id, $start, $end, $order_class, $act_id); // 新增支付额
        $order_count = $this->order_count($company_id, $start, $end, $order_class, $act_id); // 新增订单
        $order_payed_count = $this->order_payed_count($company_id, $start, $end, $order_class, $act_id); // 新增已付款订单
        $gmv_count = $this->gmv_count($company_id, $start, $end, $order_class, $act_id); // 新增gmv

        $updateData = [
            'member_count' => $member_count ?? 0,
            'aftersales_count' => $aftersales_count ?? 0,
            'refunded_count' => $refunded_count ?? 0,
            'amount_payed_count' => $amount_payed_count ?? 0,
            'order_count' => $order_count ?? 0,
            'order_payed_count' => $order_payed_count ?? 0,
            'gmv_count' => $gmv_count ?? 0,
        ];

        $conn = app('registry')->getConnection('default');
        if ($order_class) {
            $conn->update('datacube_company_data', $updateData, ['count_date' => $date, 'company_id' => $company_id, 'order_class' => $order_class, 'act_id' => $act_id]);
        } else {
            $conn->update('datacube_company_data', $updateData, ['count_date' => $date, 'company_id' => $company_id, 'act_id' => 0]);
        }
        app('log')->info('统计商城数据结束');
    }

    /**
     * 统计新增会员
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function member_count($company_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
           ->from('members')
           ->where($qb->expr()->eq('company_id', $company_id))
           ->andWhere($qb->expr()->gte('created', $start))
           ->andWhere($qb->expr()->lte('created', $end));
        $count = $qb->execute()->fetchColumn();

        return $count;
    }

    /**
     * 统计新增售后单
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function aftersales_count($company_id, $start, $end, $order_class, $act_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
           ->from('aftersales', 'a')
           ->where($qb->expr()->eq('a.company_id', $company_id))
           ->andWhere($qb->expr()->gte('a.create_time', $start))
           ->andWhere($qb->expr()->lte('a.create_time', $end));
        if ($order_class) {
            $qb->leftJoin('a', 'orders_normal_orders', 'o', 'a.order_id=o.order_id')
                ->andWhere($qb->expr()->eq('o.order_class', $qb->expr()->literal($order_class)))
                ->andWhere($qb->expr()->eq('o.act_id', $act_id));
        }
        $count = $qb->execute()->fetchColumn();

        return $count;
    }

    /**
     * 统计新增退款额
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function refunded_count($company_id, $start, $end, $order_class, $act_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(r.refunded_fee)')
           ->from('aftersales_refund', 'r')
           ->where($qb->expr()->eq('r.company_id', $company_id))
           ->andWhere($qb->expr()->gte('r.update_time', $start))
           ->andWhere($qb->expr()->lte('r.update_time', $end))
           ->andWhere($qb->expr()->eq('r.refund_status', $qb->expr()->literal('SUCCESS')));
        if ($order_class) {
            $qb->leftJoin('r', 'orders_normal_orders', 'o', 'r.order_id=o.order_id')
                ->andWhere($qb->expr()->eq('o.order_class', $qb->expr()->literal($order_class)))
                ->andWhere($qb->expr()->eq('o.act_id', $act_id));
        }
        $sum = $qb->execute()->fetchColumn();
        return $sum;
    }

    /**
     * 统计新增支付额
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function amount_payed_count($company_id, $start, $end, $order_class, $act_id)
    {
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        array_walk($trade_state, function (&$value) use ($qb) {
            $value = $qb->expr()->literal($value);
        });
        $qb->select('sum(cast(t.total_fee as SIGNED))')
           ->from('trade', 't')
           ->where($qb->expr()->eq('t.company_id', $company_id))
           ->andWhere($qb->expr()->in('t.trade_state', $trade_state))
           ->andWhere($qb->expr()->gte('t.time_expire', $start))
           ->andWhere($qb->expr()->lte('t.time_expire', $end));
        if ($order_class) {
            $qb->leftJoin('t', 'orders_normal_orders', 'o', 't.order_id=o.order_id')
                ->andWhere($qb->expr()->eq('o.order_class', $qb->expr()->literal($order_class)))
                ->andWhere($qb->expr()->eq('o.act_id', $act_id));
        }
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    /**
     * 统计新增订单
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function order_count($company_id, $start, $end, $order_class, $act_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
           ->from('orders_normal_orders')
           ->where($qb->expr()->eq('company_id', $company_id))
           ->andWhere($qb->expr()->gte('create_time', $start))
           ->andWhere($qb->expr()->lte('create_time', $end));
        if ($order_class) {
            $qb->andWhere($qb->expr()->eq('order_class', $qb->expr()->literal($order_class)))
                ->andWhere($qb->expr()->eq('act_id', $act_id));
        }
        $count = $qb->execute()->fetchColumn();

        return $count;
    }

    /**
     * 统计新增已付款订单
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function order_payed_count($company_id, $start, $end, $order_class, $act_id)
    {
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        array_walk($trade_state, function (&$value) use ($qb) {
            $value = $qb->expr()->literal($value);
        });
        $qb->select('count(*)')
           ->from('trade', 't')
           ->where($qb->expr()->eq('t.company_id', $company_id))
           ->andWhere($qb->expr()->in('t.trade_state', $trade_state))
           ->andWhere($qb->expr()->gte('t.time_expire', $start))
           ->andWhere($qb->expr()->lte('t.time_expire', $end));
        if ($order_class) {
            $qb->leftJoin('t', 'orders_normal_orders', 'o', 't.order_id=o.order_id')
                ->andWhere($qb->expr()->eq('o.order_class', $qb->expr()->literal($order_class)))
                ->andWhere($qb->expr()->eq('o.act_id', $act_id));
        }
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    /**
     * 统计新增gmv
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function gmv_count($company_id, $start, $end, $order_class, $act_id)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(cast(total_fee as SIGNED))')
           ->from('orders_normal_orders')
           ->where($qb->expr()->eq('company_id', $company_id))
           ->andWhere($qb->expr()->gte('create_time', $start))
           ->andWhere($qb->expr()->lte('create_time', $end));
        if ($order_class) {
            $qb->andWhere($qb->expr()->eq('order_class', $qb->expr()->literal($order_class)))
                ->andWhere($qb->expr()->eq('act_id', $act_id));
        }
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    // 检查日期格式是否正确
    private function isDate($strDate, $format = 'Y-m-d')
    {
        $arr = explode('-', $strDate);
        return checkdate($arr[1], $arr[2], $arr[0]) ? true : false;
    }
}
