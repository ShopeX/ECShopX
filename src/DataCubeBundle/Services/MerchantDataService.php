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

use DataCubeBundle\Entities\MerchantData;
use DataCubeBundle\Jobs\MerchantStatisticJob;
use Dingo\Api\Exception\ResourceException;

class MerchantDataService
{
    /** @var merchantDataRepository */
    private $merchantDataRepository;

    /**
     * MonitorsService 构造函数.
     */
    public function __construct()
    {
        $this->merchantDataRepository = app('registry')->getManager('default')->getRepository(MerchantData::class);
    }

    public function getMerchantDataList($filter, $page, $pageSize, $orderBy = ['count_date' => 'ASC'])
    {
        $merchantDataist = $this->merchantDataRepository->lists($filter, $page, $pageSize, $orderBy);

        return $merchantDataist;
    }

    /**
     * 初始化任务。
     *
     * @return void
     */
    public function scheduleInitStatistic()
    {
        app('log')->info('执行统计商城数据初始化脚本');
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $merchants = $criteria->select('id,company_id')->from('merchant')->execute()->fetchAll();
        foreach ($merchants as $v) {
            $count_date = date('Y-m-d', strtotime('-1 day'));
            $conn = app('registry')->getConnection('default');
            $qb = $conn->createQueryBuilder();
            $qb->select('count(*)')
                ->from('datacube_merchant_data')
                ->where($qb->expr()->eq('count_date', $qb->expr()->literal($count_date)))
                ->andWhere($qb->expr()->eq('merchant_id', $v['id']))
                ->andWhere($qb->expr()->eq('company_id', $v['company_id']));
            $fetchcount = $qb->execute()->fetchColumn();
            if (!$fetchcount) {
                $conn = app('registry')->getConnection('default');
                $data = ['merchant_id' => $v['id'], 'company_id' => $v['company_id'], 'count_date' => $count_date];
                $conn->insert('datacube_merchant_data', $data);
            }
            $v['count_date'] = $count_date;
            $job = (new MerchantStatisticJob($v))->onQueue('slow');
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
    public function runStatistics($company_id, $merchant_id, $date)
    {
        app('log')->info('统计商城数据开始,参数{company_id:' . $company_id . ',count_date:' . $date . '}');
        if (!$company_id) {
            throw new ResourceException('必须指定company_id才能统计数据');
        }
        if (!$merchant_id) {
            throw new ResourceException('必须指定merchant_id才能统计数据');
        }
        if (!$date || !$this->isDate($date)) {
            throw new ResourceException('必须填写日期，且格式为为"Y-m-d"');
        }
        $start = strtotime($date . ' 00:00:00');
        $end = strtotime($date . ' 23:59:59');
        $aftersales_count = $this->aftersales_count($company_id, $merchant_id, $start, $end); // 新增售后单
        $refunded_count = $this->refunded_count($company_id, $merchant_id, $start, $end); // 新增退款额
        $amount_payed_count = $this->amount_payed_count($company_id, $merchant_id, $start, $end); // 新增支付额
        $amount_point_payed_count = $this->amount_point_payed_count($company_id, $merchant_id, $start, $end); // 新增支付额(积分)
        $order_count = $this->order_count($company_id, $merchant_id, $start, $end); // 新增订单
        $order_point_count = $this->order_point_count($company_id, $merchant_id, $start, $end); // 新增订单(积分)
        $order_payed_count = $this->order_payed_count($company_id, $merchant_id, $start, $end); // 新增已付款订单
        $order_point_payed_count = $this->order_point_payed_count($company_id, $merchant_id, $start, $end); // 新增已付款订单(积分)
        $gmv_count = $this->gmv_count($company_id, $merchant_id, $start, $end); // 新增gmv
        $gmv_point_count = $this->gmv_point_count($company_id, $merchant_id, $start, $end); // 新增gmv(积分)

        $updateData = [
            'aftersales_count' => $aftersales_count ?? 0,
            'refunded_count' => $refunded_count ?? 0,
            'amount_payed_count' => $amount_payed_count ?? 0,
            'amount_point_payed_count' => $amount_point_payed_count ?? 0,
            'order_count' => $order_count ?? 0,
            'order_point_count' => $order_point_count ?? 0,
            'order_payed_count' => $order_payed_count ?? 0,
            'order_point_payed_count' => $order_point_payed_count ?? 0,
            'gmv_count' => $gmv_count ?? 0,
            'gmv_point_count' => $gmv_point_count ?? 0,
            'merchant_id' => $merchant_id
        ];

        $conn = app('registry')->getConnection('default');
        $conn->update('datacube_merchant_data', $updateData, ['count_date' => $date, 'company_id' => $company_id,'merchant_id' => $merchant_id]);
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
    private function aftersales_count($company_id, $merchant_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from('aftersales')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('merchant_id', $merchant_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end));
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
    private function refunded_count($company_id, $merchant_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(refunded_fee)')
            ->from('aftersales_refund')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('merchant_id', $merchant_id))
            ->andWhere($qb->expr()->gte('update_time', $start))
            ->andWhere($qb->expr()->lte('update_time', $end))
            ->andWhere($qb->expr()->eq('refund_status', $qb->expr()->literal('SUCCESS')));
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
    private function amount_payed_count($company_id, $merchant_id, $start, $end)
    {
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        array_walk($trade_state, function (&$value) use ($qb) {
            $value = $qb->expr()->literal($value);
        });
        $qb->select('sum(cast(total_fee as SIGNED))')
            ->from('trade')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('merchant_id', $merchant_id))
            ->andWhere($qb->expr()->in('trade_state', $trade_state))
            ->andWhere($qb->expr()->gte('time_expire', $start))
            ->andWhere($qb->expr()->lte('time_expire', $end));
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    /**
     * 统计新增支付额(积分)
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function amount_point_payed_count($company_id, $merchant_id, $start, $end)
    {
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        array_walk($trade_state, function (&$value) use ($qb) {
            $value = $qb->expr()->literal($value);
        });
        $qb->select('sum(cast(total_fee as SIGNED))')
            ->from('trade')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('merchant_id', $merchant_id))
            ->andWhere($qb->expr()->in('trade_state', $trade_state))
            ->andWhere($qb->expr()->gte('time_expire', $start))
            ->andWhere($qb->expr()->lte('time_expire', $end));
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
    private function order_count($company_id, $merchant_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from('orders_normal_orders')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('merchant_id', $merchant_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end));
        $count = $qb->execute()->fetchColumn();
        return $count;
    }

    /**
     * 统计新增订单(积分)
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function order_point_count($company_id, $merchant_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('count(*)')
            ->from('orders_normal_orders')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('merchant_id', $merchant_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end));
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
    private function order_payed_count($company_id, $merchant_id, $start, $end)
    {
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        array_walk($trade_state, function (&$value) use ($qb) {
            $value = $qb->expr()->literal($value);
        });
        $qb->select('count(*)')
            ->from('trade')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('merchant_id', $merchant_id))
            ->andWhere($qb->expr()->in('trade_state', $trade_state))
            ->andWhere($qb->expr()->gte('time_expire', $start))
            ->andWhere($qb->expr()->lte('time_expire', $end));
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    /**
     * 统计新增已付款订单(积分)
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function order_point_payed_count($company_id, $merchant_id, $start, $end)
    {
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        array_walk($trade_state, function (&$value) use ($qb) {
            $value = $qb->expr()->literal($value);
        });
        $qb->select('count(*)')
            ->from('trade')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('merchant_id', $merchant_id))
            ->andWhere($qb->expr()->in('trade_state', $trade_state))
            ->andWhere($qb->expr()->gte('time_expire', $start))
            ->andWhere($qb->expr()->lte('time_expire', $end));
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
    private function gmv_count($company_id, $merchant_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(cast(total_fee as SIGNED))')
            ->from('orders_normal_orders')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('merchant_id', $merchant_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end));
        $sum = $qb->execute()->fetchColumn();

        return $sum;
    }

    /**
     * 统计新增gmv(积分)
     *
     * @param integer $company_id 公司id
     * @param date $date 日期格式为 Y-m-d
     * @return int
     */
    private function gmv_point_count($company_id, $merchant_id, $start, $end)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('sum(cast(total_fee as SIGNED))')
            ->from('orders_normal_orders')
            ->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->eq('merchant_id', $merchant_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end));
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
